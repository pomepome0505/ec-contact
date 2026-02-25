# ACM証明書（SSL/TLS証明書）
# ALBでHTTPS通信を行うために必要
resource "aws_acm_certificate" "main" {
  domain_name       = var.domain_name
  validation_method = "DNS"

  tags = {
    Name    = "${var.project_name}-${var.environment}-cert"
    Project = var.project_name
  }

  # 証明書更新時は新しい証明書を先に作成してから古い証明書を削除
  lifecycle {
    create_before_destroy = true
  }
}

# ACM証明書のDNS検証用CNAMEレコードを自動作成
# Route 53にDNS検証レコードを追加することで、ドメイン所有権を証明
resource "aws_route53_record" "cert_validation" {
  for_each = {
    for dvo in aws_acm_certificate.main.domain_validation_options : dvo.domain_name => {
      name   = dvo.resource_record_name
      record = dvo.resource_record_value
      type   = dvo.resource_record_type
    }
  }

  allow_overwrite = true
  name            = each.value.name
  records         = [each.value.record]
  ttl             = 60
  type            = each.value.type
  zone_id         = aws_route53_zone.main.zone_id
}

# ACM証明書の検証完了を待機
# Terraform apply実行時に証明書が発行されるまで待機する
resource "aws_acm_certificate_validation" "main" {
  certificate_arn         = aws_acm_certificate.main.arn
  validation_record_fqdns = [for record in aws_route53_record.cert_validation : record.fqdn]
}
