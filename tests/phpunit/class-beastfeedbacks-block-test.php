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

	/**
	 * Test that vote render callback renders form with attributes and content.
	 *
	 * @test
	 */
	public function vote_render_callback_renders_form_with_attributes_and_content(): void {
		if ( ! function_exists( 'beastfeedbacks_block_vote_render_callback' ) ) {
			require_once dirname( __DIR__, 2 ) . '/src/vote/init.php';
		}

		$post_id         = wp_insert_post(
			array(
				'post_type'   => 'post',
				'post_status' => 'publish',
				'post_title'  => 'Vote Test Post',
			)
		);
		$GLOBALS['post'] = get_post( $post_id );

		$content = '<div class="vote-options"><input type="radio" name="option" value="1" /> Option 1</div>';

		WP_Block_Supports::$block_to_render = array(
			'blockName' => 'beastfeedbacks/vote',
			'attrs'     => array(),
		);

		$html = beastfeedbacks_block_vote_render_callback( array(), $content );

		WP_Block_Supports::$block_to_render = null;

		$this->assertStringContainsString( 'name="beastfeedbacks_vote_form"', $html );
		$this->assertStringContainsString( 'admin-ajax.php', $html );
		$this->assertStringContainsString( '_wpnonce', $html );
		$this->assertStringContainsString( '<input type="hidden" name="action" value="register_beastfeedbacks_form" />', $html );
		$this->assertStringContainsString( '<input type="hidden" name="beastfeedbacks_type" value="vote" />', $html );
		$this->assertStringContainsString( '<input type="hidden" name="id" value="' . $post_id . '" />', $html );
		$this->assertStringContainsString( $content, $html );

		wp_delete_post( $post_id, true );
		unset( $GLOBALS['post'] );
	}

	/**
	 * Test that vote render callback handles empty content and zero post ID.
	 *
	 * @test
	 */
	public function vote_render_callback_handles_empty_content_and_zero_post_id(): void {
		if ( ! function_exists( 'beastfeedbacks_block_vote_render_callback' ) ) {
			require_once dirname( __DIR__, 2 ) . '/src/vote/init.php';
		}

		unset( $GLOBALS['post'] );

		WP_Block_Supports::$block_to_render = array(
			'blockName' => 'beastfeedbacks/vote',
			'attrs'     => array(),
		);

		$html = beastfeedbacks_block_vote_render_callback( array(), '' );

		WP_Block_Supports::$block_to_render = null;

		$this->assertStringContainsString( 'name="beastfeedbacks_vote_form"', $html );
		$this->assertStringContainsString( '<input type="hidden" name="beastfeedbacks_type" value="vote" />', $html );
		$this->assertStringContainsString( '<input type="hidden" name="id" value="0" />', $html );
		$this->assertStringContainsString( '<form action="', $html );
		$this->assertStringContainsString( '</form>', $html );
	}
}
