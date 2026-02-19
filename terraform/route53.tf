# Route 53ホストゾーン
resource "aws_route53_zone" "main" {
  name = var.domain_name

  tags = {
    Name = "${var.project_name}-${var.environment}-route53-zone"
  }
}
