<?php
class Paynova_Paynovapayment_Model_Http extends Paynova_Paynovapayment_Model_Abstract
{
    /**
     * @access private
     */

    public function __construct() { }

    /**
     * Do a REST DELETE request
     *
     * @throws PaynovaExceptionHttp if exception occured when contacting server
     * @throws PaynovaExceptionConfig
     * @param string $restPath the api rest path
     * @param HttpConfig $httpConfig (optional)
     * @return HttpEvent
     */
    public function delete($restPath, HttpConfig $httpConfig = null) {
        $httpEvent = $this->_curlRequest($restPath,"DELETE", array(), $httpConfig);

        if(in_array($httpEvent->code,$this->getOkStatusCodes())) {
            return $httpEvent;
        } else {
            $this->_throwHttpException($httpEvent);
        }
    }

    /**
     * Do a REST GET request
     *
     * @throws PaynovaExceptionHttp if exception occured when contacting server
     * @throws PaynovaExceptionConfig
     * @param string $restPath the api rest path
     * @param HttpConfig $httpConfig (optional)
     * @return HttpEvent
     */
    public function get($restPath, HttpConfig $httpConfig = null) {
        $httpEvent = $this->_curlRequest($restPath,"GET", array(), $httpConfig);

        if(in_array($httpEvent->code,$this->getOkStatusCodes())) {
            return $httpEvent;
        } else {
            $this->_throwHttpException($httpEvent);
        }
    }

    /**
     * Do a POST request
     *
     * @throws PaynovaExceptionHttp if exception occured when contacting server
     * @throws PaynovaExceptionConfig
     * @param string $restPath the api rest path
     * @param array $params of properties to send
     * @param HttpConfig $httpConfig (optional)
     * @return HttpEvent
     */
    public function post($restPath, $params, HttpConfig $httpConfig = null) {
        $httpEvent = $this->_curlRequest($restPath,"POST", $params, $httpConfig);

        if(in_array($httpEvent->code,$this->getOkStatusCodes())) {
            return $httpEvent;
        } else {
            $this->_throwHttpException($httpEvent);
        }
    }

    /**
     * Returns an array of status codes that is considered to be a successful response
     * @return array status codes
     */
    public function getOkStatusCodes() {
        return array(200,201);
    }

    /**
     * Helper method to throw the correct Exception according to a HTTP status response
     * @param int $code
     * @throws PaynovaExceptionHttp
     */
    private function _throwHttpException(HttpEvent $httpEvent,$message = "") {
        $lines = explode("\n",trim($httpEvent->responseHeader()));
        $message.="\n".
            trim($lines[0]).
            "\nInspect the HttpEvent (use try/catch exception->getHttpEvent()) to find out more";
        throw new PaynovaExceptionHttp($message,$httpEvent);
    }


    /**
     * The function that does the actual curl request
     *
     * @throws PaynovaExceptionApiCredentialsNotSet
     * @throws PaynovaExceptionHttp
     * @param string $restPath
     * @param string $customMethod PUT/POST/GET
     * @param array $params (optional) properties to append in the request body
     * @param HttpConfig $httpConfig (optional)
     * @return HttpEvent
     */
    private function _curlRequest($restPath,$customMethod, $params = array(), HttpConfig $httpConfig = null) {

        if($httpConfig == null) {
            try{
                $httpConfig = HttpConfig::getDefaultConfig();
            } catch (PaynovaExceptionConfig $pec) {
                throw new PaynovaExceptionApiCredentialsNotSet("Not all API credentials has been set, see PaynovaConfig");
            }
        }

        $httpConfig->set_CURLOPT(CURLOPT_CUSTOMREQUEST, $customMethod);

        $url = $httpConfig->get_CURLOPT(CURLOPT_URL)."/".trim($restPath,"/");
        $httpConfig->set_CURLOPT(CURLOPT_URL, $url);


        $ch = curl_init();
        curl_setopt_array($ch, $httpConfig->getCurlOptionsAsArray());

        if($customMethod=="POST") {
            $params = json_encode($params);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        }

        $response = curl_exec($ch);
        $responseHeaderSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

        $httpEvent = HttpEvent::factory(array(
            "code"				=>	curl_getinfo($ch, CURLINFO_HTTP_CODE),
            "requestHeader"		=>	curl_getinfo($ch, CURLINFO_HEADER_OUT ),
            "requestBody"		=>	var_export($params,true),
            "responseHeader"	=>	substr($response, 0, $responseHeaderSize),
            "responseBody"		=>	substr($response, $responseHeaderSize)
        ));

        if($response == FALSE) {
            $this->_throwHttpException(
                $httpEvent,
                "Something went wrong when doing curl_exec(), curl_error = ".curl_error($ch)
            );
        }

        curl_close($ch);

        $acceptType 	= 	$this->_getSpecificHeader("Accept",			$httpEvent->requestHeader());
        $contentType 	=  	$this->_getSpecificHeader("Content-type",	$httpEvent->responseHeader());


        if($acceptType!="" && $contentType!=$acceptType) {
            $this->_throwHttpException(
                $httpEvent,
                "Expected content-type:".$acceptType." (set by Accept in request-headers) but response-header content-type was:".$contentType
            );

        }

        return $httpEvent;
    }

    /**
     * Get a specific header from a http header-request/response
     * @param string $specificHeader
     * @param string $headers
     * @return string
     */
    private function _getSpecificHeader($specificHeader,$headers) {
        $lines = explode("\n",trim($headers));
        $specificHeader = strtolower($specificHeader);
        foreach($lines as $line) {
            $line = strtolower($line);
            if(stristr($line,$specificHeader)!==FALSE) {
                $tokens = explode(";",trim(str_replace($specificHeader,"",$line),": "));
                return trim($tokens[0]);
            }
        }
        return "";
    }
}