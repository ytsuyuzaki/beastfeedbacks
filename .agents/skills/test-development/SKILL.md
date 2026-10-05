---
name: test-development
description: >-
  Guides the creation and extension of PHPUnit tests, React block unit tests, and Playwright E2E tests.
  Use when adding new tests, writing test cases for bug fixes, or improving test coverage.
---

# Test Development Guide (PHPUnit / JS / E2E)

このスキルは、BeastFeedbacks において新しいテストケース（PHPUnit 統合テスト、React 単体テスト、Playwright E2E テスト）を作成・拡張するための実践ガイドです。

---

## 1. PHPUnit テストの書き方 (`tests/phpunit/`)

WordPress コアのテストスイート (`WP_UnitTestCase`) および Yoast WP Test Utils を利用します。

### (1) 基本構成
```php
<?php
/**
 * Test case for BeastFeedbacks features.
 *
 * @package BeastFeedbacks
 */

class Test_BeastFeedbacks_Feature extends WP_UnitTestCase {

    public function set_up(): void {
        parent::set_up();
        // テスト用初期化処理
    }

    public function tear_down(): void {
        // テスト後クリーンアップ処理
        parent::tear_down();
    }

    public function test_sample_feature(): void {
        // 1. テストデータの準備 (ファクトリ利用)
        $post_id = $this->factory()->post->create(
            array(
                'post_title'  => 'Test Post',
                'post_status' => 'publish',
            )
        );

        // 2. テスト対象メソッドの実行
        $count = BeastFeedbacks_Utils::get_like_count( $post_id );

        // 3. アサーション
        $this->assertSame( 0, $count );
    }
}
```

### (2) Ajax アクション & Nonce テストのベストプラクティス
- `WPAjaxDieContinueException` または `wp_die` ハンドラをキャッチする。
- `$_POST` スーパーグローバルに値をセットし、`wp_create_nonce` で Nonce を生成して検証。
- 管理者権限のテスト時は `wp_set_current_user()` で管理者ユーザーを設定する。

---

## 2. JavaScript / React 単体テストの書き方 (`tests/unit-js/` or `src/**/__tests__/`)

Jest および `@testing-library/react` を利用して、ブロックのレンダリングやユーザー操作をテストします。

### (1) 基本構成
```javascript
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { Edit } from '../index';

describe( 'Like Block Edit Component', () => {
    it( 'renders the block correctly', () => {
        render( <Edit /> );
        expect( screen.getByRole( 'button' ) ).toBeInTheDocument();
    } );

    it( 'handles click events', async () => {
        const user = userEvent.setup();
        render( <Edit /> );
        const button = screen.getByRole( 'button' );
        await user.click( button );
        // アサーション
    } );
} );
```

---

## 3. Playwright E2E テストの書き方 (`tests/e2e/`)

`@wordpress/e2e-test-utils-playwright` を利用して、実際の WordPress ブロックエディタおよび公開ページをブラウザ自動操作でテストします。

### (1) 基本構成
```javascript
const { test, expect } = require( '@wordpress/e2e-test-utils-playwright' );

test.describe( 'Like Block E2E', () => {
    test.beforeEach( async ( { admin } ) => {
        // 新規投稿作成画面を開く
        await admin.createNewPost();
    } );

    test( 'inserts Like block and saves post', async ( { editor, page } ) => {
        // 1. ブロック挿入
        await editor.insertBlock( { name: 'beastfeedbacks/like' } );

        // 2. ブロックが存在することを確認
        const block = page.locator( '[data-type="beastfeedbacks/like"]' );
        await expect( block ).toBeVisible();

        // 3. 下書き保存または公開
        await editor.publishPost();
    } );
} );
```

### (2) テスト実行 & デバッグ
```bash
# ヘッドレス実行
npm run test:e2e

# UI デバッグモード (セレクタや動作を目視確認)
npm run test:e2e:debug
```
