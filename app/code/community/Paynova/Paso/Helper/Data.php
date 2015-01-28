<?php
/**
 * Magento
 *
 */
class Paynova_Paso_Helper_Data extends Mage_Payment_Helper_Data
{
    const XML_PATH_EMAIL        = 'paso/settings/aero_email';
    const XML_PATH_CUSTOMER_ID  = 'paso/settings/customer_id';
    const XML_PATH_SECRET_KEY   = 'paso/settings/secret_key';

    /**
     * Internal parameters for validation
     */
    protected $_paynovaServer           	 = 'https://www.paynova.com';
    protected $_checkEmailUrl                = '/app/email_check.pl';
    protected $_checkEmailCustId             = '6999315';
    protected $_checkEmailPassword           = 'a4ce5a98a8950c04a3d34a2e2cb8c89f';
    protected $_checkSecretUrl               = '/app/secret_word_check.pl';
    protected $_activationEmailTo            = 'ecommerce@paynova.com';
    protected $_activationEmailSubject       = 'Magento Paynova Activation';
    protected $_paynovaMasterCustId          = '7283403';
    protected $_paynovaMasterSecretHash      = 'c18524b6b1082653039078a4700367f0';

    protected $_supportedMethods = array(
        "paso_acc","paso_allcards","paso_allbanks","paso_invoice","paso_did","paso_dnk","paso_ebt","paso_ebt","paso_gir","paso_idl","paso_so2","paso_swe","paso_seb","paso_han","paso_akt","paso_sam","paso_poh"
    );

    public function getSupportedMethods()
    {
        return $this->_supportedMethods;
    }

    public function isMethodPaynova($method)
    {
        if (in_array($method, $this->getSupportedMethods())) {
            return true;
        }
        return false;
    }

    public function isOneStepCheckout($store = null)
    {
        $res = false;
        if (Mage::getStoreConfig('onestepcheckout/general/rewrite_checkout_links', $store)) {
            $res = true;
            $request = Mage::app()->getRequest();
            $requestedRouteName = $request->getRequestedRouteName();
            $requestedControllerName = $request->getRequestedControllerName();
            if ($requestedRouteName == 'checkout' && $requestedControllerName == 'onepage') {
                $res = false;
            }
        }
        return $res;
    }

    function log($loginfo,$type = NULL){

        if(Mage::getStoreConfig('paso/settings/debug_flag')==1) {
            $ermsg ="";
            if ($type AND $type=="exception") {
                $ermsg = 'Exception:';
                if ($loginfo->getCode()) {
                    $ermsg = $ermsg . ' Code: ' . $loginfo->getCode();
                }
                if ($loginfo->getMessage()) {
                    $ermsg = $ermsg . ' Message: ' . $loginfo->getMessage();
                }
                if ($loginfo->getLine()){
                    $ermsg = $ermsg . ' Row: ' . $loginfo->getLine();
                }
                if ($loginfo->getFile()){
                    $ermsg = $ermsg . ' File: ' . $loginfo->getFile();
                }
            } else {
                if (is_array($loginfo)) {
                    $ermsg = print_r($loginfo, true);
                } elseif (is_object($loginfo)) {
                    $ermsg =  print_r(array($loginfo), true);
                } else {
                    $ermsg = $loginfo;
                }
            }

            $logDir  = Mage::getBaseDir('var') . DS . 'log' . DS;
            $logFile = $logDir."paynova.log";

            if (!is_dir($logDir)) {
                mkdir($logDir);
                chmod($logDir, 0777);
            }
            if( file_exists($logFile) ){
                $fp = fopen( $logFile, "a" );
            } else {
                $fp = fopen( $logFile, "w" );
            }

            if( $fp ) {
                fwrite( $fp, date("Y/m/d H:i:s").': '.$ermsg."\n" );
                fclose( $fp );
            }
        }

    }
}
