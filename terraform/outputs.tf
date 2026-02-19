# Route 53ホストゾーンのネームサーバー出力
output "route53_name_servers" {
  description = "お名前.comでこれらのネームサーバーを設定してください"
  value       = aws_route53_zone.main.name_servers
}
