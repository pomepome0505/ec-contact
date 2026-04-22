# ==============================================================================
# Datadog 監視設定
# ==============================================================================

# ------------------------------------------------------------------------------
# Secrets Manager: Datadog APIキー
# 値はterraform apply後に手動で設定する
# ECSタスク実行時にDD_API_KEYとして注入される
# ------------------------------------------------------------------------------
resource "aws_secretsmanager_secret" "datadog_api_key" {
  name        = "${var.project_name}-${var.environment}-datadog-api-key"
  description = "Datadog API Key for dd-trace-php APM and Forwarder Lambda"

  tags = {
    Name        = "${var.project_name}-${var.environment}-datadog-api-key"
    Project     = var.project_name
    Environment = var.environment
    ManagedBy   = "terraform"
  }
}

resource "aws_secretsmanager_secret_version" "datadog_api_key" {
  secret_id     = aws_secretsmanager_secret.datadog_api_key.id
  secret_string = var.datadog_api_key
}

# ------------------------------------------------------------------------------
# Datadog Forwarder Lambda（CloudWatch Logs → Datadog ログ転送）
# Lambda自体はVPC外に配置されるためインターネットアクセスが自動的に有効
# Datadogの公式CloudFormationテンプレートを使用
# ------------------------------------------------------------------------------
resource "aws_cloudformation_stack" "datadog_forwarder" {
  name         = "${var.project_name}-${var.environment}-datadog-forwarder"
  capabilities = ["CAPABILITY_IAM", "CAPABILITY_NAMED_IAM", "CAPABILITY_AUTO_EXPAND"]

  parameters = {
    DdApiKeySecretArn = aws_secretsmanager_secret.datadog_api_key.arn
    DdSite            = "ap1.datadoghq.com"
    FunctionName      = "${var.project_name}-${var.environment}-datadog-forwarder"
  }

  template_url = "https://datadog-cloudformation-template.s3.amazonaws.com/aws/forwarder/latest.yaml"

  depends_on = [aws_secretsmanager_secret_version.datadog_api_key]

  tags = {
    Project     = var.project_name
    Environment = var.environment
    ManagedBy   = "terraform"
  }
}

# CloudWatch LogsがこのLambda関数を呼び出せるよう許可する
resource "aws_lambda_permission" "datadog_forwarder_logs" {
  statement_id   = "datadog-forwarder-cloudwatch-logs"
  action         = "lambda:InvokeFunction"
  function_name  = aws_cloudformation_stack.datadog_forwarder.outputs["DatadogForwarderArn"]
  principal      = "logs.${var.aws_region}.amazonaws.com"
  source_arn     = "${aws_cloudwatch_log_group.ecs.arn}:*"
  source_account = data.aws_caller_identity.current.account_id
}

# ECSアプリログをDatadog Forwarderに転送するサブスクリプションフィルター
resource "aws_cloudwatch_log_subscription_filter" "datadog_forwarder" {
  name            = "${var.project_name}-${var.environment}-datadog-forwarder"
  log_group_name  = aws_cloudwatch_log_group.ecs.name
  filter_pattern  = ""
  destination_arn = aws_cloudformation_stack.datadog_forwarder.outputs["DatadogForwarderArn"]

  depends_on = [aws_lambda_permission.datadog_forwarder_logs]
}

# ------------------------------------------------------------------------------
# Datadog AWS Integration
# DatadogがCloudWatchメトリクスをポーリングするための統合設定
# external_idはDatadogが自動生成し、IAMロールのAssumeRoleポリシーで使用される
# ------------------------------------------------------------------------------
locals {
  datadog_external_id = datadog_integration_aws_account.main.auth_config.aws_auth_config_role.external_id
}

resource "datadog_integration_aws_account" "main" {
  aws_account_id = data.aws_caller_identity.current.account_id
  aws_partition  = "aws"

  auth_config {
    aws_auth_config_role {
      role_name = "${var.project_name}-${var.environment}-datadog-integration"
      # external_idはDatadogが自動生成する（locals.datadog_external_idでIAMロールのTrust Policyに反映済み）
    }
  }

  aws_regions {
    include_all = true
  }

  logs_config {
    lambda_forwarder {}
  }

  metrics_config {
    namespace_filters {
      include_only = [
        "AWS/ECS",
        "AWS/RDS",
        "AWS/ApplicationELB",
      ]
    }
  }

  traces_config {
    xray_services {
      include_all = true
    }
  }

  resources_config {
    cloud_security_posture_management_collection = false
    extended_collection                          = false
  }

  account_tags = ["env:production", "project:lmi"]
}

