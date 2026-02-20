# ==============================================================================
# SES (Simple Email Service)
# ==============================================================================

# ------------------------------------------------------------------------------
# SES Domain Identity
# ------------------------------------------------------------------------------
resource "aws_ses_domain_identity" "main" {
  domain = var.domain_name
}

# ------------------------------------------------------------------------------
# SES Domain Verification TXT Record
# ------------------------------------------------------------------------------
resource "aws_route53_record" "ses_verification" {
  zone_id = aws_route53_zone.main.zone_id
  name    = "_amazonses.${var.domain_name}"
  type    = "TXT"
  ttl     = 600
  records = [aws_ses_domain_identity.main.verification_token]
}

# ------------------------------------------------------------------------------
# SES Domain Identity Verification
# ------------------------------------------------------------------------------
resource "aws_ses_domain_identity_verification" "main" {
  domain = aws_ses_domain_identity.main.id

  depends_on = [aws_route53_record.ses_verification]
}

# ------------------------------------------------------------------------------
# SES Domain DKIM
# ------------------------------------------------------------------------------
resource "aws_ses_domain_dkim" "main" {
  domain = aws_ses_domain_identity.main.domain
}

# DKIM検証用Route 53レコード (3つ)
resource "aws_route53_record" "ses_dkim" {
  count   = 3
  zone_id = aws_route53_zone.main.zone_id
  name    = "${aws_ses_domain_dkim.main.dkim_tokens[count.index]}._domainkey.${var.domain_name}"
  type    = "CNAME"
  ttl     = 300
  records = ["${aws_ses_domain_dkim.main.dkim_tokens[count.index]}.dkim.amazonses.com"]
}

# ------------------------------------------------------------------------------
# SPFレコード
# ------------------------------------------------------------------------------
resource "aws_route53_record" "ses_spf" {
  zone_id = aws_route53_zone.main.zone_id
  name    = var.domain_name
  type    = "TXT"
  ttl     = 300
  records = ["v=spf1 include:amazonses.com ~all"]
}

# ------------------------------------------------------------------------------
# DMARCレコード
# ------------------------------------------------------------------------------
resource "aws_route53_record" "ses_dmarc" {
  zone_id = aws_route53_zone.main.zone_id
  name    = "_dmarc.${var.domain_name}"
  type    = "TXT"
  ttl     = 300
  records = ["v=DMARC1; p=none; rua=mailto:postmaster@${var.domain_name}"]
}

