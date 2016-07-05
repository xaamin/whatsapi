<?php 
namespace Xaamin\Whatsapi\Tools;

use WhatsProt;
use Registration;
use Xaamin\Whatsapi\Events\Listener;
use Xaamin\Whatsapi\Contracts\ListenerInterface;
use Xaamin\Whatsapi\Contracts\WhatsapiToolInterface;

class MGP25 implements WhatsapiToolInterface
{
    /**
     * Debug app?
     * 
     * @var boolean
     */
    private $debug;

    /**
     * Custom path to next challenge file
     * @var string
     */
    private $customPath;

    /**
     * Event Registrarion listener 
     * 
     * @var \Xaamin\Whatsapi\Events\Listener
     */
    private $listener;

    public function __construct(Listener $listener, $debug = false, $customPath = false)
    {
        $this->setDebug($debug);
        $this->listener = $listener;
        $this->customPath = $customPath;
    }

    /**
     * Sets the Whatsapi event listener
     * 
     * @param  \Xaamin\Whatsapi\Contracts\ListenerInterface $listener 
     * @return void
     */
    public function setListener(ListenerInterface $listener)
    {
        $this->listener->setListener($listener);
    }

    /**
     * We're debugging the registration process?
     * 
     * @param  boolean $debug 
     * @return boolean
     */
    public function setDebug($debug = true)
    {
        $this->debug = $debug;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function requestCode($number, $type = 'sms', $carrier = null)
    {
        $registration = $this->getRegistrationClient($number);
        
        return $registration->codeRequest(strtolower($type), $carrier);
    }

    /**
     * {@inheritdoc}
     */
    public function registerCode($number, $code)
    {
        $registration = $this->getRegistrationClient($number);
        
        return $registration->codeRegister($code);
    }

    /**
     * Get WhatsProt instance for given number
     * 
     * @param  string $number 
     * @return \Registration
     */
    private function getRegistrationClient($number)
    {
        $file = $this->customPath ? $this->customPath . '/phone-id-' . $number . '.dat' : null;
        $registration = new Registration($number, $this->debug, $file);

        $this->listener->registerRegistrationEvents($registration);
        
        return $registration;
    }
}