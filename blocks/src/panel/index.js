/**
 * Plugin Settings Panel: Calendas Setup
 * https://github.com/WordPress/gutenberg/blob/trunk/packages/editor/src/components/plugin-document-setting-panel/index.js
 */

import { registerPlugin } from '@wordpress/plugins';
import { __, sprintf } from '@wordpress/i18n';
import { PluginDocumentSettingPanel } from '@wordpress/editor';
import {
	TextControl,
	TextareaControl,
	SelectControl,
	CheckboxControl,
} from '@wordpress/components';
import { date } from '@wordpress/date';
import { select, useSelect, useDispatch } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';
import { useState } from '@wordpress/element';
import { applyFilters } from '@wordpress/hooks';

const dateFormatted = (val, tz = 'UTC', format = 'Y-m-d H:i') => {
	const ts = isNaN(val) ? val : Number(val) * 1000;

	return date(format, ts, tz);
};

const roundToNearestMins = (val, roundMins = 5) => {
	if (!val.includes('T')) {
		return val;
	}

	const [datePart, timePart] = val?.split('T');
	const [hours, minutes] = timePart?.split(':').map(Number);

	// Round to the nearest x minutes
	const roundedMinutes = Math.round(minutes / roundMins) * roundMins;
	const adjustedHours = Math.floor(roundedMinutes / 60);

	const finalHours = (hours + adjustedHours) % 24;
	const finalMinutes = roundedMinutes % 60;

	const formattedTime = `${finalHours.toString().padStart(2, '0')}:${finalMinutes.toString().padStart(2, '0')}`;

	return `${datePart}T${formattedTime}`;
};

const addDefaultMins = (val, minsToAdd = 30) => {
	if (!val.includes('T')) {
		return val;
	}

	const [datePart, timePart] = val?.split('T');
	const [hours, minutes] = timePart?.split(':').map(Number);

	// Calculate new total minutes
	let totalMinutes = hours * 60 + minutes + minsToAdd;

	// Wrap around if totalMinutes exceeds a day
	totalMinutes = (totalMinutes + 1440) % 1440; // 1440 minutes in a day

	const finalHours = Math.floor(totalMinutes / 60);
	const finalMinutes = totalMinutes % 60;

	// Format the final string
	const formattedTime = `${finalHours.toString().padStart(2, '0')}:${finalMinutes.toString().padStart(2, '0')}`;

	// Update the value in the input element
	return `${datePart}T${formattedTime}`;
};

