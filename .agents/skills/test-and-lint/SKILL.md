---
name: test-and-lint
description: >-
  Runs static analysis, linters, PHPUnit, and Playwright E2E tests for BeastFeedbacks.
  Use when validating code changes, fixing lint or coding standard issues, or investigating test failures.
---

# Test and Lint Workflow

このスキルは、BeastFeedbacks プラグインの静的解析（Lint）、構文チェック、および単体・統合・E2E テストを実行・デバッグするための包括的手順書です。

---

## 1. 静的解析 & Lint 実行手順

コード編集後、まずは静的解析・構文チェックを実行して規約違反や構文エラーがないか確認します。

### 一括 Lint (CI 同等)
```bash
npm run lint
```

### エラー発生時の個別検査 & 自動修正コマンド

#### (1) コードフォーマット (Prettier)
```bash
# 検査
npm run format -- --check

# 自動整形
npm run format
```

#### (2) JavaScript / React (ESLint)
```bash
# 検査
npm run lint:js

# 自動修正
npm run lint:js:fix
```

#### (3) CSS / SCSS (Stylelint)
```bash
npm run lint:css
```

#### (4) PHP 構文 & Coding Standards (PHPCS)
```bash
# PHP 構文チェック (php -l)
npm run lint:php

# WordPress Coding Standards チェック
npm run lint:php:cs

# 自動修正可能な違反の自動修正 (phpcbf)
npm run lint:php:fix
```

#### (5) Markdown ドキュメント
```bash
npm run lint:md:docs
```

---

## 2. バージョン整合性チェック

バージョン番号を変更した際、またはリリース前の検査として実行します。

```bash
npm run test:version
```

- 失敗した場合は、`package.json`, `package-lock.json`, `beastfeedbacks.php`, `readme.txt` の 4 箇所のバージョン番号が完全に一致しているか確認してください。

---

## 3. テストの実行手順

### (1) JavaScript 単体テスト (Jest + React Testing Library)
Gutenberg ブロックのコンポーネントやロジック単体を高速に検証します。

```bash
# 全 JS 単体テスト実行
npm run test:unit:js

# ウォッチモード (開発中)
npm run test:unit:js:watch

# 特定のテストファイルのみ実行
npm run test:unit:js -- src/like/__tests__/index.test.js
```

### (2) PHPUnit 単体/統合テスト
事前に `wp-env` を起動しておく必要があります。

```bash
# 1. 環境起動
npm run wp-env:start

# 2. 全 PHPUnit テスト実行
npm run wp-env:test

# 特定のテストファイル・メソッドのみ実行 (filter)
npx wp-env run tests-cli --env-cwd="wp-content/plugins/beastfeedbacks/" vendor/bin/phpunit --filter test_name

# カバレッジ付き実行
npm run wp-env:test:coverage
```

### (3) Playwright E2E テスト
実ブラウザを用いてブロックの挿入・設定、フロントエンドでのフィードバック送信、管理画面での集計表示を検証します。

```bash
# 通常実行 (ヘッドレス)
npm run test:e2e

# 特定のテストファイルのみ実行
npx playwright test tests/e2e/like.spec.js

# UI / デバッグモード (ステップ実行・セレクタ確認)
npm run test:e2e:debug
```

### (4) テスト終了後の環境停止
```bash
npm run wp-env:stop
```

### (5) CI 同等のフルテスト一括実行
```bash
npm test
```
*(Version check -> JS Unit -> Build -> wp-env start -> PHPUnit -> E2E -> wp-env stop を順次実行)*
