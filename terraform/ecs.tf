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
  cpu                      = 512  # 0.5 vCPU（Datadogエージェント追加に伴い増量）
  memory                   = 1024 # 1 GB（Datadogエージェント追加に伴い増量）

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
          name  = "DB_CONNECTION"
          value = "mariadb"
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
        },
        {
          name  = "DD_SERVICE"
          value = "ec-contact"
        },
        {
          name  = "DD_ENV"
          value = "production"
        },
        {
          name  = "DD_VERSION"
          value = "1.0.0"
        },
        {
          name  = "DD_LOGS_INJECTION"
          value = "true"
        },
        {
          name  = "LOG_STDERR_FORMATTER"
          value = "Monolog\\Formatter\\JsonFormatter"
        }
      ]

      # Secrets Manager からシークレット値を注入する
      secrets = [
        {
          name      = "DD_API_KEY"
          valueFrom = aws_secretsmanager_secret.datadog_api_key.arn
        }
      ]

      essential = true
    },
    # ------------------------------------------------------------------------------
    # Datadog Agent サイドカーコンテナ
    # PHPトレーサーからAPMトレースを受け取り、Datadogバックエンドへ転送する
    # PHPトレーサーはデフォルトで localhost:8126 にトレースを送信する
    # ------------------------------------------------------------------------------
    {
      name  = "datadog-agent"
      image = "public.ecr.aws/datadog/agent:7"

      portMappings = [
        {
          containerPort = 8126
          hostPort      = 8126
          protocol      = "tcp"
        }
      ]

      # CloudWatch Logsへのログ転送設定
      logConfiguration = {
        logDriver = "awslogs"
        options = {
          "awslogs-group"         = aws_cloudwatch_log_group.ecs.name
          "awslogs-region"        = var.aws_region
          "awslogs-stream-prefix" = "datadog-agent"
        }
      }

      environment = [
        {
          name  = "DD_SITE"
          value = "ap1.datadoghq.com"
        },
        # APMトレース収集を有効化
        {
          name  = "DD_APM_ENABLED"
          value = "true"
        },
        # Fargate環境であることを明示（コンテナメタデータ収集に必要）
        {
          name  = "ECS_FARGATE"
          value = "true"
        },
        # プロセス監視は不要のため無効化（リソース節約）
        {
          name  = "DD_PROCESS_AGENT_ENABLED"
          value = "false"
        },
        # ログはCloudWatch経由で収集するためAgent側のログ収集は無効化
        {
          name  = "DD_LOGS_ENABLED"
          value = "false"
        }
      ]

      secrets = [
        {
          name      = "DD_API_KEY"
          valueFrom = aws_secretsmanager_secret.datadog_api_key.arn
        }
      ]

      # Agentが停止してもアプリコンテナは継続稼働させる
      essential = false
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
  desired_count   = 1
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

  # デプロイ並行数制御: 最大タスク数 200%（新旧タスクを同時に起動できる）
  deployment_maximum_percent = 200
  # デプロイ並行数制御: 最低稼働タスク数 50%（ダウンタイムを最小化しつつコスト抑制）
  deployment_minimum_healthy_percent = 50

  # デプロイサーキットブレーカー: 失敗時に自動的に前のタスク定義へロールバックする
  deployment_circuit_breaker {
    enable   = true
    rollback = true
  }

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
