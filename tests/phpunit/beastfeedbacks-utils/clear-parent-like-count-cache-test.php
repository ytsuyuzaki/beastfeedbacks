<?php
/**
 * Tests for BeastFeedbacks_Utils::clear_parent_like_count_cache().
 *
 * @package BeastFeedbacks
 */

class BeastFeedbacks_Utils_Clear_Parent_Like_Count_Cache_Test extends BeastFeedbacks_TestCase {

	/** @test */
	public function clear_parent_like_count_cache_deletes_cache_for_positive_parent_id(): void {
		$parent_id   = 123;
		$cache_key   = 'like_count_' . $parent_id;
		$cache_group = 'beastfeedbacks';

		wp_cache_set( $cache_key, 10, $cache_group );
		$this->assertSame( 10, wp_cache_get( $cache_key, $cache_group ) );

		\BeastFeedbacks_Utils::clear_parent_like_count_cache( $parent_id );

		$this->assertFalse( wp_cache_get( $cache_key, $cache_group ), 'Cache entry should be deleted for positive parent ID.' );
	}

	/** @test */
	public function clear_parent_like_count_cache_handles_numeric_string_parent_id(): void {
		$parent_id   = '456';
		$cache_key   = 'like_count_456';
		$cache_group = 'beastfeedbacks';

		wp_cache_set( $cache_key, 5, $cache_group );
		$this->assertSame( 5, wp_cache_get( $cache_key, $cache_group ) );

		\BeastFeedbacks_Utils::clear_parent_like_count_cache( $parent_id );

		$this->assertFalse( wp_cache_get( $cache_key, $cache_group ), 'Numeric string parent ID should be cast to integer and cache deleted.' );
	}

	/**
	 * @test
	 * @dataProvider invalid_parent_id_provider
	 *
	 * @param mixed $invalid_parent_id Invalid parent ID input.
	 */
	public function clear_parent_like_count_cache_ignores_non_positive_or_invalid_parent_ids( $invalid_parent_id ): void {
		$derived_key = 'like_count_' . (int) $invalid_parent_id;
		$cache_group = 'beastfeedbacks';

		wp_cache_set( $derived_key, 42, $cache_group );

		\BeastFeedbacks_Utils::clear_parent_like_count_cache( $invalid_parent_id );

		$this->assertSame( 42, wp_cache_get( $derived_key, $cache_group ), 'Cache for invalid parent ID cast key should remain untouched.' );
	}

	/**
	 * Data provider for invalid or non-positive parent IDs.
	 *
	 * @return array<string, array<int, mixed>>
	 */
	public function invalid_parent_id_provider(): array {
		return array(
			'zero'               => array( 0 ),
			'string zero'        => array( '0' ),
			'negative integer'   => array( -10 ),
			'non-numeric string' => array( 'invalid' ),
			'null'               => array( null ),
			'boolean false'      => array( false ),
		);
	}
}
