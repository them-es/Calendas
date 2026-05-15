<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Events calendar class for the admin area.
 */
class Calendas_Admin extends Calendas {
	/**
	 * Construct.
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'wp_dashboard_setup', array( $this, 'add_wp_dashboard_widget' ), 100 );

		add_filter( 'manage_' . self::EVENT_CPT . '_posts_columns', array( $this, 'posts_columns' ), 5 );
		add_action( 'manage_' . self::EVENT_CPT . '_posts_custom_column', array( $this, 'posts_custom_columns' ), 5, 2 );

		add_filter( 'wp_insert_post_data', array( $this, 'ignore_future_post_status' ), 10 );

		add_filter( 'pll_copy_post_metas', array( $this, 'copy_polylang_post_metas' ), 10, 4 );

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_assets' ) );
	}

	/**
	 * Add dashboard widget.
	 *
	 * @return void
	 */
	public function add_wp_dashboard_widget(): void {
		wp_add_dashboard_widget(
			'calendas_dashboard',
			sprintf( /* translators: %s: Locale. */ esc_html__( 'Calendas Events', 'calendas' ) ),
			array( $this, 'output_wp_dashboard_widget' )
		);
	}

	/**
	 * Output the dashboard widget content.
	 *
	 * @return void
	 */
	public function output_wp_dashboard_widget(): void {
		$output      = '<div style="background-color: #f6f7f7; padding: 1rem; margin-bottom: 1.5rem; border-radius: 0.5rem;">';
		$output     .= '<div style="display: flex; justify-content: space-between;">';
			$output .= '<p><a href="https://them.es/plugins/calendas/"><img src="' . plugins_url( 'assets/logo.png', __DIR__ ) . '" alt="Calendas" style="width: 110px;" /></a></p>';
			$output .= '<p><strong><a href="' . esc_url( 'https://wordpress.org/support/plugin/' . self::$plugin_slug . '/reviews/#new-post' ) . '">' . sprintf( /* translators: %s: Icon. */ esc_html__( 'Please rate this Plugin %s', 'calendas' ), '<span class="dashicons dashicons-external" aria-hidden="true"></span>' ) . '</a></strong><br><span class="dashicons dashicons-star-filled" aria-hidden="true"></span><span class="dashicons dashicons-star-filled" aria-hidden="true"></span><span class="dashicons dashicons-star-filled" aria-hidden="true"></span><span class="dashicons dashicons-star-filled" aria-hidden="true"></span><span class="dashicons dashicons-star-filled" aria-hidden="true"></span></p>';
		$output     .= '</div>';

		if ( class_exists( 'Calendas_Pro' ) ) {
			$output .= '<p><span class="dashicons dashicons-smiley" aria-hidden="true"></span> ' . sprintf( /* translators: %s: Plugin name. */ __( 'Thank you for purchasing %s', 'calendas' ), '<strong>Calendas Pro</strong>' ) . '</p><hr>';
		} else {
			$output .= '<h2>' . esc_html__( 'Looking for additional features?', 'calendas' ) . '</h2>';
			$output .= '<p>' . sprintf( /* translators: %1$s: URL, %2$s: Product name. */ __( '<a href="%1$s">%2$s</a> is a premium add-on that includes recurring events, RSVP + ticket sales, as well as easy data import.', 'calendas' ), 'https://them.es/plugins/calendas', '<strong>' . self::$plugin_name . ' PRO</strong>' ) . '</p><hr>';
		}

		$output .= '<h2>' . esc_html__( 'Need help?', 'calendas' ) . '</h2>';
		$output .= '<p>' . wp_kses_post( sprintf( /* translators: %s: URL. */ __( 'You can find docs, showcases, FAQs and more on our <a href="%s">website</a>.', 'calendas' ), 'https://them.es/plugins/calendas/help' ) ) . '</p>';
		$output .= '</div>';

		$output .= do_blocks( '<!-- wp:calendas/upcoming-events {"title": "' . sprintf( /* translators: %s: Locale. */ esc_attr__( 'Upcoming Events (%s)', 'calendas' ), esc_attr( strtoupper( mb_substr( get_locale(), 0, 2 ) ) ) ) . '","showVenue":true,"showOrganizer":true} /-->' );

		echo wp_kses_post( $output );
	}

