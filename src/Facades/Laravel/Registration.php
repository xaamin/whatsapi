<?php 
namespace Xaamin\Whatsapi\Facades\Laravel;

use Illuminate\Support\Facades\Facade;

class Registration extends Facade 
{
    protected static function getFacadeAccessor()
    {
        return 'Xaamin\Whatsapi\Contracts\WhatsapiToolInterface';
    }
}