# 採用管理システム(ATS) - PHP/Laravel版

採用担当者・面接官・応募者・管理者の4ロールで動く採用管理システム(ミニATS)のポートフォリオ実装です。
同一の要件定義書・設計書をもとに、PHP/Laravel版(本リポジトリ)とJava/Spring Boot版(`ats-java`)を実装し、
2言語での実装力を比較できる構成にしています。

## 技術スタック

| レイヤー | 技術 |
|---|---|
| バックエンド | PHP 8.3以上(推奨: 8.4) / Laravel |
| フロントエンド | Blade(SSR) + Vue3(Composition API) ※選考ステータス管理画面(カンバンUI)のみ |
| データベース | MySQL 8.x |
| ローカル開発環境 | Docker Compose(PHP/Laravel + MySQLをコンテナ化。本番Renderとの環境差異を防止) |
| Lint | Laravel Pint |
| CI | GitHub Actions |
| 公開環境 | Render(無料プラン) |

## ドキュメント

- 要件定義書: <!-- TODO: リンクを追記 -->
- 設計書: <!-- TODO: リンクを追記 -->

## セットアップ

本プロジェクトはDocker Composeで開発環境を構築します(詳細はPhase1タスク7完了後に追記)。

<!-- TODO: docker compose up -d 以降の具体的な手順をタスク7完了後に追記 -->

## デモ

- URL: <!-- TODO: Phase3(Renderデプロイ)完了後に追記。無料プランのためスリープから初回アクセス時は起動に30〜60秒かかります -->
- ログイン情報: <!-- TODO: デモ用アカウント(ロール別)を追記 -->

## ブランチ運用

- `main`: 常にデプロイ可能な状態を保つ本番ブランチ。直接pushは禁止し、Pull Request経由でのみマージ
- `feature/REQ-XXX-概要`: 機能追加用(例: `feature/REQ-004-entry-form`)。要件定義書のREQ-IDをブランチ名に含め、要件とコードの対応関係を追跡
- `fix/概要`: バグ修正用(例: `fix/status-transition-validation`)

## コミットメッセージ規約(Conventional Commits)

`feat(REQ-004): 応募エントリーフォームの実装` のように、関連する要件ID(REQ-XXX)を含めます。

| プレフィックス | 用途 |
|---|---|
| feat | 新機能の追加 |
| fix | バグ修正 |
| docs | ドキュメントのみの変更 |
| style | 動作に影響しないコード整形 |
| refactor | 挙動を変えないコード整理 |
| test | テストコードの追加・修正 |
| chore | ビルド設定・依存パッケージ更新等 |

## リリースタグ

- `v0.1.0`: Phase1(PHP実装)完了時 — MVP機能一式が動作する最初のマイルストーン
- `v1.0.0`: Phase3(Docker化・Renderデプロイ)完了時 — PHP版ポートフォリオとして公開・案件応募開始
