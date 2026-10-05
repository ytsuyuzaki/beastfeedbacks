---
name: build-and-release
description: >-
  Builds Gutenberg block assets, updates POT translation files, checks version consistency, and packages the plugin ZIP for release.
  Use when preparing a release, compiling assets, updating i18n pot files, or generating the plugin zip.
---

# Build and Release Workflow

このスキルは、Gutenberg ブロックアセットのビルド、国際化 (POT) ファイルの更新、バージョン整合性検証、配布用 ZIP アーカイブの生成、および WordPress.org SVN デプロイ手順を案内します。

---

## 1. アセットのビルド

`src/` 配下の Gutenberg ブロックコードやスタイルを変更した後に実行します。

```bash
# 本番ビルド (PHP ファイルのコピーを含む)
npm run build
```

- 生成先: `build/` ディレクトリ
- **重要**: `build/` 内の生成物は Git にコミットする必要があります。CI ではビルド後の差分 (`git diff --exit-code`) が検証されます。コミット漏れがあると CI が失敗します。

---

## 2. 翻訳ファイル (POT) の更新

PHP または JavaScript 内で翻訳文字列 (`__()`, `_e()`, `_x()`, `sprintf`) を追加・変更した後に実行します。

```bash
npm run make-pot
```

- 出力先: `languages/beastfeedbacks.pot`
- 対象外: `node_modules`, `dist`, `tests`, `e2e-tests`

---

## 3. リリース前チェックリスト & バージョン更新

新しいリリースを作成する際は、以下のステップを順に実施します。

### ステップ 1: バージョン番号の同期 (4 箇所)
バージョン番号 (SemVer `x.y.z`) を以下の 4 箇所で完全に一致させます。

1. **`package.json`**: `"version": "x.y.z"`
2. **`package-lock.json`**: `"version": "x.y.z"` および `"packages[""].version": "x.y.z"`
3. **`beastfeedbacks.php`**:
   - プラグインヘッダー: `* Version: x.y.z`
   - 定数定義: `define( 'BEASTFEEDBACKS_VERSION', 'x.y.z' );`
4. **`readme.txt`**: `Stable tag: x.y.z`

### ステップ 2: `readme.txt` の Changelog 更新
`readme.txt` の `== Changelog ==` セクションに新バージョンの変更履歴を追記します。

### ステップ 3: バージョン整合性チェック実行
```bash
npm run test:version
```

### ステップ 4: アセットの再ビルド & POT 更新
```bash
npm run make-pot
npm run build
```

### ステップ 5: 全テストの合格確認
```bash
npm run lint
npm test
```

---

## 4. 配布用 ZIP アーカイブの生成 & 検証

配布パッケージ (`beastfeedbacks.zip`) を生成します。

```bash
npm run plugin-zip
```

- 生成ファイル: `beastfeedbacks.zip`
- 内容検証: 不要なソースファイル (`src/`, `tests/`, `node_modules/`, `.git/` 等) が含まれておらず、必要なファイル (`beastfeedbacks.php`, `includes/`, `build/`, `languages/`, `readme.txt`) のみが含まれていることを確認します。

---

## 5. WordPress.org へのリリース (GitHub Actions 連携)

本リポジトリでは、GitHub Actions (`.github/workflows/deploy.yml`) により WordPress.org SVN への自動デプロイが設定されています。

1. 全変更を `main` ブランチにマージする。
2. バージョンタグを作成してリモートにプッシュする:
   ```bash
   git tag v0.1.6
   git push origin v0.1.6
   ```
3. タグのプッシュをトリガーに GitHub Actions が起動し、自動的に GitHub Release の作成と WordPress.org SVN リポジトリへのコミットが行われます。
