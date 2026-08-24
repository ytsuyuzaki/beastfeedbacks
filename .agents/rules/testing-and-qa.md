# Testing and Quality Assurance Rules

BeastFeedbacks プラグインの品質保証、静的解析、テスト実行に関する規約とガイドラインです。

---

## 1. 静的解析 & Lint 必須チェック

コード変更後は必ず `npm run lint` がパスすることを確認します。

```bash
npm run lint
```

`npm run lint` は以下のチェックを包括的に実行します：
- **`npm run format -- --check`**: Prettier によるコードフォーマット検査
- **`npm run lint:pkg-json`**: `package.json` のプロパティ順序等の整合性検査
- **`npm run lint:js`**: ESLint による JS/React コード検査
- **`npm run lint:css`**: Stylelint による CSS/SCSS 検査
- **`npm run lint:md:docs`**: Markdownlint による Markdown 文書検査
- **`npm run lint:php`**: `php -l` による PHP 構文エラー検査
- **`npm run lint:php:cs`**: `vendor/bin/phpcs` による WordPress Coding Standards 検査
- **`npm run check-engines`**: サポート環境要件の検証
- **`npm run check-licenses`**: 依存ライブラリのライセンス検証

### 修正コマンド
- フォーマット自動整形: `npm run format`
- JS 自動修正: `npm run lint:js:fix`
- PHPCS 自動修正: `npm run lint:php:fix`

---

## 2. バージョン整合性チェック (`test:version`)

プラグインのリリースやバージョン更新時には、バージョン番号の不一致を防ぐため `npm run test:version` を実行します。

対象ファイル:
1. `package.json` -> `"version"`
2. `package-lock.json` -> `"version"` および `"packages[""].version"`
3. `beastfeedbacks.php` -> プラグインヘッダー `* Version: x.y.z` および `define( 'BEASTFEEDBACKS_VERSION', 'x.y.z' );`
4. `readme.txt` -> `Stable tag: x.y.z`

SemVer (`x.y.z` 形式) を厳守し、1箇所でも不一致があるとテストが失敗します。

---

## 3. テスト実行環境

### (1) PHPUnit テスト
- ローカル環境 `wp-env` のコンテナ内で実行します。
  ```bash
  # 事前に wp-env を起動
  npm run wp-env:start

  # PHPUnit 実行
  npm run wp-env:test

  # カバレッジレポート生成 (tests/coverage に出力)
  npm run wp-env:test:coverage
  ```
- テストファイル配置先: `tests/phpunit/`

### (2) Playwright E2E テスト
- ブロックの挿入、設定、フロントエンドでのフィードバック送信、管理画面での集計表示を包括的にテストします。
  ```bash
  # E2E テスト実行 (wp-env 起動状態が必要)
  npm run test:e2e

  # デバッグ実行
  npm run test:e2e:debug
  ```
- テストファイル配置先: `tests/e2e/`

### (3) フルテストパイプライン
CI と同等のフルテストを実行する場合は次を使用します:
```bash
npm test
```
*(Version check -> Build -> wp-env start -> PHPUnit -> Playwright E2E -> wp-env stop を順次実行)*

---

## 4. ビルド成果物のコミット整合性

- `src/` 配下の Gutenberg ブロックコードやスタイルを変更した際は、必ず `npm run build` を実行してください。
- `build/` ディレクトリの成果物はプラグイン実行時に直接読み込まれるため、リポジトリにコミットする必要があります。
- CI では `npm run build` 後に `git diff --exit-code` を行い、未ビルドの差分がないか厳格に検査されます。
