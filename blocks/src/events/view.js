import { store, getElement, getContext } from '@wordpress/interactivity';
// import { __, sprintf } from "@wordpress/i18n";
// [TODO]: Remove workaround once @wordpress modules other than "interactivity" work!
const __ = wp.i18n.__;
const sprintf = wp.i18n.sprintf;

// https://developer.wordpress.org/block-editor/reference-guides/packages/packages-api-fetch/
// import apiFetch from "@wordpress/api-fetch"; // [TODO]: Uncomment once @wordpress modules other than "interactivity" are supported!
// import date from "@wordpress/date"; // [TODO]: Uncomment once @wordpress modules other than "interactivity" are supported!
const { apiFetch, date } = wp;

// https://schedule-x.dev/docs/calendar
import 'temporal-polyfill/global';
import {
	createCalendar,
	createViewDay,
	createViewMonthAgenda,
	createViewMonthGrid,
	createViewWeek,
	createViewList,
} from '@schedule-x/calendar';
import { createEventsServicePlugin } from '@schedule-x/events-service';
import { createCalendarControlsPlugin } from '@schedule-x/calendar-controls';

// Programmatically update entries: https://schedule-x.dev/docs/calendar/plugins/events-service
const eventsServicePlugin = createEventsServicePlugin();
// Programmatically change timezone: https://schedule-x.dev/docs/calendar/plugins/calendar-controls
const calendarControls = createCalendarControlsPlugin();

import { outputEventDatetime, lightenColor, textColor } from './helpers.js';

// Helper function to convert dates: https://schedule-x.dev/docs/calendar/temporal#js-date
const dateToZonedDateTime = (date, timeZone = 'UTC') => {
	const instant = Temporal.Instant.fromEpochMilliseconds(date.getTime());

	return instant.toZonedDateTimeISO(timeZone);
};

