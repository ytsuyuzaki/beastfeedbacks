<?php
/**
 * Likeで表示されるhtmlコード
 *
 * @package BeastFeedbacks
 */

/**
 * Renders the `beastfeedbacks/like` block on the server.
 *
 * @param array  $attributes Block attributes.
 * @param string $content    Block default content.
 *
 * @return string
 */
function beastfeedbacks_block_like_render_callback( $attributes, $content ) {
	// ブロックの wrapper 属性（className など）を正しく組み立てる
	$wrapper_attrs = get_block_wrapper_attributes();

	// 非表示で nonce を“出力せずに”取得
	$nonce_field = wp_nonce_field(
		'register_beastfeedbacks_form',
		'_wpnonce',
		true,   // referer hidden も出力
		false   // echo せず、文字列で返す
	);

	$action_url = esc_url( admin_url( 'admin-ajax.php' ) );

	$post_id = get_the_ID();
	$post_id_attr = esc_attr( absint( $post_id ) );

	$like_count = BeastFeedbacks::get_instance()->get_like_count( $post_id );
	$like_count_text = esc_html( $like_count );

	$html = <<<HTML
<div %s>
	<form action="%s" name="beastfeedbacks_like_form" method="POST">
		<div class="beastfeedbacks-like_balloon">
			<p class="like-count">%s</p>
		</div>
		%s
		<input type="hidden" name="action" value="register_beastfeedbacks_form" />
		<input type="hidden" name="beastfeedbacks_type" value="like" />
		<input type="hidden" name="id" value="%s" />
		%s
	</form>
</div>
HTML;

	return sprintf(
		$html,
		$wrapper_attrs,
		$action_url,
		$like_count_text,
		$nonce_field,
		$post_id_attr,
		$content
	);
}

/**
 * ブロック登録
 */
function beastfeedbacks_block_like_init() {

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
