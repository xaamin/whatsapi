<?php 
namespace Xaamin\Whatsapi;

use Config;
use WhatsProt;
use Illuminate\Support\ServiceProvider;

class WhatsapiServiceProvider extends ServiceProvider 
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    public function boot()
    {
        $this->publishConfigFiles();
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerWhatsProt();
        $this->registerEventListener();
        $this->registerMediaManager();
        $this->registerMessageManager();
        $this->registerSessionManager();
        $this->registerRegistrationTool();
        $this->registerWhatsapi();

        $this->mergeConfigFrom(__DIR__ . '/Config/config.php', 'whatsapi');
    }

    private function publishConfigFiles()
    {
        $this->publishes([
            __DIR__.'/Config/config.php' => config_path('whatsapi.php'),
        ], 'config');
    }

    private function registerWhatsProt()
    {        
        // Set up how the create the WhatsProt object when using MGP25 fork
        $this->app->singleton('WhatsProt', function ()
        {
            // Setup Account details.
            $debug     = Config::get("whatsapi.debug");
            $account   = Config::get("whatsapi.default");
            $nickname  = Config::get("whatsapi.accounts.$account.nickname");
            $number    = Config::get("whatsapi.accounts.$account.number");
            $nextChallengeFile = Config::get("whatsapi.challenge-path") . "/" . $number . "-next-challenge.dat";

            $whatsProt =  new WhatsProt($number, $nickname, $debug);
            $whatsProt->setChallengeName($nextChallengeFile);

            return $whatsProt;
        });
    }

    private function registerEventListener()
    {
        $this->app->singleton('Xaamin\Whatsapi\Events\Listener', function($app)
        {   
            $session = $app->make('Xaamin\Whatsapi\Sessions\SessionInterface');

            return new \Xaamin\Whatsapi\Events\Listener($session, Config::get('whatsapi'));
        });
    }

    private function registerMediaManager()
    {
        $this->app->singleton('Xaamin\Whatsapi\Media\Media', function($app)
        {   
            return new \Xaamin\Whatsapi\Media\Media(Config::get('whatsapi.media-path'));
        });
    }

    private function registerMessageManager()
    {
        $this->app->singleton('Xaamin\Whatsapi\MessageManager', function($app)
        {   
            $media = $app->make('Xaamin\Whatsapi\Media\Media');

            return new \Xaamin\Whatsapi\MessageManager($media);
        });
    }

    private function registerSessionManager()
    {
        $this->app->singleton('Xaamin\Whatsapi\Sessions\SessionInterface', function ($app)
        {
             return $app->make('Xaamin\Whatsapi\Sessions\Laravel\Session');
        });
    }

    private function registerWhatsapi()
    {
        $this->app->singleton('Xaamin\Whatsapi\Contracts\WhatsapiInterface', function ($app)
        {
             // Dependencies
             $whatsProt = $app->make('WhatsProt');
             $manager = $app->make('Xaamin\Whatsapi\MessageManager');
             $session = $app->make('Xaamin\Whatsapi\Sessions\SessionInterface');
             $listener = $app->make('Xaamin\Whatsapi\Events\Listener');

             $config = Config::get('whatsapi');

             return new \Xaamin\Whatsapi\Clients\MGP25($whatsProt, $manager, $listener, $session, $config);
        });

    }

    private function registerRegistrationTool()
    {
        $this->app->singleton('Xaamin\Whatsapi\Contracts\WhatsapiToolInterface', function($app)
        {
            $listener = $app->make('Xaamin\Whatsapi\Events\Listener');

            return new \Xaamin\Whatsapi\Tools\MGP25($listener, Config::get('whatsapi.debug'));
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['Xaamin\Whatsapi\Contracts\WhatsapiInterface', 'Xaamin\Whatsapi\Contracts\WhatsapiToolInterface'];
    }
}