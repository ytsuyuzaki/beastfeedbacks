<?php
/**
 * Base test case for BeastFeedbacks tests.
 *
 * @package BeastFeedbacks
 */

use Yoast\WPTestUtils\BrainMonkey\TestCase;

/**
 * Abstract base class providing common helpers and setup/teardown logic.
 */
abstract class BeastFeedbacks_TestCase extends TestCase {

	/**
	 * Created post IDs to delete in tear down.
	 *
	 * @var int[]
	 */
	protected $created_ids = array();

	/**
	 * Set up test environment with required constants.
	 */
	protected function set_up(): void {
		parent::set_up();

		if ( ! defined( 'ABSPATH' ) ) {
			define( 'ABSPATH', true );
		}
		if ( ! defined( 'BEASTFEEDBACKS_DIR' ) ) {
			define( 'BEASTFEEDBACKS_DIR', dirname( __DIR__, 2 ) . '/' );
		}
		if ( ! defined( 'BEASTFEEDBACKS_DOMAIN' ) ) {
			define( 'BEASTFEEDBACKS_DOMAIN', 'beastfeedbacks' );
		}
		if ( ! defined( 'BEASTFEEDBACKS_URL' ) ) {
			define( 'BEASTFEEDBACKS_URL', 'https://example.com/wp-content/plugins/beastfeedbacks/' );
		}
		if ( ! defined( 'BEASTFEEDBACKS_VERSION' ) ) {
			define( 'BEASTFEEDBACKS_VERSION', '0.1.0-test' );
		}
	}

	/**
	 * Tear down test environment and cleanup created posts.
	 */
	protected function tear_down(): void {
		// Clean up created posts in reverse order.
		foreach ( array_reverse( $this->created_ids ) as $pid ) {
			if ( get_post( $pid ) ) {
				wp_delete_post( $pid, true );
			}
		}
		$this->created_ids = array();

		// Reset block supports if present.
		if ( class_exists( 'WP_Block_Supports' ) ) {
			WP_Block_Supports::$block_to_render = null;
		}

		parent::tear_down();
	}

	/**
	 * Create a WordPress post and track its ID for automatic cleanup.
	 *
	 * @param array $args Arguments passed to wp_insert_post().
	 * @return int Created post ID.
	 */
	protected function create_post( array $args = array() ): int {
		$defaults = array(
			'post_type'   => 'post',
			'post_status' => 'publish',
			'post_title'  => 'Test Post',
		);
		$parsed   = array_merge( $defaults, $args );
		$pid      = wp_insert_post( $parsed );

		if ( ! is_wp_error( $pid ) && $pid > 0 ) {
			$this->created_ids[] = $pid;
		}

		return (int) $pid;
	}

	/**
	 * Create a feedback CPT post and track its ID for automatic cleanup.
	 *
	 * @param string $type Feedback type ('like', 'vote', 'survey').
	 * @param int    $parent_id Parent post ID.
	 * @param array  $post_params Submitted form parameters.
	 * @param array  $extra Extra attributes (ip_address, user_agent, etc.).
	 * @return int Created feedback post ID.
	 */
	protected function create_feedback_post( string $type, int $parent_id = 0, array $post_params = array(), array $extra = array() ): int {
		$content_data = array_merge(
			array(
				'type'        => $type,
				'ip_address'  => '127.0.0.1',
				'user_agent'  => 'Unit Test Agent',
				'post_params' => $post_params,
			),
			$extra
		);

		$post_id = $this->create_post(
			array(
				'post_type'    => 'beastfeedbacks',
				'post_status'  => 'publish',
				'post_parent'  => $parent_id,
				'post_title'   => 'Feedback ' . $type,
				'post_content' => wp_json_encode( $content_data ),
			)
		);

		add_post_meta( $post_id, 'beastfeedbacks_type', $type );

		return $post_id;
	}

	/**
	 * Create a Like feedback post.
	 *
	 * @param int $parent_id Parent post ID.
	 * @return int Created feedback post ID.
	 */
	protected function create_like_post( int $parent_id = 0 ): int {
		return $this->create_feedback_post( 'like', $parent_id );
	}

