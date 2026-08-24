<?php

use Yoast\WPTestUtils\BrainMonkey\TestCase;

class BeastFeedbacks_Test extends TestCase {

	/** @var int[] 作成した投稿のIDを記録して後始末 */
	private $created_ids = array();

	public function set_up(): void {
		parent::set_up();

		if ( ! defined( 'BEASTFEEDBACKS_DIR' ) ) {
			define( 'BEASTFEEDBACKS_DIR', dirname( __DIR__, 2 ) . '/' );
		}
	}

	public function tear_down(): void {
		// 作成した投稿を削除してクリーンアップ
		foreach ( array_reverse( $this->created_ids ) as $pid ) {
			if ( get_post( $pid ) ) {
				wp_delete_post( $pid, true );
			}
		}
		$this->created_ids = array();

		parent::tear_down();
	}

	/** @test */
	public function get_like_count_returns_zero_when_no_likes(): void {
		$parent_id = $this->create_post(
			array(
				'post_type'   => 'post',
				'post_status' => 'publish',
				'post_title'  => 'parent',
			)
		);

		// ノイズ1: publish でない
		$draft_like = $this->create_post(
			array(
				'post_type'   => 'beastfeedbacks',
				'post_status' => 'draft',
				'post_parent' => $parent_id,
				'post_title'  => 'noise-draft-like',
			)
		);
		add_post_meta( $draft_like, 'beastfeedbacks_type', 'like' );

		// ノイズ2: 親が違う
		$other_parent = $this->create_post(
			array(
				'post_type'   => 'post',
				'post_status' => 'publish',
				'post_title'  => 'other-parent',
			)
		);
		$other_like   = $this->create_post(
			array(
				'post_type'   => 'beastfeedbacks',
				'post_status' => 'publish',
				'post_parent' => $other_parent,
				'post_title'  => 'noise-other-parent-like',
			)
		);
		add_post_meta( $other_like, 'beastfeedbacks_type', 'like' );

		// ノイズ3: meta が like ではない
		$vote_noise = $this->create_post(
			array(
				'post_type'   => 'beastfeedbacks',
				'post_status' => 'publish',
				'post_parent' => $parent_id,
				'post_title'  => 'noise-vote',
			)
		);
		add_post_meta( $vote_noise, 'beastfeedbacks_type', 'vote' );

		$count = \BeastFeedbacks::get_instance()->get_like_count( $parent_id );
		$this->assertSame( 0, $count, 'like が無ければ 0 を返すべき' );
	}

	/** @test */
	public function get_like_count_returns_positive_number_when_likes_exist(): void {
		$parent_id = $this->create_post(
			array(
				'post_type'   => 'post',
				'post_status' => 'publish',
				'post_title'  => 'parent',
			)
		);

		// 条件に合う like を3件
		for ( $i = 0; $i < 3; $i++ ) {
			$like_id = $this->create_post(
				array(
					'post_type'   => 'beastfeedbacks',
					'post_status' => 'publish',
					'post_parent' => $parent_id,
					'post_title'  => 'like-' . $i,
				)
			);
			add_post_meta( $like_id, 'beastfeedbacks_type', 'like' );
		}

		// ノイズ: type が vote
		$vote_noise = $this->create_post(
			array(
				'post_type'   => 'beastfeedbacks',
				'post_status' => 'publish',
				'post_parent' => $parent_id,
				'post_title'  => 'vote-noise',
			)
		);
		add_post_meta( $vote_noise, 'beastfeedbacks_type', 'vote' );

		$count = \BeastFeedbacks::get_instance()->get_like_count( $parent_id );
		$this->assertSame( 3, $count, 'like が3件なら 3 を返すべき' );
	}

	/** @test */
	public function beastfeedbacks_deactivate_calls_deactivator_deactivate(): void {
		$dir = defined( 'BEASTFEEDBACKS_DIR' ) ? BEASTFEEDBACKS_DIR : dirname( __DIR__, 2 ) . '/';
		include_once $dir . 'includes/class-beastfeedbacks-deactivator.php';

		$called = false;
		$handle = \Patchwork\redefine(
			'BeastFeedbacks_Deactivator::deactivate',
			function () use ( &$called ) {
				$called = true;
			}
		);

		beastfeedbacks_deactivate();

		\Patchwork\restore( $handle );

		$this->assertTrue( $called, 'beastfeedbacks_deactivate() は BeastFeedbacks_Deactivator::deactivate() を呼び出すべき' );
	}

	/** @test */
	public function beastfeedbacks_activate_calls_activator_activate(): void {
		$dir = defined( 'BEASTFEEDBACKS_DIR' ) ? BEASTFEEDBACKS_DIR : dirname( __DIR__, 2 ) . '/';
		include_once $dir . 'includes/class-beastfeedbacks-activator.php';

		$called = false;
		$handle = \Patchwork\redefine(
			'BeastFeedbacks_Activator::activate',
			function () use ( &$called ) {
				$called = true;
			}
		);

		beastfeedbacks_activate();

		\Patchwork\restore( $handle );

		$this->assertTrue( $called, 'beastfeedbacks_activate() は BeastFeedbacks_Activator::activate() を呼び出すべき' );
	}

	/**
	 * Verify init loads dependencies and registers admin, public, and block hooks when is_admin returns true.
	 *
	 * @test
	 */
	public function init_registers_admin_public_and_block_hooks_when_is_admin_is_true(): void {
		$GLOBALS['current_screen'] = new class() {
			public function in_admin() {
				return true;
			}
		};

		\BeastFeedbacks::get_instance()->init();

		$this->assertNotFalse(
			has_action( 'admin_enqueue_scripts', array( \BeastFeedbacks_Admin::get_instance(), 'admin_enqueue_scripts' ) )
		);
		$this->assertNotFalse(
			has_action( 'wp_ajax_register_beastfeedbacks_form', array( \BeastFeedbacks_Public::get_instance(), 'register_beastfeedbacks_form' ) )
		);
		$this->assertNotFalse(
			has_action( 'init', array( \BeastFeedbacks_Block::get_instance(), 'init_blocks' ) )
		);
	}

	/**
	 * Verify init registers public and block hooks but skips admin initialization when is_admin returns false.
	 *
	 * @test
	 */
	public function init_registers_public_and_block_hooks_but_not_admin_when_is_admin_is_false(): void {
		$GLOBALS['current_screen'] = new class() {
			public function in_admin() {
				return false;
			}
		};

		// Clean up any actions added in previous tests/runs.
		remove_all_actions( 'admin_enqueue_scripts' );
		remove_all_actions( 'wp_ajax_register_beastfeedbacks_form' );
		remove_all_actions( 'init' );

		\BeastFeedbacks::get_instance()->init();

		$this->assertFalse(
			has_action( 'admin_enqueue_scripts', array( \BeastFeedbacks_Admin::get_instance(), 'admin_enqueue_scripts' ) )
		);
		$this->assertNotFalse(
			has_action( 'wp_ajax_register_beastfeedbacks_form', array( \BeastFeedbacks_Public::get_instance(), 'register_beastfeedbacks_form' ) )
		);
		$this->assertNotFalse(
			has_action( 'init', array( \BeastFeedbacks_Block::get_instance(), 'init_blocks' ) )
		);
	}

	/**
	 * 投稿を作成し、ID を回収・記録するユーティリティ
	 *
	 * @param array $args wp_insert_post() の引数.
	 * @return int 作成した投稿ID
	 */
	private function create_post( array $args ): int {
		$pid                 = wp_insert_post( $args );
		$this->created_ids[] = $pid;
		return $pid;
	}
}
