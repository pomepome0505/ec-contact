# Terraformおよびプロバイダーのバージョン制約
terraform {
  required_version = ">= 1.0"

  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "~> 5.0"
    }
    datadog = {
      source  = "DataDog/datadog"
      version = "~> 3.0"
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

# Datadogプロバイダー設定
provider "datadog" {
  api_key = var.datadog_api_key
  app_key = var.datadog_app_key
  api_url = "https://api.ap1.datadoghq.com/"
}
