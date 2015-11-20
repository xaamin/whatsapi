<?php 

namespace Xaamin\Whatsapi\Contracts;

use vCard;
use Closure;

interface ListenerInterface 
{
	/**
	 * Fire an event
	 * See all events on https://github.com/WHAnonymous/Chat-API/wiki/WhatsAPI-Documentation#list-of-all-events
	 * 
	 * @param  string $eventFired Event name
	 * @param  array  $parameters Event parameters
	 * @param  string $message    Resumed message from event
	 * @return void
	 */
	public function fire($eventFired, array $parameters, $message);
}
