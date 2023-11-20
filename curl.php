<?php

class Curl {
    const REQUEST_METHOD_GET = 'GET';
    const REQUEST_METHOD_POST = 'POST';
    protected $_header;
    protected $_url;
    protected $_timeout;
    protected $_method;
    protected $_body;
    protected $_nobody;
    protected $_response;
    protected $_header_size;
    protected $_status;
    protected $_response_content_type;
    protected $_error;
    protected $_errorno;

    public function __construct($url = null, $timeout = 30, $nobody = false) {
        $this->_url = $url;
        $this->_timeout = $timeout;
        $this->_nobody = $nobody;
    }

    public function setUrl(string $url) {
        $this->_url = $url;
    }

    public function setMethod(string $method) {
        $this->_method = $method;
    }

    public function setBody($body) {
        $this->_body = $body;
    }

    public function setNoBody($noBody) {
        $this->_nobody = $noBody;
    }

    public function getStatus() {
        return $this->_status;
    }

    public function getResponse() {
        return $this->_response;
    }

    public function setHeader(array $header) {
        $this->_header = $header;
    }

    public function sendCurl(): void {
        $ch = $this->initCurl();
        $response = curl_exec($ch);
        $this->_header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $this->_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->_response_content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $this->_error = curl_error($ch);
        $this->_errorno = curl_errno($ch);

        curl_close($ch);

        $this->_response = json_decode($response, true);
    }

    public function initCurl() {
        $ch = curl_init($this->_url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $this->_method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->_header);
        if (!$this->_nobody) {
            if (mb_strpos(implode($this->_header), 'multipart/form-data') !== false) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $this->_body);
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($this->_body));
            }
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        //curl_setopt($ch, CURLOPT_NOBODY, $this->_nobody);
        //curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        return $ch;
    }
}