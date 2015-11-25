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
     * Event Registrarion listener 
     * 
     * @var \Xaamin\Whatsapi\Events\Listener
     */
    private $listener;

    public function __construct(Listener $listener, $debug = false)
    {
        $this->setDebug($debug);
        $this->listener = $listener;
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
     * @param  boolean $debug [description]
     * @return [type]         [description]
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
        $registration = new Registration($number, $this->debug);
        $this->listener->registerRegistrationEvents($registration);
        return $registration;
    }
}