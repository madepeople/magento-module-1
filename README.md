![Paynova logo](/../screenshots/paynova_logo_c0392b_50px.png?raw=true "Paynova logo")

# Magento Payment Module v1.0

> This is Paynova's payment module for Magento. The module supports Magento 1.7 - 1.9. and is tested in Magento 1.9.1.0.

## Contents
<!-- MarkdownTOC depth=2 autolink=true bracket=round -->

- [Supported payment methods](#supported-payment-methods)
- [Download & installation](#download--installation)
- [Configuration](#configuration)

<!-- /MarkdownTOC -->


## Supported payment methods
* Credit/debit cards
* Real-time bank transfer
  * Swedish/Finnish/Danish banks
  * iDEAL
  * Überweisung (Germany)
* Invoice/installments
  
## Download & installation

1. Download the latest version from the _Releases- page of this repository.
2. Unzip the release file and move the contents to the root of your Magento installation (the module has the same directory structure as your Magento installation).

Note: We recommend that you backup your database before installing new modules.

## Configuration

1. Log in to Magento admin and clear all caches and re-index the store.
2. Activate the module under `System -> Configuration -> Sales | Paynova`

   ![admin sales paynova](/../screenshots/admin-sales-paynova.png?raw=true "admin sales paynova")
3. Enter the account information you have received from Paynova Merchant Support (merchant id, password, secret key, API url). 
4. Activate the desired payment methods.
5. (optional) Activate logging in the Magento admin `System -> Configuration -> Advanced | Developer`.
