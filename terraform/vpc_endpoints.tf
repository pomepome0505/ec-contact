# ==============================================================================
# VPCエンドポイント
# ==============================================================================

# ------------------------------------------------------------------------------
# Interface型エンドポイント: ECR API
# コスト削減のため停止中（ECS稼働再開時にコメントを外すこと）
# ------------------------------------------------------------------------------
# resource "aws_vpc_endpoint" "ecr_api" {
#   vpc_id              = aws_vpc.main.id
#   service_name        = "com.amazonaws.${var.aws_region}.ecr.api"
#   vpc_endpoint_type   = "Interface"
#   subnet_ids          = [aws_subnet.private_fargate_1a.id]
#   security_group_ids  = [aws_security_group.vpce.id]
#   private_dns_enabled = true
#
#   tags = {
#     Name = "${var.project_name}-${var.environment}-vpce-ecr-api"
#   }
# }

# ------------------------------------------------------------------------------
# Interface型エンドポイント: ECR DKR
# コスト削減のため停止中（ECS稼働再開時にコメントを外すこと）
# ------------------------------------------------------------------------------
# resource "aws_vpc_endpoint" "ecr_dkr" {
#   vpc_id              = aws_vpc.main.id
#   service_name        = "com.amazonaws.${var.aws_region}.ecr.dkr"
#   vpc_endpoint_type   = "Interface"
#   subnet_ids          = [aws_subnet.private_fargate_1a.id]
#   security_group_ids  = [aws_security_group.vpce.id]
#   private_dns_enabled = true
#
#   tags = {
#     Name = "${var.project_name}-${var.environment}-vpce-ecr-dkr"
#   }
# }

# ------------------------------------------------------------------------------
# Interface型エンドポイント: CloudWatch Logs
# コスト削減のため停止中（ECS稼働再開時にコメントを外すこと）
# ------------------------------------------------------------------------------
# resource "aws_vpc_endpoint" "logs" {
#   vpc_id              = aws_vpc.main.id
#   service_name        = "com.amazonaws.${var.aws_region}.logs"
#   vpc_endpoint_type   = "Interface"
#   subnet_ids          = [aws_subnet.private_fargate_1a.id]
#   security_group_ids  = [aws_security_group.vpce.id]
#   private_dns_enabled = true
#
#   tags = {
#     Name = "${var.project_name}-${var.environment}-vpce-logs"
#   }
# }

# ------------------------------------------------------------------------------
# Interface型エンドポイント: SES (メール送信)
# コスト削減のため停止中（ECS稼働再開時にコメントを外すこと）
# ------------------------------------------------------------------------------
# resource "aws_vpc_endpoint" "ses" {
#   vpc_id              = aws_vpc.main.id
#   service_name        = "com.amazonaws.${var.aws_region}.email"
#   vpc_endpoint_type   = "Interface"
#   subnet_ids          = [aws_subnet.private_fargate_1a.id]
#   security_group_ids  = [aws_security_group.vpce.id]
#   private_dns_enabled = true
#
#   tags = {
#     Name = "${var.project_name}-${var.environment}-vpce-ses"
#   }
# }

# ------------------------------------------------------------------------------
# Gateway型エンドポイント: S3
# Gateway型は無料のため維持
# ------------------------------------------------------------------------------
resource "aws_vpc_endpoint" "s3" {
  vpc_id            = aws_vpc.main.id
  service_name      = "com.amazonaws.${var.aws_region}.s3"
  vpc_endpoint_type = "Gateway"
  route_table_ids = [
    aws_route_table.private_fargate.id,
    aws_route_table.private_rds.id,
  ]

  tags = {
    Name = "${var.project_name}-${var.environment}-vpce-s3"
  }
}
