<?php 
namespace Xaamin\Whatsapi\Clients;

use Closure;
use stdClass;
use Exception;
use WhatsProt;
use Xaamin\Whatsapi\Media\VCard;
use Xaamin\Whatsapi\MessageManager;
use Xaamin\Whatsapi\Events\Listener;
use Xaamin\Whatsapi\Sessions\SessionInterface;
use Xaamin\Whatsapi\Contracts\WhatsapiInterface;
use Xaamin\Whatsapi\Contracts\ListenerInterface;

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
     * Holds connect status
     * 
     * @var boolean
     */
    protected $connected = false;

    /**
     * Holds SessionInterface implementation
     * 
     * @var Xaamin\Whatsapi\Sessions\SessionInterface
     */
    protected $session;

    /**
     * Holds event listener
     * 
     * @var \Xaamin\Whatsapi\Events\Listener
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
    public function __construct(WhatsProt $whatsProt, MessageManager $manager, Listener $listener, SessionInterface $session, array $config)
    {
        $this->whatsProt = $whatsProt;
        $this->manager = $manager;
        $this->walistener = $listener->registerWhatsProtEvents($whatsProt);
        $this->session = $session;
        $this->config = $config;

        $account = $this->config["default"];

        $this->password = $this->config["accounts"][$account]["password"];
        $this->account = $this->config["accounts"][$account];
    }

    /**
     * Sets the Whatsapi event listener
     * 
     * @param  \Xaamin\Whatsapi\Contracts\ListenerInterface $listener 
     * @return void
     */
    public function setListener(ListenerInterface $listener)
    {
        $this->walistener->setListener($listener);
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
     * {@inheritdoc}
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

                if($object = $this->walistener->getListener())
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
     * {@inheritdoc}
     */
    public function syncContacts(array $contacs, array $delete = null)
    {
        $this->gateway()->sendSync($contacs, $delete);

        return $this->session->pull();
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
     * {@inheritdoc}
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