=== Log cleaner for Kadence Security ===
Contributors:       mikeyott
Tags:               kadence, security, ithemes, log, cleaner
Requires at least:  6.0
Tested up to:       7.1
Requires PHP:       7.4
Stable tag:         1.5.1
License:            GPLv2 or later
License URI:        https://www.gnu.org/licenses/gpl-2.0.html

Restores the ability to manually delete Kadence Security (formerly Solid Security / iThemes Security) logs from the database.

== Description ==

In early 2018, the ability to delete logs from the database was <a href="https://wordpress.org/support/topic/latest-update-missing-a-button-to-clear-logs/">removed</a> from iThemes Security, now known as Kadence Security. This plugin gives you that control back.

Works with Kadence Security, plus older versions released as Solid Security and iThemes Security.

== Installation ==

Install, activate, done.

== Support ==

<a href="https://wordpress.org/support/plugin/log-cleaner-for-ithemes-security">Log cleaner for Kadence Security Support</a> at the official WordPress repository.

== How to use ==

* Go to <strong>Tools</strong> -> <strong>Kadence Security Log Cleaner</strong> (on multisite, find <strong>Kadence Security Log Cleaner</strong> in the Network Admin menu)
* Select which logs to delete (or select 'All')
* Hit the <strong>Clear logs</strong> button
* Get on with the rest of your day (optional)

Note: This plugin comes with no warranty of any kind.

== Uninstall ==

Deactivate the plugin, delete if desired.

== Changelog ==

= 1.5.1 =

* Fix: Large menu icon in multisite installations.
* Added 'Requires Plugins' to header.

= 1.5.0 =

* Long overdue update to reflect new Kadence Security branding.
* Added disk space used by each table.
* Added option to clear the itsec_dashboard_lockouts and itsec_geolocation_cache tables.
* Fix: Translations not loading (text domain now matches the plugin slug again).
* Fix: Broken "view the logs first" link on multisite.
* Fix: Selecting only "All" now works without JavaScript.
* Security: Added capability check to the delete handler and hardened nonce, input and output handling.
* Multisite: Page now requires the manage_network_options capability.
* Stylesheet and script now only load on the plugin's own page.
* Minor presentation tweaks.

= 1.4.1 =

* Fix: Navigation icon size on multi-site installations.

= 1.4.0 =

* Updates to reflect new Solid Security branding.

= 1.3.6 =

* Fix: Issue where event logs could not be deleted.

= 1.3.5 =

* Removed broken sidebar.

= 1.3.4 =

* Removed handling of deprecated itsec_log table.

= 1.3.3 =

* Changed text domain to match plugin slug.

= 1.3.2 =

* Added option to clear the itsec_distributed_storage table.

= 1.3.1 =

* Removed source map reference that was causing 404 errors to be logged in Solid Security.

= 1.3 =

* Fixed 'table x doesn’t exist' error.
* Removed check for iThemes plugin activation.
* Minor presentation tweaks.

= 1.2.1 =

* Minor, non-important UI tweak.

= 1.2 =

* Corrected older reference to ITSec Log Cleaner location.
* Fixed CSS cache issue.

= 1.1 =

* Spanish translation update.

= 1.0.9 =

* Hide the warning message when the logs are clear.
* Fixed minor responsive issue.

= 1.0.8 =

* Added support for deleting dashboard events (iThemes Security Pro).
* UI reduction.
* Sidebar promoting my other WordPress tools.

= 1.0.7 =

* Spanish translation (translation by @borrockalari - thanks!).

= 1.0.6 =

* Changed menu item label (suggested by @jetxpert - thanks!).
* Removed individual links to the logs, replaced with single link to log page.
* Language updates.

= 1.0.5 =

* Tested on WordPress 5.0.2.

= 1.0.4 =

* Added multisite support.

= 1.0.3 =

* Prevent direct access to plugin file.

= 1.0.2 =

* Updated readme.

= 1.0.1 =

* Updated readme.

= 1.0 =

* Initial release.