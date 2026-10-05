# Testing and Quality Assurance Rules

BeastFeedbacks プラグインの品質保証、静的解析、多層テスト実行、および CI/CD 連携に関する規約とガイドラインです。

---

## 1. 多層テストピラミッド

BeastFeedbacks では、変更によるリグレッションを防止するため、以下の階層的なテストピラミッドを採用しています。

```text
       ▲
      / \     Playwright E2E テスト (ブラウザ自動操作・統合シナリオ)
     /   \
    /     \    PHPUnit テスト (WP_UnitTestCase / wp-env コンテナ内)
   /       \
  /         \   JS 単体テスト (Jest + React Testing Library)
 /           \
/             \  静的解析 & Lint (PHPCS, ESLint, Stylelint, Prettier, PHP -l)
---------------
```

---

## 2. 静的解析 & Lint 必須チェック

コード変更後は必ず `npm run lint` がパスすることを確認します。

```bash
npm run lint
```

### 検査項目一覧:
- **`npm run format -- --check`**: Prettier によるコードフォーマット検査
- **`npm run lint:pkg-json`**: `package.json` のプロパティ順序・整合性検査
- **`npm run lint:js`**: ESLint による JS/React コード検査
- **`npm run lint:css`**: Stylelint による CSS/SCSS 検査
- **`npm run lint:md:docs`**: Markdownlint による Markdown 文書検査
- **`npm run lint:php`**: `php -l` による PHP 構文エラー検査
- **`npm run lint:php:cs`**: `vendor/bin/phpcs` による WordPress Coding Standards 検査
- **`npm run check-engines`**: Node / npm エンジン要件の検証
- **`npm run check-licenses`**: 依存ライブラリのライセンス検証 (GPL 互換性)

### 自動修正コマンド:
- フォーマット自動整形: `npm run format`
- JS 自動修正: `npm run lint:js:fix`
- PHPCS 自動修正: `npm run lint:php:fix`

---

## 3. バージョン整合性チェック (`test:version`)

プラグインのリリースやバージョン更新時には、バージョン番号の不一致を防ぐため `npm run test:version` を実行します。

### 検証対象ファイル (4箇所完全一致必須):
1. **`package.json`**: `"version": "x.y.z"`
2. **`package-lock.json`**: `"version": "x.y.z"` および `"packages[""].version": "x.y.z"`
3. **`beastfeedbacks.php`**:
   - プラグインヘッダー: `* Version: x.y.z`
   - 定数定義: `define( 'BEASTFEEDBACKS_VERSION', 'x.y.z' );`
4. **`readme.txt`**: `Stable tag: x.y.z`

SemVer (`x.y.z` 形式) を厳守し、1箇所でも不一致があるとテストが失敗します。

---

## 4. テスト実行環境と実行規約

### (1) JavaScript 単体テスト (`test:unit:js`)
- React コンポーネントおよびユーティリティの挙動を検証します。
- `tests/unit-js/` 配下のテストファイルを実行。
  ```bash
  npm run test:unit:js
  ```

### (2) PHPUnit 単体/統合テスト (`wp-env:test`)
- ローカル環境 `wp-env` のコンテナ内で実行します（事前に `npm run wp-env:start` が必要）。
  ```bash
  npm run wp-env:test
  ```
- カバレッジレポート生成 (tests/coverage に出力):
  ```bash
  npm run wp-env:test:coverage
  ```
- テストファイル配置先: `tests/phpunit/`

### (3) Playwright E2E テスト (`test:e2e`)
- ブロックの挿入、設定変更、フロントエンドでのフィードバック送信、管理画面での集計表示をエンドツーエンドで検証します。
  ```bash
  npm run test:e2e
  ```
- デバッグモード:
  ```bash
  npm run test:e2e:debug
  ```
- テストファイル配置先: `tests/e2e/`

### (4) フルテストパイプライン
CI と同等のフルテストを実行する場合:
```bash
npm test
```
*(Version check -> JS Unit -> Build -> wp-env start -> PHPUnit -> Playwright E2E -> wp-env stop を順次実行)*

---

## 5. ビルド成果物のコミット整合性 (`build/`)

- `src/` 配下の Gutenberg ブロックコードやスタイルを変更した際は、必ず `npm run build` を実行してください。
- `build/` ディレクトリの成果物はプラグイン実行時に直接読み込まれるため、リポジトリにコミットする必要があります。
- CI では `npm run build` 後に `git diff --exit-code` を行い、未ビルドの差分がないか厳格に検査されます。
