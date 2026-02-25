# Terraformおよびプロバイダーのバージョン制約
terraform {
  required_version = ">= 1.0"

  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "~> 5.0"
    }
  }
}

# AWSプロバイダー設定
provider "aws" {
  region = var.aws_region

  # 全リソースに適用される共通タグ
  default_tags {
    tags = {
      Environment = var.environment
      ManagedBy   = "Terraform"
    }
  }
}
