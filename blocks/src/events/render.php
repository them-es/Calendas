<?php

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'WP_Interactivity_API' ) ) {
	$post_id = $attributes['id'] ?? get_the_ID();

	$enabled_views = array(
		$attributes['showMonth'],
		$attributes['showWeek'],
		$attributes['showDay'],
		$attributes['showList'],
	);
	$count_views   = count( array_filter( $enabled_views ) );

	$context = array(
		'id'                 => (int) $post_id,
		'postType'           => Calendas::EVENT_CPT,
		'urlParameterSearch' => 'search-events',
		'locale'             => get_locale(),
		'defaultView'        => $attributes['defaultView'],
		'showDatepicker'     => $attributes['showDatepicker'],
		'showMonth'          => $attributes['showMonth'],
		'showWeek'           => $attributes['showWeek'],
		'showDay'            => $attributes['showDay'],
		'showList'           => $attributes['showList'],
		'firstDayOfWeek'     => $attributes['firstDayOfWeek'],
		'weekDays'           => $attributes['weekDays'],
		'showWeekNumbers'    => $attributes['showWeekNumbers'],
		'dayBoundaries'      =>
			array(
				'start' => $attributes['dayBoundariesStart'],
				'end'   => $attributes['dayBoundariesEnd'],
			),
		'primaryColor'       => $attributes['primaryColor'] ?? null,
	);
	?>
	<div
		<?php echo get_block_wrapper_attributes( array( 'class' => 'alignfull' . ( 1 === $count_views ? ' hide-view-selector' : '' ) ) ); ?>
		<?php echo wp_interactivity_data_wp_context( $context ); ?>
		data-wp-interactive="calendas"
		data-wp-init---events="callbacks.getEvents"
	>
	<?php
	if ( $attributes['showSearch'] ) :
		$search_value = filter_input( INPUT_GET, $context['urlParameterSearch'], FILTER_SANITIZE_SPECIAL_CHARS ) ?? '';
		?>
		<p class="calendas-search">
			<input type="text" class="sx__date-input search-input" data-wp-on--input="callbacks.searchEvents" value="<?php echo esc_attr( $search_value ); ?>" placeholder="<?php esc_attr_e( 'Search', 'calendas' ); ?>" required>
			<button class="clear-search-input" data-wp-on--click="callbacks.clearInput" aria-label="<?php esc_attr_e( 'Clear input', 'calendas' ); ?>">&times;</button>
		</p>
		<?php
			endif;
	?>
		<div id="calendar-view"></div>
	</div>
	<div class="calendas-custom-controls">
		<?php
		if ( $attributes['showTimezoneselector'] ) :
			?>
		<p class="sx__view-selection">
			<label for="timezone-select" class="sx__view-selection-label"><?php esc_attr_e( 'Timezone', 'calendas' ); ?></label>
			<input id="timezone-select" style="box-sizing: border-box;" list="timezones" value="<?php echo esc_attr( wp_timezone_string() ); ?>" data-wp-interactive="calendas" data-wp-on--focus="actions.clearTimezone" data-wp-on--blur="actions.changeTimezone" class="sx__view-selection-selected-item">
			<datalist id="timezones">
			<?php
			foreach ( ( new Calendas() )->get_timezones() as $timezone ) {
				echo '<option value="' . esc_attr( $timezone ) . '"' . ( wp_timezone_string() === $timezone ? ' selected' : '' ) . '>' . esc_html( $timezone ) . '</option>';
			}
			?>
			</datalist>
		</p>
			<?php
			endif;

		if ( $attributes['showIcs'] ) :
			?>
		<p><a href="<?php echo esc_url( rest_url( '/wp/v2/' . Calendas::EVENT_CPT . '?per_page=100&output=ics' ) ); ?>"><?php printf( /* translators: %s: File type. */ esc_html__( '%s Feed', 'calendas' ), 'ICS' ); ?></a></p>
			<?php
			endif;
		?>
	</div>
	<?php
}
