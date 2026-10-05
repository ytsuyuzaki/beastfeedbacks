# WordPress Coding Standards & Security Rules

BeastFeedbacks プラグインにおける PHP / JavaScript のコーディング規約およびセキュリティ実装ルールです。
WordPress 6.8 以上、PHP 8.1 以上のモダンな環境を対象としています。

---

## 1. PHP コーディング規約 (WordPress-Core / Docs / Extra)

- **命名規則**:
  - クラスファイル: `class-beastfeedbacks-*.php` 形式。
  - クラス名: アンダースコア区切りのパスカルケース (`BeastFeedbacks_*`)。
  - 関数/メソッド名: スネークケース (`snake_case`)。
  - 変数名: スネークケース (`snake_case`)。
  - 定数名: アッパースネークケース (`BEASTFEEDBACKS_*`)。
- **ファイルヘッダー & DocBlocks**:
  - 全ての PHP ファイルの先頭に `@package BeastFeedbacks` のファイル DocBlock を記述。
  - 直接アクセス防止ガードを必須記述:
    ```php
    if ( ! defined( 'ABSPATH' ) ) {
        exit;
    }
    ```
- **PHP 8.1+ の適用指針**:
  - `declare(strict_types=1);` や純粋な型宣言は WordPress コアのフックシステムとの互換性に配慮して使用する。
  - クラス定数のアクセス修飾子 (`public const`)、列挙型ライクな定数定義を活用する。
- **配列構文 & コードフォーマット**:
  - 原則として `array( ... )` 構文またはプロジェクトの PHPCS 規則に沿った形式を使用。
  - インデントはタブ文字を使用。
- **フォーマット・構文検査**:
  - `vendor/bin/phpcs` を必ずクリアすること。自動修正は `vendor/bin/phpcbf` を活用。

---

## 2. セキュリティ必須原則 (三原則 + Nonce + 認可)

### (1) Nonce 検証 (CSRF 対策)

- 管理画面処理・Ajax・REST API・フォーム送信時の CSRF 対策として、nonce を必須検証する。
- リクエストデータ（`$_POST` や `$_GET`）の値を読み取ったり処理したりする前に、最初に Nonce を検証すること。
  ```php
  check_ajax_referer( 'register_beastfeedbacks_form' );
  // または
  if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'beastfeedbacks_action' ) ) {
      wp_die( esc_html__( 'Invalid nonce verification.', 'beastfeedbacks' ) );
  }
  ```

### (2) サニタイズ (入力受取時)

- `$_POST` や `$_GET`、`$_SERVER` から取得する入力値は、型や用途に応じた関数で必ずサニタイズする。
  - 1行文字列: `sanitize_text_field( wp_unslash( $_POST['key'] ) )`
  - 複数行テキスト: `sanitize_textarea_field( wp_unslash( $_POST['content'] ) )`
  - 数値 / ID: `absint( $_POST['id'] )` または `(int) $_POST['id']`
  - メールアドレス: `sanitize_email( wp_unslash( $_POST['email'] ) )`
  - URL: `esc_url_raw( wp_unslash( $_POST['url'] ) )`
  - クエリキー / スラッグ: `sanitize_key( wp_unslash( $_POST['key'] ) )`
- **注意**: `wp_unslash()` を必ず適用してからサニタイズ関数に渡すこと。

### (3) バリデーション (処理実行前)

- 入力値がサニタイズされた後、ビジネスロジックで期待する値の範囲や型に合致しているかを必ず検証する。
  - 固定の選択肢: `in_array( $type, BeastFeedbacks_Block::TYPES, true )`
  - 投稿の存在確認: `get_post( $post_id )` かつ期待する投稿ステータス・タイプであるか。
  - 不正な値や欠損値の場合は早期リターンまたはエラーレスポンス (`wp_send_json_error`) を返す。

### (4) レイトエスケープ (Late Escaping on Output)

- HTML や属性、JS 内に出力する値は、出力する直前に文脈に応じた適切な関数で必ずエスケープする。
  - HTML 要素内容: `esc_html( $text )`, `esc_html__( 'Text', 'beastfeedbacks' )`
  - HTML 属性値: `esc_attr( $attr )`, `esc_attr__( 'Text', 'beastfeedbacks' )`
  - URL: `esc_url( $url )`
  - 許可タグを含むリッチテキスト: `wp_kses_post( $content )` または `wp_kses( $content, $allowed_tags )`
  - JavaScript / JSON: `wp_json_encode( $data )`
- 内部で生成した安全と思われる文字列であっても、原則として出力時にエスケープ関数を通すこと。

### (5) 権限チェック (Capability Check)

- 管理者機能、設定更新、集計閲覧、CSV エクスポート等の特権処理では、必ず capability を検証する。
  ```php
  if ( ! current_user_can( 'manage_options' ) ) {
      wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'beastfeedbacks' ) );
  }
  ```

---

## 3. Ajax & JSON レスポンス規約

- Ajax コールバック (`wp_ajax_*`, `wp_ajax_nopriv_*`) では、原則として `wp_send_json_success()` および `wp_send_json_error()` を使用する。
- 適切な HTTP ステータスコード（例: 400 Bad Request, 403 Forbidden, 429 Too Many Requests）を指定する。
  ```php
  // 正常時
  wp_send_json_success( array( 'count' => $new_count ) );

  // レートリミット超過時
  wp_send_json_error( array( 'message' => __( 'Too many requests.', 'beastfeedbacks' ) ), 429 );
  ```

---

## 4. 国際化 (i18n)

- テキストドメインは一貫して `'beastfeedbacks'` を指定する。
- 変数を直接翻訳関数に渡さないこと。動的な値を含める場合は必ず `sprintf` や `printf` を使用する。
  ```php
  // Good
  printf(
      /* translators: %d: feedback count */
      esc_html__( 'Total feedbacks: %d', 'beastfeedbacks' ),
      absint( $count )
  );

  // Bad (動的変数の直渡しやエスケープ漏れ)
  echo __( "Total feedbacks: $count", 'beastfeedbacks' );
  ```
- JS / React 側でも同様に `@wordpress/i18n` の `__`, `_x`, `_n`, `sprintf` を使用し、テキストドメインを渡す。
  ```javascript
  import { __, sprintf } from '@wordpress/i18n';

  const label = __( 'Submit Feedback', 'beastfeedbacks' );
  ```
- 新しい翻訳文字列を追加した際は、必ず `npm run make-pot` を実行して `languages/beastfeedbacks.pot` を更新すること。
