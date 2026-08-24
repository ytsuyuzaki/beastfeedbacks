---
name: build-and-release
description: >-
  Builds Gutenberg block assets, updates POT translation files, checks version consistency, and packages the plugin ZIP for release.
  Use when preparing a release, compiling assets, updating i18n pot files, or generating the plugin zip.
---

# Build and Release Workflow

このスキルは、Gutenberg ブロックアセットのビルド、国際化 (POT) ファイルの更新、バージョン整合性検証、および配布用 ZIP アーカイブの生成手順を案内します。

---

## 1. アセットのビルド

`src/` 配下の Gutenberg ブロック (Like, Vote, Survey Form, Survey Input, Survey Choice) のコードやスタイルを変更した後に実行します。

```bash
# 本番ビルド (PHP ファイルのコピーを含む)
npm run build
```

- 生成先: `build/` ディレクトリ
- 注意: `build/` 内の生成物は Git にコミットする必要があります。CI ではビルド後の差分 (`git diff --exit-code`) が検証されます。

---

## 2. 翻訳ファイル (POT) の更新

PHP または JavaScript 内で翻訳文字列 (`__()`, `_e()`, `_x()`, `sprintf`) を追加・変更した後に実行します。

```bash
npm run make-pot
```

- 出力先: `languages/beastfeedbacks.pot`
- 対象外: `node_modules`, `dist`, `tests`, `e2e-tests`

---

## 3. バージョン更新 & 整合性チェック

リリース向けにバージョン番号を上げる場合は、以下の 4 箇所すべてを同じバージョン番号 (SemVer 形式 `x.y.z`) に更新します。

1. **`package.json`**: `"version": "x.y.z"`
2. **`package-lock.json`**: `"version": "x.y.z"` および `"packages[""].version": "x.y.z"`
3. **`beastfeedbacks.php`**:
   - プラグインヘッダー: `* Version: x.y.z`
   - 定数定義: `define( 'BEASTFEEDBACKS_VERSION', 'x.y.z' );`
4. **`readme.txt`**: `Stable tag: x.y.z`

更新後、チェックを実行します:
```bash
npm run test:version
```

---

## 4. 配布用 ZIP アーカイブの生成

リリース用パッケージ (`beastfeedbacks.zip`) を生成します。

```bash
npm run plugin-zip
```

- 生成されるファイル: `beastfeedbacks.zip`
- 実行内容: `@wordpress/scripts plugin-zip` により、配布に必要なファイル (PHP, build, languages, readme 等) のみがパッケージングされ、不要な開発用ファイル (node_modules, src, tests 等) は除外されます。
