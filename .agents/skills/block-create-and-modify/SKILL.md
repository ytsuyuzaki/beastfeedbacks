---
name: block-create-and-modify
description: >-
  Creates or modifies Gutenberg blocks, configures block.json, handles block deprecations, and registers server-side rendering.
  Use when adding a new block, editing block attributes or markup, or updating existing block lifecycle logic.
---

# Block Creation and Modification Workflow

このスキルは、BeastFeedbacks プラグインにおいて新規 Gutenberg ブロックを追加、または既存ブロックを変更・非推奨化対応する際の手順書です。

---

## 1. 新規ブロックの作成フロー

### ステップ 1: ディレクトリと基本ファイルの作成
`src/<block-name>/` ディレクトリを作成し、以下のファイルを配置します。

1. **`block.json`**:
   ```json
   {
       "$schema": "https://schemas.wp.org/trunk/block.json",
       "apiVersion": 3,
       "name": "beastfeedbacks/<block-name>",
       "version": "0.1.0",
       "title": "Block Title",
       "category": "beastfeedbacks",
       "icon": "feedback",
       "description": "Block description",
       "supports": {
           "html": false
       },
       "textdomain": "beastfeedbacks",
       "editorScript": "file:./index.js",
       "editorStyle": "file:./index.css",
       "style": "file:./style-index.css",
       "viewScript": "file:./view.js"
   }
   ```

2. **`index.js`**:
   ```javascript
   import { registerBlockType } from '@wordpress/blocks';
   import { useBlockProps } from '@wordpress/block-editor';
   import metadata from './block.json';
   import './style.scss';

   export function Edit() {
       const blockProps = useBlockProps();
       return <div { ...blockProps }>Edit View</div>;
   }

   registerBlockType( metadata.name, {
       edit: Edit,
       save: () => {
           const blockProps = useBlockProps.save();
           return <div { ...blockProps }>Frontend View</div>;
       },
   } );
   ```

3. **`style.scss`**: 共通スタイルの定義。

### ステップ 2: サーバー側レンダリング (`init.php`) の登録 (動的ブロックの場合)
動的に Nonce や集計カウントを出力する場合は `init.php` を作成し、サーバー側コールバックを登録します。

```php
<?php
/**
 * Block server-side rendering
 *
 * @package BeastFeedbacks
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'beastfeedbacks_block_<name>_render_callback' ) ) {
    function beastfeedbacks_block_<name>_render_callback( $attributes, $content ) {
        $wrapper_attrs = get_block_wrapper_attributes();
        // サニタイズ・エスケープして HTML を構築
        return '<div ' . $wrapper_attrs . '>' . esc_html__( 'Content', 'beastfeedbacks' ) . '</div>';
    }
}

if ( ! function_exists( 'beastfeedbacks_block_<name>_init' ) ) {
    function beastfeedbacks_block_<name>_init() {
        $type = register_block_type(
            __DIR__,
            array(
                'render_callback' => 'beastfeedbacks_block_<name>_render_callback',
            )
        );

        if ( $type && ! empty( $type->editor_script ) ) {
            wp_set_script_translations(
                $type->editor_script,
                BEASTFEEDBACKS_DOMAIN,
                BEASTFEEDBACKS_DIR . 'languages'
            );
        }
    }
}
beastfeedbacks_block_<name>_init();
```

`includes/class-beastfeedbacks-block.php` から該当の `init.php` が読み込まれるように登録します。

---

## 2. 既存ブロックの変更と非推奨化 (`deprecated`) の追加手順

静的ブロックのマークアップや属性を変更する場合、既存の投稿で「壊れたブロック」エラーが発生しないよう、以下の手順で `deprecated` を追加します。

### 手順:
1. 現在の `save` 関数と属性定義をコピーし、非推奨オブジェクトを作成する。
2. `deprecated` 配列の**先頭**に追加する。
3. 属性名や型が変更された場合は `migrate` 関数を記述する。
4. 新しい `save` 関数およびエディタ描画コンポーネントを更新する。

```javascript
registerBlockType( metadata.name, {
    attributes: newAttributes,
    edit: Edit,
    save: NewSave,
    deprecated: [
        {
            attributes: oldAttributes,
            save: OldSave,
            migrate( attributes ) {
                return {
                    ...attributes,
                    newAttribute: attributes.oldAttribute,
                };
            },
        },
    ],
} );
```

---

## 3. アセットのビルドとテスト

ブロックを追加または変更した後は、必ずビルドと単体テストを実行します。

```bash
# ビルド実行
npm run build

# JS 単体テスト実行
npm run test:unit:js

# フォーマット & Lint 検査
npm run lint
```
