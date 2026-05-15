<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Primary controller class.
 */
class Calendas_Polylang_To_WP_API {
	/**
	 * Post types.
	 *
	 * @access private
	 * @var array
	 */
	private $post_types;

	/**
	 * On load.
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Plugin initiation.
	 *
	 * A helper function to initiate actions, hooks and other features needed.
	 *
	 * @return void
	 */
	public function init(): void {
		$this->post_types = array(
			Calendas::EVENT_CPT,
			Calendas::EVENT_ORGANIZER_CPT,
			Calendas::EVENT_VENUE_CPT,
		);

		foreach ( $this->post_types as $post_type ) {
			if ( function_exists( 'pll_is_translated_post_type' ) && pll_is_translated_post_type( $post_type ) ) {
				add_filter( "rest_{$post_type}_query", array( $this, 'wpapi_filter_rest_post_query' ), 10, 2 );
			}
		}

		// Init REST.
		add_action(
			'rest_api_init',
			array( $this, 'register_language_field' )
		);
	}

	/**
	 * Query by "lang" parameter:
	 * /wp-json/wp/v2/posts?lang=en
	 *
	 * @param array           $args    Options for the function.
	 * @param WP_REST_Request $request Options for the function.
	 *
	 * @return array
	 */
	public function wpapi_filter_rest_post_query( $args, $request ): array {
		$lang_parameter = $request->get_param( 'lang' );

		if ( isset( $lang_parameter ) ) {
			$args['lang'] = $lang_parameter; // https://polylang.pro/doc/developpers-how-to/#query
		}

		return $args;
	}

	/**
	 * Register language field for all post types.
	 *
	 * @return void
	 */
	public function register_language_field(): void {
		foreach ( $this->post_types as $post_type ) {
			register_rest_field(
				$post_type,
				'language',
				array(
					'get_callback' => array( $this, 'wpapi_get_postlanguage_function' ),
					'schema'       => null,
				)
			);
		}
	}

	/**
	 * Register post language as custom REST field.
	 * https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-custom-endpoints
	 *
	 * @return array
	 */
	/*
	public function wpapi_register_postlanguage_function(): array {
		return array(
			'methods'      => 'GET',
			'get_callback' => array( $this, 'wpapi_get_postlanguage_function' ),
			'schema'       => null,
		);
	}*/

	/**
	 * "GET" Callback.
	 *
	 * @param array $data Callback data.
	 *
	 * @return mixed
	 */
	public function wpapi_get_postlanguage_function( $data ): mixed {
		return function_exists( 'pll_get_post_language' ) ? pll_get_post_language( $data['id'] ) : null;
	}
}

new Calendas_Polylang_To_WP_API();
