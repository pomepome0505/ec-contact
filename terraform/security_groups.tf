# ==============================================================================
# セキュリティグループ
# ==============================================================================

# ------------------------------------------------------------------------------
# ALB用セキュリティグループ
# ------------------------------------------------------------------------------
resource "aws_security_group" "alb" {
  name        = "${var.project_name}-${var.environment}-alb-sg"
  description = "Security group for Application Load Balancer"
  vpc_id      = aws_vpc.main.id

  tags = {
    Name        = "${var.project_name}-${var.environment}-alb-sg"
    Project     = var.project_name
    Environment = var.environment
    ManagedBy   = "terraform"
  }
}

# ALB Ingressルール: HTTPS
resource "aws_vpc_security_group_ingress_rule" "alb_https" {
  security_group_id = aws_security_group.alb.id
  description       = "HTTPS from internet"

  cidr_ipv4   = "0.0.0.0/0"
  from_port   = 443
  to_port     = 443
  ip_protocol = "tcp"

  tags = {
    Name = "${var.project_name}-${var.environment}-alb-https-ingress"
  }
}

# ALB Ingressルール: HTTP (リダイレクト用)
resource "aws_vpc_security_group_ingress_rule" "alb_http" {
  security_group_id = aws_security_group.alb.id
  description       = "HTTP from internet (for redirect)"

  cidr_ipv4   = "0.0.0.0/0"
  from_port   = 80
  to_port     = 80
  ip_protocol = "tcp"

  tags = {
    Name = "${var.project_name}-${var.environment}-alb-http-ingress"
  }
}

# ALB Egressルール: ALL
resource "aws_vpc_security_group_egress_rule" "alb_all" {
  security_group_id = aws_security_group.alb.id
  description       = "Allow all outbound traffic"

  cidr_ipv4   = "0.0.0.0/0"
  ip_protocol = "-1"

  tags = {
    Name = "${var.project_name}-${var.environment}-alb-all-egress"
  }
}

# ------------------------------------------------------------------------------
# Fargate用セキュリティグループ
# ------------------------------------------------------------------------------
resource "aws_security_group" "fargate" {
  name        = "${var.project_name}-${var.environment}-fargate-sg"
  description = "Security group for Fargate tasks"
  vpc_id      = aws_vpc.main.id

  tags = {
    Name        = "${var.project_name}-${var.environment}-fargate-sg"
    Project     = var.project_name
    Environment = var.environment
    ManagedBy   = "terraform"
  }
}

# Fargate Ingressルール: HTTP from ALB
resource "aws_vpc_security_group_ingress_rule" "fargate_http" {
  security_group_id = aws_security_group.fargate.id
  description       = "HTTP from ALB"

  referenced_security_group_id = aws_security_group.alb.id
  from_port                    = 80
  to_port                      = 80
  ip_protocol                  = "tcp"

  tags = {
    Name = "${var.project_name}-${var.environment}-fargate-http-ingress"
  }
}

# Fargate Egressルール: MariaDB to RDS
resource "aws_vpc_security_group_egress_rule" "fargate_mariadb" {
  security_group_id = aws_security_group.fargate.id
  description       = "MariaDB to RDS"

  referenced_security_group_id = aws_security_group.rds.id
  from_port                    = 3306
  to_port                      = 3306
  ip_protocol                  = "tcp"

  tags = {
    Name = "${var.project_name}-${var.environment}-fargate-mariadb-egress"
  }
}

# Fargate Egressルール: HTTPS to VPC Endpoint (Interface型: ECR API/DKR/Logs)
resource "aws_vpc_security_group_egress_rule" "fargate_https_vpce" {
  security_group_id = aws_security_group.fargate.id
  description       = "HTTPS to VPC Endpoint (Interface type)"

  referenced_security_group_id = aws_security_group.vpce.id
  from_port                    = 443
  to_port                      = 443
  ip_protocol                  = "tcp"

  tags = {
    Name = "${var.project_name}-${var.environment}-fargate-https-vpce-egress"
  }
}

# Fargate Egressルール: HTTPS to S3 Gateway Endpoint
# S3 Gateway型エンドポイントはSGを持たずルートテーブル経由のため、
# CIDRベースで許可する必要がある。ECRイメージレイヤーはS3から取得される。
resource "aws_vpc_security_group_egress_rule" "fargate_https_s3" {
  security_group_id = aws_security_group.fargate.id
  description       = "HTTPS to S3 Gateway Endpoint (for ECR image layers)"

  cidr_ipv4   = "0.0.0.0/0"
  from_port   = 443
  to_port     = 443
  ip_protocol = "tcp"

  tags = {
    Name = "${var.project_name}-${var.environment}-fargate-https-s3-egress"
  }
}

# ------------------------------------------------------------------------------
# RDS用セキュリティグループ
# ------------------------------------------------------------------------------
resource "aws_security_group" "rds" {
  name        = "${var.project_name}-${var.environment}-rds-sg"
  description = "Security group for RDS MariaDB"
  vpc_id      = aws_vpc.main.id

  tags = {
    Name        = "${var.project_name}-${var.environment}-rds-sg"
    Project     = var.project_name
    Environment = var.environment
    ManagedBy   = "terraform"
  }
}

# RDS Ingressルール: MariaDB from Fargate
resource "aws_vpc_security_group_ingress_rule" "rds_mariadb" {
  security_group_id = aws_security_group.rds.id
  description       = "MariaDB from Fargate"

  referenced_security_group_id = aws_security_group.fargate.id
  from_port                    = 3306
  to_port                      = 3306
  ip_protocol                  = "tcp"

  tags = {
    Name = "${var.project_name}-${var.environment}-rds-mariadb-ingress"
  }
}

# ------------------------------------------------------------------------------
# VPCエンドポイント用セキュリティグループ
# ------------------------------------------------------------------------------
resource "aws_security_group" "vpce" {
  name        = "${var.project_name}-${var.environment}-vpce-sg"
  description = "Security group for VPC Endpoints"
  vpc_id      = aws_vpc.main.id

  tags = {
    Name        = "${var.project_name}-${var.environment}-vpce-sg"
    Project     = var.project_name
    Environment = var.environment
    ManagedBy   = "terraform"
  }
}

# VPCエンドポイント Ingressルール: HTTPS from Fargate
resource "aws_vpc_security_group_ingress_rule" "vpce_https" {
  security_group_id = aws_security_group.vpce.id
  description       = "HTTPS from Fargate"

  referenced_security_group_id = aws_security_group.fargate.id
  from_port                    = 443
  to_port                      = 443
  ip_protocol                  = "tcp"

  tags = {
    Name = "${var.project_name}-${var.environment}-vpce-https-ingress"
  }
}

# VPCエンドポイント Egressルール: VPC内への応答通信を許可
# Interface型エンドポイントはENIとして機能するため、
# VPC内（Fargateタスク等）へのレスポンス通信を明示的に許可する必要がある
resource "aws_vpc_security_group_egress_rule" "vpce_all" {
  security_group_id = aws_security_group.vpce.id
  description       = "Allow all outbound traffic from VPC Endpoint"

  cidr_ipv4   = "0.0.0.0/0"
  ip_protocol = "-1"

  tags = {
    Name = "${var.project_name}-${var.environment}-vpce-all-egress"
  }
}
