<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Events calendar class.
 */
class Calendas {
	/**
	 * Plugin Name.
	 *
	 * @access public
	 * @var string
	 */
	public static $plugin_name;

	/**
	 * Plugin Version.
	 *
	 * @access public
	 * @var string
	 */
	public static $plugin_version;

	/**
	 * Plugin Slug.
	 *
	 * @access public
	 * @var string
	 */
	public static $plugin_slug;

	/**
	 * Plugin URL.
	 *
	 * @access public
	 * @var string
	 */
	public static $plugin_url;

	/**
	 * Plugin URI.
	 *
	 * @access public
	 * @var string
	 */
	public static $plugin_uri;

	/**
	 * Plugin URL.
	 *
	 * @access public
	 * @var string
	 */
	public static $calendas_url;

	/**
	 * User locale URL.
	 *
	 * @access public
	 * @var string
	 */
	public static $locale;

	/** @var string */
	const EVENT_CPT    = 'calendas-event';
	const EVENT_CPT_DB = 'calendas';
	/** @var string */
	const EVENT_ORGANIZER_CPT = 'calendas-organizer';
	/** @var string */
	const EVENT_VENUE_CPT = 'calendas-venue';
	/** @var string */
	const NONCE = self::EVENT_CPT_DB . '_nonce';
	/** @var string */
	const EVENT_FEATURED = self::EVENT_CPT_DB . '_featured';
	/** @var string */
	const EVENT_START_TIMESTAMP = self::EVENT_CPT_DB . '_start_timestamp';
	/** @var string */
	const EVENT_END_TIMESTAMP = self::EVENT_CPT_DB . '_end_timestamp';
	/** @var string */
	const EVENT_ALLDAY = self::EVENT_CPT_DB . '_allday';
	/** @var string */
	const EVENT_TIMEZONE = self::EVENT_CPT_DB . '_timezone';
	/** @var string */
	const EVENT_URL = self::EVENT_CPT_DB . '_url';
	/** @var string */
	const EVENT_COST = self::EVENT_CPT_DB . '_cost';
	/** @var string */
	const EVENT_CURRENCY = self::EVENT_CPT_DB . '_currency';
	/** @var string */
	const EVENT_COST_INFO = self::EVENT_CPT_DB . '_cost_info';
	/** @var string */
	const EVENT_VENUE = self::EVENT_CPT_DB . '_venue';
	/** @var string */
	const EVENT_ORGANIZER = self::EVENT_CPT_DB . '_organizer';

	/** @var string */
	const ORGANIZER_EMAIL = self::EVENT_CPT_DB . '_email';
	/** @var string */
	const ORGANIZER_PHONE = self::EVENT_CPT_DB . '_phone';
	/** @var string */
	const ORGANIZER_URL = self::EVENT_CPT_DB . '_url';

	/** @var string */
	const VENUE_EMAIL = self::EVENT_CPT_DB . '_email';
	/** @var string */
	const VENUE_PHONE = self::EVENT_CPT_DB . '_phone';
	/** @var string */
	const VENUE_URL = self::EVENT_CPT_DB . '_url';
	/** @var string */
	const VENUE_ADDRESS = self::EVENT_CPT_DB . '_address';
	/** @var string */
	const VENUE_CITY = self::EVENT_CPT_DB . '_city';
	/** @var string */
	const VENUE_STATE = self::EVENT_CPT_DB . '_state';
	/** @var string */
	const VENUE_POSTCODE = self::EVENT_CPT_DB . '_postcode';
	/** @var string */
	const VENUE_LATITUDE = self::EVENT_CPT_DB . '_lat';
	/** @var string */
	const VENUE_LONGITUDE = self::EVENT_CPT_DB . '_lng';

	/** @var string */
	const QUERY = 'query';
	/** @var string */
	const PASSED = 'passed-events';

	/**
	 * Construct.
	 *
	 * @return void
	 */
	public function __construct() {
		$plugin_data          = get_file_data(
			plugin_dir_path( __DIR__ ) . 'calendas.php',
			array(
				'Name'       => 'Plugin Name',
				'Version'    => 'Version',
				'TextDomain' => 'Text Domain',
				'PluginURI'  => 'Plugin URI',
				'AuthorURI'  => 'Author URI',
			),
		);
		self::$plugin_name    = esc_attr( $plugin_data['Name'] );
		self::$plugin_version = esc_attr( $plugin_data['Version'] );
		self::$plugin_slug    = esc_attr( $plugin_data['TextDomain'] );
		self::$plugin_url     = defined( 'CALENDAS_PLUGIN_URL' ) ? esc_url( CALENDAS_PLUGIN_URL ) : esc_url( plugin_dir_url( __DIR__ ) );
		self::$plugin_uri     = esc_url( $plugin_data['PluginURI'] );
		self::$calendas_url   = esc_url( $plugin_data['AuthorURI'] );
		self::$locale         = esc_attr( str_replace( '_', '-', get_user_locale() ) );

		add_action( 'init', array( $this, 'on_init' ) );

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		add_filter( 'pre_render_block', array( $this, 'update_events_query' ), 10, 2 );
		add_filter( 'render_block_core/query', array( $this, 'modify_query_block' ), 20, 2 );
		add_filter( 'render_block_core/post-date', array( $this, 'modify_post_date_block' ), 20, 3 );

		add_action( 'pre_get_posts', array( $this, 'sort_events_by_start_date' ) );
		remove_action( 'future_post', '_future_post_hook' );
	}

	/**
	 * Get a list of all timezones.
	 *
	 * @return array
	 */
	public function get_timezones(): array {
		$identifiers = timezone_identifiers_list();

		$timezones = array();

		foreach ( $identifiers as $tzid ) {
			$timezones[] = $tzid;
		}

		return $timezones;
	}

	/**
	 * Google Maps link.
	 *
	 * @param string $lat   Latitude.
	 * @param string $lng   Longitude.
	 * @param string $place Place name (optional).
	 *
	 * @return string
	 */
	public static function google_maps_link( $lat, $lng, $place = '' ): string {
		if ( empty( $lat ) && empty( $lng ) ) {
			return '';
		}

		$place   = ! empty( $place ) ? "search/$place/" : '';
		$lat_lng = esc_attr( "{$lat},{$lng}" );

		return '<a href="https://www.google.com/maps/' . esc_attr( $place ) . '@' . esc_attr( $lat_lng ) . ',19z">' . esc_html__( 'Google Maps', 'calendas' ) . '</a>';
	}

	/**
	 * Google Maps iFrame.
	 *
	 * @param string $lat   Latitude.
	 * @param string $lng   Longitude.
	 * @param string $place Place name (optional).
	 *
	 * @return string
	 */
	public static function google_maps_iframe( $lat, $lng, $place = '' ): string {
		// $place = ! empty( $place ) ? "{$place} " : '';
		$lat_lng = esc_attr( "{$lat},{$lng}" );

		return "<p><iframe src='https://www.google.com/maps?q={$lat_lng}&output=embed&embed_true' width='600' height='450' style='border:0;' allowfullscreen=' loading='lazy' referrerpolicy='no-referrer-when-downgrade'></iframe></p>";
	}

