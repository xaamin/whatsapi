<?php 
namespace Xaamin\Whatsapi\Clients;

use Closure;
use stdClass;
use Exception;
use WhatsProt;
use Xaamin\Whatsapi\Media\VCard;
use Xaamin\Whatsapi\MessageManager;
use Xaamin\Whatsapi\Events\Listener;
use Xaamin\Whatsapi\Contracts\WhatsapiInterface;
use Xaamin\Whatsapi\Contracts\WhatsapiToolInterface;

class MGP25 implements WhatsapiInterface
{
    /**
     * @var string $password
     */
    protected $password;

    /**
     * @var WhatsProt
     */
    protected $whatsProt;

    /**
     * Message manager
     * 
     * @var \Xaamin\Whatsapi\Whatsapi\MessageManager
     */
    protected $manager;

    /**
     * Tool implementation to request an register Whatsapp code
     * 
     * @var \Xaamin\Whatsapi\Contracts\WhatsapiToolInterface
     */
    protected $tool;

    /**
     * Holds connect status
     * 
     * @var boolean
     */
    protected $connected = false;

    /**
     * Holds ListenerInterface instance
     * 
     * @var ListenerInterface
     */
    protected $listener;

    /**
     * Holds ListenerInterface instance
     * 
     * @var ListenerInterface
     */
    protected $walistener;

    /**
     * Información about account login
     * 
     * @var array
     */
    protected $account = [];

    /**
     * Configuration variables
     * 
     * @var array
     */
    protected $config = [];

    /**
     * Will be used broadcast to send messages?
     * 
     * @var boolean
     */
    protected $broadcast = false;

    /**
     * @param WhatsProt $whatsProt
     */
    public function __construct(WhatsProt $whatsProt, MessageManager $manager, WhatsapiToolInterface $tool, Listener $listener, array $config)
    {
        $this->whatsProt = $whatsProt;
        $this->manager = $manager;
        $this->tool = $tool;
        $this->walistener = $listener;
        $this->config = $config;

        $account = $this->config["default"];

        $this->password = $this->config["accounts"][$account]["password"];
        $this->account = $this->config["accounts"][$account];

        if($this->config['listen-type'] != 'custom')
        {
            $this->connectAndLogin();
        }
    }

    /**
     * Registration tool
     * 
     * @return WhatsapiToolInterface
     */
    public function tool()
    {
        return $this->tool;
    }

    /**
     * Sets the Whatsapi event listener
     * 
     * @param  ListenerInterface $listener 
     * @return ListenerInterface
     */
    public function setListener(ListenerInterface $listener)
    {
        $this->listener = $this->walistener->setListener($listener);
        $this->connectAndLogin();
    }

    /**
     * Returns current WhatsProt instance
     * 
     * @return WhatsProt
     */
    public function gateway()
    {
        $this->connectAndLogin();
        return $this->whatsProt;
    }

    /**
     * Send messages with Whatsapi
     * 
     * <code>
     *     $message = 'Hi Ben, SOS!';
     *     $result = WA::send($message, function($message)
     *     {
     *         // Sets receivers
     *         $message->to('5219511552222', '52195115583333', '5219511552233');
     *         
     *         // Add an image
     *         $message->audio('http://itnovado.com/example.mp3');
     * 
     *         // Add an audio file
     *         $message->image('http://itnovado.com/example.jpg', 'Cool image');
     *         
     *         // Add a video
     *         $message->video('http://itnovado.com/example.mp4', 'Fun video');
     * 
     *         // Add a location
     *         $message->location(-89.164138, 19.412405, 'Itnovado Location');
     * 
     *         // Add a VCard
     *         $vcard = new Xaamin\Whatsapi\Media\VCard();
     *     
     *         $vcard->set('data', array(
     *             'first_name' => 'John',
     *             'last_name' => 'Doe',
     *             'tel' => '9611111111',
     *             ));
     *     
     *         $message->vcard('Xaamin Mat', $vcard);
     *         
     *         // Add new text message
     *         $message->message('Thanks in advanced');
     *     }
     * 
     *     // Loop on each message
     *     foreach($result as $message)
     *     {
     *         // Do something with message
     *     }
     * </code>
     * 
     * @param  string  $message
     * @param  Closure $callback
     * @return array Return the messages sended with injected variables and MessageID
     */
    public function send($message, Closure $callback)
    {
        $this->manager->message($message);

        if(!is_callable($callback))
        {
            throw new Exception('Callback is not a function or isn\'t callable');
        }
            
        call_user_func($callback, $this->manager);

        return $this->processAllMessages();
    }

    /**
     * Process all messages types
     * 
     * @return array
     */
    private function processAllMessages()
    { 
        $processed = [];

        $receivers = $this->receivers();

        $messages = $this->manager->getMessages();
        
        foreach ($receivers as $receiver)
        {   
            $this->presence($receiver);

            foreach ($messages as $index => $message)
            {                
                $this->composition($receiver, $message);

                $id = $this->sendMessage($receiver, $message);

                $copy = new stdClass();
                $copy->id = $id;
                $copy->type = $message->type;
                $copy->sender = $this->account['number'];
                $copy->nickname = $this->account['nickname'];
                $copy->to = implode(', ', (array) $receiver);

                $copy->message = $message;

                if(isset($message->file) && !$message->hash)
                {
                    $copy->message->hash = $messages[$index]->hash = base64_encode(hash_file("sha256", $message->file, true));
                }

                foreach ($this->manager->getInjectedVars() as $key => $value)
                {
                    $copy->$key = $value;
                }

                if($object = $this->listener)
                {
                    $copy->raw = json_encode($copy);
                    $object->fire('onSendCompleted', (array) $copy);
                }

                $processed[] = $copy;
            }
        }

        $this->broadcast = false;

        return $processed;
    }

