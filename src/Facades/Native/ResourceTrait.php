<?php 
namespace Xaamin\Whatsapi\Facades\Native;

use Xaamin\Whatsapi\Sessions\SessionInterface;
use Xaamin\Whatsapi\Contracts\ListenerInterface;

trait ResourceTrait
{	
    /**
     * SessionInterface implementation
     * 
     * @var Xaamin\Whatsapi\Sessions\SessionInterface
     */
	protected static $session;

	/**
     * ListenerInterface implementation
     * 
     * @var \Xaamin\Whatsapi\Contracts\ListenerInterface
     */
	protected static $listener;

	/**
	 * Config values
	 * 
	 * @var array
	 */
	protected static $config;

    /**
     * Sets the session manager
     * 
     * @param \Xaamin\Whatsapi\Sessions\SessionInterface $session
     */
    public static function setSessionManager(SessionInterface $session)
    {
        static::$session = $session;
    }

    /**
     * Sets the event listener
     * 
     * @param Xaamin\Whatsapi\Contracts\ListenerInterface $listener
     */
    public static function setEventListener(ListenerInterface $listener)
    {
    	static::$listener = $listener;
    }

    /**
     * Sets the config to use. See the example config file in Config/config.php
     * 
     * @param array $config
     */
    public static function setConfig(array $config)
    {
    	static::$config = $config;
    }
}