	/**
	 * Functions on init.
	 *
	 * @return void
	 */
	public function on_init(): void {
		/**
		 * Add body class.
		 *
		 * @param string[] $classes An array of post class names.
		 *
		 * @return array
		 */
		add_filter(
			'body_class',
			function ( $classes ): array {
				$post_id = get_the_ID();

				if ( is_singular() && get_post_meta( $post_id, '_' . self::EVENT_FEATURED, true ) ) {
					$classes[] = 'featured-event';
				}

				return $classes;
			}
		);

		/**
		 * Add post class.
		 *
		 * @param string[] $classes   An array of post class names.
		 * @param string[] $css_class An array of additional class names added to the post.
		 * @param int|null $post_id   Post ID.
		 *
		 * @return array
		 */
		add_filter(
			'post_class',
			function ( $classes, $css_class, $post_id ): array {
				if ( self::EVENT_CPT !== get_post_type() ) {
					return $classes;
				}

				if ( get_post_meta( $post_id, '_' . self::EVENT_FEATURED, true ) ) {
					$classes[] = 'featured-event';
				}

				$now = wp_date( 'U' );

				$classes[] = ( get_post_meta( $post_id, '_' . self::EVENT_END_TIMESTAMP, true ) < $now ? 'passed' : 'future' ) . '-event';

				return $classes;
			},
			10,
			3
		);

		add_filter( 'the_content', array( $this, 'modify_content' ), 90 );

		add_rewrite_endpoint( self::QUERY, EP_PAGES ); // /query/passed-events

		// Add rewrite rules: https://codex.wordpress.org/Rewrite_API/add_rewrite_rule
		add_rewrite_rule(
			'(.?.+?)/' . self::QUERY . '(/(.*))?/?$',
			'index.php?pagename=$matches[1]&' . self::QUERY . '=$matches[3]',
			'top'
		);

		add_filter(
			'query_vars',
			function ( $vars ): array {
				$vars[] = self::QUERY;

				return $vars;
			}
		);

		/**
		 * REST query: Order custom Post type by meta key in ascending order.
		 * https://developer.wordpress.org/block-editor/how-to-guides/block-tutorial/extending-the-query-loop-block/#making-your-custom-query-work-on-the-editor-side
		 *
		 * @param array           $args    Array of arguments for WP_Query.
		 * @param WP_REST_Request $request The REST API request.
		 *
		 * @return array
		 */
		add_filter(
			'rest_' . self::EVENT_CPT . '_query',
			function ( $args, $request ): array {
				$args['order']    = 'ASC';
				$args['orderby']  = 'meta_value';
				$args['meta_key'] = '_' . self::EVENT_START_TIMESTAMP;

				// Modify "after" and "before" parameters to include events that go over several months and are within current range.
				$after  = $args['date_query'][1]['after'] ?? null;
				$before = $args['date_query'][0]['before'] ?? null;

				if ( $after && $before ) {
					$date_after  = new DateTimeImmutable( $after );
					$date_before = new DateTimeImmutable( $before );

					$events_within_range = false;

					// Condition 1: Event starts within and ends after the given date range.
					$date_range_query1 = new WP_Query(
						array(
							'post_type'  => self::EVENT_CPT,
							'meta_query' => array(
								'relation' => 'AND',
								array(
									'key'     => '_' . self::EVENT_START_TIMESTAMP,
									'value'   => $date_after->format( 'U' ),
									'compare' => '>=',
									'type'    => 'NUMERIC',
								),
								array(
									'key'     => '_' . self::EVENT_START_TIMESTAMP,
									'value'   => $date_before->format( 'U' ),
									'compare' => '<=',
									'type'    => 'NUMERIC',
								),
								array(
									'key'     => '_' . self::EVENT_END_TIMESTAMP,
									'value'   => $date_before->format( 'U' ),
									'compare' => '>=',
									'type'    => 'NUMERIC',
								),
							),
							'fields'     => 'ids',
						)
					);

					if ( $date_range_query1->have_posts() ) {
						$events_within_range = true;
					}

					if ( false === $events_within_range ) {
						// Condition 2: Event starts and ends outside the given date range.
						$date_range_query2 = new WP_Query(
							array(
								'post_type'  => self::EVENT_CPT,
								'meta_query' => array(
									'relation' => 'AND',
									array(
										'key'     => '_' . self::EVENT_START_TIMESTAMP,
										'value'   => $date_after->format( 'U' ),
										'compare' => '<=',
										'type'    => 'NUMERIC',
									),
									array(
										'key'     => '_' . self::EVENT_END_TIMESTAMP,
										'value'   => $date_before->format( 'U' ),
										'compare' => '>=',
										'type'    => 'NUMERIC',
									),
								),
								'fields'     => 'ids',
							)
						);

						if ( $date_range_query2->have_posts() ) {
							$events_within_range = true;
						}
					}

					if ( false === $events_within_range ) {
						// Condition 3: Event starts before and ends within the given date range.
						$date_range_query3 = new WP_Query(
							array(
								'post_type'  => self::EVENT_CPT,
								'meta_query' => array(
									'relation' => 'AND',
									array(
										'key'     => '_' . self::EVENT_END_TIMESTAMP,
										'value'   => $date_after->format( 'U' ),
										'compare' => '>=',
										'type'    => 'NUMERIC',
									),
									array(
										'key'     => '_' . self::EVENT_END_TIMESTAMP,
										'value'   => $date_before->format( 'U' ),
										'compare' => '<=',
										'type'    => 'NUMERIC',
									),
									array(
										'key'     => '_' . self::EVENT_START_TIMESTAMP,
										'value'   => $date_after->format( 'U' ),
										'compare' => '<=',
										'type'    => 'NUMERIC',
									),
								),
								'fields'     => 'ids',
							)
						);

						if ( $date_range_query3->have_posts() ) {
							$events_within_range = true;
						}
					}

					if ( true === $events_within_range ) {
						// TODO: Dynamically retrieve after/before date parameters from event that has the longest range.
						$args['date_query'][1]['after']  = $date_after->modify( '-7 weeks' )->format( 'Y-m-d\TH:i:s' );
						$args['date_query'][0]['before'] = $date_before->modify( '+7 weeks' )->format( 'Y-m-d\TH:i:s' );
					}
				}

				return $args;
			},
			10,
			2
		);

		/**
		 * REST query: Modify endpoint.
		 * E.g. Return events as ICS: [GET] /wp-json/wp/v2/{events_post_type}/?output=ics
		 *
		 * @param array           $result  Response data to send to the client.
		 * @param WP_REST_Server  $server  Server instance.
		 * @param WP_REST_Request $request Request used to generate the response.
		 *
		 * @return array|string
		 */
		if ( ! class_exists( 'Calendas_Pro' ) ) {
			add_filter(
				'rest_pre_echo_response',
				function ( $result, $server, $request ) {
					if ( isset( $result[0] ) && ( ! isset( $result[0]['type'] ) || self::EVENT_CPT !== $result[0]['type'] ) ) {
						return $result;
					}

					$params = $request->get_params();

					$output = $params['output'] ?? null;

					if ( ! $output || ( $output && 'ics' !== $output ) ) {
						return $result;
					}

					// Custom data output: "ICS" formatted string.
					$output_ics  = "BEGIN:VCALENDAR\n";
					$output_ics .= "VERSION:2.0\n";
					$output_ics .= "X-WR-CALNAME;VALUE=TEXT:Calendas WordPress Plugin\n";
					$output_ics .= 'PRODID:-//' . esc_url( home_url( add_query_arg( null, null ) ) ) . '//' . esc_html( substr( get_locale(), 0, 2 ) ) . "\n";

					$rows = array();

					foreach ( $result as $i => $event ) {
						$event_data = $event['event_data'];

						$timezone = $event_data['timezone'] ?? null;

						if ( empty( $timezone ) ) {
							$timezone = wp_timezone_string();
						}

						$rows[] =
							"BEGIN:VEVENT\n" .
							'UID:' . esc_attr( $i ) . "\n" .
							'SUMMARY:' . esc_html( htmlspecialchars_decode( $event['title']['rendered'] ) ) . "\n" .
							'DESCRIPTION:' . esc_html( htmlspecialchars_decode( wp_strip_all_tags( $event['excerpt']['rendered'] ) ) ) . ' - ' . esc_url( get_permalink( $event['id'] ) ) . "\n" .
							'DTSTART;TZID=' . esc_attr( $timezone ) . ':' . wp_date( 'Ymd\THis', $event_data['start_datetime'], new DateTimeZone( $timezone ) ) . "\n" .
							'DTEND;TZID=' . esc_attr( $timezone ) . ':' . wp_date( 'Ymd\THis', $event_data['end_datetime'], new DateTimeZone( $timezone ) ) . "\n" .
							'DTSTAMP:' . esc_html( wp_date( 'Ymd\THis\Z', strtotime( $event['date'] ) ) ) . "\n" .
							"END:VEVENT\n";
					}

					$output_ics .= implode( '', $rows );

					$output_ics .= "END:VCALENDAR\n";

					header( 'Content-Type: text/calendar; charset=utf-8' );

					/*
					header( 'Content-Description: File Download' );
					header( 'Content-Disposition: attachment; filename="' . esc_attr( get_the_title( $post_id ) ) . '.ics"' );
					header( 'Content-Type: application/force-download' );*/

					echo $output_ics;
				},
				10,
				3
			);
		}

		/**
		 * Register Custom Post Types
		 */
		register_post_type(
			self::EVENT_CPT,
			array(
				'labels'             => array(
					'name'          => esc_html__( 'Events', 'calendas' ),
					'singular_name' => esc_html__( 'Event', 'calendas' ),
					'add_new_item'  => sprintf( /* translators: %s: Post type. */ esc_html__( 'Add New %s', 'calendas' ), esc_html__( 'Event', 'calendas' ) ),
					'edit_item'     => sprintf( /* translators: %s: Post type. */ esc_html__( 'Edit %s', 'calendas' ), esc_html__( 'Event', 'calendas' ) ),
				),
				'menu_icon'          => 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyMDBtbSIgaGVpZ2h0PSI4NDAuMDQiIHZpZXdCb3g9IjAgMCAyMDAgMjIyLjI2Ij48ZyBzdHlsZT0iaXNvbGF0aW9uOmlzb2xhdGUiPjxnIGZpbGw9Im5vbmUiIGZpbGwtb3BhY2l0eT0iMCIgc3Ryb2tlLXdpZHRoPSI4Ij48cGF0aCBzdHJva2U9IiNmZmYiIGQ9Ik0xLjk4MyA3Ni4xMDR2MTI5LjcyYzAgNS4zOTQgNC45NjkgOC4zODYgMTAuODMyIDExLjggNi4wNSAzLjUzIDkuMzkyIDIuNzEzIDEyLjI4OCAyLjM1bDE2NC4yOS0xOS41MTFjNi40Ni0uODEgOC42Mi01LjUyNCA4LjYyLTEwLjMxMlY1MS41NDF6IiB0cmFuc2Zvcm09Im1hdHJpeCguOTc4NjYgMCAwIC45OTA3MSAxLjk3NSAuMDkyKSIvPjxwYXRoIGQ9Ik03LjAwNSA3Ni41OTV2MTI0Ljk1OGMwIDQuNjc4IDMuNzQ4IDcuOTgzIDguMzUyIDcuNDA0bDE2Mi4zMi0yMC40MDZjNC42Mi0uNTgxIDguOTMzLTQuNjA1IDguOTMzLTkuMjgzVjU0LjAyM3oiLz48cGF0aCBzdHJva2U9IiNmZmYiIGQ9Ik0xOTguMDIgNTEuNTY0IDIgNzUuNjY1VjQ1LjAwMWMwLTQuMTMzIDQuNjY3LTguMDIgMTAuNDQ0LTguNjY4bDE3NS40NC0xOS43NTVjNS43OTQtLjY1MyAxMC40OCAyLjE5NSAxMC40NDQgNi4zNjV6IiB0cmFuc2Zvcm09Im1hdHJpeCguOTc4NjYgMCAwIC45OTA3MSAxLjk3NSAuMDkyKSIvPjwvZz48cGF0aCBmaWxsPSIjZmZmIiBkPSJNMTY5LjQ0IDE4Ny4xNmMtNS41NjEgMS4zNzMtMTEuMTIyIDIuMzQzLTE2LjY4MiAzLjUyMy01LjU2IDEuMDQtMTEuMTIxIDIuMDQ2LTE2LjY4MiAyLjkyOS01LjU2Ljk3LTExLjEyMSAxLjcxMy0xNi42ODIgMi41NmwtMTYuNjgxIDIuMjMtMTYuNjgyIDEuOTE0Yy01LjU2LjUzNC0xMS4xMjEgMS4xNTQtMTYuNjgyIDEuNTgyLTUuNTYuNDk5LTExLjEyLjg3NS0xNi42ODEgMS4yMTUtNS41Ni4yMDEtMTEuMTIxLjU5NS0xNi42ODIuNjJ2LS4zNDlsNjYuNzEtOC40NiA2Ni43MDgtOC4xMXYuMzV6Ii8+PGcgZmlsbD0iI2ZmZiIgdHJhbnNmb3JtPSJtYXRyaXgoMS43NDg2IC0uMjE3MTYgMCAxLjc0ODYgLTExLjkwOCAxNS44MTIpIj48ZWxsaXBzZSBjeD0iMTkuMTEiIGN5PSIxOS44IiByeD0iMi45NiIgcnk9IjMuMyIgc3R5bGU9Imlzb2xhdGlvbjppc29sYXRlIi8+PHBhdGggZD0iTTIwLjQ4IDE3LjA4Yy0uNDEtLjg2LS42OS0xLjkzLS42OS0zLjA4IDAtMi42NyAxLjM5LTQuOTMgMy4wNC00LjkzczMuMDQgMi4yNiAzLjA0IDQuOTNoNC40OGMwLTUuNDctMy4zNy05LjkzLTcuNTEtOS45M1MxNS4zMyA4LjUyIDE1LjMzIDE0YzAgMS45NS40MyAzLjc2IDEuMTYgNS4zLjY3IDEuMzkgMi4xIDIuMDEgMy4zNyAxLjM0IDEuMDYtLjU4IDEuMi0yLjM2LjYyLTMuNTYiIHN0eWxlPSJpc29sYXRpb246aXNvbGF0ZSIvPjxwYXRoIGQ9Ik0zMC4zNSAxNGMwLTMuMzUtMS4yNy02LjMxLTMuMTktOC4xMS4wMi4wNi41NyAxLjQ5LS4yMSAyLjg2LS43OSAxLjQtMi40MyAxLjE4LTIuNDMgMS4xOC44MS45IDEuMzUgMi40IDEuMzUgNC4wN3oiIHN0eWxlPSJpc29sYXRpb246aXNvbGF0ZSIvPjxlbGxpcHNlIGN4PSIzNS41OSIgY3k9IjE5LjgiIHJ4PSIyLjk2IiByeT0iMy4zIiBzdHlsZT0iaXNvbGF0aW9uOmlzb2xhdGUiLz48cGF0aCBkPSJNMzYuOTYgMTcuMDhjLS40MS0uODYtLjY5LTEuOTMtLjY5LTMuMDggMC0yLjY3IDEuMzktNC45MyAzLjA0LTQuOTNzMy4wNCAyLjI2IDMuMDQgNC45M2g0LjQ4YzAtNS40Ny0zLjM3LTkuOTMtNy41MS05LjkzUzMxLjgxIDguNTIgMzEuODEgMTRjMCAxLjk1LjQzIDMuNzYgMS4xNiA1LjMuNjcgMS4zOSAyLjEgMi4wMSAzLjM3IDEuMzQgMS4wNi0uNTggMS4yLTIuMzYuNjItMy41NiIgc3R5bGU9Imlzb2xhdGlvbjppc29sYXRlIi8+PHBhdGggZD0iTTQzLjY0IDUuODljLjAyLjA2LjU3IDEuNDktLjIxIDIuODZDNDIuNjQgMTAuMTQgNDEgOS45MyA0MSA5LjkzYy44MS45IDEuMzUgMi40IDEuMzUgNC4wN2g0LjQ5YzAtMy4zNS0xLjI4LTYuMzEtMy4yLTguMTEiIHN0eWxlPSJpc29sYXRpb246aXNvbGF0ZSIvPjxlbGxpcHNlIGN4PSI1Mi4wNiIgY3k9IjE5LjgiIHJ4PSIyLjk2IiByeT0iMy4zIi8+PHBhdGggZD0iTTUzLjQ0IDE3LjA4Yy0uNDEtLjg2LS42OS0xLjkzLS42OS0zLjA4IDAtMi42NyAxLjM5LTQuOTMgMy4wNC00LjkzczMuMDQgMi4yNiAzLjA0IDQuOTNoNC40OGMwLTUuNDctMy4zNy05LjkzLTcuNTEtOS45M1M0OC4yOSA4LjUyIDQ4LjI5IDE0YzAgMS45NS40MyAzLjc2IDEuMTYgNS4zLjY3IDEuMzkgMi4xIDIuMDEgMy4zNyAxLjM0IDEuMDYtLjU4IDEuMi0yLjM2LjYyLTMuNTYiLz48cGF0aCBkPSJNNjAuMTEgNS44OWMuMDIuMDYuNTcgMS40OS0uMjEgMi44Ni0uNzkgMS40LTIuNDMgMS4xOC0yLjQzIDEuMTguODEuOSAxLjM1IDIuNCAxLjM1IDQuMDdoNC41MWMuMDEtMy4zNS0xLjI5LTYuMzEtMy4yMi04LjExIi8+PGVsbGlwc2UgY3g9IjY4LjU0IiBjeT0iMTkuOCIgcng9IjIuOTYiIHJ5PSIzLjMiLz48cGF0aCBkPSJNNjkuOTIgMTcuMDhjLS40MS0uODYtLjY5LTEuOTMtLjY5LTMuMDggMC0yLjY3IDEuMzktNC45MyAzLjA0LTQuOTNzMy4wNCAyLjI2IDMuMDQgNC45M2g0LjQ4YzAtNS40Ny0zLjM3LTkuOTMtNy41MS05LjkzUzY0Ljc3IDguNTIgNjQuNzcgMTRjMCAxLjk1LjQzIDMuNzYgMS4xNiA1LjMuNjcgMS4zOSAyLjEgMi4wMSAzLjM3IDEuMzQgMS4wNi0uNTggMS4xOS0yLjM2LjYyLTMuNTYiLz48cGF0aCBkPSJNNzYuNTkgNS44OWMuMDIuMDYuNTcgMS40OS0uMjEgMi44Ni0uNzkgMS40LTIuNDMgMS4xOC0yLjQzIDEuMTguODEuOSAxLjM1IDIuNCAxLjM1IDQuMDdoNC41MmMuMDEtMy4zNS0xLjMtNi4zMS0zLjIzLTguMTEiLz48ZWxsaXBzZSBjeD0iODUuMDIiIGN5PSIxOS44IiByeD0iMi45NiIgcnk9IjMuMyIvPjxwYXRoIGQ9Ik04Ni4zOSAxNy4wOGMtLjQxLS44Ni0uNjktMS45My0uNjktMy4wOCAwLTIuNjcgMS4zOS00LjkzIDMuMDQtNC45M3MzLjA0IDIuMjYgMy4wNCA0LjkzaDQuNDhjMC01LjQ3LTMuMzctOS45My03LjUxLTkuOTNTODEuMjQgOC41MiA4MS4yNCAxNGMwIDEuOTUuNDMgMy43NiAxLjE2IDUuMy42NyAxLjM5IDIuMSAyLjAxIDMuMzcgMS4zNCAxLjA2LS41OCAxLjItMi4zNi42Mi0zLjU2Ii8+PHBhdGggZD0iTTkzLjA3IDUuODljLjAyLjA2LjU3IDEuNDktLjIxIDIuODYtLjc5IDEuNC0yLjQzIDEuMTgtMi40MyAxLjE4LjgxLjkgMS4zNSAyLjQgMS4zNSA0LjA3aDQuNTRjMC0zLjM1LTEuMzMtNi4zMS0zLjI1LTguMTEiLz48ZWxsaXBzZSBjeD0iMTAxLjUiIGN5PSIyMC4xMDMiIHJ4PSIyLjk2IiByeT0iMy4zIi8+PHBhdGggZD0iTTEwMi44NyAxNy4zODNjLS40MS0uODYtLjY5LTEuOTMtLjY5LTMuMDggMC0yLjY3IDEuMzktNC45MyAzLjA0LTQuOTNzMy4wNCAyLjI2IDMuMDQgNC45M2g0LjQ4YzAtNS40Ny0zLjM3LTkuOTMtNy41MS05Ljkzcy03LjUxIDQuNDUtNy41MSA5LjkzYzAgMS45NS40MyAzLjc2IDEuMTYgNS4zLjY3IDEuMzkgMi4xIDIuMDEgMy4zNyAxLjM0IDEuMDYtLjU4IDEuMi0yLjM2LjYyLTMuNTYiLz48cGF0aCBkPSJNMTEyLjggMTQuNDgzYy0uMTUtNC4wMi0xLjQ1LTYuNi0zLjI2LTguMjguMDIuMDYuNTcgMS40OS0uMjEgMi44Ni0uNzkgMS40LTIuNDMgMS4xOC0yLjQzIDEuMTguODEuOSAxLjIxIDIuNCAxLjIxIDQuMDdoMS41M2MxLjI3LS4wMiAzLjE2LjE3IDMuMTYuMTciLz48L2c+PHBhdGggc3Ryb2tlPSIjZmZmIiBzdHJva2UtbGluZWNhcD0ic3F1YXJlIiBzdHJva2UtbWl0ZXJsaW1pdD0iMTAiIGQ9Ik0xNi42NiA0NS4xMnY2MS4zM20xMi45MS02MS4zM3Y2MS4zM20xMi45MS02MS4zM3Y2MS4zM001NS40IDQ1LjEydjYxLjMzbTEyLjkxLTYxLjMzdjYxLjMzbTEyLjkxLTYxLjMzdjYxLjMzbTEyLjkyLTYxLjMzdjYxLjMzbTEyLjkxLTYxLjMzdjYxLjMzbS05MC4zOSAwaDkwLjM5TTE2LjY2IDk0LjE4aDkwLjM5TTE2LjY2IDgxLjkyaDkwLjM5TTE2LjY2IDY5LjY1aDkwLjM5TTE2LjY2IDU3LjM5aDkwLjM5TTE2LjY2IDQ1LjEyaDkwLjM5IiBzdHlsZT0iaXNvbGF0aW9uOmlzb2xhdGUiIHRyYW5zZm9ybT0ibWF0cml4KDEuNzQ4NiAtLjIxNzE2IDAgMS43NDg2IC05LjI2MiAxMy42OTUpIi8+PC9nPjwvc3ZnPg==', // 'dashicons-calendar-alt',
				'public'             => true,
				'publicly_queryable' => true,
				'has_archive'        => false,
				'supports'           => array(
					'title',
					'editor' => array( 'notes' => true ),
					'excerpt',
					'author',
					'thumbnail',
					'revisions',
					'custom-fields',
				),
				'rewrite'            => array(
					'slug'       => defined( 'CALENDAS_EVENT_SLUG' ) ? CALENDAS_EVENT_SLUG : 'event',
					'with_front' => false,
				),
				'taxonomies'         => array( 'category' ),
				'show_ui'            => true,
				'show_in_rest'       => true,
			)
		);

		register_post_type(
			self::EVENT_VENUE_CPT,
			array(
				'labels'             => array(
					'name'          => esc_html__( 'Venues', 'calendas' ),
					'singular_name' => esc_html__( 'Venue', 'calendas' ),
					'add_new_item'  => sprintf( /* translators: %s: Post type. */ esc_html__( 'Add New %s', 'calendas' ), esc_html__( 'Venue', 'calendas' ) ),
					'edit_item'     => sprintf( /* translators: %s: Post type. */ esc_html__( 'Edit %s', 'calendas' ), esc_html__( 'Venue', 'calendas' ) ),
				),
				'menu_icon'          => 'dashicons-building',
				'public'             => true,
				'publicly_queryable' => true,
				'has_archive'        => false,
				'supports'           => array(
					'title',
					'editor' => array( 'notes' => true ),
					'excerpt',
					'author',
					'thumbnail',
					'revisions',
					'custom-fields',
				),
				'rewrite'            => array(
					'slug'       => defined( 'CALENDAS_VENUE_SLUG' ) ? CALENDAS_VENUE_SLUG : 'venue',
					'with_front' => false,
				),
				'taxonomies'         => array( 'category' ),
				'show_ui'            => true,
				'show_in_menu'       => 'edit.php?post_type=' . self::EVENT_CPT,
				'show_in_rest'       => true,
			)
		);

		register_post_type(
			self::EVENT_ORGANIZER_CPT,
			array(
				'labels'             => array(
					'name'          => esc_html__( 'Organizers', 'calendas' ),
					'singular_name' => esc_html__( 'Organizer', 'calendas' ),
					'add_new_item'  => sprintf( /* translators: %s: Post type. */ esc_html__( 'Add New %s', 'calendas' ), esc_html__( 'Organizer', 'calendas' ) ),
					'edit_item'     => sprintf( /* translators: %s: Post type. */ esc_html__( 'Edit %s', 'calendas' ), esc_html__( 'Organizer', 'calendas' ) ),
				),
				'menu_icon'          => 'dashicons-groups',
				'public'             => true,
				'publicly_queryable' => true,
				'has_archive'        => false,
				'supports'           => array(
					'title',
					'editor' => array( 'notes' => true ),
					'excerpt',
					'author',
					'thumbnail',
					'revisions',
					'custom-fields',
				),
				'rewrite'            => array(
					'slug'       => defined( 'CALENDAS_ORGANIZER_SLUG' ) ? CALENDAS_ORGANIZER_SLUG : 'organizer',
					'with_front' => false,
				),
				'taxonomies'         => array( 'category' ),
				'show_ui'            => true,
				'show_in_menu'       => 'edit.php?post_type=' . self::EVENT_CPT,
				'show_in_rest'       => true,
			)
		);

		/**
		 * Register Custom Post REST fields "event_data".
		 */
		register_rest_field(
			self::EVENT_CPT,
			'event_data',
			array(
				'get_callback' => function ( $data ): array {
					$post_id = $data['id'];

					$event_data = array(
						'start_datetime' => (int) get_post_meta( $post_id, '_' . self::EVENT_START_TIMESTAMP, true ),
						'end_datetime'   => (int) get_post_meta( $post_id, '_' . self::EVENT_END_TIMESTAMP, true ),
						'timezone'       => get_post_meta( $post_id, '_' . self::EVENT_TIMEZONE, true ),
						'all_day'        => get_post_meta( $post_id, '_' . self::EVENT_ALLDAY, true ),
						'featured'       => get_post_meta( $post_id, '_' . self::EVENT_FEATURED, true ),
						'url'            => get_post_meta( $post_id, '_' . self::EVENT_URL, true ),
						'cost'           => get_post_meta( $post_id, '_' . self::EVENT_COST, true ),
						'currency'       => get_post_meta( $post_id, '_' . self::EVENT_CURRENCY, true ),
						'cost_info'      => get_post_meta( $post_id, '_' . self::EVENT_COST_INFO, true ),
						'venue'          => get_post_meta( $post_id, '_' . self::EVENT_VENUE, true ),
						'organizer'      => get_post_meta( $post_id, '_' . self::EVENT_ORGANIZER, true ),
					);

					if ( ! empty( $event_data['venue'] ) ) {
						$event_data['venue_title'] = get_the_title( $event_data['venue'] );
						$event_data['venue_link'] = get_permalink( $event_data['venue'] );
					}

					if ( ! empty( $event_data['organizer'] ) ) {
						$event_data['organizer_title'] = get_the_title( $event_data['organizer'] );
						$event_data['organizer_link'] = get_permalink( $event_data['organizer'] );
					}

					if ( has_post_thumbnail( $post_id ) ) {
						$event_data['image'] = get_the_post_thumbnail( $post_id, 'post-thumbnail' );
						$event_data['image_src'] = get_the_post_thumbnail_url( $post_id, 'post-thumbnail' );
					}

					return $event_data;
				},
			)
		);

		register_rest_field(
			self::EVENT_VENUE_CPT,
			'event_data',
			array(
				'get_callback' => function ( $data ): array {
					$post_id = $data['id'];

					$event_data = array(
						'email'     => get_post_meta( $post_id, '_' . self::VENUE_EMAIL, true ),
						'phone'     => get_post_meta( $post_id, '_' . self::VENUE_PHONE, true ),
						'url'       => get_post_meta( $post_id, '_' . self::EVENT_URL, true ),
						'address'   => get_post_meta( $post_id, '_' . self::VENUE_ADDRESS, true ),
						'postcode'  => get_post_meta( $post_id, '_' . self::VENUE_POSTCODE, true ),
						'city'      => get_post_meta( $post_id, '_' . self::VENUE_CITY, true ),
						'state'     => get_post_meta( $post_id, '_' . self::VENUE_STATE, true ),
						'latitude'  => get_post_meta( $post_id, '_' . self::VENUE_LATITUDE, true ),
						'longitude' => get_post_meta( $post_id, '_' . self::VENUE_LONGITUDE, true ),
					);

					return $event_data;
				},
			)
		);

		register_rest_field(
			self::EVENT_ORGANIZER_CPT,
			'event_data',
			array(
				'get_callback' => function ( $data ): array {
					$post_id = $data['id'];

					$event_data = array(
						'email' => get_post_meta( $post_id, '_' . self::ORGANIZER_EMAIL, true ),
						'phone' => get_post_meta( $post_id, '_' . self::ORGANIZER_PHONE, true ),
						'url'   => get_post_meta( $post_id, '_' . self::ORGANIZER_URL, true ),
					);

					return $event_data;
				},
			)
		);

		/**
		 * Language field: Polylang/WPML compatibility.
		 *
		 * @param array $data Post data.
		 *
		 * @return mixed
		 */
		$register_language_field = function ( $data ): mixed {
			$post_id = $data['id'];

			if ( empty( $post_id ) ) {
				return null;
			}

			if ( function_exists( 'pll_get_post_language' ) ) {
				// Polylang: https://polylang.wordpress.com/documentation/documentation-for-developers/functions-reference/#pll_get_post_language
				return pll_get_post_language( $post_id );
			} elseif ( class_exists( 'SitePress' ) ) {
				// WPML: https://wpml.org/wpml-hook/wpml_post_language_details
				return apply_filters( 'wpml_post_language_details', null, $post_id )['language_code'];
			}

			return null;
		};

		register_rest_field(
			self::EVENT_CPT,
			'language',
			array(
				'methods'      => 'GET',
				'get_callback' => $register_language_field,
			)
		);

		register_rest_field(
			self::EVENT_VENUE_CPT,
			'language',
			array(
				'methods'      => 'GET',
				'get_callback' => $register_language_field,
			)
		);

		register_rest_field(
			self::EVENT_ORGANIZER_CPT,
			'language',
			array(
				'methods'      => 'GET',
				'get_callback' => $register_language_field,
			)
		);

		/**
		 * Register post metas.
		 */
		$field_args = array(
			'show_in_rest'      => true,
			'single'            => true,
			'type'              => 'string',
			'revisions_enabled' => true,
			'auth_callback'     => function () {
				return current_user_can( 'edit_posts' ); // https://wordpress.org/support/topic/api-not-allowed-to-edit-custom-field/#post-16770334
			},
		);

		// Events.
		$fields = array(
			'_' . self::EVENT_FEATURED,
			'_' . self::EVENT_START_TIMESTAMP,
			'_' . self::EVENT_END_TIMESTAMP,
			'_' . self::EVENT_ALLDAY,
			'_' . self::EVENT_TIMEZONE,
			'_' . self::EVENT_URL,
			'_' . self::EVENT_COST,
			'_' . self::EVENT_CURRENCY,
			'_' . self::EVENT_COST_INFO,
			'_' . self::EVENT_VENUE,
			'_' . self::EVENT_ORGANIZER,
		);

		foreach ( $fields as $field ) {
			register_post_meta( self::EVENT_CPT, $field, $field_args );
		}

		// Organizers.
		$fields = array(
			'_' . self::ORGANIZER_EMAIL,
			'_' . self::ORGANIZER_PHONE,
			'_' . self::ORGANIZER_URL,
		);

		foreach ( $fields as $field ) {
			register_post_meta( self::EVENT_ORGANIZER_CPT, $field, $field_args );
		}

		// Venues.
		$fields = array(
			'_' . self::VENUE_EMAIL,
			'_' . self::VENUE_PHONE,
			'_' . self::VENUE_URL,
			'_' . self::VENUE_ADDRESS,
			'_' . self::VENUE_CITY,
			'_' . self::VENUE_STATE,
			'_' . self::VENUE_POSTCODE,
			'_' . self::VENUE_LATITUDE,
			'_' . self::VENUE_LONGITUDE,
		);

		foreach ( $fields as $field ) {
			register_post_meta( self::EVENT_VENUE_CPT, $field, $field_args );
		}

		/**
		 * Register blocks.
		 */
		if ( function_exists( 'register_block_type' ) ) {
			$block_json_files = glob( __DIR__ . '/../blocks/build/*/block.json' );

			// Autoregister all blocks found in the `build/blocks` folder.
			foreach ( $block_json_files as $filename ) {
				register_block_type( $filename );
			}
		}
	}

