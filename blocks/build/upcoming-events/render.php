<?php

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'WP_Interactivity_API' ) ) {
	$venue     = $attributes['venue'] ?? false;
	$organizer = $attributes['organizer'] ?? false;

	global $post;

	// Venue or Organizer post? Query upcoming events for this CPT.
	if ( isset( $post->post_type ) ) {
		if ( Calendas::EVENT_VENUE_CPT === $post->post_type ) {
			$venue = $post->ID;
		}

		if ( Calendas::EVENT_ORGANIZER_CPT === $post->post_type ) {
			$organizer = $post->ID;
		}
	}

	$context = array(
		'postType'      => Calendas::EVENT_CPT,
		'title'         => $attributes['title'] ?? __( 'Upcoming Events', 'calendas' ),
		'perPage'       => $attributes['perPage'] ?? 10,
		'layout'        => $attributes['layout'] ?? 'vertical',
		'showVenue'     => $attributes['showVenue'] ?? false,
		'showOrganizer' => $attributes['showOrganizer'] ?? false,
		'featuredOnly'  => $attributes['featuredOnly'] ?? false,
		'featuredFirst' => $attributes['featuredFirst'] ?? false,
	);

	if ( is_numeric( $venue ) ) {
		$context['venue'] = $venue;
	}

	if ( is_numeric( $organizer ) ) {
		$context['organizer'] = $organizer;
	}

	if ( ! $context['perPage'] || $context['perPage'] < 1 ) {
		$context['perPage'] = 1;
	}

	$widget_title = $context['title'];

	echo empty( $widget_title ) ? '' : '<h2 class="wp-block-heading">' . esc_html( $widget_title ) . '</h2>';
	?>
	<ul
		<?php echo get_block_wrapper_attributes( array( 'class' => esc_attr( 'upcoming-events ' . $context['layout'] ) ) ); ?>
		<?php echo wp_interactivity_data_wp_context( $context ); ?>
		data-wp-interactive="calendas"
		data-wp-init---upcomingevents="callbacks.getUpcomingEvents"
	>
		<?php
		// WP-Editor: Add fallback with dummy content.
		if ( isset( $_GET['context'] ) && 'edit' === sanitize_key( wp_unslash( $_GET['context'] ) ) ) {
			$date_format = get_option( 'date_format' );
			$y           = wp_date( 'Y' );
			$dates       = array(
				"{$y}-01-01",
				"{$y}-02-04",
				"{$y}-07-17",
				"{$y}-09-20",
				"{$y}-12-31",
			);

			foreach ( $dates as $row => $date ) {
				$timestamp = strtotime( $date );

				echo '<li class="wp-editor-dummy">
				<div class="calendar">
					<span class="month">' . esc_html( wp_date( 'M', $timestamp ) ) . '</span>
					<span class="day">' . esc_html( wp_date( 'd', $timestamp ) ) . '</span>
				</div>
				<div class="content">
					<p class="title"><a href="#">#' . esc_html( (string) ++$row ) . '</a></p>
					<p class="date"><small>' . esc_html( wp_date( $date_format, $timestamp ) ) . '</small></p>
				</div>
			</li>';
			}
		}
		?>
	</ul>
	<?php
}
