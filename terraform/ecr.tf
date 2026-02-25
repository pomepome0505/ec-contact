# ECRリポジトリ
# Nginx + PHP-FPMを統合したアプリケーションコンテナイメージを管理する
resource "aws_ecr_repository" "app" {
  name                 = "lmi/app"
  image_tag_mutability = "MUTABLE"

  # push時にイメージの脆弱性スキャンを実行する
  image_scanning_configuration {
    scan_on_push = true
  }

  # AES256による保存時暗号化
  encryption_configuration {
    encryption_type = "AES256"
  }

  tags = {
    Name    = "lmi-prod-ecr"
    Project = "ec-contact"
  }
}

# ECRライフサイクルポリシー
# タグなしイメージを1個まで保持し、古いイメージを自動削除することでストレージコストを抑制する
resource "aws_ecr_lifecycle_policy" "app" {
  repository = aws_ecr_repository.app.name

  policy = jsonencode({
    rules = [
      {
        # タグなしイメージは1個を上限として保持し、それ以上は古い順に削除する
        rulePriority = 1
        description  = "タグなしイメージを1個まで保持"
        selection = {
          tagStatus   = "untagged"
          countType   = "imageCountMoreThan"
          countNumber = 1
        }
        action = {
          type = "expire"
        }
      }
    ]
  })
}
