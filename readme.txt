=== LINE Pay Gateway for WooCommerce ===
Contributors: happydenn
Donate link:
Tags: woocommerce, payment, ecommerce, mobile-payment, line-pay
Requires at least: 4.0
Tested up to: 4.3.1
Stable tag: 0.4.3
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.en.html

Accept LINE Pay mobile payments on your WooCommerce powered store!

== Description ==

= English =

Tested on WooCommerce 2.4+

Accept LINE Pay mobile payments on your WooCommerce powered store now!

[LINE Pay](http://line.me/en/pay) is a mobile payment service available in
Taiwan, Japan and Thailand, run by one of the largest mobile messaging app
company in the world, LINE Corporation.

Easily accept payments for all your goods with credit cards and LINE Pay
balance.

The plugin has the following features:

* Requesting payments
* Confirming payments
* Refunds

This plugin supports automatic refunds from WooCommerce Order backend.

Translations are welcomed! Currently available in English and Traditional
Chinese.

Follow the development of this plugin on
[GitHub](https://github.com/happydenn/wc-payment-gateway-line-pay).

= 繁體中文 =

使用本外掛可以為您的 WooCommerce 電子商務網站輕鬆快速地加上
[LINE Pay](http://line.me/zh-hant/pay) 的收款方式。

本外掛目前支援的 LINE Pay 功能如下：

* 請求付款 (reserve)
* 確認付款 (confirm)
* 退款 (refund)

退款部分支援部分退款和全額退款，請直接到 WooCommerce 的訂單後台進行操作即可。

開發 GitHub: https://github.com/happydenn/wc-payment-gateway-line-pay

== Installation ==

1. Upload the plugin zip file using wp-admin.
2. Activate the plugin through the 'Plugins' menu.
3. Configure the gateway in WooCommerce Settings in the 'Checkout' tab.

You must fill in all the fields in gateway settings or the plugin won't work
correctly.

== Frequently Asked Questions ==

= How do I get started? =

First you will need to register as a merchant with LINE Pay. Find out more
information on LINE Pay's website.

= I've encountered some problems about the service, what should I do now? =

Please note that this plugin only serves as an API client implementation to the
LINE Pay service, any questions related to the service itself must be directed
to LINE Pay's support team. The plugin's author does not offer support about
the service.

== Changelog ==

= 0.4.3 =
* Fixed http request client not setting encoding to UTF-8 was causing problems
for some people.

= 0.4.2 =
* Added an option to change the payment method icon.
* Added Japanese translation contributed by Kay Lin.
* Fixed default payment icon to match LINE Pay's standard. (In Traditional
Chinese)

= 0.4.0 =
* Make the plugin i18n capable.

= 0.3.5 =
* Added a check for WooCommerce during plugin activation.

= 0.3.4 =
* Bumped version in all affected files.

= 0.3.3 =
* Fixed empty() usage with PHP below 5.5.

= 0.3.0 =
* First public release.
* Should be fully working with the current LINE Pay service. (Tested with a
Taiwanese LINE Pay merchant account)