    /**
     * Select the best way to send messages and send them returning the MessageID
     * 
     * @param  string|array $receiver
     * @param  stdClass $message
     * @return string
     */
    private function sendMessage($receiver, $message)
    {
        $id = null;

        switch ($message->type)
        {
            case 'text':
                $id = $this->broadcast
                        ? $this->gateway()->sendBroadcastMessage($receiver, $message->message)
                        : $this->gateway()->sendMessage($receiver, $message->message);
                break;
            case 'image':
                $id = $this->broadcast
                        ? $this->gateway()->sendBroadcastImage($receiver, $message->file, false, $message->filesize, $message->hash, $message->caption)
                        : $this->gateway()->sendMessageImage($receiver, $message->file, false, $message->filesize, $message->hash, $message->caption);
                break;
            case 'audio':
                $id = $this->broadcast
                        ? $this->gateway()->sendBroadcastAudio($receiver, $message->file, false, $message->filesize, $message->hash)
                        : $this->gateway()->sendMessageAudio($receiver, $message->file, false, $message->filesize, $message->hash);
                break;
            case 'video':
                $id = $this->broadcast
                        ? $this->gateway()->sendBroadcastVideo($receiver, $message->file, false, $message->filesize, $message->hash, $message->caption)
                        : $this->gateway()->sendMessageVideo($receiver, $message->file, false, $message->filesize, $message->hash, $message->caption);
                break;
            case 'location':
                $id = $this->broadcast 
                        ? $this->gateway()->sendBroadcastLocation($receiver, $message->longitude, $message->latitude, $message->caption, $message->url)
                        : $this->gateway()->sendMessageLocation($receiver, $message->longitude, $message->latitude, $message->caption, $message->url);
                break;
            case 'vcard':
                $id = $this->broadcast 
                        ? $this->gateway()->sendBroadcastVcard($receiver, $message->name, $message->vcard->show())
                        : $this->gateway()->sendVcard($receiver, $message->name, $message->vcard->show());
                break;
            default:
                            
            break;
        }

        $this->gateway()->pollMessage();

        return $id;
    }

    /**
     * Set receivers in a broadcast array if needed
     * 
     * @return array
     */
    private function receivers()
    {
        if(count($this->manager->getReceivers()) <= 10)
        {
            return $this->manager->getReceivers();
        }

        $this->broadcast = true;

        $receivers = [];

        $allReceivers = $this->manager->getReceivers();

        while (count($allReceivers))
        {
            $target = [];

            $count = 1;

            while ($count <= $this->config['broadcast-limit'] && count($allReceivers))
            {
                $target[] = array_shift($allReceivers);
                $count++;
            }

            $receivers[] = $target;
        }

        return $receivers;
    }

    /**
     * Smart composition before sending messages
     * 
     * @param  string|array  $receiver
     * @param  stdClass $message
     * @return void
     */
    private function composition($receiver, stdClass $message)
    {
        if(!$this->broadcast)
        {
            $this->typing($receiver);
            
            sleep($this->manager->composition($message));

            $this->paused($receiver);
        }
    }

    /**
     * Set the chat status to "Online"
     * 
     * @return void
     */
    public function online()
    {
        $this->gateway()->sendPresence('active');
    }

    /**
     * Sets chat status to "Offline"
     * 
     * @return [type] [description]
     */
    public function offline()
    {
        $this->gateway()->sendPresence('inactive');
    }

    /**
     * Sync contacts
     * 
     * @param  array $contacs Contacts to sync
     * @param  array $delete  Contacts to delete
     * @return void
     */
    public function syncContacts(array $contacs, array $delete = null)
    {
        $this->gateway()->sendSync($contacs, $delete);
    }

    /**
     * Send presence
     * 
     * @param  string|array $to
     * @return void
     */
    public function presence($to)
    {
        foreach ((array) $to as $phone) 
        {
            $this->gateway()->sendPresenceSubscription($phone);
        }
    }

    /**
     * Set chat status to "Typing"
     * 
     * @param  string $to
     * @return void
     */
    public function typing($to)
    {
        $this->gateway()->sendMessageComposing($to);
    }

    /**
     * Set chat status to "Paused"
     * 
     * @param string $to
     * @return void
     */
    public function paused($to)
    {
        $this->gateway()->sendMessagePaused($to);
    }

    /**
     * Retrieve all messages received
     * 
     * @return array|null Return null if not messages found
     */
    public function getNewMessages()
    {
        while ($this->gateway()->pollMessage());
        
        $messages = $this->gateway()->getMessages();

        return $this->manager->transformMessages($messages);
    }

    /**
     * Connect to Whatsapp server and Login
     * 
     * @return void
     */
    public function connectAndLogin()
    {
        if(!$this->connected)
        {
            $this->connected = true;

            $this->whatsProt->connect();
            $this->whatsProt->loginWithPassword($this->password);
            $this->whatsProt->sendGetServerProperties();
            $this->online();
        }
    }

    /**
     * Logout and disconnect from Whatsapp server
     * 
     * @return void
     */
    public function logoutAndDisconnect()
    {
        if($this->connected)
        {
            $this->offline();
            $this->whatsProt->disconnect();

            $this->connected = false;
        }
    }

    /**
     * Close Whatsapp connection
     */
    public function __destruct()
    {
        $this->logoutAndDisconnect();
    }
}