# BerryPay Payment Gateway Wordpress Plugin using GiveWP 

> Contributors: BerryPay<br>
> Tags: payment gateway, Malaysia, online banking<br>
> Requires at least: 4.3<br>
> Tested up to: 5.9.2<br>
> Stable tag: 2.0.1<br>
> License: GPLv2 or later<br>
> License URI: http://www.gnu.org/licenses/gpl-2.0.html<br>	
> BerryPay payment gateway plugin for GiveWP.

## Description
	
BerryPay payment gateway plugin for GiveWP. This plugin enable online payment using online banking (for Malaysian banks only). Currently BerryPay is only available for businesses that reside in Malaysia.
	
## Supported version:
* Supports Wordpress 5.9.2
* Supports GiveWP 2.19.5

## Installation

1. Download this plugin using .Zip format folder.
2. Make sure that you already have GiveWP plugin installed and activated.
3. From your Wordpress admin dashboard, go to menu 'Plugins' and 'Add New'.
4. Upload the .Zip format folder inside Wordpress Dashboard.
5. It will display the plugin and press intall.
6. Activate the plugin through the 'Plugins' screen in WordPress.
7. Go to menu Donations, settings, Payment Gateway, select and activate BerryPay, fill in your "merchant_name" as "merchant_pub_key", "api_key" and "secret_key". You can retrieve the merchant id, api_key and secret_key from BerryPay Dashboard at https://secure.berrpaystaging.com/ (login credentials will be provided upon successful merchant registration).
8. The environment mode by default is sandbox. Upon successful integration, we will provide the production credentials.
9. Make sure the 'Enable this payment gateway' is ticked. Click on 'Save changes' button.
10. Please use SBI BANK A (for success and fail transaction) on Testing mode only, fail testing scenario you can cancel upon login bank sandbox. Bank Sandbox login is username: 1234 , password: 1234

## Frequently Asked Questions
	
**Do I need to sign up with BerryPay in order to use this plugin?**
	
Yes, we require info such as merchant id and secret key that is only available after you sign up with BerryPay.
	
**Can I use this plugin without using WooCommerce?**
	
No.
	
**What currency does it support?**
	
Currently BerryPay only support Malaysian Ringgit (RM).
	
**What if I have some other question related to BerryPay?**
	
Please open a ticket by emailing to us, servicedesk@berrypay.com.
