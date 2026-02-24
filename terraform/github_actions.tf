# ==============================================================================
# GitHub Actions OIDC 認証
# GitHub ActionsワークフローがAWS一時認証情報を取得するためのOIDC連携。
# アクセスキーを発行せず、OIDCトークンによる短命な認証情報でAWSリソースを操作する。
# ==============================================================================

# ------------------------------------------------------------------------------
# GitHub Actions OIDC プロバイダー
# GitHub ActionsのOIDCエンドポイントをAWS IAMの信頼済みIDプロバイダーとして登録する。
# 同一AWSアカウントでプロバイダーが既に存在する場合は data ソースで参照する方式に変更すること。
# ------------------------------------------------------------------------------
resource "aws_iam_openid_connect_provider" "github_actions" {
  # GitHub ActionsのOIDCエンドポイントURL
  url = "https://token.actions.githubusercontent.com"

  # AWS STS へのAssumeRoleWithWebIdentityで使用するAudience
  client_id_list = ["sts.amazonaws.com"]

  # サムプリント:
  # 2023年7月以降、AWSはGitHub ActionsのOIDCプロバイダーに対して
  # 独自の信頼済みルートCA一覧で検証するため、サムプリントは不要。
  # ただしTerraformの仕様上、空リストを明示的に指定する。
  # 参考: https://github.blog/changelog/2023-06-27-github-actions-update-on-oidc-integration-with-aws/
  thumbprint_list = []

  tags = {
    Name        = "${var.project_name}-${var.environment}-github-actions-oidc"
    Project     = var.project_name
    Environment = var.environment
    ManagedBy   = "terraform"
  }
}

# ------------------------------------------------------------------------------
# GitHub Actions IAM ロール
# OIDCトークンを持つGitHub Actionsワークフローがこのロールを引き受けることができる。
# 信頼ポリシーでリポジトリとブランチを絞り込み、不正なリポジトリからの利用を防ぐ。
# ------------------------------------------------------------------------------
resource "aws_iam_role" "github_actions" {
  name = "${var.project_name}-${var.environment}-github-actions-role"

  # GitHub ActionsのOIDCトークンを持つワークフローのみがこのロールを引き受けられる
  assume_role_policy = jsonencode({
    Version = "2012-10-17"
    Statement = [
      {
        Sid    = "AllowGitHubActionsAssumeRole"
        Effect = "Allow"
        Principal = {
          Federated = aws_iam_openid_connect_provider.github_actions.arn
        }
        Action = "sts:AssumeRoleWithWebIdentity"
        Condition = {
          StringEquals = {
            # Audienceの検証: sts.amazonaws.com のみ許可
            "token.actions.githubusercontent.com:aud" = "sts.amazonaws.com"
          }
          StringLike = {
            # リポジトリとブランチの制限: 指定リポジトリの全ブランチからのみ引き受けを許可
            # main/develop ブランチのCI/CDパイプラインで利用する
            "token.actions.githubusercontent.com:sub" = "repo:pomepome0505/ec-contact:ref:refs/heads/*"
          }
        }
      }
    ]
  })

  tags = {
    Name        = "${var.project_name}-${var.environment}-github-actions-role"
    Project     = var.project_name
    Environment = var.environment
    ManagedBy   = "terraform"
  }
}

