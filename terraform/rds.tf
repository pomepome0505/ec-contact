# ==============================================================================
# RDS (MariaDB)
# ==============================================================================

# ------------------------------------------------------------------------------
# DB Subnet Group
# ------------------------------------------------------------------------------
resource "aws_db_subnet_group" "main" {
  name = "${var.project_name}-${var.environment}-db-subnet-group"
  subnet_ids = [
    aws_subnet.private_rds_1a.id,
    aws_subnet.private_rds_1c.id,
  ]

  tags = {
    Name = "${var.project_name}-${var.environment}-db-subnet-group"
  }
}

# ------------------------------------------------------------------------------
# RDS Instance
# ------------------------------------------------------------------------------
resource "aws_db_instance" "main" {
  identifier = "${var.project_name}-${var.environment}-db"

  # Engine
  engine         = "mariadb"
  engine_version = "11.8.5"
  instance_class = "db.t3.micro"

  # Storage
  allocated_storage = 20
  storage_type      = "gp3"
  storage_encrypted = true

  # Network
  availability_zone      = var.availability_zones[0]  # ap-northeast-1a に配置
  multi_az               = false
  db_subnet_group_name   = aws_db_subnet_group.main.name
  vpc_security_group_ids = [aws_security_group.rds.id]
  publicly_accessible    = false

  # Authentication
  db_name  = "lmi_production"
  username = "admin"
  password = var.db_password

  # Backup
  backup_retention_period = 0  # 自動バックアップ無効
  # backup_window は削除

  # Maintenance
  maintenance_window = "sun:11:00-sun:12:00"

  # Deletion
  deletion_protection       = false
  skip_final_snapshot       = true
  final_snapshot_identifier = null

  tags = {
    Name = "${var.project_name}-${var.environment}-db"
  }
}
