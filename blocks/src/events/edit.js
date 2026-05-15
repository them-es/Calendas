import { __, sprintf } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	TextControl,
	SelectControl,
	CheckboxControl,
	ColorPalette,
} from '@wordpress/components';

import { lightenColor } from './helpers.js';

const Edit = ({ attributes, setAttributes, className }) => {
	const {
		defaultView,
		showDatepicker,
		showMonth,
		showWeek,
		showDay,
		showList,
		firstDayOfWeek,
		weekDays,
		showWeekNumbers,
		dayBoundariesStart,
		dayBoundariesEnd,
		primaryColor,
		showTimezoneselector,
		showSearch,
		showIcs,
	} = attributes;

	const blockProps = useBlockProps({
		className: `calendas is-layout-flow${className ? ' ' + className : ''}`,
		style: {
			padding: '1rem',
			maxHeight: 'none',
			height: 'auto',
			backgroundColor: '#fff',
			boxShadow:
				'0 24px 38px 3px rgba(0,0,0,.14),0 9px 46px 8px rgba(0,0,0,.12),0 11px 15px -7px rgba(0,0,0,.2)',
		},
	});

	const calendarSettings = (
		<>
			<p>{__('Primary Color', 'calendas')}</p>

			<div style={{ display: 'flex' }}>
				<ColorPalette
					value={primaryColor}
					colors={
						wp.data.select('core/editor').getEditorSettings()
							.colors || [] // Color palette from theme
					}
					onChange={(val) => {
						setAttributes({
							primaryColor: val ?? '',
						});
					}}
					label={__('Select predefined Theme Color', 'calendas')}
				/>
				<div
					style={{
						width: '75px',
						height: '64px',
						marginLeft: '5px',
						borderRadius: '4px',
						backgroundColor: lightenColor(primaryColor, 80),
					}}
				></div>
			</div>

			<hr></hr>

			<p>
				{sprintf(
					/* translators: %s: Setting. */ __('Show %s', 'calendas'),
					__('Datepicker', 'calendas')
				)}
			</p>

			<CheckboxControl
				label={sprintf(
					/* translators: %s: Setting. */ __('Show %s', 'calendas'),
					__('Datepicker', 'calendas')
				)}
				checked={!!showDatepicker}
				onChange={() => {
					setAttributes({
						showDatepicker: !showDatepicker,
					});
				}}
			/>

			<hr></hr>

			<p>{__('Enable Views', 'calendas')}</p>

			<CheckboxControl
				label={__('Month', 'calendas')}
				checked={!!showMonth}
				onChange={() => {
					setAttributes({
						showMonth: !showMonth,
					});
				}}
			/>
			<CheckboxControl
				label={__('Week', 'calendas')}
				checked={!!showWeek}
				onChange={() => {
					setAttributes({
						showWeek: !showWeek,
					});
				}}
			/>
			<CheckboxControl
				label={__('Day', 'calendas')}
				checked={!!showDay}
				onChange={() => {
					setAttributes({
						showDay: !showDay,
					});
				}}
			/>
			<CheckboxControl
				label={__('List', 'calendas')}
				checked={!!showList}
				onChange={() => {
					setAttributes({
						showList: !showList,
					});
				}}
			/>

			<hr></hr>

			<SelectControl
				label={__('Default View', 'calendas')}
				value={defaultView}
				options={[
					{ value: 'month', label: __('Month', 'calendas') },
					{ value: 'week', label: __('Week', 'calendas') },
					{ value: 'day', label: __('Day', 'calendas') },
					{ value: 'list', label: __('List', 'calendas') },
				]}
				onChange={(val) => {
					setAttributes({
						defaultView: val,
					});
				}}
			/>

			<SelectControl
				label={__('First Day Of Week', 'calendas')}
				value={firstDayOfWeek || '1'}
				options={[
					{ value: '1', label: __('Monday', 'calendas') },
					{ value: '2', label: __('Tuesday', 'calendas') },
					{ value: '3', label: __('Wednesday', 'calendas') },
					{ value: '4', label: __('Thursday', 'calendas') },
					{ value: '5', label: __('Friday', 'calendas') },
					{ value: '6', label: __('Saturday', 'calendas') },
					{ value: '7', label: __('Sunday', 'calendas') },
				]}
				onChange={(val) => {
					setAttributes({
						firstDayOfWeek: val,
					});
				}}
			/>

			<SelectControl
				label={__('Week Days', 'calendas')}
				value={weekDays || '5'}
				options={[
					{ value: '1', label: __('1', 'calendas') },
					{ value: '2', label: __('2', 'calendas') },
					{ value: '3', label: __('3', 'calendas') },
					{ value: '4', label: __('4', 'calendas') },
					{ value: '5', label: __('5', 'calendas') },
					{ value: '6', label: __('6', 'calendas') },
					{ value: '7', label: __('7', 'calendas') },
				]}
				onChange={(val) => {
					setAttributes({
						weekDays: val,
					});
				}}
			/>

			<CheckboxControl
				label={sprintf(
					/* translators: %s: Setting. */ __('Show %s', 'calendas'),
					__('Weeknumbers', 'calendas')
				)}
				checked={!!showWeekNumbers}
				onChange={() => {
					setAttributes({
						showWeekNumbers: !showWeekNumbers,
					});
				}}
			/>

			<TextControl
				type="text"
				label={sprintf(
					/* translators: %s: Start. */
					__('Day Boundaries: %s', 'calendas'),
					__('Start', 'calendas')
				)}
				value={dayBoundariesStart || '09:00'}
				onChange={(val) => {
					setAttributes({
						dayBoundariesStart: val,
					});
				}}
			/>

			<TextControl
				type="text"
				label={sprintf(
					/* translators: %s: End. */
					__('Day Boundaries: %s', 'calendas'),
					__('End', 'calendas')
				)}
				value={dayBoundariesEnd || '17:00'}
				onChange={(val) => {
					setAttributes({
						dayBoundariesEnd: val,
					});
				}}
			/>

			<hr></hr>

			<p>
				{sprintf(
					/* translators: %s: Setting. */ __('Show %s', 'calendas'),
					__('Controls', 'calendas')
				)}
			</p>

			<CheckboxControl
				label={sprintf(
					/* translators: %s: Setting. */ __('Show %s', 'calendas'),
					__('Timezone-Selector', 'calendas')
				)}
				checked={!!showTimezoneselector}
				onChange={() => {
					setAttributes({
						showTimezoneselector: !showTimezoneselector,
					});
				}}
			/>

			<CheckboxControl
				label={sprintf(
					/* translators: %s: Setting. */ __('Show %s', 'calendas'),
					__('Search', 'calendas')
				)}
				checked={!!showSearch}
				onChange={() => {
					setAttributes({
						showSearch: !showSearch,
					});
				}}
			/>

			<CheckboxControl
				label={sprintf(
					/* translators: %s: Setting. */ __('Show %s', 'calendas'),
					__('ICS-Link', 'calendas')
				)}
				checked={!!showIcs}
				onChange={() => {
					setAttributes({
						showIcs: !showIcs,
					});
				}}
			/>
		</>
	);

	return (
		<div {...blockProps}>
			<InspectorControls>
				<PanelBody title={__('Calendar Settings', 'calendas')}>
					{calendarSettings}
				</PanelBody>
			</InspectorControls>

			<h1>
				{sprintf(
					/* translators: %s: Icon. */ __('%s Events', 'calendas'),
					'📅'
				)}
			</h1>

			<hr></hr>

			{calendarSettings}
		</div>
	);
};

export default Edit;
