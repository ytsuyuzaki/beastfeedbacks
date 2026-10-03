<?php
/**
 * Tests for BeastFeedbacks_Block::init_blocks() and BeastFeedbacks_Block::init_block().
 *
 * @package BeastFeedbacks
 */

class Init_Blocks_Test extends BeastFeedbacks_TestCase {

	/**
	 * Set up test environment.
	 */
	protected function set_up(): void {
		parent::set_up();
		remove_all_filters( 'block_categories_all' );
		remove_all_actions( 'init' );
	}

	/**
	 * Tear down test environment.
	 */
	protected function tear_down(): void {
		remove_all_filters( 'block_categories_all' );
		remove_all_actions( 'init' );
		parent::tear_down();
	}

	/**
	 * Test that init() hooks init_blocks to the WordPress init action.
	 *
	 * @test
	 */
	public function test_init_hooks_init_blocks_to_init_action(): void {
		$instance = \BeastFeedbacks_Block::get_instance();

		$instance->init();

		$this->assertNotFalse(
			has_action( 'init', array( $instance, 'init_blocks' ) )
		);
	}

	/**
	 * Test that init_block() requires the build file and registers the block.
	 *
	 * @test
	 * @dataProvider data_block_names
	 *
	 * @param string $name Block name slug.
	 */
	public function test_init_block_registers_block( string $name ): void {
		$registry = \WP_Block_Type_Registry::get_instance();
		$full_name = 'beastfeedbacks/' . $name;

		if ( $registry->is_registered( $full_name ) ) {
			unregister_block_type( $full_name );
		}

		$instance = \BeastFeedbacks_Block::get_instance();
		$instance->init_block( $name );

		$this->assertTrue( $registry->is_registered( $full_name ) );
	}

	/**
	 * Test that init_block() requires the build file if it exists.
	 *
	 * @test
	 */
	public function test_init_block_requires_file_if_exists(): void {
		$instance = \BeastFeedbacks_Block::get_instance();
		$name     = 'like';

		$file = BEASTFEEDBACKS_DIR . 'build/' . $name . '/init.php';
		$this->assertFileExists( $file );

		$instance->init_block( $name );

		$included_files = get_included_files();
		$this->assertContains( $file, $included_files );
	}

	/**
	 * Test that init_block() does not throw a fatal error if the build file does not exist.
	 *
	 * @test
	 */
	public function test_init_block_does_not_throw_error_if_file_does_not_exist(): void {
		$instance = \BeastFeedbacks_Block::get_instance();
		$name     = 'non-existent-block';

		$file = BEASTFEEDBACKS_DIR . 'build/' . $name . '/init.php';
		$this->assertFileDoesNotExist( $file );

		// The test will fail if this throws a fatal error.
		$instance->init_block( $name );

		$included_files = get_included_files();
		$this->assertNotContains( $file, $included_files );
	}

	/**
	 * Test that init_blocks() initializes all five block types.
	 *
	 * @test
	 */
	public function test_init_blocks_initializes_all_blocks(): void {
		$registry = \WP_Block_Type_Registry::get_instance();
		$names    = array(
			'like',
			'vote',
			'survey-form',
			'survey-input',
			'survey-choice',
		);

		foreach ( $names as $name ) {
			$full_name = 'beastfeedbacks/' . $name;
			if ( $registry->is_registered( $full_name ) ) {
				unregister_block_type( $full_name );
			}
		}

		$instance = \BeastFeedbacks_Block::get_instance();
		$instance->init_blocks();

		foreach ( $names as $name ) {
			$full_name = 'beastfeedbacks/' . $name;
			$this->assertTrue(
				$registry->is_registered( $full_name ),
				"Block '{$full_name}' should be registered after calling init_blocks()."
			);
		}
	}

	/**
	 * Data provider for block names.
	 *
	 * @return array<string, array{0: string}>
	 */
	public function data_block_names(): array {
		return array(
			'like'          => array( 'like' ),
			'vote'          => array( 'vote' ),
			'survey-form'   => array( 'survey-form' ),
			'survey-input'  => array( 'survey-input' ),
			'survey-choice' => array( 'survey-choice' ),
		);
	}
}
