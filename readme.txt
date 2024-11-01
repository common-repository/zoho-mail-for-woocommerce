=== Zoho Mail for WooCommerce ===
Contributors: Zoho Mail
Tags: mail,zoho,zoho mail,woo, woocommerce
Donate link: none
Requires at least: 4.8
Tested up to: 6.6
Requires PHP: 5.6
Stable tag: 1.0.0
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Zoho Mail Plugin lets you configure your Zoho Mail account on your WooCommerce site enabling you to send transactional emails of your site via Zoho Mai API.

== Description ==

= Zoho Mail for WooCommerce =

The Zoho Mail plugin helps you to configure your Zoho Mail account in your WooCommerce site, to send notification emails from your website.

== PRE-REQUISITES ==
- A Zoho Mail Account
- A self-hosted WordPress account
- WooCommerce plugin installed

== Zoho Mail API ==
You can use the OAuth token of your Zoho Mail account to send transactional emails from your site using Zoho Mail API.

== INSTALLATION ==
1. Login to your WooCommerce account. In the plugins section, search for the  "ZeptoMail" plugin.
2. Click Install now.
3. To configure your account, you need to add and generate the Client id and Client secret (credentials) parameters from your Zoho account. Navigate to this link ( https://api-console.zoho.com/)
4. Enter your server details and the authorization URL available in the plugin installation page.
5. Once you enter these details, you will be able to generate the client id and client secret parameters. Add these to the plugin installation section.
6. Once done, you will be taken to the Authorization page(https://accounts.zoho.com) where you should allow Zoho Mail to access your data. With this, the configuration setup will be done and you can start using the plugin. (The TLD (.com) for the domain will vary based on the region in which the user data is hosted (zoho.com,zoho.eu, zoho.com.au, zoho.com.cn, zoho.jp,zoho.in, zoho.sa, zohocloud.ca ) )

== Zoho Mail PLUGIN PARAMETERS ==
- ** Account hosted** : The host that will be used to invoke API call: https://accounts.zoho.com/ and https://mail.zoho.com/ (The TLD (.com) for the domain will vary based on the region in which the user data is hosted (zoho.com,zoho.eu, zoho.com.au, zoho.com.cn, zoho.jp,zoho.in, zoho.sa, zohocloud.ca ) )
- **Client Id** :The public identifier for your application.
- **Client secret** : Confidential key used to authenticate your application.
- **From Email Address** :The Email address that will be used to send all the outgoing transactional emails from your website.
- **From Name** :The Name that will be shown as the display name while sending all emails from your website.

== Frequently Asked Questions ==
Can I send the email via ZohoMail from my website using this plugin? 
Yes
= Where do I go for help with any issues? =
In case, you are not sure on how to proceed with the Zoho Mail plugin, feel free to contact support@zohomail.com.
= What should I do if I get 'Invalid Client Secret' issue? =
 - While configuring your plugin, ensure you have entered the correct information in the Domain field. Select the region in which your Zoho account is hosted (in, com etc).
 - Verify if the Client ID and Client Secret used in the configuration page matches with the Client created for the plugin in Zoho Developer Console. 
 - If all the above-given troubleshooting methods do not resolve the issue, reach out to our Customer support (support@zohomail.com) with the screenshot of the configuration settings page for a solution. 


== Screenshots ==
1. Configure Account(screenshot-1.png)

== Changelog ==
= 1.0.0 =
Initial changes

== Upgrade Notice ==
none


