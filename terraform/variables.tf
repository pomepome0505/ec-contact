# プロジェクト名
variable "project_name" {
  description = "プロジェクト識別子（リソース名のプレフィックスとして使用）"
  type        = string
  default     = "lmi"
}

# 環境名
variable "environment" {
  description = "環境名（production, staging, dev等）"
  type        = string
  default     = "production"
}

# ドメイン名
variable "domain_name" {
  description = "Route 53で管理するドメイン名"
  type        = string
  default     = "lifestyle-mart-inquiry.com"
}

# AWSリージョン
variable "aws_region" {
  description = "AWSリソースを配置するリージョン"
  type        = string
  default     = "ap-northeast-1"
}

# VPC CIDR
variable "vpc_cidr" {
  description = "VPCのCIDRブロック"
  type        = string
  default     = "10.0.0.0/16"
}

# RDSマスターパスワード
variable "db_password" {
  description = "RDS master password"
  type        = string
  sensitive   = true
}

# アベイラビリティゾーン
variable "availability_zones" {
  description = "使用するアベイラビリティゾーンのリスト（マルチAZ構成）"
  type        = list(string)
  default     = ["ap-northeast-1a", "ap-northeast-1c"]
}

# ECSタスクで使用するDockerイメージ
variable "container_image" {
  description = "ECSタスクで使用するDockerイメージURL（ECRリポジトリURL:タグ形式で指定）"
  type        = string
  default     = "" # terraform.tfvarsまたはCI/CDパイプラインからECR URLを指定する
}

# Laravel APP_KEY
variable "app_key" {
  description = "LaravelのAPP_KEY（php artisan key:generate で生成した値）"
  type        = string
  sensitive   = true
}
