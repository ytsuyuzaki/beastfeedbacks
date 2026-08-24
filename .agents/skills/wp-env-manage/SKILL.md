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

### 起動 (Xdebug Coverage 有効)
```bash
npm run wp-env:start
```
- デフォルトポート:
  - 開発用サイト: `http://localhost:8888` (ユーザー: `admin`, パスワード: `password`)
  - テスト用サイト: `http://localhost:8889` (ユーザー: `admin`, パスワード: `password`)

### 停止
```bash
npm run wp-env:stop
```

### 環境のクリーン再構築 (トラブルシュート時)
コンテナやデータベースの状態がおかしい場合、環境を破棄して再構築します。
```bash
npx wp-env destroy
npm run wp-env:start
```

---

## 2. コンテナ内でのコマンド実行

### (1) WP-CLI コマンドの実行
開発環境やテスト環境で WP-CLI コマンドを実行する場合：

```bash
# 開発インスタンス (cli) で実行
npx wp-env run cli wp plugin list

# テストインスタンス (tests-cli) で実行
npx wp-env run tests-cli wp plugin list
```

### (2) プラグインディレクトリ内での PHPUnit 実行
```bash
npx wp-env run tests-cli --env-cwd="wp-content/plugins/beastfeedbacks/" vendor/bin/phpunit
```

### (3) カバレッジレポート生成
```bash
npx wp-env run tests-cli --env-cwd="wp-content/plugins/beastfeedbacks/" sh -lc "XDEBUG_MODE=coverage vendor/bin/phpunit --coverage-html tests/coverage --colors=always"
```

---

## 3. 設定ファイル (`.wp-env.json`) の確認

プロジェクトルートの `.wp-env.json` または `.github/.wp-env.template.json` で WordPress / PHP のバージョンが指定されています。

- PHP バージョンや WordPress バージョンを切り替えてテストしたい場合は、`.wp-env.json` を編集して `npm run wp-env:start` を実行してください。