# ==============================================================================
# モニター（アラート）
# 監視・アラート設計書に基づく8指標
# ==============================================================================

# ------------------------------------------------------------------------------
# APMベースのモニター
# dd-trace-phpが有効化されデータが流れ始めてから機能する
# ------------------------------------------------------------------------------

# 可用性: 成功リクエスト率が95%未満（3分継続）→ SLO違反の可能性
resource "datadog_monitor" "availability" {
  name    = "[EC Contact] 可用性低下"
  type    = "query alert"
  message = "可用性が95%未満になっています。SLO違反の可能性があります。\n@{{monitor.name}} @email"

  query = "sum(last_3m):((sum:trace.laravel.request.hits{service:ec-contact,env:production}.as_count() - sum:trace.laravel.request.errors{service:ec-contact,env:production}.as_count()) / sum:trace.laravel.request.hits{service:ec-contact,env:production}.as_count()) * 100 < 95"

  monitor_thresholds {
    critical = 95
  }

  notify_no_data   = false
  evaluation_delay = 60
  include_tags     = true

  tags = ["service:ec-contact", "env:production", "team:lmi"]
}

# APIレイテンシ: p95が3秒超（3分継続）→ レイテンシSLO違反（目標1秒の3倍）
resource "datadog_monitor" "api_latency" {
  name    = "[EC Contact] APIレイテンシ悪化"
  type    = "query alert"
  message = "APIレイテンシのp95が3秒を超えています（目標値1秒の3倍）。\n@{{monitor.name}} @email"

  query = "avg(last_3m):p95:trace.laravel.request{service:ec-contact,env:production} > 3"

  monitor_thresholds {
    critical = 3
    warning  = 2
  }

  notify_no_data   = false
  evaluation_delay = 60
  include_tags     = true

  tags = ["service:ec-contact", "env:production", "team:lmi"]
}

# エラー率: 5xxエラー率が5%超（3分継続）→ 可用性SLO違反の可能性
resource "datadog_monitor" "error_rate" {
  name    = "[EC Contact] エラー率上昇"
  type    = "query alert"
  message = "エラー率が5%を超えています。可用性SLO違反の可能性があります。\n@{{monitor.name}} @email"

  query = "sum(last_3m):sum:trace.laravel.request.errors{service:ec-contact,env:production}.as_count() / sum:trace.laravel.request.hits{service:ec-contact,env:production}.as_count() * 100 > 5"

  monitor_thresholds {
    critical = 5
    warning  = 2
  }

  notify_no_data   = false
  evaluation_delay = 60
  include_tags     = true

  tags = ["service:ec-contact", "env:production", "team:lmi"]
}

# ------------------------------------------------------------------------------
# AWSメトリクスベースのモニター（CloudWatch経由）
# ------------------------------------------------------------------------------

# ALBターゲット正常数: ヘルスチェック成功数が0（3分継続）→ バックエンド全滅
resource "datadog_monitor" "alb_healthy_hosts" {
  name    = "[EC Contact] ALBターゲット正常数ゼロ"
  type    = "query alert"
  message = "ALBの正常ターゲット数が0になっています。バックエンドが全滅している可能性があります。\n@{{monitor.name}} @email"

  query = "min(last_3m):aws.applicationelb.healthy_host_count{*} < 1"

  monitor_thresholds {
    critical = 1
  }

  notify_no_data    = true
  no_data_timeframe = 5
  evaluation_delay  = 60
  include_tags      = true

  tags = ["service:ec-contact", "env:production", "team:lmi"]
}

# DB接続数: 最大接続数(85)の90%を超過（3分継続）→ 接続枯渇でエラー発生の恐れ
resource "datadog_monitor" "db_connections" {
  name    = "[EC Contact] DB接続数過多"
  type    = "query alert"
  message = "DB接続数が最大接続数の90%を超えています。接続枯渇によるエラーが発生する恐れがあります。\n@{{monitor.name}} @email"

  # db.t3.micro MariaDB: max_connections ≈ 85, 90% ≈ 76
  query = "max(last_3m):aws.rds.database_connections{dbinstanceidentifier:lmi-production-db} > 76"

  monitor_thresholds {
    critical = 76
    warning  = 60
  }

  notify_no_data   = false
  evaluation_delay = 60
  include_tags     = true

  tags = ["service:ec-contact", "env:production", "team:lmi"]
}

