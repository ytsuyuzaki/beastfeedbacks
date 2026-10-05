# Gutenberg Block Development Rules

BeastFeedbacks における Gutenberg (Block Editor) ブロックの開発規約と設計原則です。
WordPress 6.8 以上、React 19、`@wordpress/scripts` に準拠しています。

---

## 1. ディレクトリ構造とアセット構成

各ブロックは `src/<block-name>/` 配下に自己完結して配置します。

```text
src/<block-name>/
├── block.json       # ブロックメタデータ定義 (apiVersion: 3)
├── index.js         # ブロック登録 (registerBlockType) とエディタ/保存コンポーネント定義
├── edit.js          # エディタ側描画コンポーネント (Edit)
├── save.js          # 静的保存コンポーネント (動的ブロックの場合は null または InnerBlocks.Content)
├── init.php         # サーバー側ブロック登録 (register_block_type) & render_callback
├── view.js          # フロントエンド用スクリプト (任意)
├── style.scss       # フロントエンド & エディタ共通スタイル
├── editor.scss      # エディタ専用スタイル (任意)
└── __tests__/       # 単体テスト (Jest + React Testing Library)
```

---

## 2. `block.json` 規約

- **`apiVersion`**: 最新の `3` を指定する。
- **スキーマ指定**: `"$schema": "https://schemas.wp.org/trunk/block.json"` を含める。
- **アセット宣言**:
  - `editorScript`: `"file:./index.js"`
  - `editorStyle`: `"file:./index.css"`
  - `style`: `"file:./style-index.css"`
  - `viewScript`: `"file:./view.js"` (フロントエンドで対話処理が必要な場合)
- **テキストドメイン**: `"textdomain": "beastfeedbacks"` を明記する。
- **属性 (`attributes`) 定義**:
  - 型 (`type`)、デフォルト値 (`default`)、サニタイズ/ソースの指定を明確に行う。

---

## 3. 動的ブロック (`render_callback`) と静的ブロック (`save`)

BeastFeedbacks では、動的な集計カウント表示や Nonce の出力、CSRF トークンの埋め込みを行うため、原則として **動的ブロック (Server-side rendering)** パターンを採用しています。

- **`save` 関数の役割**:
  - 動的ブロックで子要素 (`InnerBlocks`) を持つ場合、`save` は `<InnerBlocks.Content />` のみを返します。
  - 子要素を持たない完全動的ブロックの場合、`save` は `() => null` を返します。
- **サーバー側描画 (`init.php`)**:
  - `render_callback` 内で HTML を組み立てる際は、文脈に応じたエスケープ (`esc_html`, `esc_attr`, `esc_url`) を徹底する。
  - ブロック登録時に `wp_set_script_translations()` を呼び出し、エディタ用 JS の翻訳スクリプトをロードする。

---

## 4. ブロック非推奨化とマイグレーション (`deprecated`) 【重要】

静的ブロックのマークアップや属性 (`attributes`) の構造を変更する際は、既存の投稿で「このブロックには、想定されていないか無効なコンテンツが含まれています (Block validation failed)」というエラーが発生するのを防ぐため、**必ず `deprecated` 配列を定義** します。

### ルール:
1. **降順で追加**: 新しい非推奨定義を `deprecated` 配列の先頭に追加する（WordPress は配列の先頭から順にマッチングを試みるため）。
2. **完全なスナップショット**: 過去バージョンの `attributes` と `save` 関数をそのまま残す。
3. **`migrate` の実装**: 属性の名称変更や型変更がある場合、旧属性を受け取って新形式に変換する `migrate(attributes)` 関数を実装する。
4. **過去の非推奨定義を削除しない**: リファクタリング時でも過去の `deprecated` エントリを削除してはならない。

```javascript
registerBlockType( metadata.name, {
    attributes: currentAttributes,
    edit: Edit,
    save: Save,
    deprecated: [
        {
            attributes: oldAttributesV2,
            save: OldSaveV2,
            migrate( attributes ) {
                return {
                    ...attributes,
                    newAttributeName: attributes.oldAttributeName,
                };
            },
        },
        {
            attributes: oldAttributesV1,
            save: OldSaveV1,
        },
    ],
} );
```

---

## 5. `InnerBlocks` と親子関係の設計

- **親子ブロック関係の制限**:
  - 親ブロック (例: `survey-form`) に含めることができる子ブロックを `allowedBlocks` で制限する。
  - 子ブロック側 (例: `survey-input`) では `block.json` の `parent` プロパティに親ブロック名を指定し、単体での不正挿入を防止する。
- **テンプレートとロック**:
  - 初期配置を固定したい場合、`template` 配列と `templateLock="all"` を適切に設定する。

---

## 6. アクセシビリティ (a11y) & フロントエンド規約

- **セマンティック HTML**: フォーム要素には必ず `<label>` を関連付け、`for` (React では `htmlFor`) と `id` を一致させる。
- **キーボード操作**: ボタンや入力フィールドは Tab キーでフォーカス可能とし、`Enter` / `Space` で操作可能にする。
- **ARIA 属性**: 動的な状態変更（投票完了、エラー通知、開閉）には `aria-live`, `aria-expanded`, `aria-disabled` などを適切に付与する。
- **フロントエンド JS (`view.js`)**:
  - 他のプラグインやテーマのスクリプトと衝突しないよう、即時関数 (IIFE) またはモジュール形式でスコープを保護する。
  - DOM 操作にはセレクタの衝突を防ぐため、プラグイン固有のプレフィックスクラス (`beastfeedbacks-*`) を使用する。
