# Terraform State管理用のS3バックエンド設定
terraform {
  backend "s3" {
    bucket = "lmi-prod-s3-terraform-state"
    key    = "production/terraform.tfstate"
    region = "ap-northeast-1"
  }
}