	/**
	 * Enqueue scripts.
	 *
	 * @return void
	 */
	public function enqueue_scripts(): void {
		wp_enqueue_style(
			'calendas-style',
			plugins_url( 'assets/style.css', __DIR__ ),
			array(),
			'1.0'
		);

		if ( has_block( 'calendas/events' ) || has_block( 'calendas/upcoming-events' ) || has_block( 'core/query' ) /* TODO: Also restrict to "events" CPT. */ ) {
			// Needed for JS fetch: wp.apiFetch() and JS date: wp.date().
			wp_register_script( 'api-fetch', false, array( 'wp-data', 'wp-api-fetch', 'wp-date' ), '1.0', true );
			wp_enqueue_script( 'api-fetch' );
		}
	}

	/**
	 * Output "This event has passed".
	 *
	 * @param int|null $post_id Post ID.
	 *
	 * @return string
	 */
	public function output_passed_event( $post_id = null ): string {
		if ( empty( $post_id ) ) {
			$post_id = get_the_ID();
		}

		if ( get_post_meta( $post_id, '_' . self::EVENT_END_TIMESTAMP, true ) < wp_date( 'U' ) ) {
			$notice = '<p class="notice notice-event notice-passed-event">' . esc_html__( 'This event has passed', 'calendas' ) . '</p><hr>';

			$notice = apply_filters( 'calendas_passed_event_message', $notice, $post_id );

			return $notice;
		}

		return '';
	}

