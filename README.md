## Whatsapp Chat Integrated with Laravel

Wrapper for this awesome [repository](https://github.com/WHAnonymous/Chat-API)

### Basic usage

Send messages

```
    $user = User::find(1);
    $message = "Hello $user->name, you're welcome";

    WA::send($message, function($send) use ($user)
    {
        $send->to($user->phone);

        // Add an image
        $send->audio('http://itnovado.com/example.mp3');
 
        // Add an audio file
        $send->image('http://itnovado.com/example.jpg', 'Cool image');
        
        // Add a video
        $send->video('http://itnovado.com/example.mp4', 'Fun video');
 
        // Add a location
        $send->location(-89.164138, 19.412405, 'Itnovado Location');
 
        // Add a VCard
        $vcard = new Xaamin\Whatsapi\Media\VCard();
     
        $vcard->set('data', array(
            'first_name' => 'John',
            'last_name' => 'Doe',
            'tel' => '9611111111',
            ));
     
        $send->vcard('Xaamin Mat', $vcard);

        // Add new text message
        $send->message('Thanks for subscribe');
    });
```

Check for new messages

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

You can use on routes, cli... you got the idea.