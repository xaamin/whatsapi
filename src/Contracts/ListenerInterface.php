<?php 

namespace Xaamin\Whatsapi\Contracts;

use vCard;
use Closure;

interface ListenerInterface 
{
	public function fire($fired, array $parameters, $message);
}