# ------------------------------------------------------------------------------
# GitHub Actions IAM ポリシー
# CI/CDパイプラインで必要な最小権限を付与する。
# - ECR: イメージのpush/pull
# - ECS: タスク定義の登録・サービスの更新・状態確認
# - IAM: タスク定義登録時のPassRole（ECS実行ロール・タスクロール）
# - CloudWatch Logs: タスク定義登録時のDescribeLogGroups
# ------------------------------------------------------------------------------
resource "aws_iam_role_policy" "github_actions" {
  name = "${var.project_name}-${var.environment}-github-actions-policy"
  role = aws_iam_role.github_actions.id

  policy = jsonencode({
    Version = "2012-10-17"
    Statement = [
      # ------------------------------------------------------------------
      # ECR 認証トークンの取得
      # ECRへのdocker login時に必要。特定リソースに紐付けられないため "*" を使用する
      # ------------------------------------------------------------------
      {
        Sid    = "AllowECRGetAuthorizationToken"
        Effect = "Allow"
        Action = [
          "ecr:GetAuthorizationToken",
        ]
        Resource = "*"
      },
      # ------------------------------------------------------------------
      # ECR イメージのpush/pull
      # lmi/app リポジトリへのイメージpush（CI/CDでイメージをECRに登録する）
      # ------------------------------------------------------------------
      {
        Sid    = "AllowECRImagePush"
        Effect = "Allow"
        Action = [
          "ecr:BatchCheckLayerAvailability",
          "ecr:GetDownloadUrlForLayer",
          "ecr:BatchGetImage",
          "ecr:PutImage",
          "ecr:InitiateLayerUpload",
          "ecr:UploadLayerPart",
          "ecr:CompleteLayerUpload",
        ]
        Resource = aws_ecr_repository.app.arn
      },
      # ------------------------------------------------------------------
      # ECS タスク定義の登録
      # 新しいイメージタグを含むタスク定義リビジョンをCI/CDで登録する。
      # RegisterTaskDefinitionはリソースレベルの制限が不可のため "*" を使用する
      # ------------------------------------------------------------------
      {
        Sid    = "AllowECSRegisterTaskDefinition"
        Effect = "Allow"
        Action = [
          "ecs:RegisterTaskDefinition",
        ]
        Resource = "*"
      },
      # ------------------------------------------------------------------
      # ECS サービスの更新・状態確認
      # 新しいタスク定義でサービスを更新（デプロイ）する
      # ------------------------------------------------------------------
      {
        Sid    = "AllowECSUpdateService"
        Effect = "Allow"
        Action = [
          "ecs:UpdateService",
        ]
        Resource = aws_ecs_service.main.id
      },
      {
        Sid    = "AllowECSDescribeServices"
        Effect = "Allow"
        Action = [
          "ecs:DescribeServices",
        ]
        # DescribeServicesはクラスターレベルのARNで制限する
        Resource = aws_ecs_cluster.main.arn
      },
      # ------------------------------------------------------------------
      # IAM PassRole
      # RegisterTaskDefinitionでECS実行ロール・タスクロールを指定する際に必要
      # ------------------------------------------------------------------
      {
        Sid    = "AllowIAMPassRoleForECS"
        Effect = "Allow"
        Action = [
          "iam:PassRole",
        ]
        Resource = [
          aws_iam_role.ecs_task_execution.arn,
          aws_iam_role.ecs_task.arn,
        ]
        Condition = {
          StringEquals = {
            # ECSタスク定義へのPassRoleのみ許可する
            "iam:PassedToService" = "ecs-tasks.amazonaws.com"
          }
        }
      },
      # ------------------------------------------------------------------
      # CloudWatch Logs の参照
      # タスク定義登録時にロググループの存在確認で必要となる
      # ------------------------------------------------------------------
      {
        Sid    = "AllowCloudWatchLogsDescribe"
        Effect = "Allow"
        Action = [
          "logs:DescribeLogGroups",
        ]
        Resource = "*"
      },
    ]
  })
}

# ------------------------------------------------------------------------------
# Output: GitHub Actions IAM ロール ARN
# GitHub ActionsワークフローのYAMLファイルでこのARNを参照する。
# 例: role-to-assume: ${{ secrets.AWS_ROLE_ARN }}
# または outputs から直接取得して GitHub Secrets に登録する
# ------------------------------------------------------------------------------
output "github_actions_role_arn" {
  description = "GitHub ActionsがOIDC認証でAssumeRoleするIAMロールのARN"
  value       = aws_iam_role.github_actions.arn
}
