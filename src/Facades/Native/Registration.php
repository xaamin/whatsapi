<?php 
namespace Xaamin\Whatsapi\Facades\Native;

use UnexpectedValueException;
use Xaamin\Whatsapi\Tools\MGP25;
use Xaamin\Whatsapi\Events\Listener;
use Xaamin\Whatsapi\Sessions\Native\Session;

class Registration extends Facade 
{
	use ResourceTrait;

    protected static function create()
    {
        if(!$config = static::$config)
        {
            throw new UnexpectedValueException("You must provide config details in order to use Whatsapi Registration Tool");
        }

        $session = new Session;

        $listener = new Listener($session, $config);

        $whatsapi = new MGP25($listener, $config['debug']);

        if($eventListener = static::$listener)
        {
            $whatsapi->setListener($eventListener);
        }

        return $whatsapi;
    }
}