	/**
	 * Get page that contains an "events-calendar" block and output as link.
	 *
	 * @return string
	 */
	public function output_events_pagelink(): string {
		// TODO: Optionally define page in Theme Customizer? Save/Cache ID with Transients API or similar!
		$events_page_query = new WP_Query(
			array(
				'post_type' => 'page',
				's'         => 'calendas/events',
				'fields'    => 'ids',
			)
		);

		$page_found = false;

		if ( $events_page_query->have_posts() ) {
			foreach ( $events_page_query->posts as $page_id ) {
				// Get first page that contains an "Events Calendar" block.
				if ( has_block( 'calendas/events', $page_id ) ) {
					$page_found = $page_id;
					break;
				}
			}
			wp_reset_postdata();
		}

		if ( $page_found ) {
			$events_link = get_permalink( $page_found );

			return '<p><a href="' . esc_url( $events_link ) . '">' . sprintf( /* translators: %s: Back arrow. */ esc_html__( '%s Events', 'calendas' ), '←' ) . '</a></p>';
		}

		return '';
	}

	/**
	 * Helper function to convert a datetime string to a UNIX timestamp for database storing.
	 *
	 * @param string $timestamp Timestamp.
	 * @param string $timezone  Timezone.
	 *
	 * @return int|string
	 */
	public function convert_datetime_to_unix( $timestamp, $timezone = '' ): int|string {
		if ( empty( $timestamp ) ) {
			return '';
		}

		if ( empty( $timezone ) ) {
			$timezone = wp_timezone_string();
		}

		$date = new DateTime( $timestamp, new DateTimeZone( $timezone ) );

		// Return the Unix timestamp.
		return $date->getTimestamp();
	}

