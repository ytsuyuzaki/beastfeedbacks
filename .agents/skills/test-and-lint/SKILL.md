---
name: test-and-lint
description: >-
  Runs static analysis, linters, PHPUnit, and Playwright E2E tests for BeastFeedbacks.
  Use when validating code changes, fixing lint or coding standard issues, or investigating test failures.
---

# Test and Lint Workflow

このスキルは、BeastFeedbacks プラグインの品質検査（Lint / 静的解析）および単体・統合・E2Eテストを実行・デバッグするための手順書です。

---

## 1. 静的解析 & Lint 実行手順

コード編集後、まずは静的解析・構文チェックを実行します。

### 一括 Lint
```bash
npm run lint
```

### エラー発生時の個別対応 & 自動修正

#### (1) フォーマット・Prettier
```bash
# チェック
npm run format -- --check

# 自動整形
npm run format
```

#### (2) JavaScript / React (ESLint)
```bash
# チェック
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

# 自動修正可能な違反の修正 (phpcbf)
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

- 失敗した場合は、`package.json`, `package-lock.json`, `beastfeedbacks.php`, `readme.txt` のバージョン番号が完全に一致しているか確認してください。

---

## 3. テストの実行手順

### (1) ローカル WordPress 環境 (`wp-env`) の準備
PHPUnit または Playwright E2E テストを実行する前に、`wp-env` が起動している必要があります。

```bash
# 環境起動
npm run wp-env:start
```

### (2) PHPUnit 単体/統合テスト
```bash
# PHPUnit 実行
npm run wp-env:test

# カバレッジ付き実行
npm run wp-env:test:coverage
```

### (3) Playwright E2E テスト
```bash
# E2E テスト実行
npm run test:e2e

# UI/デバッグモードでの実行
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
*(Version check -> Build -> wp-env start -> PHPUnit -> E2E -> wp-env stop を順次実行)*
