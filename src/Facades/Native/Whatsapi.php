<?php 
namespace Xaamin\Whatsapi\Facades\Native;

use WhatsProt;
use UnexpectedValueException;
use Xaamin\Whatsapi\Media\Media;
use Xaamin\Whatsapi\Clients\MGP25;
use Xaamin\Whatsapi\MessageManager;
use Xaamin\Whatsapi\Events\Listener;
use Xaamin\Whatsapi\Sessions\Native\Session;

class Whatsapi extends Facade 
{	
	use ResourceTrait;

    public static function create()
    {
        if(!$session = static::$session)
        {
            $session = new Session;
        }

        if(!$config = static::$config)
        {
            throw new UnexpectedValueException("You must provide config details in order to use Whatsapi");
        }

        // Setup Account details.
        $debug     = $config["debug"];
        $account   = $config["default"];
        $nickname  = $config["accounts"][$account]["nickname"];
        $number    = $config["accounts"][$account]["number"];
        $nextChallengeFile = $config["challenge-path"] . "/" . $number . "-next-challenge.dat";

        $whatsProt =  new WhatsProt($number, $nickname, $debug);
        $whatsProt->setChallengeName($nextChallengeFile);
        
        $media = new Media($config['media-path']);
        $manager = new MessageManager($media);
        $listener = new Listener($session, $config);

        $whatsapi = new MGP25($whatsProt, $manager, $listener, $session, $config);

        if($eventListener = static::$listener)
        {
            $whatsapi->setListener($eventListener);
        }

        return $whatsapi;
    }
}