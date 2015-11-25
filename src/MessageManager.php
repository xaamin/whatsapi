<?php 
namespace Xaamin\Whatsapi;

use stdClass;
use Xaamin\Whatsapi\Media\VCard;
use Xaamin\Whatsapi\Media\Media;
use Xaamin\Whatsapi\Media\VCardReader;

class MessageManager
{
    /**
     * Media instance
     * 
     * @var \Xaamin\Whatsapi\Media\Media
     */
    protected $media;

    /**
     * Targets to send messages
     * 
     * @var array
     */
    protected $receivers = array();

    /**
     * Holds messages to send.
     * 
     * @var array
     */
    protected $messages = array();

    /**
     * Holds custom variables to inject en message processed
     * 
     * @var array
     */
    protected $injected = array();

    /**
     * Constructor
     * 
     * @param \Xaamin\Whatsapi\Media\Media $media
     */
    public function __construct(Media $media)
    {
        $this->media = $media;
    }

    /**
     * Add custom variables to message
     * 
     * @param  string $key
     * @param  mixed $value
     * @return void
     */
    public function inject($key, $value)
    {
        $this->injected[$key] = $value;
    }

    /**
     * Returns the message receivers list
     * 
     * @return array
     */
    public function getReceivers()
    {
        return $this->receivers;
    }

    /**
     * Returns all messages to be sended
     * 
     * @return array
     */
    public function getMessages()
    {
        return $this->messages;
    }    

    /**
     * Returns all injected vars
     * 
     * @return array
     */
    public function getInjectedVars()
    {
        return $this->injected;
    }

    /**
     * Send text message   
     *  
     * <code>
     *     WA::send('Hi !', function($send)
     *     {
     *         $send->to('5219622222222');
     * 
     *         // You can add more messages
     *         $send->message('Chao !');
     *     }):
     * </code>
     * 
     * @param  string $message
     * @return void
     */
    public function message($message)
    {
        if(trim($message))
        {
            $msg = new stdClass();
            $msg->type = 'text';
            $msg->message = $message;
            $this->messages[] = $msg;
        }        
    }

    /**
     * Send audio message
     *     
     * <code>
     *     WA::send('Listen this !', function($send)
     *     {
     *         $send->to('5219622222222');
     * 
     *         // From local storage
     *         $send->audio('/home/xaamin/example.mp3');
     * 
     *         // or from web url
     *         $send->audio('http://itnovado.com/example.mp3');
     *     }):
     * </code>
     * 
     * @param  string $file File location
     * @return void
     */
    public function audio($file)
    {
        $this->messages[] = $this->media->compile($file, null, 'audio');
    }

    /**
     * Send image message
     *     
     * <code>
     *     WA::send('See this cool image !', function($send)
     *     {
     *         $send->to('5219622222222');
     * 
     *         // From local storage
     *         $send->image('/home/xaamin/example.jpg', 'Cool image');
     * 
     *         // or from web url
     *         $send->image('http://itnovado.com/example.jpg', 'Cool image');
     *     }):
     * </code>
     * 
     * @param  string $file File location
     * @param  string Caption
     * @return void
     */
    public function image($file, $caption = null)
    {
        $this->messages[] = $this->media->compile($file, $caption, 'image');
    }

    /**
     * Send location message
     * 
     * <code>
     *     WA::send('Go to itnovado !', function($send)
     *     {
     *         $send->to('5219622222222');
     *         $send->location(-89.164138, 19.412405, 'Itnovado Location');
     *     }):
     * </code>
     * 
     * @param  float $longitude
     * @param  float $latitude
     * @param  string Caption
     * @param  string $url
     * @return void
     */
    public function location($longitude, $latitude, $caption = null, $url = null)
    {
        $location = new stdClass();
        $location->type = 'location';
        $location->longitude = $longitude;
        $location->latitude = $latitude;
        $location->caption = $caption;
        $location->url = $url;

        $this->messages[] = $location;
    }