// Helper function to create event data
const eventsDataOutput = (data) => {
	const {
		id,
		title: { rendered: title } = {},
		link,
		class_list = null,
	} = data;
	const {
		image,
		featured,
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

	if (!id || !title || !link || !start_datetime || !end_datetime) {
		console.error(data.id, 'Required event_data parameters not found!');
	}

	const postClass = class_list ? class_list.join(' ') : '';
	const eventTitle = title ?? __('Untitled', 'calendas');
	const eventTimezone =
		timezone && timezone.length ? timezone : systemTimezone;
	const eventTimestamp = outputEventDatetime(
		start_datetime,
		end_datetime,
		eventTimezone,
		date
	);

	// TODO: Include Organizer + Venue?
	const customContent = (view = 'month') => {
		return `<div class="${postClass}" title="${eventTitle}"><small class="sx__${view}-grid-event-time">${eventTimestamp}${
			eventTimezone !== systemTimezone
				? `<br><small>${eventTimezone.replace('_', ' ')}</small>`
				: ''
		}</small><span class="sx__${view}-grid-event-title">${eventTitle}</span>${
			image ?? ''
		}</div>`;
	};

	const startDatetimeUtc = new Date(start_datetime * 1000);
	const endDatetimeUtc = new Date(end_datetime * 1000);

	return {
		id: id,
		title: eventTitle,
		link: link,
		start: dateToZonedDateTime(startDatetimeUtc),
		startPlain: start_datetime,
		end: dateToZonedDateTime(endDatetimeUtc),
		endPlain: end_datetime,
		timezone: eventTimezone,
		venue: venue_title,
		venueLink: venue_link,
		organizer: organizer_title,
		organizerLink: organizer_link,
		_customContent: {
			monthGrid: customContent(),
			timeGrid: customContent('time'),
			dateGrid: customContent('time'),
		},
	};
};

const lang = document.documentElement.lang.substring(0, 2); // Required for Polylang
const systemTimezone = date.getSettings().timezone.string;
const userTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone;

let calendarView;
let rangeStartDate;
let rangeEndDate;

store('calendas', {
	callbacks: {
		getEvents: () => {
			const { ref } = getElement(),
				context = getContext();

			calendarView = ref.querySelector('#calendar-view');

			//const timeIcon = `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><g stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M12 8v4l3 3"/><circle cx="12" cy="12" r="9"/></g></svg>`;
			const venueIcon = `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><g stroke="currentColor" stroke-width="2"><path d="M12 11h.01v.01H12z"/><path d="m12 22 5.5-5.5a7.778 7.778 0 1 0-11 0z"/></g></svg>`;
			const organizerIcon = `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><g stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="m12.78 8.084h2.439v3.833h-2.439zm-4 0h2.439v3.833h-2.439z" fill="currentColor" stroke-width="0"/></g></svg>`;

			// https://schedule-x.dev/docs/calendar/theme
			if (context.primaryColor) {
				const colorPrimaryContainer = lightenColor(
					context.primaryColor,
					80
				);
				const colorSurfaceContainer = lightenColor(
					context.primaryColor,
					94
				);

				let stylesheet = document.createElement('style');
				stylesheet.innerText = `:root {
					--sx-color-primary: ${
						context.primaryColor
					}; --sx-color-primary-container: ${colorPrimaryContainer}; ${
						textColor(colorPrimaryContainer) ?? ''
					}; --sx-color-surface-container-low: ${colorSurfaceContainer};
				}`;
				document.head.appendChild(stylesheet);
			}

			let calViews = [];

			if (context.showMonth) {
				calViews.push(createViewMonthGrid());
				calViews.push(createViewMonthAgenda());
			}
			if (context.showWeek) {
				calViews.push(createViewWeek());
			}
			if (context.showDay) {
				calViews.push(createViewDay());
			}
			if (context.showList) {
				calViews.push(createViewList());
			}

			// Read values from getContext(): https://schedule-x.dev/docs/calendar/configuration
			const config = {
				postType: context.postType,

				views: calViews,

				/**
				 * Set the language. List of supported languages: https://schedule-x.dev/docs/calendar/language
				 * For support of further languages, please open a PR, adding your translations under the folder:
				 * packages/translations/src/locales/xx-XX
				 *
				 * Defaults to 'en-US'
				 */
				locale: context.locale.replace(/[_]/g, '-'),

				/**
				 * Set the timezone.
				 * Defaults to 'UTC'
				 */
				timezone: systemTimezone,

				/**
				 * Set which day is to be considered the starting day of the week.
				 * Follows Temporal API for Gregorian calendar: 1 = Monday, 2 = Tuesday, (...other days) 7 = Sunday
				 * Defaults to 1 (Monday)
				 */
				firstDayOfWeek: context.firstDayOfWeek, // 7 = Sunday

				/**
				 * The preferred view to display when the calendar is first rendered.
				 * all views that you import have a "name" property, which helps you identify them.
				 * Defaults to the first view in the "views" array
				 */
				defaultView: context.defaultView,

				/**
				 * The default date to display when the calendar is first rendered. Only accepts Temporal.PlainDate format.
				 * Defaults to the current date
				 */
				//selectedDate: Temporal.PlainDate.from('2025-12-24'),

				/**
				 * Render the calendar in dark mode.
				 * Defaults to false
				 */
				isDark: false,

				/**
				 * Decides which hours should be displayed in the week and day grids. Only full hours are allowed; 01:30, for example, is not allowed.
				 * Defaults to midnight - midnight (a full day)
				 * Can also be set to a "hybrid" day, such as { start: '06:00', end: '03:00' }, meaning each day starts at 6am but
				 * extends into the next day until 3am.
				 */
				dayBoundaries: context.dayBoundaries,

				weekOptions: {
					/**
					 * The total height in px of the week grid (week- and day views)
					 */
					gridHeight: 2500,

					/**
					 * The number of days to display in week view
					 */
					nDays: context.weekDays,

					/**
					 * The width in percentage of the event element in the week grid
					 * Defaults to 100, but can be used to leave a small margin to the right of the event
					 */
					eventWidth: 95,

					/**
					 * Intl.DateTimeFormatOptions used to format the hour labels on the time axis
					 * Default: { hour: 'numeric' }
					 */
					timeAxisFormatOptions: {
						hour: '2-digit',
						minute: '2-digit',
					},

					/**
					 * Determines whether concurrent events can overlap.
					 * Defaults to true. Set to false to disable overlapping.
					 */
					eventOverlap: true,
				},

				monthGridOptions: {
					/**
					 * Number of events to display in a day cell before the "+ N events" button is shown
					 */
					nEventsPerDay: 8,
				},

				/**
				 * Display week numbers. Not 100% according to ISO 8601, which considers a week to start on Monday and end on Sunday.
				 * Since Schedule-X enables you to configure the first day of the week, the week numbers are calculated based on that.
				 */
				showWeekNumbers: context.showWeekNumbers,

				/**
				 * Toggle automatic view change when the calendar is resized below a certain width breakpoint.
				 * Defaults to true
				 */
				isResponsive: true,

				/**
				 * Skip validating events when initializing the calendar. This can help you gain a bit of performance if you are loading a lot of events,
				 * and you are sure that the events are valid.
				 */
				skipValidation: true,

				callbacks: {
					async fetchEvents(range) {
						calendarView.classList.add('processing');

						// Get dates based on calendar view range
						rangeStartDate = range.start?.toString().split('T')[0];
						rangeEndDate = range.end?.toString().split('T')[0];

						// Create API parameters
						const apiParams = {
							per_page: 100,
							status: 'publish',
							after: `${rangeStartDate}T00:00:00`,
							before: `${rangeEndDate}T23:59:59`,
							lang: lang,
						};

						const params = new URLSearchParams(
							document.location.search
						);
						const search = params.get(
							encodeURIComponent(context.urlParameterSearch)
						);

						if (search && search.length > 2) {
							apiParams.search = search;
						}

						/**
						 * Fetch events
						 */
						let eventsData = [];

						try {
							const res = await apiFetch({
								path: `/wp/v2/${context.postType}?${new URLSearchParams(apiParams).toString()}`,
								method: 'GET',
							});

							if (!res.length) {
								return [];
							}

							res.forEach((data, index) => {
								const eventAlreadyExists = eventsData.some(
									(item) => item['id'] === data['id']
								);

								if (!eventAlreadyExists) {
									eventsData.push(eventsDataOutput(data));
								}
							});

							calendarView.classList.remove('processing');
						} catch (error) {
							console.error(
								'Error fetching events on init:',
								error
							);
						}

						return eventsData;
					},

					onEventClick(calendarEvent, e) {
						//console.log("element", e);
						//console.log("onEventClick", calendarEvent);

						const {
							id,
							title,
							link,
							timezone,
							startPlain,
							endPlain,
							venue,
							venueLink,
							organizer,
							organizerLink,
						} = calendarEvent;

						const eventId = id;

						const eventWrapper = e.target.closest('.sx__event');
						eventWrapper.id = `event${eventId}`;

						// Create popover HTML element
						const popoverBodyId = `popover${eventId}`;

						let popoverBody =
							document.getElementById(popoverBodyId);

						if (document.body.contains(popoverBody)) {
							document.body.removeChild(popoverBody);
						}

						const eventTimezone =
							timezone && timezone.length
								? timezone
								: systemTimezone;

						const eventTimestamp = outputEventDatetime(
							startPlain,
							endPlain,
							eventTimezone,
							date
						);

						const img =
							calendarEvent?._customContent?.monthGrid.match(
								/<img[^>]*src=["']([^"']+)["'][^>]*>/g
							);

						popoverBody = document.createElement('div');
						popoverBody.dataset.id = eventId;
						popoverBody.id = popoverBodyId;
						popoverBody.setAttribute('popover', '');
						popoverBody.innerHTML = `<div><h2><a href="${link}">${title}</a></h2><small>${eventTimestamp}${
							eventTimezone !== systemTimezone
								? `<br><small>${eventTimezone.replace('_', ' ')}</small>`
								: ''
						}</small>${
							organizer
								? `<small>${organizerIcon}<a href="${organizerLink}">${organizer}</a></small>`
								: ''
						}${
							venue
								? `<small>${venueIcon}<a href="${venueLink}">${venue}</a></small>`
								: ''
						}</div>${img ? `<a href="${link}">${img[0]}</a>` : ''}`;

						// Logged in? TODO: Restrict to users with edit permissions
						if (document.body.classList.contains('logged-in')) {
							const url = wp.apiFetch.nonceEndpoint;

							popoverBody.innerHTML += `<p class="edit"><a href="${url.slice(
								0,
								url.lastIndexOf('/')
							)}/post.php?post=${id}&action=edit">✏️</a></p>`;
						}

						// Position the popover in the event wrapper
						const rect = eventWrapper.getBoundingClientRect();
						const scrollY = Math.round(window.scrollY);

						popoverBody.style.position = 'absolute';
						popoverBody.style.top = `${scrollY + rect.top}px`;
						popoverBody.style.left = `${rect.left}px`;

						document.body.appendChild(popoverBody);

						// Show the popover
						if (typeof popoverBody.showPopover === 'function') {
							popoverBody.showPopover();
						} else {
							console.warn(
								'showPopover method not found on popoverBody.'
							);
						}

						// Adjust popover position if it overlaps the window
						const popoverRightEdge =
							rect.left + popoverBody.offsetWidth;
						const windowWidth = window.innerWidth;

						if (popoverRightEdge > windowWidth) {
							const offset = popoverBody.offsetWidth - rect.width;
							popoverBody.style.transform = `translateX(-${offset}px)`;
						}
					},

					onRangeUpdate: ({ start, end }) => {
						setTimeout(() => {
							calendarView.classList.remove('processing');
						}, 100);
					},

					onRender($app) {
						calendarView.classList.remove('processing');
					},
				},
			};

			// Fetch min date from first event and update config
			const fetchData = async () => {
				try {
					await apiFetch({
						path: `/wp/v2/${context.postType}?order=asc&per_page=1`,
						method: 'GET',
					}).then((res) => {
						config.minDate = Temporal.PlainDate.from(
							new Date(res[0].event_data.start_datetime * 1000)
								.toISOString()
								.split('T')[0]
						); // YYYY-MM-DD.

						const today = new Date();
						const maxDate = new Date(
							today.setFullYear(today.getFullYear() + 1) // Max. 1 year. TODO: Make it configurable!
						);
						config.maxDate = Temporal.PlainDate.from(
							maxDate.toISOString().split('T')[0]
						); // YYYY-MM-DD
					});
				} catch (error) {
					console.error('Error fetching events on init:', error);
				}
			};

			// Call the async function and render the calendar view
			fetchData().then(() => {
				const calendar = createCalendar(config, [
					calendarControls,
					eventsServicePlugin,
				]);
				calendar.render(calendarView);
			});
		},
		searchEvents: () => {
			const { ref } = getElement(),
				context = getContext();

			calendarView.classList.add('processing');

			// Create API parameters
			const apiParams = {
				per_page: 100,
				status: 'publish',
				after: `${rangeStartDate}T00:00:00`,
				before: `${rangeEndDate}T23:59:59`,
				lang: lang,
			};

			const search = ref.value;

			const url = new URL(window.location.href);

			if (search && search.length > 2) {
				url.searchParams.set(context.urlParameterSearch, search);

				apiParams.search = search;
			} else {
				url.searchParams.delete(context.urlParameterSearch);
			}

			window.history.replaceState(null, null, url);

			/**
			 * Fetch events incl. the ones that are potentially overlapping the current view range and therefore also need to be added to the data output
			 */
			let pastDate = new Date(Date.parse(rangeStartDate));
			pastDate.setDate(pastDate.getDate() - 120); // Limit events to last 120 days - TBD!

			apiParams.after =
				pastDate.toISOString().split('T')[0] + 'T00:00:00';

			let eventsData = [];

			const fetchData = async () => {
				try {
					const res = await apiFetch({
						path: `/wp/v2/${context.postType}?${new URLSearchParams(apiParams).toString()}`,
						method: 'GET',
					});

					if (!res.length) {
						return [];
					}

					res.forEach((data, index) => {
						const eventAlreadyExists = eventsData.some(
							(item) => item['id'] === data['id']
						);

						if (!eventAlreadyExists) {
							eventsData.push(eventsDataOutput(data));
						}
					});
				} catch (error) {
					console.error('Error fetching events on search:', error);
				}
			};

			// Execute the fetch function and set events
			fetchData().then(() => {
				eventsServicePlugin.set(eventsData);
			});
		},
		clearInput: () => {
			const { ref } = getElement();

			ref.previousElementSibling.value = '';
			ref.previousElementSibling.dispatchEvent(new Event('input'));
			ref.previousElementSibling.focus();
		},
	},
	actions: {
		clearTimezone: () => {
			const { ref } = getElement();

			ref.value = '';
		},
		changeTimezone: () => {
			const { ref } = getElement();

			let val = ref.value;

			if (val.length === 0 || (val !== 'UTC' && !val.includes('/'))) {
				val = systemTimezone;
				ref.value = val;
			}

			calendarControls.setTimezone(val);
		},
	},
});
