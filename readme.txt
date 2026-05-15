=== Calendas ===
Contributors: themes1
Donate link: https://them.es
Tags: events, calendar, event-calendar, events-calendar
Requires at least: 6.8
Tested up to: 7.0
Stable tag: 0.9.0
Requires PHP: 8.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

📅 A modern Events Calendar Plugin for WordPress

== Description ==

* Beautiful design
* Light-weight
* Fully customizable out of the box with easy configuration
* Calendar block with several views
* Upcoming Events: Block and Dashboard widget
* Core technology: WP-API, Query Loop block integration
* Responsive layout
* Multilingual: Polylang and WPML
* Venues
* Organizers
* Geocoding of address fields - powered by openstreetmap.org
* Embedded Google Maps
* Fast load time by making use of an intelligent caching method

= Contribution? =

* The Plugin development can be followed via GitHub <3
* We are happy to receive feature suggestions and pull requests: [https://github.com/them-es](https://github.com/them-es "GitHub")

== Screenshots ==

1. Edit mode
2. Frontend: Month view
3. Frontend: Single view
4. Dashboard widget: Upcoming Events

== Installation ==

1. Upload the Plugin to the `/wp-content/plugins/` directory.
2. Activate it through the 'Plugins' menu in WordPress.
3. Add a new page and include the `Events` block. Configure the calendar settings.
4. Publish your first events.

== External services ==

This plugin can embed a Google Maps iframe when lat/lng geocoordinates are specified by an editor.
Google Maps is a web mapping platform developed by Google.
For more information on the data processing please visit: https://www.google.com/help/terms_maps/

== Frequently Asked Questions ==

= Where can I find the developer documentation? =
* __Demos and docs can be found here:__
  * [https://them.es/plugins/calendas](https://them.es/plugins/calendas "them.es")

= Which calendar views are available? =
* You can choose between Month, Week, Day and List view (or even enable all of them).
* The views can be configured in the block settings.
* If you don't want to use the provided Calendar block at all, just add a Query Loop block and select the post type "Events". This basic core-only feature that may be sufficient for one or the other.

= There are already several Events Calendar plugins on the market. Why did you develop a new plugin? =
* We believe that the majority of WordPress users are looking for a calendar solution that is simple, light-weight and future-proof. So we decided to start building a new plugin from scratch.
* Our solution is easy to customize and uses a well-crafted code base with the latest WordPress technology that is fully compatible with the latest PHP version.
* What's more, it only makes use of really necessary dependencies, selected with care. Compare the file sizes of the plugins and you will notice a difference.

= The calendar frontend looks really great. What's the technology in use? =
* Most of the calendar frontend, including the Javascript logic is based on the "schedule-x" JS library (MIT).

= Is the Plugin multilingual-ready? =
* Yes, the Plugin has been tested with Polylang and WPML.

= How do I change the color scheme? =
* You can define a primary color in the Events Calendar block via a color picker.
* If you require additional customization, you need to modify your stylesheet.

= Can I create organizers and venues? =
* Yes, these have been integrated as custom posts, ensuring you full flexibility.
* Once setup the posts can be assigned to your events.

= How do I migrate my existing events from third-party plugins? =
* Currently only "The Events Calendar" migrations with relevant data are supported.
* Please understand that this feature can only be carried out with the PRO version. Please go to our website https://them.es/plugins/calendas to find out more.

= Is it possible to add a contact form to my events? =
* You have full control over the content in the Block editor and of course you can also add a contact form there.
* Additionaly you can use PHP filters to add custom content or similar.

= Do you accept code contributions? =
* Yes. This is an Open Source project, so bug fixes, useful code additions or integrating automated tests, that could improve quality and make development easier, are more than welcome. You can find all information on https://them.es/plugins/calendas and in our Github profile https://github.com/them-es.
* The plugin has been uploaded to the WordPress plugin directory, so please make sure you follow the [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards "WordPress Coding Standards") and document your code accordingly.
* Please understand that we are strong advocates of the [KISS](https://en.wikipedia.org/wiki/KISS_principle "KISS") principle, meaning that our main goal is to keep everything as simple as possible. This means that not all sophisticated pull requests can be considered for inclusion. Sometimes it's better to just create an add-on plugin or similar.

= What features are available in the PRO version? Where can I purchase it? =
* Migration of Events, Organizers and Venues from "The Events Calendar".
* Recurring Events.
* Duplicating Events.
* Customizable Block Templates with Full Site Editing.
* Please go to https://them.es/plugins/calendas to purchase a lifetime license.
* Priority support via https://them.es/support

== Changelog ==

= 0.9.0 =
* Initial Release - see announcement post on https://them.es/support for more information
* Created a GitHub repository with all development sources