	/**
	 * Show additional posts columns: https://code.tutsplus.com/articles/add-a-custom-column-in-posts-and-custom-post-types-admin-screen--wp-24934/
	 *
	 * @param array $columns Default columns.
	 *
	 * @return array
	 */
	public function posts_columns( $columns ): array {
		$columns[ self::EVENT_START_TIMESTAMP ] = esc_html__( 'Start', 'calendas' );
		$columns[ self::EVENT_END_TIMESTAMP ]   = esc_html__( 'End', 'calendas' );
		$columns['event_meta']                  = esc_html__( 'Event Meta', 'calendas' );

		$columns['post_featuredimage'] = '<span class="dashicons dashicons-format-image"></span>';

		return $columns;
	}

	/**
	 * Additional posts columns: Content
	 * https://codex.wordpress.org/Plugin_API/Action_Reference/manage_posts_custom_column
	 *
	 * @param string $column  Posts column.
	 * @param int    $post_id Post ID.
	 *
	 * @return void
	 */
	public function posts_custom_columns( $column, $post_id ): void {
		$timezone = get_post_meta( $post_id, '_' . self::EVENT_TIMEZONE, true );

		if ( empty( $timezone ) ) {
			$timezone = wp_timezone_string();
		}

		$date_format = get_option( 'date_format' );
		$time_format = get_option( 'time_format' );

		switch ( $column ) {
			case self::EVENT_START_TIMESTAMP:
				$start_datetime = get_post_meta( $post_id, '_' . self::EVENT_START_TIMESTAMP, true );

				echo wp_kses_post( wp_date( "{$date_format} {$time_format}", $start_datetime, new DateTimeZone( $timezone ) ) . ( wp_timezone_string() !== $timezone ? '<br><small>' . esc_html( $timezone ) . '</small>' : '' ) );
				break;

			case self::EVENT_END_TIMESTAMP:
				$end_datetime = get_post_meta( $post_id, '_' . self::EVENT_END_TIMESTAMP, true );

				echo wp_kses_post( wp_date( "{$date_format} {$time_format}", $end_datetime, new DateTimeZone( $timezone ) ) . ( wp_timezone_string() !== $timezone ? '<br><small>' . esc_html( $timezone ) . '</small>' : '' ) );
				break;

			case 'event_meta':
				$organizer = get_post_meta( $post_id, '_' . self::EVENT_ORGANIZER, true );

				if ( ! empty( $organizer ) ) {
					echo '<p>🧑‍💼 <a href="' . esc_url( get_permalink( $organizer ) ) . '">' . esc_html( get_the_title( $organizer ) ) . '</a></p>';
				}

				$venue = get_post_meta( $post_id, '_' . self::EVENT_VENUE, true );

				if ( ! empty( $venue ) ) {
					echo '<p>📍 <a href="' . esc_url( get_permalink( $venue ) ) . '">' . esc_html( get_the_title( $venue ) ) . '</a></p>';
				}

				break;

			case 'post_featuredimage':
				if ( has_post_thumbnail() ) {
					echo get_the_post_thumbnail( $post_id, array( 75, 75 ), array( 'style' => 'max-width: 75px; height: auto; border-radius: 5px;' ) );
				}
				break;
		}
	}

