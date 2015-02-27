<?php

class Paynova_Paynovapayment_IndexController extends Mage_Core_Controller_Front_Action
{

    protected function getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }


    public function getQuote()
    {
        return $this->getCheckout()->getQuote();
    }

    public function getEmailAction()
    {
        $billMailAddress ="";
        $checkout = $this->getQuote();
        $billMailAddress = $checkout->getCustomerEmail();
        echo $billMailAddress;
    }


    public function getLogFileAction()
    {
        if (isset($_GET['tid'])) {
            $checktimestmp = time();
            $checktimestmp = substr($checktimestmp, 0, -4);
           if ($checktimestmp == $_GET['tid'])
           {

               $logDir  = Mage::getBaseDir('var') . DS . 'log' . DS;
               $logFile = $logDir."paynova.log";

               if( file_exists($logFile) ){
                   header('Content-Description: File Transfer');
                   header('Content-Type: text/plain');
                   header('Content-Disposition: attachment; filename='.basename($logFile));
                   header('Expires: 0');
                   header('Cache-Control: must-revalidate');
                   header('Pragma: public');
                   header('Content-Length: ' . filesize($logFile));
                   readfile($logFile);
                   exit;
               }
               else
               {
                    $this->_redirect('adminhtml/dashboard');
               }
           }
        }
        else
        {
            $this->_redirect('adminhtml/dashboard');
        }

    }
    /*
     *
     */
    public function getAddressAction()
    {
        //grab values posted
        $postarray = $this->getRequest()->getParams();
        //$postarray['governmentid'] = "198101037979";
   
        $s = false;
        $t = false;

        if(!empty($postarray['governmentid'])) {
            $governmentId = $postarray['governmentid'];
            $checkout = $this->getQuote();
            $payment = $checkout->getPayment();

            $abstractModel = Mage::getModel('paynovapayment/acc');
            $res = '';
            $output = $abstractModel->setCurlCall($res, '/addresses/SE/' . $governmentId, 'GET');

            if(isset($output) && isset($output->status)){
                
            
            if($output->status->isSuccess){

                foreach($output->addresses as $outputAddress){

                    if(isset($outputAddress->name)){
                        $name = $outputAddress->name;
                    }
                    if(isset($outputAddress->address->type) and $outputAddress->address->type == 'legal'){
                        $address = $outputAddress->address;
                    }
                }




                $billingAddress = $checkout->getBillingAddress();
                $shippingAddress = $checkout->getShippingAddress();

                if($billingAddress->getFirstname() != $name->firstName){
                    $billingAddress->setFirstname($name->firstName);$t = true;
                };

                if($billingAddress->getLastname() != $name->lastName){
                    $billingAddress->setLastname($name->lastName);$t = true;
                };

                if($billingAddress->getStreet() != $address->street1){
                    $billingAddress->setStreet($address->street1);$t = true;
                };

                if($billingAddress->getPostcode() != $address->postalCode){
                    $billingAddress->setPostcode($address->postalCode);$t = true;
                };

                if($billingAddress->getCity() != $address->city){
                    $billingAddress->setCity($address->city);$t = true;
                };

                $billingAddress->save();
                if(!empty($shippingAddress)) {
                    if ($shippingAddress->getFirstname() != $name->firstName) {
                        $shippingAddress->setFirstname($name->firstName);
                        $s = true;
                    };

                    if ($shippingAddress->getLastname() != $name->lastName) {
                        $shippingAddress->setLastname($name->lastName);
                        $s = true;
                    };

                    if ($shippingAddress->getStreet() != $address->street1) {
                        $shippingAddress->setStreet($address->street1);
                        $s = true;
                    };

                    if ($shippingAddress->getPostcode() != $address->postalCode) {
                        $shippingAddress->setPostcode($address->postalCode);
                        $s = true;
                    };

                    if ($shippingAddress->getCity() != $address->city) {
                        $shippingAddress->setCity($address->city);
                        $s = true;
                    };

                    $shippingAddress->save();
                }
                $res['isSuccess'] = 1;
                $res['governmentId'] = $governmentId;
                $res['email'] = $checkout->getCustomerEmail();
                $res['name']['firstName'] = $name->firstName;
                $res['name']['lastName'] = $name->lastName;
                $res['address']['Street'] = $address->street1;
                $res['address']['City'] = $address->city;
                $res['address']['postalCode'] = $address->postalCode;
                $res['address']['countryCode'] = $address->countryCode;

                $res['validationError']['billing'] = $s;
                $res['validationError']['shipping'] = $t;

                $res['statusMessage'] = $output->status->statusMessage;
                $res_json = json_encode($res);
                echo $res_json;

                $payment->setAdditionalInformation('governmentid', $governmentId);

                $payment->setAdditionalInformation('CustomerName',$res['name']);

                $payment->setAdditionalInformation('CustomerAddress',$res['address']);
                $payment->save();

                return;
                //
            }else{
                //something went wrong with verifying governmentId
                $res['isSuccess'] = 0;


               if ($output->status->errors) {
                   $errorarr = array();
                   $i = 0;
                   foreach ($output->status->errors as $error) {
                        $errorarr[$i]['errorCode'] = $error->errorCode;
                        $errorarr[$i]['fieldName'] = $error->fieldName;
                        $errorarr[$i]['message'] = $error->message;
                        $i++;
                   }
                   $res['errors'] = $errorarr;
               }


                $res['errorNumber'] = $output->status->errorNumber;
                $res['statusMessage'] = $output->status->statusMessage;
                $res_json = json_encode($res);
                echo $res_json;
                return;
            }
        }


        }
        //something went wrong with verifying governmentId
        $res['isSuccess'] = 0;
        $res['statusMessage'] = Mage::helper('paynovapayment')->__('No GovernmentId');
        $res_json = json_encode($res);
        echo $res_json;
        return;
    }
        /*
            *
            */
        public function getPaymentMethodsAction()
        {

            $abstractModel = Mage::getModel('paynovapayment/acc');

            $totals = Mage::getSingleton('checkout/cart')->getQuote()->getTotals();
            $subtotal = $totals["subtotal"]->getValue();

            //Language code
            $iso2 = Mage::getStoreConfig('general/country/default');
            $iso3 = Mage::getModel('directory/country')->load($iso2)->getIso3Code();;

            $res['TotalAmount'] = $subtotal;
            $res['CurrencyCode'] = Mage::app()->getStore()->getCurrentCurrencyCode();
            $res['PaymentChannelId'] = '1';
            $res['CountryCode'] = Mage::getStoreConfig('paynovapayment/advanced_settings/payment_channel_id');
            $res['LanguageCode'] = $iso3;



            $output = $abstractModel->setCurlCall($res, '/paymentoptions/' );


            if(!isset($output->availablePaymentMethods)){
                $res['isSuccess'] = 0;
                $res['statusMessage'] = Mage::helper('paynovapayment')->__('No payment methods');
                $res_json = json_encode($res);
                echo $res_json;
                return;
            }

            $res = array_filter($output->availablePaymentMethods, function($obj){
                if($obj->group->key == 'installment'  ){
                    return true;
                }
                return false;
            });


            $options = array();

            $i = $o = 0;

            $options[$i]['productId'] = '';
            $options[$i]['displayName'] = Mage::helper('paynovapayment')->__('Choose installment');
            $i++;
            foreach ($res as $robj) {
                //var_dump($robj);
                $options[$i]['productId'] = $robj->paymentMethodProductId;
                $options[$i]['displayName'] = Mage::helper('paynovapayment')->__($robj->paymentMethodProductId.'_label');

                $legaloptions = array();
                foreach ($robj->legalDocuments as $legaldocs) {
                    $legaloptions[$o]['label'] = $legaldocs->label;
                    $legaloptions[$o]['uri'] = $legaldocs->uri;
                    $options[$i]['uri'] = $legaldocs->uri;
                    $o++;
                }
                $options[$i]['legalDocs'] = $legaloptions;
                $interestRate = $robj->interestRate->value. ' ' .$robj->interestRate->symbol;
                $notificationFee = $robj->notificationFee->value. ' ' .$robj->notificationFee->symbol;
                $setupFee = $robj->setupFee->value. ' ' .$robj->setupFee->symbol;
                $nrInstallments = $robj->numberOfInstallments;
                $installmentPeriod = $robj->installmentPeriod;
                $installmentUnit = $robj->installmentUnit;
                $options[$i]['installmentText'] =   Mage::Helper('paynovapayment')->__($robj->paymentMethodProductId, $interestRate,$notificationFee,$setupFee,$nrInstallments,$installmentPeriod,$installmentUnit);

                $i++;
            }

            $res2['isSuccess'] = 1;
            $res2['options'] = $options;
            $res_json = json_encode($res2);
            echo $res_json;
            return;
        }
}