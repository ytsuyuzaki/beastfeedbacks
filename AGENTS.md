# BeastFeedbacks Development Guide (AGENTS.md)

このドキュメントは、WordPressプラグイン **BeastFeedbacks** の開発・保守を行うAIエージェントおよび開発者のための共通ガイドラインです。

---

## 1. プロジェクト概要

- **名称**: BeastFeedbacks
- **種別**: WordPress プラグイン (Gutenberg / Block Editor 対応)
- **目的**: ブロックエディター上で「いいね (Like)」「単一選択投票 (Choice voting)」「アンケートフォーム (Survey Form / Input / Choice)」を設置し、訪問者からのフィードバックを収集・集計・CSVエクスポートする。
- **データ構造**:
    - 送信データはカスタム投稿タイプ `beastfeedbacks` として保存。
    - 送信元投稿ID、回答内容、IPアドレス、User-Agent、送信日時等をメタデータとして保持。

---

## 2. 技術スタック & 動作要件

| 区分 | 要件 / 採用技術 |
| :--- | :--- |
| **WordPress** | 6.8 以上 |
| **PHP** | 8.1 以上 |
| **Node.js** | 24 (CI基準) / npm |
| **フロントエンド** | React 19, `@wordpress/scripts`, `@wordpress/components`, `@wordpress/element`, `@wordpress/i18n` |
| **ローカル環境** | Docker, `@wordpress/env` (`wp-env`) |
| **PHP テスト** | PHPUnit 9.6, Yoast WP Test Utils, Yoast PHPUnit Polyfills |
| **E2E テスト** | Playwright (`@playwright/test`, `@wordpress/e2e-test-utils-playwright`) |
| **静的解析・構文チェック** | PHP_CodeSniffer (WordPress-Core, WordPress-Docs, WordPress-Extra), ESLint, Stylelint, Markdownlint |

---

## 3. ディレクトリ構成

```text
beastfeedbacks/
├── beastfeedbacks.php       # プラグインのエントリーポイント・ヘッダー
├── uninstall.php            # アンインストール時のクリーンアップ処理
├── includes/                # バックエンドPHPクラス群
│   ├── class-beastfeedbacks.php            # コアオーケストレーション
│   ├── class-beastfeedbacks-activator.php  # 有効化処理
│   ├── class-beastfeedbacks-deactivator.php# 無効化処理
│   ├── class-beastfeedbacks-admin.php      # 管理画面・一覧・集計・CSVエクスポート
│   ├── class-beastfeedbacks-public.php     # REST API / 公開側Ajax・フィードバック保存
│   └── class-beastfeedbacks-block.php      # ブロック登録・アセット連携
├── src/                     # Gutenbergブロックのソースコード (JS/CSS)
│   ├── like/                # Like button ブロック
│   ├── vote/                # Choice voting ブロック
│   ├── survey-form/         # Survey Form 親ブロック
│   ├── survey-input/        # Survey Input 子ブロック (テキスト/テキストエリア)
│   └── survey-choice/       # Survey Choice 子ブロック (ラジオ/チェックボックス/セレクト)
├── public/                  # 管理画面向けアセット (CSS/JS)
├── build/                   # npm run build で自動生成される配布用成果物 (要コミット)
├── languages/               # 翻訳ファイル (beastfeedbacks.pot)
├── tests/                   # テスト群
│   ├── check-version.js     # バージョン整合性チェック
│   ├── phpunit/             # PHPUnit 単体/統合テスト
│   └── e2e/                 # Playwright E2Eテスト
└── .agents/                 # AIエージェント用ルール・スキル定義
```

---

## 4. 開発コマンド早見表

### 依存関係インストール

```bash
npm ci
composer install
```

### ビルド & 開発

```bash
# 開発モード (ホットリロード・差分ビルド)
npm start

# 本番ビルド (PHPファイルのコピーを含む)
npm run build

# 配布用ZIP生成
npm run plugin-zip
```

