<?php
/*
 * This file is part of the Paynova Paynovapayment Magento Payment Module, which enables the use of Paynova within the
 * Magento e-commerce platform.
 *
 * LK: Abstract model for handling communication
 *
 * @category    Paynova
 * @package     Paynova_Paynovapayment
 */
abstract class Paynova_Paynovapayment_Model_Abstract extends Mage_Payment_Model_Method_Abstract
{
    /**
     * unique internal payment method identifier
     */
    protected $_code = 'paynovapayment_abstract';

    protected $_formBlockType = 'paynovapayment/form';
    protected $_infoBlockType = 'paynovapayment/info';
    protected $_selectedPaymentId= '0';
    /**
     * Availability options
     */
    protected $_isGateway              = true;
    protected $_canAuthorize           = true;
    protected $_canCapture             = true;
    protected $_canCapturePartial      = true;
    protected $_canRefund              = true;
    protected $_canVoid                = false;
    protected $_canUseInternal         = false;
    protected $_canUseCheckout         = true;
    protected $_canUseForMultishipping = false;

    protected $_paymentMethod    = 'abstract';
    protected $_defaultLocale    = 'en';
    protected $_supportedLocales = array('da', 'en', 'es', 'fi', 'de', 'fr', 'el', 'it', 'nl', 'ja', 'ru', 'pl', 'sv', 'tr', 'nn', 'ko', 'zh', 'et');
    protected $_hidelogin        = '1';
    protected $_order;

    const SERVER_PORT   = 443; // paynova server port.

    const NORMAL_WEB_PAYMENT  = 1;
    const INTERFACE_TYPE   = 5;

    /**
     * Get order model
     *
     * @return Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        if (!$this->_order) {
            $this->_order = $this->getInfoInstance()->getOrder();
        }
        return $this->_order;
    }



    /**
     * Setup the CURL call for establishing the paynova server through IP method.
     * $param request array
     * @return response array
     */
    public function setCurlCall($data, $address, $method = 'POST'){

        if ($data) {

            if ($address) {
                Mage::helper('paynovapayment')->log($address);
            }


            $data = json_encode($data);
            Mage::helper('paynovapayment')->log($data);
            $apiUrl=Mage::getStoreConfig('paynovapayment/settings/api_live_url');
            $apiTest=Mage::getStoreConfig('paynovapayment/settings/api_test_url');
            $apiMode=Mage::getStoreConfig('paynovapayment/settings/api_mode');
            $password=Mage::helper('core')->decrypt(Mage::getStoreConfig('paynovapayment/settings/password'));
            $username=Mage::getStoreConfig('paynovapayment/settings/merchant_id');


            $paynova_url = $apiUrl;
            if($apiMode!='1') {
                $paynova_url = $apiTest;
            }

            //check if https:// or http:// exist in URL.
            $prot_sec = "https://";
            $prot_uns = "http://";

            $pos = stripos($paynova_url, $prot_sec);
            if ($pos === false) {
                $pos = stripos($paynova_url, $prot_uns);
                if ($pos === false) {
                    $paynova_url = $prot_sec.$paynova_url;
                } else if ($pos>0){
                    $paynova_url = $prot_sec.$paynova_url;
                }
            } else if ($pos>0){
                $paynova_url = $prot_sec.$paynova_url;
            }



            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "$paynova_url$address");
            curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            if($method == 'POST') {
                curl_setopt($ch, CURLOPT_POST, 1);
            }
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json')); //
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            $output = curl_exec($ch);
            Mage::helper('paynovapayment')->log(json_decode($output));

            curl_close($ch);
            return json_decode($output);
        }else {

            Mage::helper('paynovapayment')->log($address);
            Mage::helper('paynovapayment')->log($data);
            $data = json_encode($data);

            $apiUrl=Mage::getStoreConfig('paynovapayment/settings/api_live_url');
            $apiTest=Mage::getStoreConfig('paynovapayment/settings/api_test_url');
            $apiMode=Mage::getStoreConfig('paynovapayment/settings/api_mode');
            $password=Mage::helper('core')->decrypt(Mage::getStoreConfig('paynovapayment/settings/password'));
            $username=Mage::getStoreConfig('paynovapayment/settings/merchant_id');


            $paynova_url = $apiUrl;
            if($apiMode!='1') {
                $paynova_url = $apiTest;
            }

            //check if https:// or http:// exist in URL.
            $prot_sec = "https://";
            $prot_uns = "http://";

            $pos = stripos($paynova_url, $prot_sec);
            if ($pos === false) {
                $pos = stripos($paynova_url, $prot_uns);
                if ($pos === false) {
                    $paynova_url = $prot_sec.$paynova_url;
                } else if ($pos>0){
                    $paynova_url = $prot_sec.$paynova_url;
                }
            } else if ($pos>0){
                $paynova_url = $prot_sec.$paynova_url;
            }



            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "$paynova_url$address");
            curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            if($method == 'POST') {
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            }
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json')); //

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            $output = curl_exec($ch);
            Mage::helper('paynovapayment')->log(json_decode($output));

