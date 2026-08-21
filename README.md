# BeastFeedbacks

[English](README.en.md)

BeastFeedbacks は、WordPress のブロックエディターから「いいね」、選択式投票、
アンケートフォームを設置し、訪問者からフィードバックを収集できるプラグインです。
収集した回答は WordPress 管理画面で確認でき、CSV 形式でエクスポートできます。

## 主な機能

- いいねボタンと累計いいね数の表示
- 複数のボタンを使った選択式投票
- テキスト入力、テキストエリア、選択項目を組み合わせたアンケートフォーム
- 入力項目の必須設定、幅、プレースホルダーなどの編集
- フィードバック種別や送信元ページによる管理画面の絞り込み
- 回答データの CSV エクスポート
- 日本語翻訳

## 動作要件

- WordPress 6.8 以上
- PHP 8.1 以上

ソースからビルドまたは開発する場合は、次の環境も必要です。

- Node.js 24（CI で使用しているバージョン）
- npm
- Composer
- Docker（`wp-env` によるローカル WordPress 環境とテストで使用）

## インストール

### 配布 ZIP を利用する場合

1. WordPress 管理画面の「プラグイン」からプラグイン ZIP をアップロードします。
2. BeastFeedbacks を有効化します。

または、展開したプラグインを
`wp-content/plugins/beastfeedbacks` に配置して有効化してください。

### ソースから ZIP を作成する場合

```bash
git clone https://github.com/ytsuyuzaki/beastfeedbacks.git
cd beastfeedbacks
npm ci
npm run build
npm run plugin-zip
```

生成された `beastfeedbacks.zip` を WordPress にアップロードしてください。

## 使い方

1. 投稿または固定ページをブロックエディターで開きます。
2. 「BeastFeedbacks」カテゴリーから目的のブロックを追加します。
3. 必要に応じて質問、選択肢、入力形式などを編集し、ページを公開します。
4. WordPress 管理画面の「BeastFeedbacks」で回答を確認します。
5. 一覧画面の「エクスポート」から回答を CSV 形式でダウンロードできます。

### 提供ブロック

- **Like button**: いいねボタンと、そのページの累計数を表示します。
- **Choice voting**: ボタンによる単一回答の投票を作成します。
- **Survey Form**: アンケート全体を囲む親ブロックです。
- **Survey Input**: Survey Form 内でテキスト入力またはテキストエリアを追加します。
- **Survey Choice**: Survey Form 内でラジオボタン、チェックボックス、
  セレクトボックスを追加します。

## 保存されるデータ

回答は `beastfeedbacks` カスタム投稿タイプとして WordPress データベースに保存されます。
回答内容に加えて、送信元の投稿、送信日時、IP アドレス、User-Agent が記録されます。
サイトのプライバシーポリシー、適用法令、データ保持方針に従って運用してください。

## 開発

依存関係をインストールします。

```bash
npm ci
composer install
```

ブロックを開発モードでビルドするには、次を実行します。

```bash
npm start
```

本番用アセットを生成するには、次を実行します。

```bash
npm run build
```

ローカルの WordPress 環境は `wp-env` で起動できます。

```bash
npm run wp-env:start
```

終了時には環境を停止します。

```bash
npm run wp-env:stop
```

既定の `.wp-env.json` は WordPress 7.1、PHP 8.5、テスト用ポート 8889 を使用します。

## 品質チェックとテスト

コードフォーマット、JavaScript、CSS、Markdown、PHP、WordPress Coding Standards
などをまとめて検査します。

```bash
npm run lint
```

すべてのテストは、ビルド、`wp-env` の起動、PHPUnit、Playwright の E2E テストを
順に実行します。Docker が起動している必要があります。

```bash
npm test
```

個別のコマンドも利用できます。

```bash
npm run test:version
npm run wp-env:test
npm run test:e2e
```

## ディレクトリ構成

```text
beastfeedbacks.php  プラグインのエントリーポイント
includes/           管理画面、投稿受付、ブロック登録などの PHP コード
src/                各 Gutenberg ブロックのソース
public/             管理画面用の CSS と JavaScript
languages/          翻訳ファイル
tests/phpunit/       PHPUnit テスト
tests/e2e/           Playwright E2E テスト
```

`build/` は `npm run build` によって `src/` から生成され、実行時に読み込まれます。

## ライセンス

[GPL-2.0-or-later](https://www.gnu.org/licenses/gpl-2.0.html)