# メモリ使用率: Fargateタスクメモリ使用率90%超（3分継続）→ OOM Kill の恐れ
resource "datadog_monitor" "memory_utilization" {
  name    = "[EC Contact] メモリ使用率過多"
  type    = "query alert"
  message = "FargateタスクのメモリUtilizationが90%を超えています。OOM Killが発生する恐れがあります。\n@{{monitor.name}} @email"

  # タスクメモリ上限 1024MB の 90% = 943MB, 80% = 819MB
  query = "max(last_3m):avg:ecs.fargate.mem.usage{clustername:lmi-production-cluster} > 989855744"

  monitor_thresholds {
    critical = 989855744 # 1024MB * 90% ≈ 944MB (bytes)
    warning  = 858993459 # 1024MB * 80% ≈ 819MB (bytes)
  }

  notify_no_data   = false
  evaluation_delay = 60
  include_tags     = true

  tags = ["service:ec-contact", "env:production", "team:lmi"]
}

# RDSストレージ空き容量: 残10%未満（20GBの10% = 2GB）→ ディスク満杯でDB停止の恐れ
resource "datadog_monitor" "rds_storage" {
  name    = "[EC Contact] RDSストレージ残少"
  type    = "query alert"
  message = "RDSの空きストレージが2GB未満になっています。ディスクが満杯になるとDBが停止します。\n@{{monitor.name}} @email"

  # 20GBの10% = 2,000,000,000 bytes
  query = "min(last_3m):aws.rds.free_storage_space{dbinstanceidentifier:lmi-production-db} < 2000000000"

  monitor_thresholds {
    critical = 2000000000
    warning  = 4000000000
  }

  notify_no_data   = false
  evaluation_delay = 60
  include_tags     = true

  tags = ["service:ec-contact", "env:production", "team:lmi"]
}

# ==============================================================================
# SLO用モニター（アラートモニターとは別に、SLO目標値でのモニター）
# ==============================================================================

# APIレイテンシSLO用モニター: p95が1秒超（SLO目標値）
resource "datadog_monitor" "api_latency_slo" {
  name    = "[EC Contact][SLO] APIレイテンシ p95"
  type    = "query alert"
  message = "APIレイテンシp95が目標値1秒を超えています。"

  query = "avg(last_5m):p95:trace.laravel.request{service:ec-contact,env:production} > 1"

  monitor_thresholds {
    critical = 1
  }

  notify_no_data = false
  include_tags   = true

  tags = ["service:ec-contact", "env:production", "slo:api-latency"]
}

# 画面レイテンシSLO用モニター: p95が3秒超（SLO目標値）
resource "datadog_monitor" "screen_latency_slo" {
  name    = "[EC Contact][SLO] 画面レイテンシ p95"
  type    = "query alert"
  message = "画面レイテンシp95が目標値3秒を超えています。"

  query = "avg(last_5m):p95:trace.laravel.request{service:ec-contact,env:production} > 3"

  monitor_thresholds {
    critical = 3
  }

  notify_no_data = false
  include_tags   = true

  tags = ["service:ec-contact", "env:production", "slo:screen-latency"]
}

# ==============================================================================
# SLO（28日間ローリングウィンドウ）
# ==============================================================================

# 可用性SLO: 99.5%（メトリクスベース - 28日間ローリングウィンドウ対応）
resource "datadog_service_level_objective" "availability" {
  name        = "[EC Contact] 可用性 SLO"
  type        = "metric"
  description = "問い合わせシステムの可用性 99.5% 目標（30日間ローリングウィンドウ）"

  query {
    # http.status_class タグはdd-trace-phpで付与されないため、総ヒット数からエラー数を引く方式に変更
    numerator   = "sum:trace.laravel.request.hits{service:ec-contact,env:production}.as_count() - sum:trace.laravel.request.errors{service:ec-contact,env:production}.as_count()"
    denominator = "sum:trace.laravel.request.hits{service:ec-contact,env:production}.as_count()"
  }

  thresholds {
    timeframe = "30d"
    target    = 99.5
    warning   = 99.7
  }

  tags = ["service:ec-contact", "env:production", "team:lmi"]
}

