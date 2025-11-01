<?php
/**
 * アンケートフォームで表示されるhtmlコード
 *
 * @package BeastFeedbacks
 */

/**
 * Renders the `beastfeedbacks/survey-form` block on the server.
 *
 * @param array  $attributes Block attributes.
 * @param string $content    Block default content.
 *
 * @return string Returns the next or previous post link that is adjacent to the current post.
 */
function beastfeedbacks_block_survey_form_render_callback( $attributes, $content ) {
	$wrapper_attrs = get_block_wrapper_attributes();

	$nonce_field = wp_nonce_field(
		'register_beastfeedbacks_form',
		'_wpnonce',
		true,
		false
	);

	$action_url = esc_url( admin_url( 'admin-ajax.php' ) );
	$post_id    = esc_attr( absint( get_the_ID() ) );

	$html = <<<HTML
<div %s>
	<form action="%s" name="beastfeedbacks_survey_form" method="POST">
		%s
		<input type="hidden" name="action" value="register_beastfeedbacks_form" />
		<input type="hidden" name="beastfeedbacks_type" value="survey" />
		<input type="hidden" name="id" value="%s" />
		%s
	</form>
</div>
HTML;

	return sprintf(
		$html,
		$wrapper_attrs,
		$action_url,
		$nonce_field,
		$post_id,
		$content
	);
}

/**
 * ブロック登録
 */
function beastfeedbacks_block_survey_form_init() {

	$type = register_block_type(
		__DIR__,
		array(
			'render_callback' => 'beastfeedbacks_block_survey_form_render_callback',
		)
	);

	wp_set_script_translations(
		$type->editor_script,
		BEASTFEEDBACKS_DOMAIN,
		BEASTFEEDBACKS_DIR . 'languages',
	);
}

beastfeedbacks_block_survey_form_init();
