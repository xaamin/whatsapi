<?php 
namespace Xaamin\Whatsapi\Tools;

use WhatsProt;
use Xaamin\Whatsapi\Contracts\WhatsapiToolInterface;

class MGP25 implements WhatsapiToolInterface
{
    private $debug;

    public function __construct($debug = false)
    {
        $this->debug($debug);
    }

    public function debug($debug = true)
    {
        $this->debug = $debug;

        return $this;
    }

    public function requestCode($number, $type = 'sms', $carrier = null)
    {
        $WA = $this->getClientForNumber($number);
        
        return $WA->codeRequest(strtolower($type), $carrier);
    }

    public function registerCode($number, $code)
    {
        $WA = $this->getClientForNumber($number);
        
        return $WA->codeRegister($code);
    }

    private function getClientForNumber($number)
    {
        return new WhatsProt($number, '', $this->debug);
    }
}