# APIレイテンシSLO: 95%ile 1秒以内（モニターベース）
resource "datadog_service_level_objective" "api_latency" {
  name        = "[EC Contact] APIレイテンシ SLO"
  type        = "monitor"
  description = "問い合わせ受付APIのレイテンシ p95 1秒以内 目標（30日間）"

  monitor_ids = [datadog_monitor.api_latency_slo.id]

  thresholds {
    timeframe = "30d"
    target    = 95.0
    warning   = 97.0
  }

  tags = ["service:ec-contact", "env:production", "team:lmi"]
}

# 画面レイテンシSLO: 95%ile 3秒以内（モニターベース）
resource "datadog_service_level_objective" "screen_latency" {
  name        = "[EC Contact] 画面レイテンシ SLO"
  type        = "monitor"
  description = "管理画面全エンドポイントのレイテンシ p95 3秒以内 目標（30日間）"

  monitor_ids = [datadog_monitor.screen_latency_slo.id]

  thresholds {
    timeframe = "30d"
    target    = 95.0
    warning   = 97.0
  }

  tags = ["service:ec-contact", "env:production", "team:lmi"]
}

# ==============================================================================
# ダッシュボード
# ==============================================================================

# ------------------------------------------------------------------------------
# 1. SLO管理ダッシュボード
# ------------------------------------------------------------------------------
resource "datadog_dashboard" "slo" {
  title       = "[EC Contact] SLO管理ダッシュボード"
  description = "SLO達成状況の可視化・エラーバジェット管理"
  layout_type = "ordered"

  widget {
    service_level_objective_definition {
      title             = "可用性 SLO（99.5%目標 / 30日間）"
      view_type         = "detail"
      view_mode         = "overall"
      slo_id            = datadog_service_level_objective.availability.id
      time_windows      = ["30d"]
      show_error_budget = true
    }
  }

  widget {
    service_level_objective_definition {
      title             = "APIレイテンシ SLO（p95 1秒以内 / 30日間）"
      view_type         = "detail"
      view_mode         = "overall"
      slo_id            = datadog_service_level_objective.api_latency.id
      time_windows      = ["30d"]
      show_error_budget = true
    }
  }

  widget {
    service_level_objective_definition {
      title             = "画面レイテンシ SLO（p95 3秒以内 / 30日間）"
      view_type         = "detail"
      view_mode         = "overall"
      slo_id            = datadog_service_level_objective.screen_latency.id
      time_windows      = ["30d"]
      show_error_budget = true
    }
  }

  widget {
    timeseries_definition {
      title = "可用性推移（28日間）"

      request {
        q            = "((sum:trace.laravel.request.hits{service:ec-contact,env:production}.as_count() - sum:trace.laravel.request.errors{service:ec-contact,env:production}.as_count()) / sum:trace.laravel.request.hits{service:ec-contact,env:production}.as_count()) * 100"
        display_type = "line"
        style {
          palette    = "dog_classic"
          line_type  = "solid"
          line_width = "normal"
        }
      }

      yaxis {
        min   = "95"
        max   = "100"
        label = "可用性 (%)"
      }
    }
  }

  widget {
    timeseries_definition {
      title = "APIレイテンシ p95推移"

      request {
        q            = "p95:trace.laravel.request{service:ec-contact,env:production}"
        display_type = "line"
        style {
          palette    = "warm"
          line_type  = "solid"
          line_width = "normal"
        }
      }

      yaxis {
        label = "レイテンシ (秒)"
      }

      marker {
        value        = "y = 1"
        display_type = "error dashed"
        label        = "APIレイテンシ SLO目標"
      }

      marker {
        value        = "y = 3"
        display_type = "warning dashed"
        label        = "画面レイテンシ SLO目標"
      }
    }
  }
}

