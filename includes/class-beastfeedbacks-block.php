<?php
/**
 * ブロックエディタ
 *
 * @link       https://beastfeedbacks.com
 * @since      0.1.0
 *
 * @package    BeastFeedbacks
 * @subpackage BeastFeedbacks/includes
 */

/**
 * Block
 */
class BeastFeedbacks_Block {

	/**
	 * ブロックで提供するタイプリスト
	 *
	 * @var array
	 */
	public const TYPES = array(
		'like',
		'vote',
		'survey',
	);

	/**
	 * Self class
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * Instance
	 *
	 * @return self
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Init
	 */
	public function init() {
		add_filter( 'block_categories_all', array( $this, 'block_categories_all' ), 10, 2 );
		add_action( 'init', array( $this, 'init_blocks' ) );
	}

	/**
	 * ブロックのカテゴリを追加
	 *
	 * @param array[] $block_categories     Array of categories for block types.
	 * @param object  $block_editor_context `WP_Block_Editor_Context` object.
	 * @see https://developer.wordpress.org/block-editor/reference-guides/filters/block-filters/#block_categories_all
	 */
	public function block_categories_all( array $block_categories, object $block_editor_context ) {
		if ( ! empty( $block_editor_context->post ) ) {
			array_push(
				$block_categories,
				array(
					'slug'  => 'beastfeedbacks',
					'title' => 'BeastFeedbacks',
					'icon'  => null,
				)
			);
		}
		return $block_categories;
	}

	/**
	 * ブロックエディタの登録
	 */
	public function init_blocks() {
		$names = array(
			'like',
			'vote',
			'survey-form',
			'survey-input',
			'survey-choice',
		);

		foreach ( $names as $name ) {
			$this->init_block( $name );
		}
	}

	/**
	 * ブロックの登録 & 翻訳ファイルも適応
	 *
	 * @param string $name 読み込むブロックエディタの対象.
	 */
	public function init_block( $name ) {
		require BEASTFEEDBACKS_DIR . 'build/' . $name . '/init.php';
	}
}