    /**
     * Send Virtual Cards
     * 
     * <code>
     *     // Create VCard Instance
     *     $vcard = new Xaamin\Whatsapi\Media\VCard();
     *     
     *     // Set properties
     *     $vcard->set('data', array(
     *         'first_name' => 'John',
     *         'last_name' => 'Doe',
     *         'tel' => '9611111111',
     *     ));
     *     
     *     // Send
     *     WA::send('Hi, meet to Xaamin !', function($send) use ($vcard)
     *     {
     *         $send->to('5219622222222');
     *         $send->vcard('Xaamin Mat', $vcard);
     *     }):
     * </code>
     * 
     * @param  string $name  Person name
     * @param  vCard  $vCard vCard Object
     * @return void
     */
    public function vcard($name, VCard $vcard)
    {
        $card = new stdClass();
        $card->type = 'vcard';
        $card->name = $name;
        $card->vcard = $vcard;
        
        $this->messages[] = $card;
    }

    /**
     * Send video message    
     * 
     * <code>
     *     WA::send('Watch this !', function($send)
     *     {
     *         $send->to('5219622222222');
     * 
     *         // From local storage
     *         $send->video('/home/xaamin/example.mp4', 'Fun video');
     * 
     *         // or from web url
     *         $send->video('http://itnovado.com/example.mp4', 'Fun video');
     *     }):
     * </code>
     * 
     * @param  string $file File location
     * @param  string|null Caption
     * @return void
     */
    public function video($file, $caption = null)
    {
        $this->messages[] = $this->media->compile($file, $caption, 'video');
    }

    /**
     * Set message targets (Receivers)
     * 
     * <code>
     *     WA::send('Hi !', function($send)
     *     {
     *         // One target
     *         $send->to('5219622222222');
     *         
     *         // or using array
     *         $targets = [
     *             '5219622222222', 
     *             '5219511558222', 
     *             '5219511558633'
     *         ];
     *         
     *         $send->to($targets);
     *     }):
     * </code>
     * 
     * @return void
     */
    public function to($targets = null)
    {
        if(!$targets)
        {
            throw new Exception('Target phone(s) not found');            
        }

        foreach ((array) $targets as $receiver)
        {
            if(is_array($receiver))
            {
                $this->to($receiver);
            }
            else
            {
                $this->receivers[] = $receiver;
            }
        }
    }

    /**
     * Smart pause on message composition
     * 
     * @param  stdClass $message
     * @return float
     */
    public function composition(stdClass $message)
    {
        return $message->type == 'text' ? floor(strlen($message->message) * 0.12366667) : 1;
    }


    /**
     * Parse messages and determines message type and set captions and links
     * 
     * @param  array $messages
     * @return array|null
     */
    public function transformMessages(array $messages)
    {
        $transformed = null;

        if(count($messages))
        {
            $transformed = [];

            foreach ($messages as $message)
            {
                $attributes = (object) $message->getAttributes();

                $attributes->timestamp = $attributes->t;

                unset($attributes->t);
                
                $child = $message->getChild(0);

                $node = new stdClass();
                $node->tag = $child->getTag();
                $node->attributes = (object) $child->getAttributes();
                $node->data = $child->getData();

                $attributes->body = $node;

                if($attributes->type == 'media')
                {
                    if($attributes->body->attributes->type == 'vcard')
                    {
                        $vcard = new VCardReader(null, $child->getChild(0)->getData());

                        if(count($vcard) == 1)
                        {
                            $attributes->body->vcard[] = $vcard->parse($vcard);
                        }
                        else
                        {
                            foreach ($vcard as $card)
                            {
                                $attributes->body->vcard[] = $vcard->parse($card);
                            }
                        }
                    }
                    else
                    {
                        $tmp = $this->media->link($attributes);
                    
                        $attributes->body->file = $tmp->file;
                        $attributes->body->html = $tmp->html;
                        $attributes->body->attributes2 = $node->attributes;
                    }           
                }

                $transformed[] = $attributes;
            }
        }

        return $transformed;
    }
}