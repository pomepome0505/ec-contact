# ==============================================================================
# ECS Cluster / Task Definition / Service
# ==============================================================================

# ------------------------------------------------------------------------------
# ECS Cluster
# ------------------------------------------------------------------------------
resource "aws_ecs_cluster" "main" {
  name = "${var.project_name}-${var.environment}-cluster"

  # Container Insights を有効化してCloudWatchでメトリクスを収集する
  setting {
    name  = "containerInsights"
    value = "enabled"
  }

  tags = {
    Name        = "${var.project_name}-${var.environment}-cluster"
    Project     = var.project_name
    Environment = var.environment
    ManagedBy   = "terraform"
  }
}

# ------------------------------------------------------------------------------
# ECS Task Definition
# Nginx + PHP-FPM を1コンテナに統合した構成（Supervisorで管理）
# ネットワークモード awsvpc: Fargateで必須
# ------------------------------------------------------------------------------
resource "aws_ecs_task_definition" "main" {
  family                   = "${var.project_name}-${var.environment}-task"
  requires_compatibilities = ["FARGATE"]
  network_mode             = "awsvpc"
  cpu                      = 256 # 0.25 vCPU
  memory                   = 512 # 0.5 GB

  # ECSコントロールプレーン用ロール（ECRイメージpull / CloudWatch Logsへの書き込み）
  execution_role_arn = aws_iam_role.ecs_task_execution.arn

  # アプリケーションコンテナ用ロール（SESメール送信等）
  task_role_arn = aws_iam_role.ecs_task.arn

  container_definitions = jsonencode([
    {
      name  = "app"
      image = var.container_image

      # ポートマッピング: ALBからのHTTPトラフィックを受け付ける
      portMappings = [
        {
          containerPort = 80
          hostPort      = 80
          protocol      = "tcp"
        }
      ]

      # CloudWatch Logsへのログ転送設定
      logConfiguration = {
        logDriver = "awslogs"
        options = {
          "awslogs-group"         = aws_cloudwatch_log_group.ecs.name
          "awslogs-region"        = var.aws_region
          "awslogs-stream-prefix" = "ecs"
        }
      }

      # 環境変数: アプリケーション設定
      environment = [
        {
          name  = "APP_NAME"
          value = "EC Contact"
        },
        {
          name  = "APP_ENV"
          value = "production"
        },
        {
          name  = "APP_DEBUG"
          value = "false"
        },
        # APP_URL を設定することで Laravel の @vite ディレクティブが
        # 開発サーバー(localhost:5173)ではなく public/build/manifest.json を参照する
        {
          name  = "APP_URL"
          value = "https://${var.domain_name}"
        },
        {
          name  = "APP_KEY"
          value = var.app_key
        },
        # ログは stderr に出力して CloudWatch Logs に転送する
        {
          name  = "LOG_CHANNEL"
          value = "stderr"
        },
        {
          name = "DB_HOST"
          # RDSエンドポイントはホスト名のみ抽出（"hostname:port" → "hostname"）
          value = split(":", aws_db_instance.main.endpoint)[0]
        },
        {
          name  = "DB_DATABASE"
          value = "lmi_production"
        },
        {
          name  = "DB_USERNAME"
          value = "admin"
        },
        {
          name  = "DB_PASSWORD"
          value = var.db_password
        },
        {
          name  = "MAIL_MAILER"
          value = "ses"
        },
        {
          name  = "MAIL_FROM_ADDRESS"
          value = "support@${var.domain_name}"
        },
        {
          name  = "MAIL_FROM_NAME"
          value = "EC Contact"
        }
      ]

      essential = true
    }
  ])

  tags = {
    Name        = "${var.project_name}-${var.environment}-task"
    Project     = var.project_name
    Environment = var.environment
    ManagedBy   = "terraform"
  }
}

# ------------------------------------------------------------------------------
# ECS Service
# Fargateタスクを常時1台維持し、ALBターゲットグループへ登録する
# ------------------------------------------------------------------------------
resource "aws_ecs_service" "main" {
  name            = "${var.project_name}-${var.environment}-service"
  cluster         = aws_ecs_cluster.main.id
  task_definition = aws_ecs_task_definition.main.arn
  desired_count   = 0 # コスト削減のため停止中（再開時は1に戻す）
  launch_type     = "FARGATE"

  # プラットフォームバージョンは LATEST を使用する
  platform_version = "LATEST"

  # ネットワーク設定: Fargateタスクを Private Subnet に配置する
  network_configuration {
    subnets = [
      aws_subnet.private_fargate_1a.id,
    ]
    security_groups  = [aws_security_group.fargate.id]
    assign_public_ip = false
  }

  # ALBターゲットグループへの登録設定
  load_balancer {
    target_group_arn = aws_lb_target_group.main.arn
    container_name   = "app"
    container_port   = 80
  }

  # タスク起動後、ALBヘルスチェックが安定するまでの猶予期間（秒）
  health_check_grace_period_seconds = 60

  # タスク定義の変更（イメージ更新等）をTerraform管理外で行う場合に備えて
  # desired_count と task_definition の変更を無視する設定を追加することも検討できる
  # lifecycle {
  #   ignore_changes = [task_definition, desired_count]
  # }

  # ALBリスナーが作成された後にServiceを作成する
  depends_on = [
    aws_lb_listener.https,
  ]

  tags = {
    Name        = "${var.project_name}-${var.environment}-service"
    Project     = var.project_name
    Environment = var.environment
    ManagedBy   = "terraform"
  }
}
