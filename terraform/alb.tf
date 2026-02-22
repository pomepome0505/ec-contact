# ==============================================================================
# Application Load Balancer
# ==============================================================================

# ------------------------------------------------------------------------------
# ALB
# ------------------------------------------------------------------------------
resource "aws_lb" "main" {
  name               = "${var.project_name}-${var.environment}-alb"
  internal           = false # internet-facing
  load_balancer_type = "application"
  security_groups    = [aws_security_group.alb.id]
  subnets = [
    aws_subnet.public_1a.id,
    aws_subnet.public_1c.id
  ]

  # 削除保護は無効（テスト/開発段階のため）
  enable_deletion_protection = false

  # アクセスログは設定しない（コスト削減）

  tags = {
    Name    = "${var.project_name}-${var.environment}-alb"
    Project = var.project_name
  }
}

# ------------------------------------------------------------------------------
# ターゲットグループ
# ------------------------------------------------------------------------------
resource "aws_lb_target_group" "main" {
  name        = "${var.project_name}-${var.environment}-tg"
  port        = 80
  protocol    = "HTTP"
  vpc_id      = aws_vpc.main.id
  target_type = "ip" # Fargate用

  # ヘルスチェック設定
  # /health は認証不要で 200 OK を返す専用エンドポイント
  health_check {
    enabled             = true
    path                = "/health"
    interval            = 30
    timeout             = 5
    healthy_threshold   = 2
    unhealthy_threshold = 2
    matcher             = "200"
  }

  tags = {
    Name    = "${var.project_name}-${var.environment}-tg"
    Project = var.project_name
  }
}

# ------------------------------------------------------------------------------
# HTTPリスナー (80番ポート)
# HTTPSへリダイレクト
# ------------------------------------------------------------------------------
resource "aws_lb_listener" "http" {
  load_balancer_arn = aws_lb.main.arn
  port              = 80
  protocol          = "HTTP"

  default_action {
    type = "redirect"

    redirect {
      port        = "443"
      protocol    = "HTTPS"
      status_code = "HTTP_301"
    }
  }
}

# ------------------------------------------------------------------------------
# HTTPSリスナー (443番ポート)
# ターゲットグループへ転送
# ------------------------------------------------------------------------------
resource "aws_lb_listener" "https" {
  load_balancer_arn = aws_lb.main.arn
  port              = 443
  protocol          = "HTTPS"
  ssl_policy        = "ELBSecurityPolicy-2016-08"
  certificate_arn   = aws_acm_certificate.main.arn

  default_action {
    type             = "forward"
    target_group_arn = aws_lb_target_group.main.arn
  }
}
