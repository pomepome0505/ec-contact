# CLAUDE.md

## プロジェクト概要

ECサイト「株式会社ライフスタイルマート」の問い合わせ管理システム。
カスタマーサポート8名が使用する社内向けWebアプリケーション。

## ドキュメント情報
ルートディレクトリのdocuments配下に設計書等のドキュメントがある。

## 技術スタック

- **バックエンド**: Laravel 12 (PHP 8.5)
- **フロントエンド**: Vue.js + Inertia.js, Vuetify 3, Vite
- **DB**: MariaDB 11.8
- **インフラ**: AWS (ECS/Fargate), Docker, Terraform
- **CI/CD**: GitHub Actions
- **監視**: Datadog, CloudWatch

## ディレクトリ構成

```
ec-contact/
├── src/                  # Laravelプロジェクト（アプリケーションコード）
├── docker/               # Docker設定ファイル
│   ├── entrypoint-dev.sh # 開発用エントリポイント（自動セットアップ）
│   ├── nginx/            # Nginx設定
│   ├── php/              # PHP-FPM設定（dev/prod）
│   └── supervisor/       # Supervisor設定
├── documents/            # 設計書
├── Dockerfile            # マルチステージ（development / production）
├── docker-compose.yml    # ローカル開発環境
└── README.md
```

## 開発環境

### 起動方法

```bash
docker compose up -d --build
```

`entrypoint-dev.sh` が自動で composer install / key:generate / migrate / npm install / npm run dev を実行する。

### URL一覧

| サービス | URL |
|---------|-----|
| Laravel | http://localhost |
| phpMyAdmin | http://localhost:8080 |
| MailHog | http://localhost:8025 |

### DB接続情報

- Host: db (コンテナ間) / localhost:3306 (ホストから)
- Database: ec_contact
- User: ec_user
- Password: ec_password

## Git運用

- **メインブランチ**: main
- **開発ブランチ**: develop
- **リモート**: git@github.com:pomepome0505/ec-contact.git
- **コミットメッセージ**: 日本語

## Docker構成

- Nginx + PHP-FPM を1コンテナに統合（Supervisorで管理）
- Unixソケット通信（/run/php-fpm.sock）
- ログは全て stdout/stderr（CloudWatch対応）
- 本番: 開発ツール（git, vim, xdebug）を含めない

## 注意事項

- Laravelコードは `src/` 配下。ルート直下ではない
- `.gitattributes` で全ファイル LF 改行に統一（Windows環境のCRLF問題防止）
- PHP 8.5 では opcache が組み込み済み。`docker-php-ext-install opcache` は不要
- `docker-php-ext-install -j$(nproc)` はPHP 8.5で競合するため使わない

## 開発ルール
- バックエンド・フロントエンド共に実装後は、静的解析ツール・フォーマッターを実行する
- バックエンドの実装後は、テストコードを実行する
- バックエンドの業務ロジックはServiceクラス、HTTP層の実装はControllerクラスに実装する
- AWSリソースの命名規則は、lmi-prod-{サービス名}-{number（同じサービスを複数使用する場合などに使う）}
- Terraformを使用して、AWSでのインフラ構築を行う
- Dockerイメージの更新・composerやnpmの依存関係を更新した際はOSV-Scannerを使って解析し、脆弱性があれば改善提案をする。