	/**
	 * Helper function to output the formatted events datetime string.
	 *
	 * @param int    $start_timestamp Start datetime.
	 * @param int    $end_timestamp   End datetime.
	 * @param string $timezone        Timezone.
	 *
	 * @return string
	 */
	public function output_event_datetime( $start_timestamp, $end_timestamp, $timezone = '' ): string {
		$date_format     = get_option( 'date_format' );
		$time_format     = get_option( 'time_format' );
		$system_timezone = wp_timezone_string();

		if ( ! empty( $timezone ) && $system_timezone !== $timezone ) {
			$different_timezone = true;
		} else {
			$timezone = $system_timezone;
		}

		$date_timezone = new DateTimeZone( $timezone );

		$start_date_unformatted = wp_date( 'Y-m-d\TH:i:sP', $start_timestamp, $date_timezone );
		$start_datetime_string  = wp_date( "{$date_format} {$time_format}", $start_timestamp, $date_timezone );
		$start_date_string      = wp_date( $date_format, $start_timestamp, $date_timezone );
		$start_time_string      = wp_date( $time_format, $start_timestamp, $date_timezone );

		$end_date_unformatted = wp_date( 'Y-m-d\TH:i:sP', $end_timestamp, $date_timezone );
		$end_datetime_string  = wp_date( "{$date_format} {$time_format}", $end_timestamp, $date_timezone );
		$end_date_string      = wp_date( $date_format, $end_timestamp, $date_timezone );
		$end_time_string      = wp_date( $time_format, $end_timestamp, $date_timezone );

		// "All day" event (00:00 - 23:59): Remove time output.
		$is_all_day = '00:00' === wp_date( 'H:i', $start_timestamp, $date_timezone ) && '23:59' === wp_date( 'H:i', $end_timestamp, $date_timezone );

		if ( $is_all_day ) {
			if ( $start_date_string === $end_date_string ) {
				return trim( sprintf( /* translators: %1$s: Date unformatted, %2$s: Start date, %3$s: End date. */ __( '<time datetime="%1$s">%2$s</time> - %3$s', 'calendas' ), $start_date_unformatted, $start_date_string, __( 'All day', 'calendas' ) ) );
			}

			$start_date_formatted = $start_date_string;
			$end_date_formatted   = $end_date_string;
		} elseif ( $start_date_string === $end_date_string ) {
			$start_date_formatted = is_single() ? $start_datetime_string : $start_time_string;
			$end_date_formatted   = $end_time_string;
		} else {
			$start_date_formatted = $start_datetime_string;
			$end_date_formatted   = $end_datetime_string;
		}

		$output = trim( sprintf( /* translators: %1$s: Start date unformatted, %2$s: Start date formatted, %3$s: End date unformatted , %4$s: End date formatted. */ __( '<time datetime="%1$s">%2$s</time> - <time datetime="%3$s">%4$s</time>', 'calendas' ), $start_date_unformatted, $start_date_formatted, $end_date_unformatted, $end_date_formatted ) );

		if ( isset( $different_timezone ) ) {
			// Event timezone is different from system.
			$formatted_timezone = str_replace( '_', ' ', $timezone );

			return trim( sprintf( /* translators: %1$s: Date string, %2$s: Timezone. */ __( '%1$s <small>%2$s</small>', 'calendas' ), $output, $formatted_timezone ) );
		}

		return $output;
	}