### ローカル環境 (`wp-env`)

```bash
# 環境起動 (高速・Xdebug無効)
npm run wp-env:start

# 環境起動 (Xdebug coverage有効)
npm run wp-env:start:coverage

# 環境停止
npm run wp-env:stop
```

### 静的解析・コード品質チェック (Lint)

```bash
# 全チェック一括実行 (Format, Package JSON, JS, CSS, Docs, PHP構文, PHPCS, Engines, Licenses)
npm run lint

# 各種個別実行 & 自動修正
npm run format             # フォーマット修正
npm run lint:js:fix        # JS Lint 修正
npm run lint:php:cs        # PHP_CodeSniffer チェック
npm run lint:php:fix       # PHP_CodeSniffer 自動修正 (phpcbf)
npm run make-pot           # 翻訳ファイル (POT) 生成・更新
```

### テスト実行

```bash
# 全テスト一括実行 (Version check -> React Unit Test -> Build -> wp-env start -> PHPUnit -> E2E -> wp-env stop)
npm test

# 個別実行
npm run test:version       # バージョン整合性チェック
npm run test:unit:js       # React / Gutenberg ブロック単体テスト (Jest + RTL)
npm run test:unit:js:watch # React 単体テスト (ウォッチモード)
npm run wp-env:test        # PHPUnit テスト実行 (wp-env起動中)
npm run test:e2e           # Playwright E2E テスト実行 (wp-env起動中)
npm run test:e2e:debug     # Playwright デバッグモード
```

---

## 5. 重要なコーディング規約 & セキュリティ原則

### 1. WordPress セキュリティ

- **Nonce 検証**: すべての POST / REST / Ajax リクエストで nonce の検証を徹底すること (`check_ajax_referer` または `wp_verify_nonce`)。
- **サニタイズ**: 入力データは受け取り時に適切にサニタイズすること (`sanitize_text_field`, `absint`, `sanitize_textarea_field` 等)。
- **エスケープ**: 出力時は文脈に応じたエスケープを徹底すること (`esc_html`, `esc_attr`, `esc_url`, `wp_kses_post` 等)。
- **権限チェック**: 管理画面処理やCSVエクスポート等では `current_user_can('manage_options')` などの capability を必ず確認すること。

### 2. 国際化 (i18n)

- テキストドメインは一貫して `'beastfeedbacks'` を使用すること。
- UI文言を追加・変更した際は、`npm run make-pot` を実行して `languages/beastfeedbacks.pot` を更新すること。
- JS側では `@wordpress/i18n` の `__`, `_x`, `_n`, `sprintf` を使用すること。

### 3. バージョン整合性ルール

バージョン番号を更新する際は、`npm run test:version` で検証される次の4箇所すべてを完全に一致させること：

1. `package.json` (`version`)
2. `package-lock.json` (`version` 及び `packages[""].version`)
3. `beastfeedbacks.php` (プラグインヘッダー `Version:` 及び `BEASTFEEDBACKS_VERSION` 定数)
4. `readme.txt` (`Stable tag:`)

### 4. ビルド成果物 (`build/`)

- `src/` のブロックコードを変更した後は、必ず `npm run build` を実行して `build/` ディレクトリのファイルを生成・更新すること。
- CIではビルド後の差分 (`git diff --exit-code`) を検証しているため、生成ファイルのコミット漏れに注意すること。

---

## 6. AIエージェントへの作業指針

1. **変更前の事前調査**: 変更対象のブロックやPHPクラスの既存実装パターンを把握してから作業を開始すること。
2. **検証の自動化**: コード編集後は必ず `npm run lint` や関連テスト (`npm run test:version`, `npm run wp-env:test` 等) を実行してリグレッションがないか確認すること。
3. **ルール & スキルの活用**: 詳細な手順や規約は `.agents/rules/` および `.agents/skills/` を参照すること。
