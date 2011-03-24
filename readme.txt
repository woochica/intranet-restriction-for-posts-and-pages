=== Intranet Restriction for Posts and Pages ===
Contributors: nyuhuhuu
Donate link: http://webdevil.hu/
Tags: intranet, access, restriction
Requires at least: 2.9.2
Tested up to: 3.1
Stable tag: 0.1

Allows to restrict the access of specific posts and pages to intranet only.

== Description ==

Allows to restrict the access of specific posts and pages to intranet only.

*   Adds an extra option to pages and posts in the admin panel where contents may be marked as restricted.
*   Intranet can defined by domain names and IP ranges (address/mask).

== Installation ==

1. Upload `intranet-restriction-for-posts-and-pages.php` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= How can I specify my intranet? =

Intranet is defined by domains names and IP ranges.

Go to the options page of the plugin, and add domain names and IP ranges to the textarea.  Put each of them in newline.  IP ranges are expected in format `address/mask`, e.g. `192.168.0.0/255.255.0.0`.

= What if I cleared all domains and IP ranges on the options page? =

Then, as you'd expect, restriction would have no effect.  Everyone may access all of your posts and pages, even those marked as restricted.

== Screenshots ==

1. Enable the checkbox to restrict the access of a page.
2. You can configure the plugin under the 'Settings' page.

== Changelog ==

= 0.1 =
* Plugin introduced.

== Upgrade Notice ==

