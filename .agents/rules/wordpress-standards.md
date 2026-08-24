# WordPress Coding Standards & Security Rules

BeastFeedbacks プラグインにおける PHP / JavaScript のコーディング規約およびセキュリティ実装ルールです。

---

## 1. PHP コーディング規約 (WordPress-Core / Docs / Extra)

- **命名規則**:
  - クラス名: `class-beastfeedbacks-*.php` 形式のファイル名、クラス名は `BeastFeedbacks_*`。
  - 関数/メソッド名: スネークケース (`snake_case`)。
  - 変数名: スネークケース (`snake_case`)。
  - 定数名: アッパースネークケース (`BEASTFEEDBACKS_*`)。
- **ファイルヘッダー & DocBlocks**:
  - 全ての PHP ファイルの先頭に `@package BeastFeedbacks` のファイル DocBlock を記述。
  - 直接アクセス防止ガードを記述:
    ```php
    if ( ! defined( 'ABSPATH' ) ) {
        exit;
    }
    ```
- **配列構文**:
  - 原則として `array( ... )` 構文または PHPCS 規則に沿った形式を使用。
- **フォーマットチェック**:
  - `vendor/bin/phpcs` をクリアすること。修正は `vendor/bin/phpcbf` を活用。

---

## 2. セキュリティ必須要件

### (1) Nonce 検証
- 管理画面処理・Ajax・REST API・フォーム送信時の CSRF 対策として、nonce を必須検証する。
  ```php
  check_ajax_referer( 'beastfeedbacks_action_nonce', 'nonce' );
  // または
  if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'beastfeedbacks_action' ) ) {
      wp_die( esc_html__( 'Invalid nonce verification.', 'beastfeedbacks' ) );
  }
  ```

### (2) サニタイズ (入力時)
- `$_POST` や `$_GET` から取得するデータは、用途に応じた関数で必ずサニタイズする。
  - 文字列: `sanitize_text_field( wp_unslash( $_POST['key'] ) )`
  - 複数行テキスト: `sanitize_textarea_field( wp_unslash( $_POST['key'] ) )`
  - 数値/ID: `absint( $_POST['id'] )` または `intval( ... )`
  - メールアドレス: `sanitize_email( ... )`
  - URL: `esc_url_raw( ... )`

### (3) エスケープ (出力時)
- HTML 内に出力する値は文脈に応じて必ずエスケープする。
  - HTML 要素内容: `esc_html( $var )`, `esc_html__( 'Text', 'beastfeedbacks' )`
  - 属性値: `esc_attr( $var )`, `esc_attr__( 'Text', 'beastfeedbacks' )`
  - URL: `esc_url( $var )`
  - 許可タグを含むリッチテキスト: `wp_kses_post( $var )`
  - JSON / JS 内: `wp_json_encode( $var )`

### (4) 権限チェック (Capability Check)
- 管理者機能や機密データ (CSVエクスポート、設定変更等) にアクセスする際は、常に権限をチェックする。
  ```php
  if ( ! current_user_can( 'manage_options' ) ) {
      wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'beastfeedbacks' ) );
  }
  ```

---

## 3. 国際化 (i18n)

- テキストドメインは必ず `'beastfeedbacks'` を指定する。
- 変数を直接翻訳関数に渡さないこと。フォーマットが必要な場合は `sprintf` を使用する。
  ```php
  // Good
  printf( esc_html__( 'Total feedbacks: %d', 'beastfeedbacks' ), absint( $count ) );

  // Bad
  echo __( "Total feedbacks: $count", 'beastfeedbacks' );
  ```
- JS側でも同様に `@wordpress/i18n` の `__`, `sprintf` 等を使用し、テキストドメインを渡す。
  ```javascript
  import { __, sprintf } from '@wordpress/i18n';
  const label = __( 'Submit Feedback', 'beastfeedbacks' );
  ```
- 文言を追加・変更した場合は `npm run make-pot` で `languages/beastfeedbacks.pot` を更新する。

---

## 4. Gutenberg ブロック実装規約

- `@wordpress/scripts` の標準ディレクトリ構造 (`src/<block-name>/`) に従う。
- 各ブロックは `block.json` を定義し、`index.js`, `edit.js`, `save.js`, `style.scss`, `editor.scss` などを適切に分離。
- フロントエンド用アセット (`view.js` 等) の読み込みは `block.json` または `class-beastfeedbacks-block.php` で制御。
- `npm run build` を実行して `build/` 成果物を最新に保つこと。
