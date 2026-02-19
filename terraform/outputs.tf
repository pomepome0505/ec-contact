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

# ECS Task Execution Role ARN
output "ecs_task_execution_role_arn" {
  description = "ECS Task Execution Role ARN"
  value       = aws_iam_role.ecs_task_execution.arn
}

# ECS Task Role ARN
output "ecs_task_role_arn" {
  description = "ECS Task Role ARN"
  value       = aws_iam_role.ecs_task.arn
}

# ACM証明書ARN
output "acm_certificate_arn" {
  description = "ACM Certificate ARN"
  value       = aws_acm_certificate.main.arn
}

# ALB DNS名
output "alb_dns_name" {
  description = "ALB DNS name"
  value       = aws_lb.main.dns_name
}

# ターゲットグループARN
output "target_group_arn" {
  description = "Target Group ARN"
  value       = aws_lb_target_group.main.arn
}
