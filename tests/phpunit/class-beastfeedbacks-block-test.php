<?php
/**
 * Tests for BeastFeedbacks_Block
 *
 * 実行例:
 * wp-env run tests-cli --env-cwd='wp-content/plugins/beastfeedbacks/' vendor/bin/phpunit
 */
use Yoast\WPTestUtils\BrainMonkey\TestCase;

class BeastFeedbacks_Block_Test extends TestCase {

	protected function set_up(): void {
		parent::set_up();
		// 念のため該当フックをリセット（他テストからの影響排除）
		remove_all_filters( 'block_categories_all' );
		remove_all_actions( 'init' );
	}

	protected function tear_down(): void {
		// 後片付け
		remove_all_filters( 'block_categories_all' );
		remove_all_actions( 'init' );
		parent::tear_down();
	}

	/** @test */
	public function get_instance_returns_singleton(): void {
		$a = \BeastFeedbacks_Block::get_instance();
		$b = \BeastFeedbacks_Block::get_instance();
		$this->assertInstanceOf( \BeastFeedbacks_Block::class, $a );
		$this->assertSame( $a, $b, 'get_instance() must return the same instance' );
	}

	/** @test */
	public function types_constant_has_expected_values(): void {
		$this->assertTrue( defined( '\BeastFeedbacks_Block::TYPES' ) );
		$this->assertSame(
			array( 'like', 'vote', 'survey' ),
			\BeastFeedbacks_Block::TYPES
		);
	}

	/** @test */
	public function init_registers_hooks(): void {
		$instance = \BeastFeedbacks_Block::get_instance();

		// 実行
		$instance->init();

		// フィルタが instance メソッドで優先度10で登録されているか
		$this->assertSame(
			10,
			has_filter( 'block_categories_all', array( $instance, 'block_categories_all' ) )
		);

		// アクション 'init' に init_blocks が登録されているか
		$this->assertNotFalse(
			has_action( 'init', array( $instance, 'init_blocks' ) )
		);
	}

	/** @test */
	public function block_categories_all_adds_category_when_context_has_post(): void {
		$instance = \BeastFeedbacks_Block::get_instance();

		$cats_in       = array(
			array(
				'slug'  => 'text',
				'title' => 'Text',
				'icon'  => null,
			),
		);
		$context       = new stdClass();
		$context->post = (object) array( 'ID' => 123 ); // ポストあり

		$cats_out = $instance->block_categories_all( $cats_in, $context );

		$this->assertCount( 2, $cats_out );
		$last = end( $cats_out );
		$this->assertSame( 'beastfeedbacks', $last['slug'] );
		$this->assertSame( 'BeastFeedbacks', $last['title'] );
		$this->assertArrayHasKey( 'icon', $last );
		$this->assertNull( $last['icon'] );
	}

	/** @test */
	public function block_categories_all_does_not_add_when_no_post_in_context(): void {
		$instance = \BeastFeedbacks_Block::get_instance();

		$cats_in = array(
			array(
				'slug'  => 'text',
				'title' => 'Text',
				'icon'  => null,
			),
		);
		$context = new stdClass(); // post 無し

		$cats_out = $instance->block_categories_all( $cats_in, $context );

		$this->assertSame( $cats_in, $cats_out, 'Context without post must not add category' );
	}

	/** @test */
	public function beastfeedbacks_block_like_render_callback_renders_like_block_html(): void {
		require_once BEASTFEEDBACKS_DIR . 'src/like/init.php';

		$post_id = wp_insert_post(
			array(
				'post_type'   => 'post',
				'post_status' => 'publish',
				'post_title'  => 'Test Post for Like Block',
			)
		);

		$this->go_to( get_permalink( $post_id ) );

		$attributes = array();
		$content    = '<button type="submit" class="like-button">Like</button>';

		$html = beastfeedbacks_block_like_render_callback( $attributes, $content );

		$this->assertStringContainsString( 'name="beastfeedbacks_like_form"', $html );
		$this->assertStringContainsString( 'value="register_beastfeedbacks_form"', $html );
		$this->assertStringContainsString( 'value="like"', $html );
		$this->assertStringContainsString( 'value="' . $post_id . '"', $html );
		$this->assertStringContainsString( '<p class="like-count">0</p>', $html );
		$this->assertStringContainsString( $content, $html );

		wp_delete_post( $post_id, true );
	}

	/** @test */
	public function beastfeedbacks_block_like_render_callback_displays_correct_like_count(): void {
		require_once BEASTFEEDBACKS_DIR . 'src/like/init.php';

		$post_id = wp_insert_post(
			array(
				'post_type'   => 'post',
				'post_status' => 'publish',
				'post_title'  => 'Test Post with Likes',
			)
		);

		$like_post_1 = wp_insert_post(
			array(
				'post_type'   => 'beastfeedbacks',
				'post_status' => 'publish',
				'post_parent' => $post_id,
				'post_title'  => 'Like 1',
			)
		);
		add_post_meta( $like_post_1, 'beastfeedbacks_type', 'like' );

		$like_post_2 = wp_insert_post(
			array(
				'post_type'   => 'beastfeedbacks',
				'post_status' => 'publish',
				'post_parent' => $post_id,
				'post_title'  => 'Like 2',
			)
		);
		add_post_meta( $like_post_2, 'beastfeedbacks_type', 'like' );

		$this->go_to( get_permalink( $post_id ) );

		$html = beastfeedbacks_block_like_render_callback( array(), '' );

		$this->assertStringContainsString( '<p class="like-count">2</p>', $html );

		wp_delete_post( $like_post_1, true );
		wp_delete_post( $like_post_2, true );
		wp_delete_post( $post_id, true );
	}
}
