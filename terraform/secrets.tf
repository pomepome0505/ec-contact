# ==============================================================================
# Secrets Manager: アプリケーションシークレット
# ==============================================================================

# ------------------------------------------------------------------------------
# Laravel APP_KEY
# ECSタスク実行時にAPP_KEYとして注入される
# ------------------------------------------------------------------------------
resource "aws_secretsmanager_secret" "app_key" {
  name        = "${var.project_name}-${var.environment}-app-key"
  description = "Laravel APP_KEY for encryption"

  tags = {
    Name        = "${var.project_name}-${var.environment}-app-key"
    Project     = var.project_name
    Environment = var.environment
    ManagedBy   = "terraform"
  }
}

resource "aws_secretsmanager_secret_version" "app_key" {
  secret_id     = aws_secretsmanager_secret.app_key.id
  secret_string = var.app_key
}

# ------------------------------------------------------------------------------
# RDS DB_PASSWORD
# ECSタスク実行時にDB_PASSWORDとして注入される
# RDS自体のパスワード設定はvar.db_passwordで引き続き管理
# ------------------------------------------------------------------------------
resource "aws_secretsmanager_secret" "db_password" {
  name        = "${var.project_name}-${var.environment}-db-password"
  description = "RDS master password for application DB connection"

  tags = {
    Name        = "${var.project_name}-${var.environment}-db-password"
    Project     = var.project_name
    Environment = var.environment
    ManagedBy   = "terraform"
  }
}

resource "aws_secretsmanager_secret_version" "db_password" {
  secret_id     = aws_secretsmanager_secret.db_password.id
  secret_string = var.db_password
}
