# Security, Privacy & Lifecycle Rules

BeastFeedbacks におけるデータ保護、WordPress Plugin Directory レビュー基準、セキュリティ要件、プライバシー (GDPR) 対応、およびアンインストール時のライフサイクル管理ルールです。

---

## 1. WordPress Plugin Directory 必須レビュー基準

WordPress.org 公式ディレクトリでの配布・公開を維持するため、以下の基準を厳守します。

- **実行可能コードの外部取得禁止**: 外部サーバーからスクリプト、PHP ファイル、スタイルシートを動的に取得・実行してはならない。
- **無断トラッキング・テレメトリの禁止**: ユーザーの明示的な事前同意 (Opt-in) なしに利用動向や統計データを外部に送信してはならない。
- **GPLv2+ ライセンス互換性**: 読み込むライブラリやアセットはすべて GPL 互換ライセンスであること。

---

## 2. データベース操作 & SQL インジェクション防止

WordPress のデータベース操作では、直接クエリを極力避け、可能な限り WP 組み込み API (`WP_Query`, `get_post`, `update_post_meta`) を使用します。

### `$wpdb` クエリ使用時の鉄則:
1. **必ず `$wpdb->prepare()` を通す**: 変数を含むクエリでプレースホルダーを通さない生クエリの実行は禁止。
   ```php
   // Good
   $results = $wpdb->get_results(
       $wpdb->prepare(
           "SELECT meta_value, COUNT(*) as count FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = %s GROUP BY meta_value",
           $post_id,
           $meta_key
       )
   );

   // Bad (SQL Injection 脆弱性)
   $results = $wpdb->get_results( "SELECT * FROM {$wpdb->postmeta} WHERE post_id = $post_id" );
   ```
2. **テーブル名・カラム名の扱い**:
   - プレースホルダー (`%s`, `%d`) は識別子（テーブル名やカラム名）には使用できない。
   - テーブル名は `$wpdb->prefix` や `$wpdb->posts` などの安全なプロパティを使用し、動的な識別子が必要な場合はホワイトリストで厳密に検証する。

---

## 3. 公開エンドポイントの保護 & スパム・DoS 対策

公開側フォームからのフィードバック送信 (`wp_ajax_nopriv_*` / REST API) は、不特定多数の訪問者からアクセスされるため、強固な防御策を講じます。

- **Nonce 検証の先行実行**: 入力パラメータの解析やデータベースアクセスを行う前に、必ず `check_ajax_referer` を実行する。
- **レートリミット (Rate Limiting)**:
  - 送信元 IP アドレスに基づき、一定時間内の連続送信を制限する (`is_rate_limited`)。
  - レート制限を超過した場合は即座に HTTP 429 (Too Many Requests) を返す。
- **IP アドレス取得時の注意**:
  - `REMOTE_ADDR` を基本とし、プロキシヘッダー (`HTTP_X_FORWARDED_FOR` 等) を参照する場合は偽装の可能性を考慮して適切な検証・サニタイズを行う。
- **投稿ステータス・存在確認**:
  - 送信対象となる `post_id` が実在し、公開状態 (`publish`) であることを確認した上でフィードバックを保存する。

---

## 4. プライバシー & 個人情報 (PII) 保護 (GDPR 対応)

フィードバック送信時に取得する情報（IP アドレス、User-Agent 等）は個人を特定しうる情報 (PII) に該当します。

- **プライバシー配慮の保存**:
  - 必要以上の個人情報を収集しない。
  - IP アドレスの保存時は、設定に応じたマスキング（末尾オクテットのゼロ化等）を考慮する。
- **WordPress Privacy API 連携**:
  - プライバシーポリシーの推奨文面登録: `wp_add_privacy_policy_content()` を使用して、本プラグインが収集するデータ（フィードバック回答、IP、日時）と保持期間を明記する。
  - 個人データのエクスポート・消去: 将来的に WordPress 標準の Personal Data Exporter / Eraser フックに対応できるよう、ユーザー識別子（メール等）とデータの紐付け構造を明確にしておく。

---

## 5. アンインストール時のライフサイクル (`uninstall.php`)

プラグイン停止時 (`deactivate`) とアンインストール時 (`uninstall`) の責務を厳密に分離します。

### (1) プラグイン停止時 (`class-beastfeedbacks-deactivator.php`)
- 一時的なフックの解除、登録された Cron イベントのクリアのみを行う。
- **ユーザーが作成したフィードバックデータや設定は絶対に削除してはならない**（一時的な無効化である可能性があるため）。

### (2) プラグイン削除時 (`uninstall.php`)
- 管理者が管理画面から明示的に「削除」を実行した際に動作する。
- **必須ガード**:
  ```php
  if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
      exit;
  }
  ```
- **完全消去の対象**:
  - カスタム投稿タイプ `beastfeedbacks` の全投稿および関連 post meta。
  - プラグインが保存したオプション値 (`get_option` / `delete_option`)。
  - プラグインが設定したトランジェントキャッシュ (`delete_transient`)。
- 大量データ削除時はメモリ枯渇を防ぐため、チャンク処理または `$wpdb` による安全な一括削除を行う。
