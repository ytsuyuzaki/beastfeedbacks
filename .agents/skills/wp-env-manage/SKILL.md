---
name: wp-env-manage
description: >-
  Manages the local WordPress Docker environment using wp-env.
  Use when starting/stopping the test environment, executing WP-CLI commands, or troubleshooting Docker/WordPress test instances.
---

# Local WordPress Environment (`wp-env`) Management

このスキルは、`@wordpress/env` (`wp-env`) を利用したローカル WordPress コンテナ環境の起動・停止・管理・デバッグ手順を案内します。

---

## 1. 環境の基本操作

### (1) 通常起動
```bash
npm run wp-env:start
```
- 開発用サイト: `http://localhost:8888` (ユーザー: `admin`, パスワード: `password`)
- テスト用サイト: `http://localhost:8889` (ユーザー: `admin`, パスワード: `password`)

### (2) Xdebug カバレッジ有効化での起動
PHPUnit のコードカバレッジを測定する場合は、Xdebug coverage を有効にして起動します。
```bash
npm run wp-env:start:coverage
```

### (3) 環境の停止
```bash
npm run wp-env:stop
```

### (4) 環境の完全リセット (トラブルシューティング時)
コンテナの停止や起動に失敗する場合、またはデータベースを初期化したい場合は完全破棄して再起動します。
```bash
npx wp-env destroy
npm run wp-env:start
```

---

## 2. コンテナ内でのコマンド実行 (WP-CLI & Shell)

### (1) WP-CLI コマンドの実行
開発インスタンス (`cli`) またはテストインスタンス (`tests-cli`) を指定して WP-CLI を実行します。

```bash
# プラグインの有効化状態を確認
npx wp-env run cli wp plugin list

# テスト用の固定ページ / 投稿を作成
npx wp-env run cli wp post create --post_title="Feedback Test Post" --post_status=publish

# サイトのオプション値を取得
npx wp-env run cli wp option get siteurl

# プラグインのアクティベート
npx wp-env run cli wp plugin activate beastfeedbacks
```

### (2) コンテナ内でのテスト実行
```bash
# テストインスタンス内で PHPUnit を直接実行
npx wp-env run tests-cli --env-cwd="wp-content/plugins/beastfeedbacks/" vendor/bin/phpunit

# 特定のテストメソッドのみを実行 (フィルター指定)
npx wp-env run tests-cli --env-cwd="wp-content/plugins/beastfeedbacks/" vendor/bin/phpunit --filter test_save_feedback
```

---

## 3. 設定ファイル (`.wp-env.json`) のカスタマイズ

プロジェクトルートの `.wp-env.json` または `.github/.wp-env.template.json` で WordPress コアや PHP のバージョンが指定されています。

- **バージョン変更時の例**:
  ```json
  {
    "core": "WordPress/WordPress#6.8",
    "phpVersion": "8.2",
    "plugins": [ "." ]
  }
  ```
- 設定を変更した場合は、`npm run wp-env:stop` 後に `npm run wp-env:start` を実行して反映します。
