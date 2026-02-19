# ==============================================================================
# IAM Role / Policy
# ==============================================================================

# ------------------------------------------------------------------------------
# ECS Task Execution Role
# ECSコントロールプレーンがタスクを起動する際に使用するロール。
# ECRからのイメージpullおよびCloudWatch Logsへのログ書き込みを許可する。
# ------------------------------------------------------------------------------
resource "aws_iam_role" "ecs_task_execution" {
  name = "lmi-production-ecs-task-execution-role"

  # ECSタスクがこのロールを引き受けられるよう信頼ポリシーを設定する
  assume_role_policy = jsonencode({
    Version = "2012-10-17"
    Statement = [
      {
        Sid    = "AllowECSTasksAssumeRole"
        Effect = "Allow"
        Principal = {
          Service = "ecs-tasks.amazonaws.com"
        }
        Action = "sts:AssumeRole"
      }
    ]
  })

  tags = {
    Name        = "lmi-production-ecs-task-execution-role"
    Project     = var.project_name
    Environment = var.environment
    ManagedBy   = "terraform"
  }
}

# AWS管理ポリシー: ECSタスク実行に必要な基本権限をアタッチする
# (ECR認証トークン取得、ECRイメージlayer取得、CloudWatch Logsへの書き込み等)
resource "aws_iam_role_policy_attachment" "ecs_task_execution_managed" {
  role       = aws_iam_role.ecs_task_execution.name
  policy_arn = "arn:aws:iam::aws:policy/service-role/AmazonECSTaskExecutionRolePolicy"
}

# カスタムポリシー: VPCエンドポイント経由でのECRアクセスを明示的に許可する
# PrivateLinkを介したECRアクセスに必要な権限を付与する
resource "aws_iam_role_policy" "ecs_task_execution_ecr_vpc_endpoint" {
  name = "lmi-production-ecr-vpc-endpoint-policy"
  role = aws_iam_role.ecs_task_execution.id

  policy = jsonencode({
    Version = "2012-10-17"
    Statement = [
      {
        Sid    = "AllowECRViaVpcEndpoint"
        Effect = "Allow"
        Action = [
          # イメージlayerの取得に必要
          "ecr:GetDownloadUrlForLayer",
          "ecr:BatchGetImage",
          "ecr:BatchCheckLayerAvailability",
          # ECR認証トークンの取得に必要
          "ecr:GetAuthorizationToken",
        ]
        Resource = "*"
      },
      {
        Sid    = "AllowCloudWatchLogsViaVpcEndpoint"
        Effect = "Allow"
        Action = [
          # ECSエージェントがCloudWatch Logsへログを書き込む際に必要
          "logs:CreateLogStream",
          "logs:PutLogEvents",
        ]
        Resource = "${aws_cloudwatch_log_group.ecs.arn}:*"
      }
    ]
  })
}

# ------------------------------------------------------------------------------
# ECS Task Role
# アプリケーションコンテナ自身が実行時に使用するロール。
# SES経由のメール送信権限を付与する。
# ------------------------------------------------------------------------------
resource "aws_iam_role" "ecs_task" {
  name = "lmi-production-ecs-task-role"

  # ECSタスクがこのロールを引き受けられるよう信頼ポリシーを設定する
  assume_role_policy = jsonencode({
    Version = "2012-10-17"
    Statement = [
      {
        Sid    = "AllowECSTasksAssumeRole"
        Effect = "Allow"
        Principal = {
          Service = "ecs-tasks.amazonaws.com"
        }
        Action = "sts:AssumeRole"
      }
    ]
  })

  tags = {
    Name        = "lmi-production-ecs-task-role"
    Project     = var.project_name
    Environment = var.environment
    ManagedBy   = "terraform"
  }
}

# カスタムポリシー: SESによるメール送信を許可する
# 問い合わせ受付・担当者通知等のメール送信に使用する
resource "aws_iam_role_policy" "ecs_task_ses" {
  name = "lmi-production-ses-send-policy"
  role = aws_iam_role.ecs_task.id

  policy = jsonencode({
    Version = "2012-10-17"
    Statement = [
      {
        Sid    = "AllowSESSend"
        Effect = "Allow"
        Action = [
          "ses:SendEmail",
          "ses:SendRawEmail",
        ]
        Resource = "*"
      }
    ]
  })
}