# ------------------------------------------------------------------------------
# 2. リアルタイム監視ダッシュボード
# ------------------------------------------------------------------------------
resource "datadog_dashboard" "realtime" {
  title       = "[EC Contact] 運用監視ダッシュボード"
  description = "システム状態の把握・障害の早期検知"
  layout_type = "ordered"

  widget {
    query_value_definition {
      title     = "リクエスト数 (req/min)"
      autoscale = true

      request {
        q          = "sum:trace.laravel.request.hits{service:ec-contact,env:production}.as_count()"
        aggregator = "sum"
      }

      timeseries_background {
        type = "area"
      }
    }
  }

  widget {
    query_value_definition {
      title     = "エラー率 (%)"
      autoscale = true

      request {
        q          = "sum:trace.laravel.request.errors{service:ec-contact,env:production}.as_count() / sum:trace.laravel.request.hits{service:ec-contact,env:production}.as_count() * 100"
        aggregator = "avg"

        conditional_formats {
          comparator = ">="
          value      = 5
          palette    = "white_on_red"
        }

        conditional_formats {
          comparator = ">="
          value      = 2
          palette    = "white_on_yellow"
        }

        conditional_formats {
          comparator = "<"
          value      = 2
          palette    = "white_on_green"
        }
      }
    }
  }

  widget {
    query_value_definition {
      title     = "レスポンスタイム p95 (秒)"
      autoscale = true

      request {
        q          = "p95:trace.laravel.request{service:ec-contact,env:production}"
        aggregator = "avg"

        conditional_formats {
          comparator = ">="
          value      = 3
          palette    = "white_on_red"
        }

        conditional_formats {
          comparator = ">="
          value      = 1
          palette    = "white_on_yellow"
        }

        conditional_formats {
          comparator = "<"
          value      = 1
          palette    = "white_on_green"
        }
      }
    }
  }

  widget {
    query_value_definition {
      title     = "ALBヘルシーターゲット数"
      autoscale = true

      request {
        q          = "min:aws.applicationelb.healthy_host_count{*}"
        aggregator = "last"

        conditional_formats {
          comparator = "<="
          value      = 0
          palette    = "white_on_red"
        }

        conditional_formats {
          comparator = ">"
          value      = 0
          palette    = "white_on_green"
        }
      }
    }
  }

  widget {
    timeseries_definition {
      title = "リクエスト数 / エラー率"

      request {
        q            = "sum:trace.laravel.request.hits{service:ec-contact,env:production}.as_count()"
        display_type = "bars"

        style {
          palette = "dog_classic"
        }
      }

      request {
        q            = "sum:trace.laravel.request.errors{service:ec-contact,env:production}.as_count()"
        display_type = "bars"

        style {
          palette = "warm"
        }
      }
    }
  }

  widget {
    timeseries_definition {
      title = "ALBヘルシーターゲット数"

      request {
        q            = "min:aws.applicationelb.healthy_host_count{*}"
        display_type = "line"

        style {
          palette    = "cool"
          line_type  = "solid"
          line_width = "thick"
        }
      }
    }
  }
}

# ------------------------------------------------------------------------------
# 3. トラブルシューティングダッシュボード
# ------------------------------------------------------------------------------
resource "datadog_dashboard" "troubleshoot" {
  title       = "[EC Contact] トラブルシューティングダッシュボード"
  description = "障害発生時の原因特定を迅速化"
  layout_type = "ordered"

  widget {
    log_stream_definition {
      title               = "エラーログ（ERROR / CRITICAL）"
      query               = "service:ec-contact status:error"
      columns             = ["@level", "@message", "timestamp"]
      indexes             = ["*"]
      message_display     = "expanded-md"
      show_date_column    = true
      show_message_column = true
    }
  }

  widget {
    timeseries_definition {
      title = "タスク別CPU使用率"

      request {
        q            = "avg:aws.ecs.cpuutilization{clustername:lmi-production-cluster} by {taskid}"
        display_type = "line"
      }
    }
  }

  widget {
    timeseries_definition {
      title = "タスク別メモリ使用率"

      request {
        q            = "avg:ecs.fargate.mem.usage{ecs_cluster:lmi-production-cluster} by {task_family}"
        display_type = "line"
      }

      marker {
        value        = "y = 989855744"
        display_type = "error dashed"
        label        = "Critical閾値（944MB）"
      }
    }
  }

  widget {
    timeseries_definition {
      title = "DB接続数"

      request {
        q            = "avg:aws.rds.database_connections{dbinstanceidentifier:lmi-production-db}"
        display_type = "line"

        style {
          palette = "warm"
        }
      }

      marker {
        value        = "y = 76"
        display_type = "error dashed"
        label        = "Critical閾値（90%）"
      }
    }
  }

  widget {
    timeseries_definition {
      title = "RDS空きストレージ"

      request {
        q            = "avg:aws.rds.free_storage_space{dbinstanceidentifier:lmi-production-db}"
        display_type = "line"

        style {
          palette = "cool"
        }
      }

      marker {
        value        = "y = 2000000000"
        display_type = "error dashed"
        label        = "Critical閾値（2GB）"
      }
    }
  }
}
