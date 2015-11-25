<?php 
namespace Xaamin\Whatsapi\Contracts;

use Closure;
use Xaamin\Whatsapi\Media\VCard;

interface WhatsapiInterface 
{
    /**
     * Returns the api gateway used to send messages to Whatsapp
     * 
     * @return mixed
     */
    public function gateway();

    /**
     * Sync contacts
     * 
     * @param  array  $targets Contacts to sync
     * @param  array  $delete  Contacts to delete
     * @return void
     */
    public function syncContacts(array $targets, array $delete);

    /**
     * Wrapper to send messsages
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
     *         $vcard = new vCard();
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
     * @return array
     */
    public function send($message, Closure $callback);

    /**
     * Retrieve all messages received
     * 
     * @return array|null Null if not messages found
     */
    public function getNewMessages();
}
