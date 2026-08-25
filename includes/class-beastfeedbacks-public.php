<?php
/**
 * 公開用設定
 *
 * @link       https://beastfeedbacks.com
 * @since      0.1.0
 *
 * @package    BeastFeedbacks
 * @subpackage BeastFeedbacks/includes
 */

/**
 * 公開用設定
 */
class BeastFeedbacks_Public {



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
		$form_action = 'register_beastfeedbacks_form';
		add_action( 'wp_ajax_' . $form_action, array( $this, 'register_beastfeedbacks_form' ) );
		add_action( 'wp_ajax_nopriv_' . $form_action, array( $this, 'register_beastfeedbacks_form' ) );
	}

	/**
	 * アンケートフォームの受け取り処理
	 */
	public function register_beastfeedbacks_form() {
		check_ajax_referer( 'register_beastfeedbacks_form' );

		// POSTデータの存在確認と適切なサニタイズ.
		if ( ! isset( $_POST['id'] ) || ! isset( $_POST['beastfeedbacks_type'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid request', 'beastfeedbacks' ) ) );
		}

		$id   = sanitize_text_field( wp_unslash( $_POST['id'] ) );
		$type = sanitize_text_field( wp_unslash( $_POST['beastfeedbacks_type'] ) );

		if ( ! in_array( $type, BeastFeedbacks_Block::TYPES, true ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid feedback type', 'beastfeedbacks' ) ) );
		}

		$post    = get_post( $id );
		$post_id = $post ? (int) $post->ID : 0;

		if ( ! $post_id || $post_id <= 0 ) {
			wp_send_json_error( array( 'message' => __( 'Invalid post ID', 'beastfeedbacks' ) ) );
		}

		$ip_address  = $this->get_ip_address();
		$user_agent  = $this->get_user_agent();
		$time        = current_time( 'mysql' );
		$title       = "{$ip_address} - {$time}";
		$post_params = $this->extract_post_params( $_POST );
		$content     = $this->format_feedback_content( $user_agent, $ip_address, $type, $post_params );

		$saved = $this->save_feedback( $post_id, $type, $title, $time, $content );
		if ( is_wp_error( $saved ) || ! $saved ) {
			wp_send_json_error( array( 'message' => __( 'Failed to save feedback', 'beastfeedbacks' ) ) );
		}

		$response_data = $this->build_response_data( $post_id, $type );

		wp_send_json( $response_data );
		wp_die();
	}

	/**
	 * POSTデータから不要な項目を除外・サニタイズして抽出する
	 *
	 * @param array $post_data POSTデータ.
	 * @return array
	 */
	public function extract_post_params( array $post_data ) {
		$post_params = array();
		$ignore_keys = array(
			'id',
			'beastfeedbacks_type',
			'action',
			'_wp_http_referer',
			'_wpnonce',
		);

		foreach ( array_keys( $post_data ) as $post_key ) {
			if ( in_array( $post_key, $ignore_keys, true ) ) {
				continue;
			}
			if ( isset( $post_data[ $post_key ] ) ) {
				$post_value = wp_unslash( $post_data[ $post_key ] );
				if ( is_array( $post_value ) ) {
					$post_params[ $post_key ] = array_map( 'sanitize_text_field', $post_value );
					continue;
				}
				$post_params[ $post_key ] = sanitize_text_field( $post_value );
			}
		}

		return $post_params;
	}

	/**
	 * フィードバック本文をJSON形式でフォーマットする
	 *
	 * @param string $user_agent ユーザーエージェント.
	 * @param string $ip_address IPアドレス.
	 * @param string $type       フィードバック種別.
	 * @param array  $post_params 送信されたパラメーター.
	 * @return string
	 */
	public function format_feedback_content( string $user_agent, string $ip_address, string $type, array $post_params ) {
		return addslashes(
			wp_kses(
				wp_json_encode(
					array(
						'user_agent'  => $user_agent,
						'ip_address'  => $ip_address,
						'type'        => $type,
						'post_params' => $post_params,
					),
					JSON_UNESCAPED_UNICODE
				),
				array()
			)
		);
	}

	/**
	 * フィードバック投稿を保存する
	 *
	 * @param int    $post_id 親投稿ID.
	 * @param string $type    フィードバック種別.
	 * @param string $title   投稿タイトル.
	 * @param string $time    投稿日時.
	 * @param string $content 投稿本文.
	 * @return int|WP_Error
	 */
	public function save_feedback( int $post_id, string $type, string $title, string $time, string $content ) {
		return wp_insert_post(
			array(
				'post_date'    => $time,
				'post_type'    => 'beastfeedbacks',
				'post_status'  => 'publish',
				'post_parent'  => $post_id,
				'post_title'   => addslashes( wp_kses( $title, array() ) ),
				'post_name'    => md5( $title ),
				'post_content' => $content,
				'meta_input'   => array(
					'beastfeedbacks_type' => $type,
				),
			)
		);
	}

	/**
	 * レスポンス用データを構築する
	 *
	 * @param int    $post_id 親投稿ID.
	 * @param string $type    フィードバック種別.
	 * @return array
	 */
	public function build_response_data( int $post_id, string $type ) {
		$message = ( 'survey' === $type )
			? __( 'Thank you for your responses to the questionnaire. ', 'beastfeedbacks' )
			: __( 'Thank you for the vote. ', 'beastfeedbacks' );
		$count   = ( 'like' === $type )
			? BeastFeedbacks::get_instance()->get_like_count( $post_id )
			: 1;

		return array(
			'success' => 1,
			'message' => $message,
			'count'   => $count,
		);
	}

	/**
	 * ユーザーエージェントの取得
	 *
	 * @return string
	 */
	public function get_user_agent() {
		return isset( $_SERVER['HTTP_USER_AGENT'] )
			? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) )
			: ''; // @codingStandardsIgnoreLine
	}

	/**
	 * IPアドレスの取得
	 *
	 * @return string
	 */
	public function get_ip_address() {
		return isset( $_SERVER['REMOTE_ADDR'] )
			? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) )
			: '';
	}
}
