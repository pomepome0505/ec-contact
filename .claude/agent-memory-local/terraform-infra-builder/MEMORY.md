# Terraform Infrastructure Builder Memory

## プロジェクト基本情報

- プロジェクト名: ec-contact (株式会社ライフスタイルマート 問い合わせ管理システム)
- Terraformディレクトリ: `terraform/`
- AWSリージョン: ap-northeast-1
- 命名規則: `lmi-prod-{サービス名}-{number}`

## 既存Terraformリソース一覧 (terraform/)

| ファイル | 主なリソース |
|---------|------------|
| backend.tf | S3 + DynamoDB バックエンド |
| provider.tf | AWS provider (v~5.0), default_tags (Environment, ManagedBy) |
| variables.tf | project_name, environment, domain_name, aws_region, vpc_cidr, db_password, availability_zones |
| vpc.tf | VPC, サブネット等 |
| vpc_endpoints.tf | VPCエンドポイント |
| security_groups.tf | セキュリティグループ |
| route53.tf | Route53ホストゾーン |
| rds.tf | RDS (MariaDB 11.8) |
| ecr.tf | ECRリポジトリ (lmi/app), ライフサイクルポリシー |
| outputs.tf | route53_name_servers, rds_endpoint, ecr_repository_url |

## タグ設定パターン

- provider.tf の default_tags で Environment, ManagedBy="Terraform" を全リソースに付与
- 個別リソースには Name, Project タグを追加
- Nameタグは命名規則 `lmi-prod-{サービス名}` に従う

## 変数デフォルト値

- environment: "production"
- project_name: "lmi"
- aws_region: "ap-northeast-1"
- availability_zones: ["ap-northeast-1a", "ap-northeast-1c"]
- vpc_cidr: "10.0.0.0/16"
- db_password: sensitive, no default

## 構築フェーズ

- 第1段階: ネットワーク (VPC, Route53, Security Groups, VPC Endpoints)
- 第2段階: RDS (MariaDB 11.8)
- 第3段階: ECR (完了)
- 次段階: ECS/Fargate, ALB, IAM 等
