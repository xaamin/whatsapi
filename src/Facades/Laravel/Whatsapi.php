<?php 
namespace Xaamin\Whatsapi\Facades\Laravel;

use Illuminate\Support\Facades\Facade;

class Whatsapi extends Facade 
{
    protected static function getFacadeAccessor()
    {
        return 'Xaamin\Whatsapi\Contracts\WhatsapiInterface';
    }
}