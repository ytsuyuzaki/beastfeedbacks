<?php

/**
 * Likeで表示されるhtmlコード
 *
 * @package BeastFeedbacks
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Renders the `beastfeedbacks/like` block on the server.
 *
 * @param array  $attributes Block attributes.
 * @param string $content    Block default content.
 *
 * @return string
 */
function beastfeedbacks_block_like_render_callback($attributes, $content)
{
	$wrapper_attrs = get_block_wrapper_attributes();

	$nonce_field = wp_nonce_field(
		'register_beastfeedbacks_form',
		'_wpnonce',
		true,
		false
	);

	$action_url = esc_url(admin_url('admin-ajax.php'));
	$post_id = get_the_ID();
	$post_id_attr = esc_attr(absint($post_id));

	$like_count = BeastFeedbacks::get_instance()->get_like_count($post_id);
	$like_count_text = esc_html($like_count);

	$html = '<div ' . $wrapper_attrs . '>' .
		'<form action="' . $action_url . '" name="beastfeedbacks_like_form" method="POST">' .
		'<div class="beastfeedbacks-like_balloon">' .
		'<p class="like-count">' . $like_count_text . '</p>' .
		'</div>' .
		$nonce_field .
		'<input type="hidden" name="action" value="register_beastfeedbacks_form" />' .
		'<input type="hidden" name="beastfeedbacks_type" value="like" />' .
		'<input type="hidden" name="id" value="' . $post_id_attr . '" />' .
		$content .
		'</form>' .
		'</div>';

	return $html;
}

/**
 * ブロック登録
 */
function beastfeedbacks_block_like_init()
{

	$type = register_block_type(
		__DIR__,
		array(
			'render_callback' => 'beastfeedbacks_block_like_render_callback',
		)
	);

	wp_set_script_translations(
		$type->editor_script,
		BEASTFEEDBACKS_DOMAIN,
		BEASTFEEDBACKS_DIR . 'languages',
	);
}

beastfeedbacks_block_like_init();