	/**
	 * Modify post content.
	 *
	 * @param string $content Post content.
	 *
	 * @return string
	 */
	public function modify_content( $content ): string {
		$post_type = get_post_type();

		if ( ! is_single() || ! in_array( $post_type, array( self::EVENT_CPT, self::EVENT_VENUE_CPT, self::EVENT_ORGANIZER_CPT ), true ) ) {
			return $content;
		}

		$post_id = get_the_ID();

		// 1. Output before post content.
		$output_before = '';

		if ( self::EVENT_CPT === $post_type ) {
			$start_timestamp = esc_html( get_post_meta( $post_id, '_' . self::EVENT_START_TIMESTAMP, true ) );
			$end_timestamp   = esc_html( get_post_meta( $post_id, '_' . self::EVENT_END_TIMESTAMP, true ) );
			$timezone        = esc_html( get_post_meta( $post_id, '_' . self::EVENT_TIMEZONE, true ) );

			$output_before = $this->output_events_pagelink();

			$output_before .= $this->output_passed_event();

			$output_before .= '<p class="event-date">' . sprintf( /* translators: %1$s: Icon, %2$s: Date formatted. */ __( '%1$s %2$s', 'calendas' ), '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" style="width: 1.25rem;"><g stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M12 8v4l3 3"/><circle cx="12" cy="12" r="9"/></g></svg>', $this->output_event_datetime( $start_timestamp, $end_timestamp, $timezone ) ) . '</p>';
		}

		$output_before = apply_filters( 'calendas_output_before_content', $output_before );

		// 2. Post content.
		if ( self::EVENT_ORGANIZER_CPT === $post_type ) {
			$url = esc_html( get_post_meta( $post_id, '_' . self::ORGANIZER_URL, true ) );
			if ( ! empty( $url ) ) {
				$url_clean = wp_parse_url( $url )['host']; // Get host from url.
			}

			$phone = esc_html( get_post_meta( $post_id, '_' . self::ORGANIZER_PHONE, true ) );
			if ( ! empty( $phone ) ) {
				$phone_clean = preg_replace( array( '/\(\d+\)/', '/\s+/', '/\//', '/\-/' ), '', $phone ); // 1. Remove "(#)", 2. Remove whitespaces, 3. Remove dashes.
			}

			$trs = array(
				esc_html__( 'Weblink', 'calendas' ) => empty( $url_clean ) ? '' : '<a href="' . esc_url( $url ) . '">' . esc_html( $url_clean ) . '</a>',
				esc_html__( 'Phone', 'calendas' )   => empty( $phone_clean ) ? '' : '<a href="tel:' . esc_attr( $phone_clean ) . '">' . esc_html( $phone ) . '</a>',
			);

			// Table details.
			$trs = array_filter( $trs );

			if ( ! empty( $trs ) ) {
				$content .= '<hr><table class="event-details">';
				foreach ( $trs as $key => $val ) {
					if ( empty( $val ) ) {
						continue;
					}

					$content .= '<tr><td><strong>' . esc_html( $key ) . '</strong></td><td>' . $val . '</td></tr>';
				}
				$content .= '</table>';
			}
		} elseif ( self::EVENT_VENUE_CPT === $post_type ) {
			$url       = esc_html( get_post_meta( $post_id, '_' . self::VENUE_URL, true ) );
			$email     = esc_html( get_post_meta( $post_id, '_' . self::VENUE_EMAIL, true ) );
			$phone     = esc_html( get_post_meta( $post_id, '_' . self::VENUE_PHONE, true ) );
			$address   = wp_kses( get_post_meta( $post_id, '_' . self::VENUE_ADDRESS, true ) );
			$postcode  = esc_html( get_post_meta( $post_id, '_' . self::VENUE_POSTCODE, true ) );
			$city      = esc_html( get_post_meta( $post_id, '_' . self::VENUE_CITY, true ) );
			$state     = esc_html( get_post_meta( $post_id, '_' . self::VENUE_STATE, true ) );
			$latitude  = esc_html( get_post_meta( $post_id, '_' . self::VENUE_LATITUDE, true ) );
			$longitude = esc_html( get_post_meta( $post_id, '_' . self::VENUE_LONGITUDE, true ) );

			$trs = array(
				esc_html__( 'Weblink', 'calendas' )  => empty( $url ) ? '' : '<a href="' . esc_url( $url ) . '">' . esc_url( $url ) . '</a>',
				esc_html__( 'Email', 'calendas' )    => $email,
				esc_html__( 'Phone', 'calendas' )    => empty( $phone ) ? '' : '<a href="tel:' . esc_attr( $phone ) . '">' . esc_html( $phone ) . '</a>', // TODO: Output domain instead of full url.
				esc_html__( 'Address', 'calendas' )  => $address,
				esc_html__( 'Postcode', 'calendas' ) => $postcode,
				esc_html__( 'City', 'calendas' )     => $city,
				esc_html__( 'State', 'calendas' )    => $state,
			);

			if ( ! empty( $latitude ) && ! empty( $longitude ) ) {
				$trs[''] = $this->google_maps_link( $latitude, $longitude, get_the_title( $post_id ) );
			}

			// Table details.
			$trs = array_filter( $trs );

			if ( ! empty( $trs ) ) {
				$content .= '<hr><table class="event-details">';
				foreach ( $trs as $key => $val ) {
					if ( empty( $val ) ) {
						continue;
					}

					$content .= '<tr><td><strong>' . esc_html( $key ) . '</strong></td><td>' . $val . '</td></tr>';
				}
				$content .= '</table>';

				if ( ! empty( $latitude ) && ! empty( $longitude ) ) {
					$content .= $this->google_maps_iframe( $latitude, $longitude, get_the_title( $post_id ) );
				}
			}
		}

		$content = apply_filters( 'calendas_output_content', $content );

		// 3. Output after post content.
		$output_after = '';

		if ( self::EVENT_CPT === $post_type ) {
			$url       = esc_html( get_post_meta( $post_id, '_' . self::EVENT_URL, true ) );
			$cost      = esc_html( get_post_meta( $post_id, '_' . self::EVENT_COST, true ) );
			$cost_info = esc_html( get_post_meta( $post_id, '_' . self::EVENT_COST_INFO, true ) );
			$organizer = esc_html( get_post_meta( $post_id, '_' . self::EVENT_ORGANIZER, true ) );
			$venue     = esc_html( get_post_meta( $post_id, '_' . self::EVENT_VENUE, true ) );

			$trs = array(
				esc_html__( 'Weblink', 'calendas' ) => empty( $url ) ? '' : '<a href="' . esc_url( $url ) . '">' . esc_html( $url ) . '</a>', // TODO: Output domain instead of full url.
			);

			if ( ! empty( $cost ) ) {
				$currency = esc_html( get_post_meta( $post_id, '_' . self::EVENT_CURRENCY, true ) );
				// Output formatted number string with currency based on current locale: https://www.php.net/manual/en/numberformatter.formatcurrency.php
				if ( class_exists( 'NumberFormatter' ) ) {
					$fmt            = new NumberFormatter( get_locale(), NumberFormatter::CURRENCY );
					$cost_formatted = $fmt->formatCurrency( $cost, $currency );
				} else {
					$cost_formatted = '<span title="' . esc_attr( $currency ) . '">' . esc_html( $cost ) . '</span>';
				}

				$trs[ esc_html__( 'Cost', 'calendas' ) ] = empty( $cost_info ) ? $cost_formatted : sprintf( /* translators: %1$s: Cost formatted, %2$s: Cost info. */ __( '%1$s<br>%2$s', 'calendas' ), $cost_formatted, '<small>' . $cost_info . '</small>' );
			}

			if ( ! empty( $organizer ) ) {
				$trs[ esc_html__( 'Organizer', 'calendas' ) ] = '<a href="' . esc_url( get_permalink( $organizer ) ) . '">' . get_the_title( $organizer ) . '</a>';
			}

			if ( ! empty( $venue ) ) {
				$trs[ esc_html__( 'Venue', 'calendas' ) ] = '<a href="' . esc_url( get_permalink( $venue ) ) . '">' . get_the_title( $venue ) . '</a>';

				$address   = wp_kses( get_post_meta( $venue, '_' . self::VENUE_ADDRESS, true ) );
				$postcode  = esc_html( get_post_meta( $venue, '_' . self::VENUE_POSTCODE, true ) );
				$city      = esc_html( get_post_meta( $venue, '_' . self::VENUE_CITY, true ) );
				$state     = esc_html( get_post_meta( $venue, '_' . self::VENUE_STATE, true ) );
				$latitude  = esc_html( get_post_meta( $venue, '_' . self::VENUE_LATITUDE, true ) );
				$longitude = esc_html( get_post_meta( $venue, '_' . self::VENUE_LONGITUDE, true ) );

				$trs[ esc_html__( 'Address', 'calendas' ) ]  = $address;
				$trs[ esc_html__( 'Postcode', 'calendas' ) ] = $postcode;
				$trs[ esc_html__( 'City', 'calendas' ) ]     = $city;
				$trs[ esc_html__( 'State', 'calendas' ) ]    = $state;
				$trs['']                                     = $this->google_maps_link( $latitude, $longitude, get_the_title( $venue ) );
			}

			// Table details.
			$trs = array_filter( $trs );

			if ( ! empty( $trs ) ) {
				$output_after .= '<hr><table class="event-details">';
				foreach ( $trs as $key => $val ) {
					if ( empty( $val ) ) {
						continue;
					}

					$output_after .= '<tr><td><strong>' . $key . '</strong></td><td>' . $val . '</td></tr>';
				}
				$output_after .= '</table>';

				if ( ! empty( $latitude ) && ! empty( $longitude ) ) {
					$output_after .= $this->google_maps_iframe( $latitude, $longitude, get_the_title( $venue ) );
				}
			}
		}

		$output_after = apply_filters( 'calendas_output_after_content', $output_after );

		// Return combined outputs.
		return wp_kses_post( $output_before . $content . $output_after );
	}

