![Paynova logo](/../screenshots/paynova_logo_c0392b_50px.png?raw=true "Paynova logo")

# Magento Payment Module v2.0.0-rc2

> This is Paynova's payment module for Magento. The module supports Magento 1.7 - 1.9. and is tested in Magento 1.9.1.0.

## Contents
<!-- MarkdownTOC depth=2 autolink=true bracket=round -->

- [Supported payment methods](#supported-payment-methods)
- [Download & installation](#download--installation)
- [Updating from previous version](#updating-from-previous-version)
- [Configuration](#configuration)
- [Known issues](#known-issues)

<!-- /MarkdownTOC -->

## Supported payment methods
* Credit/debit cards
* Real-time bank transfer
  * Swedish/Finnish/Danish banks
  * iDEAL
  * Überweisung (Germany)
* PayPal
* MasterPass
* Invoice/installments ("Invoice as a Service")
  
## Download & installation

1. Download the latest version from the _Releases- page of this repository.
2. Unzip the release file and move the contents to the root of your Magento installation (the module has the same directory structure as your Magento installation).

Note: We recommend that you backup your database before installing new modules.

## Updating from previous version

### Version 2.x.x. is not compatible with previous versions of Paynova's magento module (1.x). Please contact Paynova before upgrading from an incompatible version.

Upgrade steps:

1. You will not be able to invoice old Paynova orders after the upgrading. If you will need to invoice orders from an older Paynova module, you have two options: 1) Invoice all the orders in Magento before upgrading; 2) Finalize the orders in Paynova Merchant Services and then do an "invoice offline" in Magento.
2. Deactivate the old Paynova payment method in Magento admin: Go to `System->Configuration->Payment Methods` and set the payment methods to *Enabled: No*
3. Install the new module
4. Configure settings

## Configuration

1. Log in to Magento admin and clear all caches and re-index the store.
2. Activate the module under `System -> Configuration -> Sales | Paynova`

   ![admin sales paynova](/../screenshots/admin-sales-paynova.png?raw=true "admin sales paynova")
3. Enter the account information you have received from Paynova Merchant Support (merchant id, password, secret key, API url). 
4. Activate the desired payment methods.
5. (optional) Activate logging in the Magento admin `System -> Configuration -> Advanced | Developer`.

## Known issues
* If a payment is declined by Paynova, the order status is still set to 'Payment Pending'
