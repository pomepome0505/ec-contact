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
| outputs.tf | route53_name_servers, rds_endpoint, ecr_repository_url, ecs_task_execution_role_arn, ecs_task_role_arn, alb_dns_name, target_group_arn, acm_certificate_arn |
| iam.tf | ECS Task Execution Role, ECS Task Role (SES送信権限) |
| cloudwatch.tf | KMS Key, CloudWatch Logs グループ `/ecs/lmi`, data.aws_caller_identity.current |
| acm.tf | ACM証明書、DNS検証 |
| alb.tf | ALB、ターゲットグループ、HTTPリスナー (80→443リダイレクト)、HTTPSリスナー (443) |

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

- 第1段階: ネットワーク (VPC, Route53, Security Groups, VPC Endpoints) - 完了
- 第2段階: RDS (MariaDB 11.8) - 完了
- 第3段階: ECR - 完了
- 第4段階: IAM Role (ecs_task_execution_role, ecs_task_role) - 完了
- 第5段階: ALB - 完了 (2026-02-20)
- 次段階: ECS/Fargate

## 注意事項
- ローカルにTerraformがインストールされていない。`terraform validate` は実行不可。
- `data.aws_caller_identity.current` は cloudwatch.tf で定義済み。他ファイルで重複定義しないこと。
- VPCエンドポイント経由のECRアクセスには `ecr:GetAuthorizationToken` の Resource が "*" 必須（リソース指定不可）。

## ALB構成 (alb.tf)
- 名前: lmi-production-alb
- DNS名: lmi-production-alb-236948396.ap-northeast-1.elb.amazonaws.com
- サブネット: public_1a, public_1c (マルチAZ)
- ターゲットグループ: lmi-production-tg (target_type=ip, Fargate用)
- ヘルスチェック: path=/, interval=30, timeout=5, matcher=200
- HTTPリスナー (80番): HTTPSへ301リダイレクト
- HTTPSリスナー (443番): ACM証明書使用、ターゲットグループへ転送
- SSL Policy: ELBSecurityPolicy-2016-08