            curl_close($ch);
            return json_decode($output);
        }
    }

    /**
     * Convert the server response into the readable format.
     *
     * $param request response
     * @return response URL return from IP call
     */
    public function getResponseFromInitialPaymentCall($response){


        if (!empty($response)) {
            $status = $response->status;

            if(!$status->isSuccess){ // check if response return any error.

                $error=$status->statusMessage;
                Mage::throwException("$error");
            }
            if(($response->status->errorNumber == 0) && (!empty($response->url))){
                return $response->url;
            }
        }
    }
    /**
     * Convert the server response into the readable format.
     *
     * if success return true to move forward with saving order info and setting state
     *
     * $param request response
     * @return response URL return from IP call
     */
    public function getResponseFromAuthorizePaymentCall($response){

        if (!empty($response)) {
            $status = $response->status;

            if(!$status->isSuccess){ // check if response return any error.

                $error=$status->statusMessage;
                return false;

            }
            if($status->statusKey == 'GENERAL_DECLINE'){ // check if response return any error.

                $error=$status->statusMessage;
                return false;

            }
            if(($response->status->errorNumber == 0)){

                return true;
            }
        }

    }
    public function getResponseFromPaymentSuccessCall($response){

        if (!empty($response)) {
            $status = $response->status;

            if(!$status->isSuccess){ // check if response return any error.

                $error=$status->statusMessage;
                Mage::throwException("$error");
            }
            if(($response->status->errorNumber == 0) && (!empty($response->url))){
                return $response->url;
            }
        }
    }


    /**
     * To check billing country is allowed for the payment method
     *
     * @return bool
     */
    public function canUseForCountry($country)
    {
        /*
        for specific country, the flag will set up as 1
        */

        $baseCurrency=Mage::app()->getStore()->getBaseCurrencyCode();
        $availableCurrency = explode(',', $this->getConfigData('currency'));

        if($this->getConfigData('allowspecific')==1){
            $availableCountries = explode(',', $this->getConfigData('specificcountry'));

            if((!in_array($country, $availableCountries))){
                return false;
            }else if(!in_array($baseCurrency,$availableCurrency)){
                return false;
            }
        }else  if(!in_array($baseCurrency,$availableCurrency)){
            return false;
        }

        return true;
    }


    /**
     * Return url for redirection after order placed
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl('paynovapayment/processing/payment');
    }

    public function capture(Varien_Object $payment, $amount)
    {
        $transid = $payment->getLastTransId();
        if (empty($transid)) {
            Mage::getSingleton('core/session')->addError('Missing transaction ID. Can´t send anull order request to Paynova');
            $order = $this->getOrder();
            $order->addStatusHistoryComment(Mage::helper('paynovapayment')->__('No transaction id when trying to capture.'));
            $order->save();
            $logmsg = "Missing transaction ID from order #".$order->getIncrementId().". Can´t send anull order request to Paynova.";
            Mage::Helper('paynovapayment')->log($logmsg);
        }


        $payment->setStatus(self::STATUS_APPROVED)
            ->setTransactionId($this->getTransactionId())
            ->setIsTransactionClosed(0);



        return $this;
    }

    /**
     * Capture payment through Paynova api
     *
     * @param Varien_Object $payment
     * @param decimal $amount
     * @return Paynova_Paynovapayment_Model_Abstract
     */
    public function captureInvoice(Varien_Object $payment)
    {

        $transaction_id = $payment->getLastTransID();
        if(empty($transaction_id)){
            $transaction_id = Mage::getSingleton('core/session')->getTransactionID($transaction_id);

        }
        if (empty($transaction_id)) {
            Mage::helper('paynovapayment')->log("Error. Missing transaction ID from order and  cannot do capture.");
            Mage::throwException('Error. Missing transaction ID.');
        }


        $invoice = $payment->getCurrentInvoice();
        $transInformation = $payment->getTransaction($transaction_id)->getAdditionalInformation();



        $order_id = $transInformation['raw_details_info']['order_id'];
        $order_number = $transInformation['raw_details_info']['order_number'];

        $order = Mage::getModel('sales/order');

        $order->loadByIncrementId($order_number);

        $res['orderId'] = $order_id;
        $res['transactionId'] = $transaction_id;
        $res['totalAmount'] = $invoice->getGrandTotal();
        $res['invoiceId'] = $transaction_id;

        $items = $invoice->getAllItems();


        $itemcount= count($items);
        $data = array();
        $i=0;
        $linenumber=1;

        $unitMeasure = Mage::getStoreConfig('paynovapayment/advanced_settings/product_unit');
        if (empty($unitMeasure)){
            $unitMeasure = "pcs";
        }
        $shippingname = Mage::getStoreConfig('paynovapayment/advanced_settings/shipping_name');
        if (empty($shippingname)){
            $shippingname = "Shipping";
        }
        $shippingsku = Mage::getStoreConfig('paynovapayment/advanced_settings/shipping_sku');
        if (empty($shippingsku)){
            $shippingsku = "Shipping";
        }

        foreach ($items as $itemId => $item)
        {
            $product = Mage::helper('catalog/product')->getProduct($item->getSku(), Mage::app()->getStore()->getId(), 'sku');
            $productUrl = Mage::getUrl($product->getUrlPath());
            $description =  $product->getShortDescription();
            if (empty($description)){
                $description = $item->getName();
            }
            $order_item = $item->getOrderItem();
            $res['lineItems'][$itemId]['id'] = $linenumber;
            $res['lineItems'][$itemId]['articleNumber'] = $order_item->getSku();
            $res['lineItems'][$itemId]['name'] = $item->getName();
            $res['lineItems'][$itemId]['quantity'] = round($item->getQty(),0);
            $res['lineItems'][$itemId]['unitMeasure'] = $unitMeasure;
            $res['lineItems'][$itemId]['unitAmountExcludingTax'] = round($item->getPrice(),2);
            $res['lineItems'][$itemId]['taxPercent'] = round($order_item->getTaxPercent(),2);
            $res['lineItems'][$itemId]['totalLineTaxAmount'] = round($item->getTaxAmount(),2);
            $res['lineItems'][$itemId]['totalLineAmount'] =  round($item->getRowTotalInclTax(),2);
            $res['lineItems'][$itemId]['description'] =  $description;
            $res['lineItems'][$itemId]['productUrl'] =  $productUrl;
            $res['lineItems'][$itemId]['productUrl'] =  $item->getProductOptions();

            $i++;
            $linenumber++;
        }

        if ($order->getShippingAmount()>0) {
            $itemId++;
            $res['lineItems'][$itemId]['id'] = $linenumber;
            $res['lineItems'][$itemId]['articleNumber'] = $shippingsku;
            $res['lineItems'][$itemId]['name'] = $shippingname;
            $res['lineItems'][$itemId]['quantity'] = 1;
            $res['lineItems'][$itemId]['unitMeasure'] =  $unitMeasure;
            $res['lineItems'][$itemId]['unitAmountExcludingTax'] = $order->getShippingAmount()-$order->getShippingTaxAmount();
            $res['lineItems'][$itemId]['taxPercent'] = 100 * $order->getShippingTaxAmount() / $order->getShippingAmount();
            $res['lineItems'][$itemId]['totalLineTaxAmount'] = round($order->getShippingTaxAmount(),2);
            $res['lineItems'][$itemId]['totalLineAmount'] =  round($order->getShippingAmount(),2);
            $res['lineItems'][$itemId]['productUrl'] = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);
        }



        $output=$this->setCurlCall($res, '/orders/'.$order_id.'/transactions/'.$transaction_id.'/finalize/'.$res['totalAmount']);


        $order->save();

        $status = $output->status;
        if($status->statusKey == 'PAYMENT_COMPLETED'){

            $status->isSuccess = true;
            $output->transactionId = 'Order has been finalized';

        }

        if($status->isSuccess){

            $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING);

            $order->save();

            $payment->setStatus(self::STATUS_APPROVED)
                ->setTransactionId($output->transactionId)
                ->setIsTransactionClosed(0);
            return $status->isSuccess;

        }else{
            $error=$status->statusMessage;
            Mage::throwException("$error");

            $this->_redirect('checkout/onepage');

        }

    }

    public function invoicing($order, $send_email = false)
    {
        try {
            if(!$order->canInvoice())
            {
                Mage::throwException(Mage::helper('core')->__('Cannot create an invoice.'));
            }

            $invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();

            if (!$invoice->getTotalQty()) {
                Mage::throwException(Mage::helper('core')->__('Cannot create an invoice without products.'));
            }

            $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
            $invoice->register();
            $transactionSave = Mage::getModel('core/resource_transaction')
                ->addObject($invoice)
                ->addObject($invoice->getOrder());

            $transactionSave->save();
            $autofinalizemail = Mage::getStoreConfig('paynovapayment/advanced_settings/autofinalize_email');
            if ($autofinalizemail==1) {
                $invoice->sendEmail();
            }

        }
        catch (Mage_Core_Exception $e) {
            $log = Mage::helper('paynovapayment');

        }

        return;
    }
    /**
     * Cancel payment
     *
     * @param Varien_Object $payment
     * @return Paynova_Paynovapayment_Model_Abstract
     */
    public function cancel(Varien_Object $payment)
    {
        $payment->setStatus(self::STATUS_DECLINED)
            ->setTransactionId($this->getTransactionId())
            ->setIsTransactionClosed(1);

        return $this;
    }

    /**
     * Return url of payment method
     *
     * @return string
     */


    /**
     * Return StoreView Locale Code in paynova format (e.g. DEU, USA, SWE ...)
     *
     * @return string
     */
    public function getLocale()
    {
        $locale = explode('_', Mage::app()->getLocale()->getLocaleCode());
        if (is_array($locale) && !empty($locale) && in_array($locale[0], $this->_supportedLocales)) {
            return $this->_convertCurrentLanToPaynovaLanCode($locale[0]);
        }
        return $this->_convertCurrentLanToPaynovaLanCode($this->_defaultLocale);
    }

    /**
     * prepare params array to send it to gateway page via POST
     *
     * @return array
     */
    public function getFormFields()
    {
        $order_id = $this->getOrder()->getRealOrderId();
        $billing  = $this->getOrder()->getBillingAddress();
        if ($this->getOrder()->getBillingAddress()->getEmail()) {
            $email = $this->getOrder()->getBillingAddress()->getEmail();
        } else {
            $email = $this->getOrder()->getCustomerEmail();
        }

        $params = array(
            'merchant_fields'       => 'partner',
            'partner'               => 'magento',
            'pay_to_email'          => Mage::getStoreConfig(Paynova_Paynovapayment_Helper_Data::XML_PATH_EMAIL),
            'transaction_id'        => $order_id,
            'return_url'            => Mage::getUrl('paynovapayment/processing/success', array('transaction_id' => $order_id)),
            'cancel_url'            => Mage::getUrl('paynovapayment/processing/cancel', array('transaction_id' => $order_id)),
            'status_url'            => Mage::getUrl('paynovapayment/processing/status'),
            'language'              => $this->getLocale(),
            'amount'                => round($this->getOrder()->getGrandTotal(), 2),
            'currency'              => $this->getOrder()->getOrderCurrencyCode(),
            'recipient_description' => $this->getOrder()->getStore()->getWebsite()->getName(),
            'firstname'             => $billing->getFirstname(),
            'lastname'              => $billing->getLastname(),
            'address'               => $billing->getStreet(-1),
            'postal_code'           => $billing->getPostcode(),
            'city'                  => $billing->getCity(),
            'country'               => $billing->getCountryModel()->getIso3Code(),
            'pay_from_email'        => $email,
            'phone_number'          => $billing->getTelephone(),
            'detail1_description'   => Mage::helper('paynovapayment')->__('Order ID'),
            'detail1_text'          => $order_id,
            'payment_methods'       => $this->_paymentMethod,
            'hide_login'            => $this->_hidelogin,
            'new_window_redirect'   => '1'
        );

        // add optional day of birth
        if ($billing->getDob()) {
            $params['date_of_birth'] = Mage::app()->getLocale()->date($billing->getDob(), null, null, false)->toString('dmY');
        }

        return $params;
    }
    /**
     * Get initialized flag status
     * @return true
     */
    public function isInitializeNeeded()
    {
        return true;
    }

    /**
     * Instantiate state and set it to state onject
     * //@param
     * //@param
     */
    public function initialize($paymentAction, $stateObject)
    {
        $state = Mage_Sales_Model_Order::STATE_PENDING_PAYMENT;
        $stateObject->setState($state);
        $stateObject->setStatus(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT);
        $stateObject->setIsNotified(false);
    }

    /**
     * Get config action to process initialization
     *
     * @return string
     */
    public function getConfigPaymentAction()
    {
        $paymentAction = $this->getConfigData('payment_action');
        return empty($paymentAction) ? true : $paymentAction;
    }

    /*
     * Create MessageId used in IP method call.
     * */

    public function _messageIdForIP(){
        return (date('H:i:s').'T'.date('Y-m-d').'.'.substr(time(),0,3).'@'.$_SERVER['REMOTE_ADDR']);
    }

    /**
     * Retrieve payment code for method form generation
     *
     * @return string
     */
    public function getSelectedPaymentId() {
        return $this->_selectedPaymentId;
    }

    /**
     * Retrieve language code form the current store.
     ** @return string
     */
    public function _convertCurrentLanToPaynovaLanCode($storeCode) {
        if(empty($storeCode)) return false;
        /*  sebastian.hafa@netzbest.de - 07.10.2013: Created right locale mapping and compare condition.
        */
        $defaultLanArray = array('sv' => 'SWE',
            'da' => 'DAN',
            'de' => 'DEU',
            'en' => 'USA',
            'fi' => 'FIN',
            'nn' => 'NOR',
            'fr' => 'FRA',
            'nl' => 'NLD',
            'ko' => 'KOR',
            'zh' => 'ZHO',
            'es' => 'SPA',
            'it' => 'ITA',
            'et' => 'EST',
            'ru' => 'RUS',
            'el' => 'ELL',
            'tr' => 'TUR',
            'ja' => 'JPN',
            'pl' => 'POL');
        foreach($defaultLanArray as $key => $value){
            if($key == $storeCode) {
                return $value;
            }
        }
        return 'USA';
    }

    protected function _setAdditionalInformation($data, $value = NULL)
    {
        if (!$data) return;
        if ($value && !is_array($data)) {
            if ($this->_additionalInfo) {
                $this->_additionalInfo->setData($data, $value);
            } else {
                $this->_additionalInfo = new Varien_Object(array($data => $value));
            }
        } else {
            if ($this->_additionalInfo) {
                $this->_additionalInfo->setData($data);
            } else {
                $this->_additionalInfo = new Varien_Object($data);
            }
        }
    }

    protected function _unsetAdditionalInformation($field)
    {
        if ($this->_additionalInfo) {
            $this->_additionalInfo->unsetData($field);
        }
    }

    protected function _getAdditionalInformation($field = '')
    {
        if ($this->_additionalInfo) {
            return $this->_additionalInfo->getData($field);
        } else {
            return NULL;
        }
    }



}
