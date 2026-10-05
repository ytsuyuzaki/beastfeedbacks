# BeastFeedbacks Development Guide (AGENTS.md)

このドキュメントは、WordPressプラグイン **BeastFeedbacks** の開発・保守を行うAIエージェントおよび開発者のための共通ガイドラインです。

---

## 1. プロジェクト概要

- **名称**: BeastFeedbacks
- **種別**: WordPress プラグイン (Gutenberg / Block Editor 対応, WordPress.org 公式ディレクトリ公開対応)
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
| **テストスイート** | PHPUnit 9.6, Yoast WP Test Utils, Jest + RTL, Playwright (`@wordpress/e2e-test-utils-playwright`) |
| **静的解析** | PHP_CodeSniffer (WordPress-Core, WordPress-Docs, WordPress-Extra), ESLint, Stylelint, Markdownlint |
| **CI/CD** | GitHub Actions (PHP 8.1〜8.5 / WP 6.8〜7.1 マトリックス検証, WordPress.org SVN 自動デプロイ) |

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
│   ├── class-beastfeedbacks-utils.php      # 共通ユーティリティ (集計、Like数等)
│   └── class-beastfeedbacks-block.php      # ブロック登録・アセット連携
├── src/                     # Gutenbergブロックのソースコード (JS/CSS)
│   ├── like/                # Like button ブロック
│   ├── vote/                # Choice voting ブロック
│   ├── survey-form/         # Survey Form 親ブロック
│   ├── survey-input/        # Survey Input 子ブロック (テキスト/テキストエリア)
│   ├── survey-choice/       # Survey Choice 子ブロック (ラジオ/チェックボックス/セレクト)
│   ├── components/          # 共通Reactコンポーネント
│   └── utils/               # フロントエンドユーティリティ
├── public/                  # 管理画面向けアセット (CSS/JS)
├── build/                   # npm run build で自動生成される配布用成果物 (要コミット)
├── languages/               # 翻訳ファイル (beastfeedbacks.pot)
├── tests/                   # テスト群
│   ├── check-version.js     # バージョン整合性チェック
│   ├── unit-js/             # React / Gutenberg ブロック単体テスト
│   ├── phpunit/             # PHPUnit 単体/統合テスト
│   └── e2e/                 # Playwright E2Eテスト
└── .agents/                 # AIエージェント用ルール・スキル定義
    ├── rules/               # 専門特化ルール群
    └── skills/              # ワークフロー手順書群
```

---

## 4. ルール & スキル一覧 (.agents/)

開発・保守時に参照すべき専門ルールおよびワークフロースキルです。

### 専門ルール (`.agents/rules/`)
- [wordpress-standards.md](file:///.agents/rules/wordpress-standards.md): PHP 8.1+ / WP 6.8+ コーディング規約、セキュアコーディング三原則（Sanitize, Validate, Late-escape）、Nonce、Capability、i18n。
- [gutenberg-blocks.md](file:///.agents/rules/gutenberg-blocks.md): Gutenberg ブロック開発規約（`block.json` apiVersion 3、動的ブロック、**非推奨化 `deprecated` / `migrate`**、`InnerBlocks`、a11y）。
- [security-and-privacy.md](file:///.agents/rules/security-and-privacy.md): Plugin Review 必須基準、SQL インジェクション対策 (`$wpdb->prepare`)、レート制限、**プライバシー & GDPR (PII/Privacy API)**、`uninstall.php` クリーンアップ。
- [testing-and-qa.md](file:///.agents/rules/testing-and-qa.md): 多層テストピラミッド、バージョン整合性 (4箇所同期)、CI/CD マトリックス、生成物コミット整合性。

### ワークフロースキル (`.agents/skills/`)
- [wp-env-manage](file:///.agents/skills/wp-env-manage/SKILL.md): `wp-env` の起動・停止・WP-CLI 操作・テストDB初期化・トラブルシューティング。
- [test-and-lint](file:///.agents/skills/test-and-lint/SKILL.md): 静的解析 (Lint) 一括実行、JS 単体テスト、PHPUnit、Playwright E2E テストの個別実行・デバッグ手順。
- [build-and-release](file:///.agents/skills/build-and-release/SKILL.md): アセットビルド、POT 翻訳更新、バージョン同期、配布 ZIP 生成、WordPress.org SVN 自動デプロイ手順。
- [block-create-and-modify](file:///.agents/skills/block-create-and-modify/SKILL.md): 新規 Gutenberg ブロック作成、既存ブロック変更、deprecated 定義、サーバー側レンダリング登録。
- [test-development](file:///.agents/skills/test-development/SKILL.md): PHPUnit (`WP_UnitTestCase`)、React 単体テスト、Playwright E2E テストの作成・拡張ガイド。

---

## 5. 開発コマンド早見表

### 依存関係インストール

```bash
npm ci
composer install
```

### ビルド & 開発

```bash
# 開発モード (差分ビルド)
npm start

# 本番ビルド (PHPファイルのコピーを含む。成果物は要コミット)
npm run build

# 配布用ZIP生成
npm run plugin-zip
```

### ローカル環境 (`wp-env`)

```bash
# 環境起動 (開発: localhost:8888, テスト: localhost:8889)
npm run wp-env:start

# 環境起動 (Xdebug coverage有効)
npm run wp-env:start:coverage

# 環境停止
npm run wp-env:stop
```

### 静的解析・構文チェック (Lint)

```bash
# 全チェック一括実行 (Format, Package JSON, JS, CSS, Docs, PHP構文, PHPCS, Engines, Licenses)
npm run lint

# 個別自動修正
npm run format             # フォーマット修正 (Prettier)
npm run lint:js:fix        # JS Lint 修正 (ESLint)
npm run lint:php:fix       # PHPCS 自動修正 (phpcbf)
npm run make-pot           # 翻訳ファイル (POT) 生成・更新
```

### テスト実行

```bash
# 全テスト一括実行 (Version check -> JS Unit -> Build -> wp-env start -> PHPUnit -> E2E -> wp-env stop)
npm test

# 個別実行
npm run test:version       # バージョン整合性チェック (4箇所完全一致)
npm run test:unit:js       # React / Gutenberg ブロック単体テスト (Jest + RTL)
npm run wp-env:test        # PHPUnit テスト実行 (wp-env起動中)
npm run test:e2e           # Playwright E2E テスト実行 (wp-env起動中)
npm run test:e2e:debug     # Playwright デバッグモード
```

---

## 6. AIエージェントへの作業指針

1. **変更前の事前調査**: 変更対象のブロックや PHP クラスの既存実装パターンおよび `.agents/rules/` の規約を確認すること。
2. **検証の自動化**: コード編集後は必ず `npm run lint` や関連テスト (`npm run test:unit:js`, `npm run test:version` 等) を実行してリグレッションがないか確認すること。
3. **ブロック変更時の deprecation 必須**: ブロックのマークアップや属性を変更する際は、必ず [gutenberg-blocks.md](file:///.agents/rules/gutenberg-blocks.md) に従い `deprecated` 配列を定義すること。
4. **生成物のコミット**: `src/` の編集後は `npm run build` を実行し、`build/` の差分を必ずコミット対象に含めること。
