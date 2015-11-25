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

    public static function setSessionManager(SessionInterface $session)
    {
        static::$session = $session;
    }

    public static function setEventListener(ListenerInterface $listener)
    {
    	static::$listener = $listener;
    }

    public static function setConfig(array $config)
    {
    	static::$config = $config;
    }
}