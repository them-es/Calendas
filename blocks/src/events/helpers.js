const __ = wp.i18n.__;

export const outputEventDatetime = (start, end, tz = 'UTC', date) => {
	// wp-date: https://developer.wordpress.org/block-editor/reference-guides/packages/packages-date/
	const startDate = new Date(start * 1000);
	const endDate = new Date(end * 1000);
	const timeIcon = `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><g stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M12 8v4l3 3"/><circle cx="12" cy="12" r="9"/></g></svg>`;
	const dateFormat = date.getSettings().formats.date;
	const timeFormat = date.getSettings().formats.time;
	const datetimeFormat = `${dateFormat} ${timeFormat}`;

	const startDateString = date.dateI18n(dateFormat ?? 'Y-m-d', startDate, tz);
	const endDateString = date.dateI18n(dateFormat ?? 'Y-m-d', endDate, tz);

	const startDateTimeString = date.dateI18n(
		datetimeFormat ?? 'Y-m-d H:i',
		startDate,
		tz
	);
	const endDateTimeString = date.dateI18n(
		datetimeFormat ?? 'Y-m-d H:i',
		endDate,
		tz
	);

	const startTimeString = date.dateI18n(timeFormat ?? 'H:i', startDate, tz);
	const endTimeString = date.dateI18n(timeFormat ?? 'H:i', endDate, tz);

	let startDateTimeFormatted = startDateTimeString;
	let endDateTimeFormatted = endDateTimeString;

	// TODO: Remove times from all day events (00:00 - 23:59)
	const isAllDay =
		'00:00' === date.dateI18n('H:i', startDate, tz) &&
		'23:59' === date.dateI18n('H:i', endDate, tz);

	if (isAllDay) {
		if (startDateString === endDateString) {
			return `${timeIcon} ${__('All day', 'calendas')}`;
		}

		startDateTimeFormatted = startDateString;
		endDateTimeFormatted = endDateString;
	} else if (startDateString === endDateString) {
		startDateTimeFormatted = startTimeString;
		endDateTimeFormatted = endTimeString;
	}

	return `${timeIcon} ${startDateTimeFormatted} - ${endDateTimeFormatted}`;
};

export const lightenColor = (color, percent) => {
	let r, g, b;

	// Handle hex color
	if (color.startsWith('#')) {
		const hexColor = color.slice(1);
		r = parseInt(hexColor.substr(0, 2), 16);
		g = parseInt(hexColor.substr(2, 2), 16);
		b = parseInt(hexColor.substr(4, 2), 16);
	}
	// Handle RGB color
	else if (color.startsWith('rgb')) {
		const rgbValues = color.match(/\d+/g);
		r = parseInt(rgbValues[0]);
		g = parseInt(rgbValues[1]);
		b = parseInt(rgbValues[2]);
	}

	// Lighten each color component
	r = Math.min(255, Math.floor(r + (255 - r) * (percent / 100)));
	g = Math.min(255, Math.floor(g + (255 - g) * (percent / 100)));
	b = Math.min(255, Math.floor(b + (255 - b) * (percent / 100)));

	// Convert back to hex
	return `#${((1 << 24) + (r << 16) + (g << 8) + b)
		.toString(16)
		.slice(1)
		.padStart(6, '0')}`;
};

export const textColor = (bgColor) => {
	let r, g, b;

	// Handle hex color
	if (bgColor.startsWith('#')) {
		const hexColor = bgColor.slice(1);
		r = parseInt(hexColor.substr(0, 2), 16);
		g = parseInt(hexColor.substr(2, 2), 16);
		b = parseInt(hexColor.substr(4, 2), 16);
	}
	// Handle RGB color
	else if (bgColor.startsWith('rgb')) {
		const rgbValues = bgColor.match(/\d+/g);
		r = parseInt(rgbValues[0]);
		g = parseInt(rgbValues[1]);
		b = parseInt(rgbValues[2]);
	}

	// Calculate brightness using the luminosity formula
	const brightness = 0.2126 * r + 0.7152 * g + 0.0722 * b;

	// Choose text color based on brightness
	if (brightness < 128) {
		return '--sx-color-on-primary-container: #FFF;';
	}
};