	/**
	 * Update the query of the Query Loop block, if the block is a variation of the Query Loop block.
	 * So it loads the events in right order.
	 *
	 * https://developer.wordpress.org/block-editor/how-to-guides/block-tutorial/extending-the-query-loop-block/#making-your-custom-query-work-on-the-front-end-side
	 *
	 * @param string|null $pre_render   The pre-rendered content. Default null.
	 * @param array       $parsed_block The block being rendered.
	 *
	 * @return string|null
	 */
	public function update_events_query( $pre_render, $parsed_block ): string|null {
		if ( 'core/query' !== $parsed_block['blockName'] ) {
			return $pre_render;
		}

		global $wp_query;

		$post_type = $parsed_block['attrs']['query']['postType'] ?? '';

		if ( self::EVENT_CPT !== $post_type ) {
			return $pre_render;
		}

		add_filter(
			'query_loop_block_query_vars',
			function ( $query ) use ( $wp_query ): mixed {
				$query['post_type']           = self::EVENT_CPT;
				$query['posts_per_page']      = -1;
				$query['ignore_sticky_posts'] = true;
				$query['order']               = 'ASC';
				$query['orderby']             = 'meta_value_num';

				$custom_meta = array(
					array(
						'key'     => '_' . self::EVENT_END_TIMESTAMP,
						'value'   => strtotime( 'today' ), // 00:00:00 today.
						'compare' => '>=',
						'type'    => 'NUMERIC',
					),
				);

				// /query/passed-events - Show passed events in DESC order.
				if ( isset( $wp_query->query[ self::QUERY ] ) && self::PASSED === wp_unslash( $wp_query->query[ self::QUERY ] ) ) {
					$query['order'] = 'DESC';
					$custom_meta    = array(
						array(
							'key'     => '_' . self::EVENT_START_TIMESTAMP,
							'value'   => strtotime( 'tomorrow' ) - 1, // 23:59:59 today.
							'compare' => '<=',
							'type'    => 'NUMERIC',
						),
					);
				}

				if ( ! empty( $query['meta_query'] ) && is_array( $query['meta_query'] ) ) {
					// Preserve existing queries and append custom query.
					$query['meta_query'] = array_merge( $query['meta_query'], $custom_meta );
				} else {
					$query['meta_query'] = $custom_meta;
				}

				return $query;
			},
			10,
			1
		);

		return $pre_render;
	}

