import { __, sprintf } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	TextControl,
	SelectControl,
	CheckboxControl,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { ServerSideRender } from '@wordpress/server-side-render';

const Edit = ({ attributes, setAttributes, className }) => {
	const {
		title,
		perPage,
		layout,
		showVenue,
		showOrganizer,
		featuredOnly,
		featuredFirst,
		venue,
		organizer,
	} = attributes;

	const blockProps = useBlockProps({
		className: `calendas upcoming-events${
			className ? ' ' + className : ''
		}`,
	});

	const { venuePosts } = useSelect((select) => {
		const { getEntityRecords } = select('core');

		return {
			venuePosts: getEntityRecords(
				'postType',
				globalDataUpcomingEvents.cptVenue,
				{
					per_page: -1,
					status: ['publish'],
					_fields: 'id,title',
					context: 'view',
					lang: globalDataUpcomingEvents.language,
				}
			),
		};
	});

	let venueOptions = [];
	if (venuePosts) {
		venueOptions.push({
			label: sprintf(
				/* translators: %s: Venue. */
				__('Select %s', 'calendas'),
				__('Venue', 'calendas')
			),
			value: 0,
		});
		venuePosts.forEach((post) => {
			venueOptions.push({ label: post.title.rendered, value: post.id });
		});
	} else {
		venueOptions.push({ label: __('Loading...', 'calendas'), value: 0 });
	}

	const { organizerPosts } = useSelect((select) => {
		const { getEntityRecords } = select('core');

		return {
			organizerPosts: getEntityRecords(
				'postType',
				globalDataUpcomingEvents.cptOrganizer,
				{
					per_page: -1,
					status: ['publish'],
					_fields: 'id,title',
					context: 'view',
					lang: globalDataUpcomingEvents.language,
				}
			),
		};
	});

	let organizerOptions = [];
	if (organizerPosts) {
		organizerOptions.push({
			label: sprintf(
				/* translators: %s: Organizer. */
				__('Select %s', 'calendas'),
				__('Organizer', 'calendas')
			),
			value: 0,
		});
		organizerPosts.forEach((post) => {
			organizerOptions.push({
				label: post.title.rendered,
				value: post.id,
			});
		});
	} else {
		organizerOptions.push({
			label: __('Loading...', 'calendas'),
			value: 0,
		});
	}

	const blockSettings = (
		<>
			<TextControl
				type="text"
				label={__('Title', 'calendas')}
				value={title || ''}
				onChange={(val) => {
					setAttributes({
						title: val,
					});
				}}
			/>

			<TextControl
				type="number"
				label={__('Number of events', 'calendas')}
				value={parseInt(perPage)}
				min="1"
				max="100"
				onChange={(val) => {
					setAttributes({
						perPage: parseInt(val),
					});
				}}
			/>

			<SelectControl
				label={__('Layout', 'calendas')}
				options={[
					{ label: __('Vertical', 'calendas'), value: 'vertical' },
					{
						label: __('Horizontal', 'calendas'),
						value: 'horizontal',
					},
				]}
				value={layout || 'vertical'}
				onChange={(val) => {
					setAttributes({
						layout: String(val),
					});
				}}
			/>

			<CheckboxControl
				label={sprintf(
					/* translators: %s: Venue. */
					__('Show %s link', 'calendas'),
					__('Venue', 'calendas')
				)}
				checked={!!showVenue}
				onChange={() => {
					setAttributes({
						showVenue: !showVenue,
					});
				}}
			/>

			<CheckboxControl
				label={sprintf(
					/* translators: %s: Organizer. */
					__('Show %s link', 'calendas'),
					__('Organizer', 'calendas')
				)}
				checked={!!showOrganizer}
				onChange={() => {
					setAttributes({
						showOrganizer: !showOrganizer,
					});
				}}
			/>

			<CheckboxControl
				label={__('Featured events only', 'calendas')}
				checked={!!featuredOnly}
				onChange={() => {
					setAttributes({
						featuredOnly: !featuredOnly,
					});
				}}
			/>

			<CheckboxControl
				label={__('Featured events first', 'calendas')}
				checked={!!featuredFirst}
				onChange={() => {
					setAttributes({
						featuredFirst: !featuredFirst,
					});
				}}
			/>

			<hr></hr>

			{!!venueOptions && (
				<SelectControl
					label={__('Venue', 'calendas')}
					options={venueOptions}
					value={parseInt(venue)}
					onChange={(val) => {
						setAttributes({
							venue: parseInt(val),
						});
					}}
				/>
			)}

			{!!organizerOptions && (
				<SelectControl
					label={__('Organizer', 'calendas')}
					options={organizerOptions}
					value={parseInt(organizer)}
					onChange={(val) => {
						setAttributes({
							organizer: parseInt(val),
						});
					}}
				/>
			)}
		</>
	);

	return (
		<div {...blockProps}>
			<InspectorControls>
				<PanelBody title={__('Settings', 'calendas')}>
					{blockSettings}
				</PanelBody>
			</InspectorControls>

			<ServerSideRender
				block="calendas/upcoming-events"
				attributes={{
					title: title,
					perPage: perPage,
					featuredOnly: featuredOnly,
					featuredFirst: featuredFirst,
					venue: venue,
					organizer: organizer,
				}}
			/>
		</div>
	);
};

export default Edit;