	/**
	 * Create a Vote feedback post.
	 *
	 * @param int    $parent_id Parent post ID.
	 * @param string $choice Selected choice.
	 * @return int Created feedback post ID.
	 */
	protected function create_vote_post( int $parent_id = 0, string $choice = 'Option 1' ): int {
		return $this->create_feedback_post( 'vote', $parent_id, array( 'selected' => $choice ) );
	}

	/**
	 * Create a Survey feedback post.
	 *
	 * @param int   $parent_id Parent post ID.
	 * @param array $params Survey response key-value pairs.
	 * @return int Created feedback post ID.
	 */
	protected function create_survey_post( int $parent_id = 0, array $params = array() ): int {
		return $this->create_feedback_post( 'survey', $parent_id, $params );
	}

	/**
	 * Create request data array for AJAX form handler.
	 *
	 * @param array  $params Additional form parameters.
	 * @param int    $post_id Target post ID.
	 * @param string $type Feedback type.
	 * @return array Request payload.
	 */
	protected function create_ajax_request( array $params, int $post_id, string $type ): array {
		return array_merge(
			array(
				'_ajax_nonce'         => wp_create_nonce( 'register_beastfeedbacks_form' ),
				'action'              => 'register_beastfeedbacks_form',
				'id'                  => (string) $post_id,
				'beastfeedbacks_type' => $type,
				'_wp_http_referer'    => '/ignored',
			),
			$params
		);
	}

	/**
	 * Call an AJAX handler with mocked wp_die and return the decoded JSON response.
	 *
	 * @param callable|null $handler Handler callable. Defaults to BeastFeedbacks_Public::register_beastfeedbacks_form.
	 * @return array Decoded JSON response.
	 */
	protected function call_ajax_handler( ?callable $handler = null ): array {
		if ( null === $handler ) {
			$handler = array( \BeastFeedbacks_Public::get_instance(), 'register_beastfeedbacks_form' );
		}

		$die_handler = static function () {
			return static function () {
				throw new RuntimeException( 'wp_die' );
			};
		};

		add_filter( 'wp_die_ajax_handler', $die_handler );
		add_filter( 'wp_die_handler', $die_handler );
		ob_start();
		try {
			call_user_func( $handler );
		} catch ( RuntimeException $e ) {
			$this->assertSame( 'wp_die', $e->getMessage() );
		} finally {
			remove_filter( 'wp_die_ajax_handler', $die_handler );
			remove_filter( 'wp_die_handler', $die_handler );
		}

		return (array) json_decode( ob_get_clean(), true );
	}

	/**
	 * Render a block with block supports and post context.
	 *
	 * @param string        $block_name Block name (e.g. 'beastfeedbacks/like').
	 * @param array         $attributes Block attributes.
	 * @param callable      $callback Render callback function.
	 * @param string        $content Inner content.
	 * @param int|null      $post_id Optional post ID for post context.
	 * @return string Rendered HTML.
	 */
	protected function render_block_with_context( string $block_name, array $attributes, callable $callback, string $content = '', ?int $post_id = null ): string {
		if ( null !== $post_id ) {
			$GLOBALS['post'] = get_post( $post_id );
			if ( $GLOBALS['post'] ) {
				setup_postdata( $GLOBALS['post'] );
			}
		}

		if ( class_exists( 'WP_Block_Supports' ) ) {
			WP_Block_Supports::$block_to_render = array(
				'blockName'    => $block_name,
				'attrs'        => $attributes,
				'innerBlocks'  => array(),
				'innerHTML'    => $content,
				'innerContent' => array( $content ),
			);
		}

		$html = call_user_func( $callback, $attributes, $content );

		if ( class_exists( 'WP_Block_Supports' ) ) {
			WP_Block_Supports::$block_to_render = null;
		}

		if ( null !== $post_id ) {
			wp_reset_postdata();
			unset( $GLOBALS['post'] );
		}

		return $html;
	}
}