	/**
	 * Add Current/Passed events button before CPT "Query Loop" blocks.
	 *
	 * @param string $block_content The block content.
	 * @param array  $block         The full block, including name and attributes.
	 *
	 * @return string
	 */
	public function modify_query_block( $block_content, $block ): string {
		if ( str_contains( $block_content, 'data-type="upcoming-events"' ) ) {
			return $block_content;
		}

		if ( is_page() && self::EVENT_CPT === $block['attrs']['query']['postType'] ) {
			global $wp_query;

			$button_text = isset( $wp_query->query[ self::QUERY ] ) && self::PASSED === wp_unslash( $wp_query->query[ self::QUERY ] ) ? sprintf( /* translators: %1$s: Events, %2$s: Arrow. */ esc_html__( 'Current/Upcoming %1$s %2$s', 'calendas' ), __( 'Events', 'calendas' ), '→' ) : sprintf( /* translators: %1$s: Arrow, %2$s: Events. */ __( '%1$s Passed %2$s', 'calendas' ), '←', __( 'Events', 'calendas' ) );
			$button_link = isset( $wp_query->query[ self::QUERY ] ) && self::PASSED === wp_unslash( $wp_query->query[ self::QUERY ] ) ? '' : self::QUERY . '/' . self::PASSED;

			return '<p><a href="' . esc_url( get_permalink() . $button_link ) . '">' . $button_text . '</a></p>' . $block_content;
		}

		return $block_content;
	}

	/**
	 * Post Date Block.
	 *
	 * @param string   $block_content The block content.
	 * @param array    $block         The full block, including name and attributes.
	 * @param WP_Block $instance      The block instance.
	 *
	 * @return string
	 */
	public function modify_post_date_block( $block_content, $block, $instance ): string {
		if ( self::EVENT_CPT !== $instance->context['postType'] ) {
			return $block_content;
		}

		$post_id = get_the_ID();

		$start_timestamp = get_post_meta( $post_id, '_' . self::EVENT_START_TIMESTAMP, true );
		$end_timestamp   = get_post_meta( $post_id, '_' . self::EVENT_END_TIMESTAMP, true );
		$timezone        = get_post_meta( $post_id, '_' . self::EVENT_TIMEZONE, true );

		return '<div class="wp-block-post-date">' . $this->output_event_datetime( $start_timestamp, $end_timestamp, $timezone ) . '</div>';
	}

	/**
	 * Sort Events by Start date.
	 * https://developer.wordpress.org/reference/hooks/pre_get_posts/
	 *
	 * @param WP_Query $query The WP_Query instance (passed by reference).
	 *
	 * @return void
	 */
	public function sort_events_by_start_date( $query ): void {
		if ( self::EVENT_CPT === $query->get( 'post_type' ) ) {
			if ( ! is_admin() && $query->is_main_query() ) {
				// Include future posts in all queries.
				$query->set( 'post_status', array( 'publish', 'future' ) );
			}

			$query->set( 'meta_key', '_' . self::EVENT_START_TIMESTAMP );
			$query->set( 'orderby', 'meta_value' );
		}
	}
}
