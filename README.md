## Whatsapp Chat Integrated with Laravel

Wrapper for this awesome [repository](https://github.com/WHAnonymous/Chat-API)

### Installation

Assuming you already have composer installed on your system, install a new [Laravel](http://laravel.com/) project into `whatsapidemo` folder

```
    composer create-project laravel/laravel whatsapidemo --prefer-dist
```

Ensure that you set webserver to use `whatsapidemo/public` as it's webroot. Now, if you visit http://localhost (or whatever domain name you are using) you should see a nice Laravel welcome message. 

Change into your new `whatsapidemo` folder.

```
    cd whatsapidemo
```

Require the needed package. 

``` 
    composer require xaamin/whatsapi
```

If you get `[InvalidArgumentException] Could not find package xaamin/whatsapi at any version for your minimum-stability (stable). Check the package spelling or your minimum-stability` you must add these lines to your composer.json an then re-run previous command.

```
    "minimum-stability": "dev",
    "prefer-stable" : true
```

We tell Laravel that there is a Whatsapi ServiceProvider. At the end of `config/app.php` file, in the providers array, add:

```
    'Xaamin\Whatsapi\WhatsapiServiceProvider'
```

Now we need to publish the config file that will allow you to very easily add all your account numbers.

```
    php artisan vendor:publish --provider="Xaamin\Whatsapi\WhatsapiServiceProvider" --tag="config"
```

Finally, Into the `config/app.php` file, add to aliases array each of these lines

```
    'WA' => 'Xaamin\Whatsapi\Facades\Laravel\Whatsapi',
    'WATOOL' => 'Xaamin\Whatsapi\Facades\Laravel\Registration',
```

### Configuration

Now everything has been installed, you just need to add your Whatsapp account details into the config file. There will now be a personal config file created for you in `whatsapidemo/config/whatsapi.php`. Open this file and edit the details with your account info. Once saved, you're good to use the API!

### Basic usage

**Request registration code**

When requesting the code, you can do it via SMS or voice call, in both cases you will receive a code like 123-456, that we will use for register the number.

```
    $number = '5219511552222'; # Number with country code
    $type = 'sms'; # This can either sms or voice

    $response = WATOOL::requestCode($number, $type);

```

Example response:

```
    stdClass Object
    (
        [status] => sent
        [length] => 6
        [method] => sms
        [retry_after] => 1805
    )
```


**Registration**

If you received the code like this 123-456 you should register like this '123456'

```
    $number = '5219511552222'; # Number with country code
    $code = '132456'; # Replace with received code  

    $response = WATOOL::registerCode($number, $code);

```

If everything went right, this should be the output:

```
    [status] => ok
    [login] => 34123456789
    [pw] => 0lvOVwZUbvLSxXRk5uYRs3d1E=
    [type] => existing
    [expiration] => 1443256747
    [kind] => free
    [price] => EUR€0.99
    [cost] => 0.89
    [currency] => EUR
    [price_expiration] => 1414897682
```

See the entire registration process on [https://github.com/WHAnonymous/Chat-API/wiki/WhatsAPI-Documentation#number-registration](https://github.com/WHAnonymous/Chat-API/wiki/WhatsAPI-Documentation#number-registration)


**Send messages**

```
    $user = User::find(1);
    $message = "Hello $user->name, you're welcome";

    WA::send($message, function($send) use ($user)
    {
        $send->to($user->phone);

        // Add an audio file
        $send->audio('http://itnovado.com/example.mp3');
 
        // Add an image file
        $send->image('http://itnovado.com/example.jpg', 'Cool image');
        
        // Add a video file
        $send->video('http://itnovado.com/example.mp4', 'Fun video');
 
        // Add a location (Longitude, Latitude)
        $send->location(-89.164138, 19.412405, 'Itnovado Location');
 
        // Add a VCard
        $vcard = new Xaamin\Whatsapi\Media\VCard();
     
        $vcard->set('data', array(
            'first_name' => 'John',
            'last_name' => 'Doe',
            'tel' => '9611111111',
            ));
     
        $send->vcard('Xaamin', $vcard);

        // Add new text message
        $send->message('Thanks for subscribe');
    });
```


**Check for new messages**

```
    $messages = WA::getNewMessages();

    if($messages)
    {
        foreach($messages as $message)
        {
            ...
        }
    }
```

**Sync contacts**

```
    $result = WA::syncContacts(['5219512222222', '5219512222223']);
    
    foreach ($result->existing as $number => $account)
    {
        ... 
    }

    foreach ($result->nonExisting as $number)
    {
        ...
    }
```

Example response

```
    SyncResult Object
    (
        [index] => 0
        [syncId] => 130926960960000000
        [existing] => Array
            (
                [+5219512222222] => 5219512222222@s.whatsapp.net
            )

        [nonExisting] => Array
            (
                [0] => 5219512222223
            )

    )
```

You can use on routes, cli... you got the idea.
