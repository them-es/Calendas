import { store, getElement, getContext } from '@wordpress/interactivity';
// import { useSelect } from "@wordpress/data";
// import { __, sprintf } from "@wordpress/i18n";
// [TODO]: Remove workaround once @wordpress modules other than "interactivity" work!
const __ = wp.i18n.__;
const sprintf = wp.i18n.sprintf;

// https://developer.wordpress.org/block-editor/reference-guides/packages/packages-api-fetch/
// import apiFetch from "@wordpress/api-fetch"; // [TODO]: Uncomment once @wordpress modules other than "interactivity" are supported!
// import date from "@wordpress/date"; // [TODO]: Uncomment once @wordpress modules other than "interactivity" are supported!
const { apiFetch, date } = wp;

import { outputEventDatetime } from '../events/helpers.js';

function liOutput(data, context = {}) {
	const { link, title: { rendered: title } = {}, class_list } = data;
	const {
		// featured,
		timezone,
		start_datetime,
		end_datetime,
		venue,
		venue_title,
		venue_link,
		organizer,
		organizer_title,
		organizer_link,
	} = data.event_data;

	const postClass = class_list ? class_list.join(' ') : '';

	const venueIcon = `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><g stroke="currentColor" stroke-width="2"><path d="M12 11h.01v.01H12z"/><path d="m12 22 5.5-5.5a7.778 7.778 0 1 0-11 0z"/></g></svg>`;
	const organizerIcon = `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><g stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="m12.78 8.084h2.439v3.833h-2.439zm-4 0h2.439v3.833h-2.439z" fill="currentColor" stroke-width="0"/></g></svg>`;
	const eventTimezone = timezone.length
		? timezone
		: date.getSettings().timezone.string; // Fallback is system timezone

	const eventTimestamp = outputEventDatetime(
		start_datetime,
		end_datetime,
		eventTimezone,
		date
	);

	const day = new Date(start_datetime * 1000).getDate();
	const month = new Date(start_datetime * 1000).toLocaleString('default', {
		month: 'short',
	});

	const organizerHtml =
		organizer && context?.showOrganizer
			? `<p class="organizer"><small>${organizerIcon} <a href="${organizer_link}">${organizer_title}</a></small></p>`
			: '';
	const venueHtml =
		venue && context?.showVenue
			? `<p class="venue"><small>${venueIcon} <a href="${venue_link}">${venue_title}</a></small></p>`
			: '';

	return `<li class="${postClass}">
		<a href="${link}" class="calendar">
			<span class="month">${month}</span>
			<span class="day">${day}</span>
		</a>
		<div class="content">
			<p class="title"><a href="${link}">${title}</a></p>
			<p class="date"><small>${eventTimestamp}</small></p>
			${organizerHtml}${venueHtml}
		</div>
	</li>`;
}

store('calendas', {
	callbacks: {
		getUpcomingEvents: function* () {
			const { ref } = getElement(),
				context = getContext();

			ref.classList.add('processing');

			// Required for Polylang
			const lang = document.documentElement.lang.substring(0, 2);
			// Only get upcoming events
			const d = new Date();
			const today = d.toISOString().split('T')[0] + 'T00:00:00';
			const nextYear =
				new Date(d.getFullYear() + 1, d.getMonth(), d.getDate())
					.toISOString()
					.split('T')[0] + 'T23:59:59';

			const apiParams = new URLSearchParams({
				per_page: -1,
				status: 'publish',
				after: today,
				before: nextYear,
				lang: lang,
				'upcoming-events': true,
			});

			let content = '';

			// Fetch all events
			yield apiFetch({
				path: `/wp/v2/${context.postType}?${apiParams.toString()}`,
				method: 'GET',
			})
				.then((res) => {
					if (res.length < 1) {
						ref.innerHTML =
							'<li>' +
							__('No events found!', 'calendas') +
							'</li>';

						return;
					}

					// [OPTIONAL] Fetch featured events first
					if (
						context.featuredFirst &&
						context.featuredFirst !== false
					) {
						let countFeatured = 0;

						res.forEach((data) => {
							const { featured, venue, organizer } =
								data.event_data;

							// Restrict to featured only
							if (!featured.length) {
								return;
							}

							// Restrict to venue
							if (
								context.venue &&
								!isNaN(context.venue) &&
								parseInt(context.venue) !== parseInt(venue)
							) {
								return;
							}

							// Restrict to organizer
							if (
								context.organizer &&
								!isNaN(context.organizer) &&
								parseInt(context.organizer) !==
									parseInt(organizer)
							) {
								return;
							}

							if (++countFeatured > 4) {
								return;
							}

							content += liOutput(data, context);
						});
					}

					let count = 0;

					res.forEach((data) => {
						const { featured, venue, organizer } = data.event_data;

						// Restrict to featured only
						if (
							context.featuredOnly &&
							context.featuredOnly !== false &&
							!featured.length
						) {
							return;
						}

						// Restrict to venue
						if (
							context.venue &&
							!isNaN(context.venue) &&
							parseInt(context.venue) !== parseInt(venue)
						) {
							return;
						}

						// Restrict to organizer
						if (
							context.organizer &&
							!isNaN(context.organizer) &&
							parseInt(context.organizer) !== parseInt(organizer)
						) {
							return;
						}

						if (++count > context.perPage) {
							return;
						}

						content += liOutput(data, context);
					});

					ref.innerHTML =
						content.length > 0
							? content
							: '<li>' +
								__('No events found!', 'calendas') +
								'</li>';

					ref.classList.remove('processing');
				})
				.catch((error) => {
					console.error(error);
				});
		},
	},
});