const CalendasSetup = () => {
	const cptCurrent = select('core/editor').getCurrentPostType();

	if (!cptCurrent) {
		return;
	}

	const cpt = globalDataCalendas.cpt;

	const { editPost } = useDispatch('core/editor');

	const updateMeta = (key, val) => {
		editPost({
			meta: { [key]: String(val) },
		});
	};

	const meta = (key) => {
		return useSelect(
			(select) =>
				select('core/editor').getEditedPostAttribute('meta')[key]
		);
	};

	// Get Organizers
	const { organizers } = useSelect((select) => {
		const { getEntityRecords } = select('core');

		return {
			organizers: getEntityRecords(
				'postType',
				cptCurrent.split('-')[0] + '-organizer',
				{
					status: 'publish',
					per_page: -1,
					orderby: 'title',
					order: 'asc',
				}
			),
		};
	});

	let optionsOrganizer = [];
	if (!!organizers) {
		optionsOrganizer.push({
			value: 0,
			label: sprintf(
				__('Select %s', 'calendas'),
				__('Organizer', 'calendas')
			),
		});
		organizers.forEach((post) => {
			optionsOrganizer.push({
				value: post.id,
				label: post.title.rendered,
			});
		});
	} else {
		optionsOrganizer.push({ value: 0, label: '...' });
	}

	// Get Venues
	const { venues } = useSelect((select) => {
		const { getEntityRecords } = select('core');

		return {
			venues: getEntityRecords(
				'postType',
				cptCurrent.split('-')[0] + '-venue',
				{
					status: 'publish',
					per_page: -1,
					orderby: 'title',
					order: 'asc',
				}
			),
		};
	});

	let optionsVenue = [];
	if (!!venues) {
		optionsVenue.push({
			value: 0,
			label: sprintf(
				__('Select %s', 'calendas'),
				__('Venue', 'calendas')
			),
		});
		venues.forEach((post) => {
			optionsVenue.push({ value: post.id, label: post.title.rendered });
		});
	} else {
		optionsVenue.push({ value: 0, label: '...' });
	}

	// Panel "Events"
	if (cptCurrent.endsWith('event')) {
		const start = meta(`_${cpt}_start_timestamp`);
		const end = meta(`_${cpt}_end_timestamp`);
		const timezone = meta(`_${cpt}_timezone`);
		const allDay = meta(`_${cpt}_allday`);
		const featured = meta(`_${cpt}_featured`);
		const url = meta(`_${cpt}_url`);
		const cost = meta(`_${cpt}_cost`);
		const currency = meta(`_${cpt}_currency`);
		const costInfo = meta(`_${cpt}_cost_info`);
		const organizer = meta(`_${cpt}_organizer`);
		const venue = meta(`_${cpt}_venue`);

		const handleStartChange = (val) => {
			const value = roundToNearestMins(val);
			const valueUnix = dateFormatted(String(value), timezone, 'U');

			document.querySelector('#event-starts').value = value;
			updateMeta(`_${cpt}_start_timestamp`, valueUnix);
			document.querySelector('#event-ends').min = value;

			updateMeta(`_${cpt}_allday`, false);

			editPost({
				date: new Date(value).toISOString(),
			});

			if (end && valueUnix > end) {
				updateMeta(`_${cpt}_end_timestamp`, '');

				document.querySelector('#event-ends').min = value;
			}
		};

		const handleEndChange = (val) => {
			const value = val.endsWith(':59') ? val : roundToNearestMins(val);
			const valueUnix = dateFormatted(String(value), timezone, 'U');

			document.querySelector('#event-ends').value = value;
			updateMeta(`_${cpt}_end_timestamp`, valueUnix);

			updateMeta(`_${cpt}_allday`, false);

			// Prevent end date from being before start date
			if (start && valueUnix < start) {
				const valueUpdated = addDefaultMins(
					dateFormatted(start, timezone)
				);
				const valueUpdatedUnix = dateFormatted(
					String(valueUpdated),
					timezone,
					'U'
				);

				updateMeta(`_${cpt}_end_timestamp`, valueUpdatedUnix);

				document.querySelector('#event-starts').max = valueUpdated;
			}
		};

		const filteredContent = applyFilters('calendas-setup-panel', null);

		return (
			<>
				<PluginDocumentSettingPanel
					name="calendas-setup"
					title={sprintf(
						__('📅 %s', 'calendas-pro'),
						__('Start/End Time', 'calendas-pro')
					)}
					className="block-editor-block-inspector"
					initialOpen={true}
				>
					<TextControl
						id="event-starts"
						type="datetime-local"
						label={sprintf(
							/* translators: %s: Date. */ __(
								'Start: %s',
								'calendas'
							),
							start ? dateFormatted(start, timezone) : '?'
						)}
						defaultValue={
							start ? dateFormatted(start, timezone) : ''
						}
						// max={end ? dateFormatted(end, timezone) : ''}
						onChange={(value) => {
							console.log(value);
						}}
						onBlur={(event) => {
							handleStartChange(event.target.value);
						}}
					/>
					<TextControl
						id="event-ends"
						type="datetime-local"
						label={sprintf(
							/* translators: %s: Date. */ __(
								'End: %s',
								'calendas'
							),
							end ? dateFormatted(end, timezone) : '?'
						)}
						defaultValue={end ? dateFormatted(end, timezone) : ''}
						min={start ? dateFormatted(start, timezone) : ''}
						onChange={(value) => {
							console.log(value);
						}}
						onBlur={(event) => {
							handleEndChange(event.target.value);
						}}
					/>
					<CheckboxControl
						id="event-allday"
						label={__('All day', 'calendas')}
						value={allDay ?? false}
						checked={allDay === 'true' ? true : false}
						onChange={(value) => {
							// Starts: 00:00
							const start0000 =
								document
									.querySelector('#event-starts')
									.value?.split('T')[0] + 'T00:00';
							handleStartChange(String(start0000));

							// Ends: 23:59
							const end2359 =
								document
									.querySelector('#event-ends')
									.value?.split('T')[0] + 'T23:59';
							handleEndChange(String(end2359));

							updateMeta(`_${cpt}_allday`, value);
						}}
					/>
					<SelectControl
						id="event-timezone"
						label={__('Timezone', 'calendas')}
						options={globalDataCalendas.timezones ?? []}
						value={timezone ?? ''}
						onChange={(value) => {
							updateMeta(`_${cpt}_timezone`, value);
						}}
					/>
					{filteredContent}
				</PluginDocumentSettingPanel>
				<PluginDocumentSettingPanel
					name="calendas-setup-details"
					title={sprintf(
						__('🧩 %s', 'calendas-pro'),
						__('Event Details', 'calendas-pro')
					)}
					className="block-editor-block-inspector"
				>
					<CheckboxControl
						id="event-featured"
						label={sprintf(
							__('⭐️ %s', 'calendas'),
							__('Featured', 'calendas')
						)}
						value={featured ?? false}
						checked={featured === 'true' ? true : false}
						onChange={(value) => {
							updateMeta(`_${cpt}_featured`, value);
						}}
					/>
					<TextControl
						id="event-url"
						label={__('Weblink', 'calendas')}
						type="url"
						className=""
						value={url ?? ''}
						onChange={(value) => {
							updateMeta(`_${cpt}_url`, value);
						}}
					/>
					<TextControl
						id="event-cost"
						label={__('Cost', 'calendas')}
						type="text"
						className=""
						value={cost ?? ''}
						onChange={(value) => {
							updateMeta(`_${cpt}_cost`, value);
						}}
					/>
					<TextControl
						id="event-info"
						label={__('Info', 'calendas')}
						type="text"
						className=""
						value={costInfo ?? ''}
						onChange={(value) => {
							updateMeta(`_${cpt}_cost_info`, value);
						}}
					/>
					<SelectControl
						id="event-currency"
						label={__('Currency', 'calendas')}
						options={globalDataCalendas.currencies ?? []}
						value={currency ?? ''}
						onChange={(value) => {
							updateMeta(`_${cpt}_currency`, value);
						}}
					/>
				</PluginDocumentSettingPanel>

				<PluginDocumentSettingPanel
					name="calendas-setup-organizer"
					title={sprintf(
						__('🧑‍💼 %s', 'calendas-pro'),
						__('Organizer', 'calendas-pro')
					)}
					className="block-editor-block-inspector"
				>
					{optionsOrganizer.length > 1 ? (
						<SelectControl
							id="event-organizer"
							label={__('Organizer', 'calendas')}
							options={optionsOrganizer}
							value={organizer ?? ''}
							onChange={(value) => {
								updateMeta(`_${cpt}_organizer`, value);
							}}
						/>
					) : (
						__('No posts found', 'calendas-pro')
					)}
				</PluginDocumentSettingPanel>

				<PluginDocumentSettingPanel
					name="calendas-setup-venue"
					title={sprintf(
						__('📍 %s', 'calendas-pro'),
						__('Venue', 'calendas-pro')
					)}
					className="block-editor-block-inspector"
				>
					{optionsVenue.length > 1 ? (
						<SelectControl
							id="event-venue"
							label={__('Venue', 'calendas')}
							options={optionsVenue}
							value={venue ?? ''}
							onChange={(value) => {
								updateMeta(`_${cpt}_venue`, value);
							}}
						/>
					) : (
						__('No posts found', 'calendas-pro')
					)}
				</PluginDocumentSettingPanel>
			</>
		);
	}

	// Panel "Organizers"
	if (cptCurrent.endsWith('organizer')) {
		const organizerEmail = meta(`_${cpt}_email`);
		const organizerPhone = meta(`_${cpt}_phone`);
		const organizerUrl = meta(`_${cpt}_url`);

		return (
			<>
				<PluginDocumentSettingPanel
					name="calendas-setup-venue"
					title={__('Organizer', 'calendas')}
					className="block-editor-block-inspector"
				>
					<TextControl
						label={__('Email', 'calendas')}
						type="email"
						className=""
						value={organizerEmail ?? ''}
						onChange={(value) => {
							updateMeta(`_${cpt}_email`, value);
						}}
					/>
					<TextControl
						label={__('Phone', 'calendas')}
						type="text"
						className=""
						value={organizerPhone ?? ''}
						onChange={(value) => {
							updateMeta(`_${cpt}_phone`, value);
						}}
					/>
					<TextControl
						label={__('Weblink', 'calendas')}
						type="url"
						className=""
						value={organizerUrl ?? ''}
						onChange={(value) => {
							updateMeta(`_${cpt}_url`, value);
						}}
					/>
				</PluginDocumentSettingPanel>
			</>
		);
	}

	// Panel "Venues"
	if (cptCurrent.endsWith('venue')) {
		const venueEmail = meta(`_${cpt}_email`);
		const venuePhone = meta(`_${cpt}_phone`);
		const venueUrl = meta(`_${cpt}_url`);
		const venueAddress = meta(`_${cpt}_address`);
		const venueCity = meta(`_${cpt}_city`);
		const venueState = meta(`_${cpt}_state`);
		const venuePostcode = meta(`_${cpt}_postcode`);
		const venueLatitude = meta(`_${cpt}_lat`);
		const venueLongitude = meta(`_${cpt}_lng`);

		return (
			<>
				<PluginDocumentSettingPanel
					name="calendas-setup-venue"
					title={__('Venue', 'calendas')}
					className="block-editor-block-inspector"
				>
					<TextControl
						label={__('Email', 'calendas')}
						type="email"
						className=""
						value={venueEmail ?? ''}
						onChange={(value) => {
							updateMeta(`_${cpt}_email`, value);
						}}
					/>
					<TextControl
						label={__('Phone', 'calendas')}
						type="text"
						className=""
						value={venuePhone ?? ''}
						onChange={(value) => {
							updateMeta(`_${cpt}_phone`, value);
						}}
					/>
					<TextControl
						label={__('Weblink', 'calendas')}
						type="url"
						className=""
						value={venueUrl ?? ''}
						onChange={(value) => {
							updateMeta(`_${cpt}_url`, value);
						}}
					/>
					<TextareaControl
						label={__('Address', 'calendas')}
						className=""
						value={venueAddress ?? ''}
						onChange={(value) => {
							updateMeta(`_${cpt}_address`, value);
						}}
					/>
					<TextControl
						label={__('City', 'calendas')}
						type="text"
						className=""
						value={venueCity ?? ''}
						onChange={(value) => {
							updateMeta(`_${cpt}_city`, value);
						}}
					/>
					<TextControl
						label={__('State', 'calendas')}
						type="text"
						className=""
						value={venueState ?? ''}
						onChange={(value) => {
							updateMeta(`_${cpt}_state`, value);
						}}
					/>
					<TextControl
						label={__('Postcode', 'calendas')}
						type="text"
						className=""
						value={venuePostcode ?? ''}
						onChange={(value) => {
							updateMeta(`_${cpt}_postcode`, value);
						}}
					/>
					<TextControl
						label={__('Latitude', 'calendas')}
						type="text"
						className=""
						value={venueLatitude ?? ''}
						onChange={(value) => {
							updateMeta(`_${cpt}_lat`, value);
						}}
					/>
					<TextControl
						label={__('Longitude', 'calendas')}
						type="text"
						className=""
						value={venueLongitude ?? ''}
						onChange={(value) => {
							updateMeta(`_${cpt}_lng`, value);
						}}
					/>
				</PluginDocumentSettingPanel>
			</>
		);
	}
};
registerPlugin('calendas-setup', { render: CalendasSetup });
