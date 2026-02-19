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

# Fargate Egressルール: HTTPS to VPC Endpoint
resource "aws_vpc_security_group_egress_rule" "fargate_https" {
  security_group_id = aws_security_group.fargate.id
  description       = "HTTPS to VPC Endpoint"

  referenced_security_group_id = aws_security_group.vpce.id
  from_port                    = 443
  to_port                      = 443
  ip_protocol                  = "tcp"

  tags = {
    Name = "${var.project_name}-${var.environment}-fargate-https-egress"
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
