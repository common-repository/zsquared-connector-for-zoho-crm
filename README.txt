=== ZSquared Connector for Zoho CRM ===
Contributors: pcis
Tags: zoho, CRM, woocommerce, ecommerce
Requires at least: 5.0
Tested up to: 5.4
Stable tag: 1.0.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

This plugin allows your WooCommerce store to send orders to Zoho CRM in real time. Each order can be triggered on various WooCommerce events to send the information into Zoho CRM.

== Description ==

This plugin allows your WooCommerce store to send orders to Zoho CRM in real time. Each order can be triggered on various WooCommerce events to send the information into Zoho CRM.

Features include:

* Populate WooCommerce store with items from Zoho CRM
* Synchronize CRM and Woocommerce products and available quantities
* Field Mapping of taxation to Zoho CRM defined taxation product (see manual)
* Send store transaction alerts to your Slack Channel

This plugin uses the ZSquared data interchange service, which is fully functional and free to try for a limited period. Subscribers enjoy email support and early access to new features. A paid subscription to Zoho One, or Zoho CRM is required.

=== Usage ===

Complete usage instructions are available here: <a href="https://zsquared.ca/files/manuals/ZSquaredConnector-CRM_UserGuide.pdf" target="_blank">ZSquared Connector for Zoho CRM Manual (PDF)</a>

== Installation ==

1. Upload the plugin files to the your plugin directory, or install the plugin through the WordPress plugins screen.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Go to Settings -> ZSquared Connector Sync to Zoho CRM
4. Click on "ZSquared Connector Service" in order to set up a new connection for your site.
5. Follow the instructions to create an account on ZSquared, and create a new connection. You will be redirected back to your site when the connection is created.
6. You're done! ZSquared will start syncing data immediately.

== Changelog ==

= 1.0.3 =
* Fixing an issue with a redirect not beiong properly handled in the API endpoints

= 1.0.2 =
* Reworked the API endpoint settings to better handle environment specific requirements

= 1.0.1 =
* Modified the API endpoint for better error reporting

= 1.0.0 =
* Initial version.