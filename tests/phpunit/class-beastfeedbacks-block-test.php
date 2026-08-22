<?php
/**
 * Tests for BeastFeedbacks_Block
 *
 * 実行例:
 * wp-env run tests-cli --env-cwd='wp-content/plugins/beastfeedbacks/' vendor/bin/phpunit
 */
use function Brain\Monkey\Functions\expect;
use Yoast\WPTestUtils\BrainMonkey\TestCase;

class BeastFeedbacks_Block_Test extends TestCase {

	protected function set_up(): void {
		parent::set_up();

		if ( ! defined( 'BEASTFEEDBACKS_DOMAIN' ) ) {
			define( 'BEASTFEEDBACKS_DOMAIN', 'beastfeedbacks' );
		}
		if ( ! defined( 'BEASTFEEDBACKS_DIR' ) ) {
			define( 'BEASTFEEDBACKS_DIR', dirname( __DIR__, 2 ) . '/' );
		}

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
	public function survey_input_init_registers_block_type_and_translations(): void {
		$dummy_type                = new stdClass();
		$dummy_type->editor_script = 'beastfeedbacks-survey-input-script';

		expect( 'register_block_type' )
			->once()
			->with( \BrainMonkey\Expectation\MD::type( 'string' ) )
			->andReturn( $dummy_type );

		expect( 'wp_set_script_translations' )
			->once()
			->with(
				'beastfeedbacks-survey-input-script',
				BEASTFEEDBACKS_DOMAIN,
				BEASTFEEDBACKS_DIR . 'languages'
			);

		if ( ! function_exists( 'beastfeedbacks_block_survey_input_init' ) ) {
			require_once BEASTFEEDBACKS_DIR . 'src/survey-input/init.php';
		}
		beastfeedbacks_block_survey_input_init();
	}

	/** @test */
	public function survey_choice_init_registers_block_type_and_translations(): void {
		$dummy_type                = new stdClass();
		$dummy_type->editor_script = 'beastfeedbacks-survey-choice-script';

		expect( 'register_block_type' )
			->once()
			->with( \BrainMonkey\Expectation\MD::type( 'string' ) )
			->andReturn( $dummy_type );

		expect( 'wp_set_script_translations' )
			->once()
			->with(
				'beastfeedbacks-survey-choice-script',
				BEASTFEEDBACKS_DOMAIN,
				BEASTFEEDBACKS_DIR . 'languages'
			);

		if ( ! function_exists( 'beastfeedbacks_block_survey_choice_init' ) ) {
			require_once BEASTFEEDBACKS_DIR . 'src/survey-choice/init.php';
		}
		beastfeedbacks_block_survey_choice_init();
	}

	/** @test */
	public function survey_form_init_registers_block_type_and_translations(): void {
		$dummy_type                = new stdClass();
		$dummy_type->editor_script = 'beastfeedbacks-survey-form-script';

		expect( 'register_block_type' )
			->once()
			->with(
				\BrainMonkey\Expectation\MD::type( 'string' ),
				array(
					'render_callback' => 'beastfeedbacks_block_survey_form_render_callback',
				)
			)
			->andReturn( $dummy_type );

		expect( 'wp_set_script_translations' )
			->once()
			->with(
				'beastfeedbacks-survey-form-script',
				BEASTFEEDBACKS_DOMAIN,
				BEASTFEEDBACKS_DIR . 'languages'
			);

		if ( ! function_exists( 'beastfeedbacks_block_survey_form_init' ) ) {
			require_once BEASTFEEDBACKS_DIR . 'src/survey-form/init.php';
		}
		beastfeedbacks_block_survey_form_init();
	}

	/** @test */
	public function vote_init_registers_block_type_and_translations(): void {
		$dummy_type                = new stdClass();
		$dummy_type->editor_script = 'beastfeedbacks-vote-script';

		expect( 'register_block_type' )
			->once()
			->with(
				\BrainMonkey\Expectation\MD::type( 'string' ),
				array(
					'render_callback' => 'beastfeedbacks_block_vote_render_callback',
				)
			)
			->andReturn( $dummy_type );

		expect( 'wp_set_script_translations' )
			->once()
			->with(
				'beastfeedbacks-vote-script',
				BEASTFEEDBACKS_DOMAIN,
				BEASTFEEDBACKS_DIR . 'languages'
			);

		if ( ! function_exists( 'beastfeedbacks_block_vote_init' ) ) {
			require_once BEASTFEEDBACKS_DIR . 'src/vote/init.php';
		}
		beastfeedbacks_block_vote_init();
	}

	/** @test */
	public function like_init_registers_block_type_and_translations(): void {
		$dummy_type                = new stdClass();
		$dummy_type->editor_script = 'beastfeedbacks-like-script';

		expect( 'register_block_type' )
			->once()
			->with(
				\BrainMonkey\Expectation\MD::type( 'string' ),
				array(
					'render_callback' => 'beastfeedbacks_block_like_render_callback',
				)
			)
			->andReturn( $dummy_type );

		expect( 'wp_set_script_translations' )
			->once()
			->with(
				'beastfeedbacks-like-script',
				BEASTFEEDBACKS_DOMAIN,
				BEASTFEEDBACKS_DIR . 'languages'
			);

		if ( ! function_exists( 'beastfeedbacks_block_like_init' ) ) {
			require_once BEASTFEEDBACKS_DIR . 'src/like/init.php';
		}
		beastfeedbacks_block_like_init();
	}
}
