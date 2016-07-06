<?php
namespace Xaamin\Whatsapi\Events;

use WhatsProt;
use Exception;
use Registration;
use WhatsApiEventsManager;
use Xaamin\Whatsapi\Sessions\SessionInterface;
use Xaamin\Whatsapi\Contracts\ListenerInterface;

class Listener
{
    /**
     * Whatsapi config
     * 
     * @var array
     */
    private $config = [];

    /**
     * Holds SessionInterface implementation
     * 
     * @var Xaamin\Whatsapi\Sessions\SessionInterface
     */
    protected $session;

    /**
     * Events to listen for
     * 
     * @var array
     */
    protected $events = [];

    /**
     * Constructor
     * 
     * @param array $config
     */
    public function __construct(SessionInterface $session, array $config)
    {
        $this->config = $config;
        $this->session = $session;

        $this->setEventsToListen();
    }

    /**
     * Register the events you want to listen for.
     *
     * @return void
     */
    private function setEventsToListen()
    {                
        if($this->config['listen-events'])
        {
            $this->events = $this->config['events-to-listen'];
        }        
    }

    /**
     * Binds the requested events to the WhatsProt event manager.
     * 
     * @return \Xaamin\Whatsapi\Events\Listener
     */
    public function registerWhatsProtEvents(WhatsProt $whatsProt)
    {
        return $this->registerEvents($whatsProt->eventManager());
    }

    /**
     * Binds the requested events to the Registration event manager.
     * 
     * @return \Xaamin\Whatsapi\Events\Listener
     */
    public function registerRegistrationEvents(Registration $registration)
    {
        return $this->registerEvents($registration->eventManager());
    }

    /**
     * Binds events to the WhatsApiEventsManager
     * @param  \WhatsApiEventsManager $manager
     * @return \Xaamin\Whatsapi\Events\Listener
     */
    protected function registerEvents(WhatsApiEventsManager $manager)
    {
        foreach ($this->events as $event) 
        {
            if (is_callable(array($this, $event))) 
            {
                $manager->bind($event, [$this, $event]);
            }
        }
        
        return $this;
    }

    ################## Events listeners ##################

    public function onGetSyncResult($result) 
    {
        $this->session->put($result);
    }
}