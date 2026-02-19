# Route 53ホストゾーンのネームサーバー出力
output "route53_name_servers" {
  description = "お名前.comでこれらのネームサーバーを設定してください"
  value       = aws_route53_zone.main.name_servers
}

# RDSエンドポイント
output "rds_endpoint" {
  description = "RDS endpoint"
  value       = aws_db_instance.main.endpoint
}

# ECRリポジトリURL
output "ecr_repository_url" {
  description = "ECR repository URL"
  value       = aws_ecr_repository.app.repository_url
}