	/**
	 * Polylang: Copy or synchronize the post metas.
	 * https://polylang.pro/documentation/support/developers/filter-reference/#pll_copy_post_metas
	 *
	 * @param string[] $keys List of custom fields names.
	 * @param bool     $sync True if it is synchronization, false if it is a copy.
	 * @param int      $from Id of the post from which we copy information.
	 * @param int      $to   Id of the post to which we paste information.
	 *
	 * @return array
	 */
	public function copy_polylang_post_metas( $keys, $sync, $from, $to ): array {
		// TODO: Get keys dynamically.
		$field_keys = array(
			// 1. Event.
			'_' . self::EVENT_START_TIMESTAMP,
			'_' . self::EVENT_END_TIMESTAMP,
			'_' . self::EVENT_ALLDAY,
			'_' . self::EVENT_TIMEZONE,
			'_' . self::EVENT_FEATURED,
			'_' . self::EVENT_URL,
			'_' . self::EVENT_COST,
			'_' . self::EVENT_COST_INFO,
			'_' . self::EVENT_CURRENCY,
			'_' . self::EVENT_ORGANIZER_CPT,
			'_' . self::EVENT_VENUE_CPT,

			// 2. Organizer.
			'_' . self::ORGANIZER_EMAIL,
			'_' . self::ORGANIZER_PHONE,
			'_' . self::ORGANIZER_URL,

			// 3. Venue.
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

		$keys = array_merge( $keys, $field_keys );

		return $keys;
	}

	/**
	 * Ignore "future" post status.
	 * https://developer.wordpress.org/reference/hooks/get_post_status/
	 *
	 * @param array $data An array of slashed, sanitized, and processed post data.
	 *
	 * @return array
	 */
	public function ignore_future_post_status( $data ): array {
		if ( 'future' === $data['post_status'] && self::EVENT_CPT === $data['post_type'] ) {
			$data['post_status'] = 'publish';
		}

		return $data;
	}

	/**
	 * Get a list of all currencies.
	 *
	 * @return array
	 */
	public function get_currencies(): array {
		if ( function_exists( 'get_woocommerce_currencies' ) ) {
			$currencies = get_woocommerce_currencies();
		} elseif ( function_exists( 'resourcebundle_create' ) ) {
			// Create the ResourceBundle for currencies.
			$locale          = get_locale();
			$currency_bundle = resourcebundle_create( $locale, 'ICUDATA-curr' );

			if ( $currency_bundle ) {
				$currencies = resourcebundle_get( $currency_bundle, 'Currencies' );

				if ( $currencies ) {
					$currency_list = array();
					foreach ( $currencies as $key => $value ) {
						$currency_list[ $key ] = $value[1];
					}
					$currencies = $currency_list;
				}
			}
		}

		if ( empty( $currencies ) ) {
			// Hardcoded fallback (last fetched 01/2026): https://woocommerce.github.io/code-reference/files/woocommerce-includes-wc-core-functions.html#source-view.496
			$currencies = array(
				'AED' => 'United Arab Emirates dirham',
				'AFN' => 'Afghan afghani',
				'ALL' => 'Albanian lek',
				'AMD' => 'Armenian dram',
				'ANG' => 'Netherlands Antillean guilder',
				'AOA' => 'Angolan kwanza',
				'ARS' => 'Argentine peso',
				'AUD' => 'Australian dollar',
				'AWG' => 'Aruban florin',
				'AZN' => 'Azerbaijani manat',
				'BAM' => 'Bosnia and Herzegovina convertible mark',
				'BBD' => 'Barbadian dollar',
				'BDT' => 'Bangladeshi taka',
				'BGN' => 'Bulgarian lev',
				'BHD' => 'Bahraini dinar',
				'BIF' => 'Burundian franc',
				'BMD' => 'Bermudian dollar',
				'BND' => 'Brunei dollar',
				'BOB' => 'Bolivian boliviano',
				'BRL' => 'Brazilian real',
				'BSD' => 'Bahamian dollar',
				'BTC' => 'Bitcoin',
				'BTN' => 'Bhutanese ngultrum',
				'BWP' => 'Botswana pula',
				'BYR' => 'Belarusian ruble (old)',
				'BYN' => 'Belarusian ruble',
				'BZD' => 'Belize dollar',
				'CAD' => 'Canadian dollar',
				'CDF' => 'Congolese franc',
				'CHF' => 'Swiss franc',
				'CLP' => 'Chilean peso',
				'CNY' => 'Chinese yuan',
				'COP' => 'Colombian peso',
				'CRC' => 'Costa Rican col&oacute;n',
				'CUC' => 'Cuban convertible peso',
				'CUP' => 'Cuban peso',
				'CVE' => 'Cape Verdean escudo',
				'CZK' => 'Czech koruna',
				'DJF' => 'Djiboutian franc',
				'DKK' => 'Danish krone',
				'DOP' => 'Dominican peso',
				'DZD' => 'Algerian dinar',
				'EGP' => 'Egyptian pound',
				'ERN' => 'Eritrean nakfa',
				'ETB' => 'Ethiopian birr',
				'EUR' => 'Euro',
				'FJD' => 'Fijian dollar',
				'FKP' => 'Falkland Islands pound',
				'GBP' => 'Pound sterling',
				'GEL' => 'Georgian lari',
				'GGP' => 'Guernsey pound',
				'GHS' => 'Ghana cedi',
				'GIP' => 'Gibraltar pound',
				'GMD' => 'Gambian dalasi',
				'GNF' => 'Guinean franc',
				'GTQ' => 'Guatemalan quetzal',
				'GYD' => 'Guyanese dollar',
				'HKD' => 'Hong Kong dollar',
				'HNL' => 'Honduran lempira',
				'HRK' => 'Croatian kuna',
				'HTG' => 'Haitian gourde',
				'HUF' => 'Hungarian forint',
				'IDR' => 'Indonesian rupiah',
				'ILS' => 'Israeli new shekel',
				'IMP' => 'Manx pound',
				'INR' => 'Indian rupee',
				'IQD' => 'Iraqi dinar',
				'IRR' => 'Iranian rial',
				'IRT' => 'Iranian toman',
				'ISK' => 'Icelandic kr&oacute;na',
				'JEP' => 'Jersey pound',
				'JMD' => 'Jamaican dollar',
				'JOD' => 'Jordanian dinar',
				'JPY' => 'Japanese yen',
				'KES' => 'Kenyan shilling',
				'KGS' => 'Kyrgyzstani som',
				'KHR' => 'Cambodian riel',
				'KMF' => 'Comorian franc',
				'KPW' => 'North Korean won',
				'KRW' => 'South Korean won',
				'KWD' => 'Kuwaiti dinar',
				'KYD' => 'Cayman Islands dollar',
				'KZT' => 'Kazakhstani tenge',
				'LAK' => 'Lao kip',
				'LBP' => 'Lebanese pound',
				'LKR' => 'Sri Lankan rupee',
				'LRD' => 'Liberian dollar',
				'LSL' => 'Lesotho loti',
				'LYD' => 'Libyan dinar',
				'MAD' => 'Moroccan dirham',
				'MDL' => 'Moldovan leu',
				'MGA' => 'Malagasy ariary',
				'MKD' => 'Macedonian denar',
				'MMK' => 'Burmese kyat',
				'MNT' => 'Mongolian t&ouml;gr&ouml;g',
				'MOP' => 'Macanese pataca',
				'MRU' => 'Mauritanian ouguiya',
				'MUR' => 'Mauritian rupee',
				'MVR' => 'Maldivian rufiyaa',
				'MWK' => 'Malawian kwacha',
				'MXN' => 'Mexican peso',
				'MYR' => 'Malaysian ringgit',
				'MZN' => 'Mozambican metical',
				'NAD' => 'Namibian dollar',
				'NGN' => 'Nigerian naira',
				'NIO' => 'Nicaraguan c&oacute;rdoba',
				'NOK' => 'Norwegian krone',
				'NPR' => 'Nepalese rupee',
				'NZD' => 'New Zealand dollar',
				'OMR' => 'Omani rial',
				'PAB' => 'Panamanian balboa',
				'PEN' => 'Sol',
				'PGK' => 'Papua New Guinean kina',
				'PHP' => 'Philippine peso',
				'PKR' => 'Pakistani rupee',
				'PLN' => 'Polish z&#x142;oty',
				'PRB' => 'Transnistrian ruble',
				'PYG' => 'Paraguayan guaran&iacute;',
				'QAR' => 'Qatari riyal',
				'RON' => 'Romanian leu',
				'RSD' => 'Serbian dinar',
				'RUB' => 'Russian ruble',
				'RWF' => 'Rwandan franc',
				'SAR' => 'Saudi riyal',
				'SBD' => 'Solomon Islands dollar',
				'SCR' => 'Seychellois rupee',
				'SDG' => 'Sudanese pound',
				'SEK' => 'Swedish krona',
				'SGD' => 'Singapore dollar',
				'SHP' => 'Saint Helena pound',
				'SLL' => 'Sierra Leonean leone',
				'SOS' => 'Somali shilling',
				'SRD' => 'Surinamese dollar',
				'SSP' => 'South Sudanese pound',
				'STN' => 'S&atilde;o Tom&eacute; and Pr&iacute;ncipe dobra',
				'SYP' => 'Syrian pound',
				'SZL' => 'Swazi lilangeni',
				'THB' => 'Thai baht',
				'TJS' => 'Tajikistani somoni',
				'TMT' => 'Turkmenistan manat',
				'TND' => 'Tunisian dinar',
				'TOP' => 'Tongan pa&#x2bb;anga',
				'TRY' => 'Turkish lira',
				'TTD' => 'Trinidad and Tobago dollar',
				'TWD' => 'New Taiwan dollar',
				'TZS' => 'Tanzanian shilling',
				'UAH' => 'Ukrainian hryvnia',
				'UGX' => 'Ugandan shilling',
				'USD' => 'United States (US) dollar',
				'UYU' => 'Uruguayan peso',
				'UZS' => 'Uzbekistani som',
				'VEF' => 'Venezuelan bol&iacute;var (2008–2018)',
				'VES' => 'Venezuelan bol&iacute;var',
				'VND' => 'Vietnamese &#x111;&#x1ed3;ng',
				'VUV' => 'Vanuatu vatu',
				'WST' => 'Samoan t&#x101;l&#x101;',
				'XAF' => 'Central African CFA franc',
				'XCD' => 'East Caribbean dollar',
				'XOF' => 'West African CFA franc',
				'XPF' => 'CFP franc',
				'YER' => 'Yemeni rial',
				'ZAR' => 'South African rand',
				'ZMW' => 'Zambian kwacha',
			);
		}

		return $currencies;
	}

	/**
	 * Enqueue scripts.
	 *
	 * @return void
	 */
	public function enqueue_scripts(): void {
		wp_enqueue_style(
			'calendas-style-admin',
			plugins_url( 'assets/style-admin.css', __DIR__ ),
			array(),
			'1.0'
		);

		if ( is_admin() && 'dashboard' === get_current_screen()->id ) {
			// https://developer.wordpress.org/reference/functions/wp_add_inline_style/
			$dashboard_styles       = '#calendas_dashboard a { text-decoration: none; } #calendas_dashboard .dashicons-star-filled { color: #ffb900; }';
			$upcoming_events_styles = '.wp-block-events-calendar-upcoming-events .title { font-weight: bold; }';

			wp_add_inline_style(
				'dashboard',
				$dashboard_styles . ' ' . $upcoming_events_styles
			);
		}

		// https://developer.wordpress.org/reference/functions/wp_add_inline_script/
		wp_add_inline_script(
			'editor',
			'var globalDataUpcomingEvents = { language: "' . esc_attr( mb_substr( get_locale(), 0, 2 ) ) . '", cptVenue: "' . esc_attr( self::EVENT_VENUE_CPT ) . '", cptOrganizer: "' . esc_attr( self::EVENT_ORGANIZER_CPT ) . '" };'
		);
	}

	/**
	 * Editor assets.
	 *
	 * @return void
	 */
	public function enqueue_editor_assets(): void {
		wp_enqueue_style(
			'calendas-editor-style',
			plugins_url( 'assets/style-editor.css', __DIR__ ),
			array(),
			'1.0'
		);

		// Create currencies JSON.
		$currencies = $this->get_currencies();

		$currencies = array_map(
			function ( $label, $code ) {
				return array(
					'label' => $label,
					'value' => $code,
				);
			},
			$currencies,
			array_keys( $currencies )
		);

		$currencies = array(
			array(
				'label' => '',
				'value' => '',
			),
		) + $currencies;

		// Create timezones JSON.
		$timezones = $this->get_timezones();

		$timezones = array_map(
			function ( $row ) {
				return array(
					'label' => $row,
					'value' => $row,
				);
			},
			$timezones
		);

		$timezones = array(
			array(
				'label' => '',
				'value' => '',
			),
		) + $timezones;

		// Scripts.
		wp_add_inline_script(
			'editor',
			'var globalDataCalendas = {
				cpt: "' . self::EVENT_CPT_DB . '",
				currencies: ' . json_encode( $currencies ) . ',
				timezones: ' . json_encode( $timezones ) . '
			};'
		);
	}
}
