<?php
namespace Xaamin\Whatsapi\Events;

use WhatsProt;
use Exception;
use Registration;
use WhatsApiEventsManager;
use Xaamin\Whatsapi\Sessions\SessionInterface;
use Xaamin\Whatsapi\Contracts\ListenerInterface;

class Listener
{
    /**
     * Whatsapi config
     * 
     * @var array
     */
    private $config = [];

    /**
     * Log lines
     * 
     * @var array
     */
    private $lines = [];

    /**
     * ListenerInterface implementation
     * 
     * @var \Xaamin\Whatsapi\Contracts\ListenerInterface
     */
    protected $listener;

    /**
     * Holds SessionInterface implementation
     * 
     * @var Xaamin\Whatsapi\Sessions\SessionInterface
     */
    protected $session;

    /**
     * Events to listen for
     * 
     * @var array
     */
    protected $events = [];

    /**
     * Constructor
     * 
     * @param array $config
     */
    public function __construct(SessionInterface $session, array $config)
    {
        $this->config = $config;
        $this->session = $session;

        $this->setEventsToListen();
    }

    /**
     * Register the events you want to listen for.
     *
     * @return void
     */
    private function setEventsToListen()
    {                
        if($this->config['listen-events'])
        {
            $this->events = $this->config['events-to-listen'];
        }
        
        $this->lines = $this->config['messages'];
    }

    /**
     * Binds the requested events to the WhatsProt event manager.
     * 
     * @return \Xaamin\Whatsapi\Events\Listener
     */
    public function registerWhatsProtEvents(WhatsProt $whatsProt)
    {
        return $this->registerEvents($whatsProt->eventManager());
    }

    /**
     * Binds the requested events to the Registration event manager.
     * 
     * @return \Xaamin\Whatsapi\Events\Listener
     */
    public function registerRegistrationEvents(Registration $registration)
    {
        return $this->registerEvents($registration->eventManager());
    }

    /**
     * Binds events to the WhatsApiEventsManager
     * @param  \WhatsApiEventsManager $manager
     * @return \Xaamin\Whatsapi\Events\Listener
     */
    protected function registerEvents(WhatsApiEventsManager $manager)
    {
        foreach ($this->events as $event) 
        {
            if (is_callable(array($this, $event))) 
            {
                $manager->bind($event, [$this, $event]);
            }
        }
        
        return $this;
    }

     /**
     * Sets custom event listener
     * 
     * @param  \Xaamin\Whatsapi\Contracts\ListenerInterface $listener 
     * @return void
     */
    public function setListener(ListenerInterface $listener)
    {
        $this->listener = $listener;
    }

    /**
     * Gets the event listener
     * 
     * @return \Xaamin\Whatsapi\Contracts\ListenerInterface
     */
    public function getListener()
    {
        return $this->listener;
    }

    /**
     * Recursive replacer for array strings adding tabs
     * 
     * @param  array $array
     * @param  string $tabs Tabs to add in current level
     * @return string
     */
    private function recursiveReplace(array $array, $tabs = "\t")
    {
        $final = "";

        foreach ($array as $key => $value)
        {
            if(is_array($value))
            {
                $final .= $this->recursiveReplace($value, $tabs . "\t");
            }
            else
            {
                $final .= $tabs . $key . ': ' . $value . "\n";
            }
        }

        return trim($final, "\n");
    }

    /**
     * Replace log entries key with log line
     * 
     * @param  string $key
     * @param  array $replaces
     * @return string|null Return null if not message
     */
    private function replacer($key, array $replaces)
    {
        $message = isset($this->lines[$key]) ? $this->lines[$key] : null;

        $final = [];

        foreach ($replaces as $index => $value)
        {
            if(is_array($value))
            {
                $final['{' . $index . '}'] = $this->recursiveReplace($value);
            }
            else
            {
                $final['{' . $index . '}'] = $value;
            }
        }

        if($message)
        {
            return str_replace(array_keys($final), array_values($final), $message);
        }

        return null;
    }

    /**
     * Get attributes from Node
     * 
     * @param  ProtocolNode $node
     * @return string
     */
    public function getAttributesHashFromNode($node)
    {
        $txt = '';

        $attributes = $node->getAttributes();

        if($attributes)
        {
            foreach ($attributes as $key => $value) 
            {
                $txt .= $key . ': ' . $value . "\n";
            }
        }          

        return $txt;
    }

    /**
     * Fire event
     * 
     * @param  string $event Event name
     * @param  array $parameters
     * @return void
     */
    public function fire($event, array $parameters)
    {
        $message = $this->replacer($event, $parameters);

        if($message)
        {
            $message = $message;

            $type = $this->config['listen-type'];

            $alt = date('H:i:s') . ' - '. substr($event, 2) . ': ' . $message;

            switch ($type)
            {                    
                case 'mixed':
                    $this->sendToScreen($message);
                case 'file':
                    $this->sendToFile($alt);
                break;
                case 'custom':
                    $this->listener->fire($event, $parameters, $message);
                break;
                default:                        
                    $this->sendToScreen($message);
                break;
            }
        }            
    }

    /**
     * Send message to file
     * 
     * @param  string $message
     * @return bool True on successfully write
     */
    protected function sendToFile($message)
    {
        $path = isset($this->config['log-path']) ? $this->config['log-path'] : null;

        if(!is_dir($path))
        {
            throw new Exception('Path ' . $path . ' must be exists and must be writable by the server.');            
        }

        $file = $path . '/' . 'whatsapi-' . date('Ymd') . '.log';

        return @file_put_contents($file, $message . "\n", LOCK_EX | FILE_APPEND);
    }

    /**
     * Send message to screen
     * 
     * @param  string $message
     * @return void
     */
    protected function sendToScreen($message)
    {
        echo $message;
        if(php_sapi_name() == 'cli')
        {
            echo "\n";
        }
        else
        {
            echo "<br>";
            @flush(); @ob_flush();
        }
    }

    /**
     * Extract phone nombre
     * 
     * @param  string $phone
     * @return string
     */
    private function extractNumber($phone)
    {
        return substr($phone, 0, strpos($phone, '@'));
    }

    ################## Events listeners ##################

    public function onCallReceived($mynumber, $from, $id, $notify, $time, $callId) 
    {
        $parameters = [
            'mynumber' => $mynumber,
            'from' => $from,
            'id' => $id,
            'notify' => $notify,
            'time' => $time,
            'callId' => $callId,
        ];

        $this->fire(__FUNCTION__, $parameters);
    }

    public function onClose($mynumber, $error) 
    {
        $parameters = [
            'mynumber' => $mynumber,
            'error' => $error,
        ];

        $this->fire(__FUNCTION__, $parameters);
    }

    public function onCodeRegister($mynumber, $login, $password, $type, $expiration, $kind, $price, $cost, $currency, $price_expiration) 
    {
        $parameters = [
            'mynumber' => $mynumber,
            'login' => $login,
            'password' => $password,
            'type' => $type,
            'expiration' => $expiration,
            'kind' => $kind,
            'price' => $price,
            'cost' => $cost,
            'currency' => $currency,
            'price_expiration' => $price_expiration,
        ];

        $this->fire(__FUNCTION__, $parameters);
    }

    public function onCodeRegisterFailed($mynumber, $status, $reason, $retry_after) 
    {
        $parameters = [
            'mynumber' => $mynumber,
            'status' => $status,
            'reason' => $reason,
            'retry_after' => $retry_after,
        ];

        $this->fire(__FUNCTION__, $parameters);
    }

    public function onCodeRequest($mynumber, $method, $length) 
    {
        $parameters = [
            'mynumber' => $mynumber,
            'method' => $method,
            'length' => $length,
        ];

        $this->fire(__FUNCTION__, $parameters);
    }

    public function onCodeRequestFailed($mynumber, $method, $reason, $param) 
    {
        $parameters = [
            'mynumber' => $mynumber,
            'method' => $method,
            'reason' => $reason,
            'param' => $param,
        ];

        $this->fire(__FUNCTION__, $parameters);
    }

    public function onCodeRequestFailedTooRecent($mynumber, $method, $reason, $retry_after) 
    {
        $parameters = [
            'mynumber' => $mynumber,
            'method' => $method,
            'reason' => $reason,
            'retry_after' => $retry_after,
        ];

        $this->fire(__FUNCTION__, $parameters);
    }

    public function onCodeRequestFailedTooManyGuesses($mynumber, $method, $reason, $retry_after) 
    {
        $parameters = [
            'mynumber' => $mynumber,
            'method' => $method,
            'reason' => $reason,
            'retry_after' => $retry_after,
        ];

        $this->fire(__FUNCTION__, $parameters);
    }

    public function onConnect($mynumber, $socket) 
    {
        $parameters = [
            'mynumber' => $mynumber,
            'socket' => $socket,
        ];

        $this->fire(__FUNCTION__, $parameters);
    }

    public function onConnectError($mynumber, $socket) 
    {
        $parameters = [
            'mynumber' => $mynumber,
            'socket' => $socket,
        ];

        $this->fire(__FUNCTION__, $parameters);
    }

    public function onCredentialsBad($mynumber, $status, $reason) 
    {
        $parameters = [
            'mynumber' => $mynumber,
            'status' => $status,
            'reason' => $reason,
        ];

        $this->fire(__FUNCTION__, $parameters);
    }

    public function onCredentialsGood($mynumber, $login, $password, $type, $expiration, $kind, $price, $cost, $currency, $price_expiration) 
    {
        $parameters = [
            'mynumber' => $mynumber,
            'login' => $login,
            'password' => $password,
            'type' => $type,
            'expiration' => $expiration,
            'kind' => $kind,
            'price' => $price,
            'cost' => $cost,
            'currency' => $currency,
            'price_expiration' => $price_expiration,
        ];

        $this->fire(__FUNCTION__, $parameters);
    }

    public function onDisconnect($mynumber, $socket) 
    {
        $parameters = [
            'mynumber' => $mynumber,
            'socket' => $socket,
        ];

        $this->fire(__FUNCTION__, $parameters);
    }

    public function onDissectPhone($mynumber, $phonecountry, $phonecc, $phone, $phonemcc, $phoneISO3166, $phoneISO639, $phonemnc) 
    {
        $parameters = [
            'mynumber' => $mynumber,
            'phonecountry' => $phonecountry,
            'phonecc' => $phonecc,
            'phone' => $phone,
            'phonemcc' => $phonemcc,
            'phoneISO3166' => $phoneISO3166,
            'phoneISO639' => $phoneISO639,
            'phonemnc' => $phonemnc,
        ];

        $this->fire(__FUNCTION__, $parameters);
    }

    public function onDissectPhoneFailed($mynumber) 
    {
        $parameters = [
            'mynumber' => $mynumber
        ];

        $this->fire(__FUNCTION__, $parameters);
    }

    public function onGetAudio($mynumber, $from, $id, $type, $time, $name, $size, $url, $file, $mimeType, $fileHash, $duration, $acodec, $fromJID_ifGroup = null) 
    {
        $parameters = [
            'mynumber' => $mynumber,
            'from' => $from,
            'id' => $id,
            'type' => $type,
            'time' => $time,
            'name' => $name,
            'size' => $size,
            'url' => $url,
            'file' => $file,
            'mimeType' => $mimeType,
            'fileHash' => $fileHash,
            'duration' => $duration,
            'acodec' => $acodec,
            'fromJID_ifGroup' => $fromJID_ifGroup,
        ];

        $this->fire(__FUNCTION__, $parameters);
    }

    public function onGetBroadcastLists($mynumber, $broadcastLists)
    {
        $parameters = [
            'mynumber' => $mynumber,
            'broadcastList' => $broadcastList,
        ];

        $this->fire(__FUNCTION__, $parameters);
    }

    public function onGetError($mynumber, $from, $id, $data, $errorType = null) 
    {
        $parameters = [
            'mynumber' => $mynumber,
            'from' => $from,
            'id' => $id,
            'data' => $data,
            'errorType' => $errorType,
        ];

        $this->fire(__FUNCTION__, $parameters);
    }

    public function onGetExtendAccount($mynumber, $kind, $status, $creation, $expiration) 
    {
        $parameters = [
            'mynumber' => $mynumber,
            'kind' => $kind,
            'status' => $status,
            'creation' => $creation,
            'expiration' => $expiration,
        ];

        $this->fire(__FUNCTION__, $parameters);
    }

    public function onGetFeature($mynumber, $from, $encrypt) 
    {
        $parameters = [
            'mynumber' => $mynumber,
            'from' => $from,
            'encrypt' => $encrypt,
        ];

        $this->fire(__FUNCTION__, $parameters);
    }

    public function onGetGroupMessage($mynumber, $from_group_jid, $from_user_jid, $id, $type, $time, $name, $body) 
    {
        $parameters = [
            'mynumber' => $mynumber,
            'from_group_jid' => $from_group_jid,
            'from_user_jid' => $from_user_jid,
            'id' => $id,
            'type' => $type,
            'time' => $time,
            'name' => $name,
            'body' => $body,
        ];

        $this->fire(__FUNCTION__, $parameters);
    }

    public function onGetGroups($mynumber, $groupList) 
    {
        $parameters = [
            'mynumber' => $mynumber,
            'groupList' => $groupList,
        ];

        $this->fire(__FUNCTION__, $parameters);
    }

    public function onGetGroupV2Info($mynumber, $group_id, $creator, $creation, $subject, $participants, $admins, $fromGetGroup)
    {
        $parameters = [
            'mynumber' => $mynumber,
            'group_id' => $group_id,
            'creator' => $creator,
            'creation' => $creation,
            'subject' => $subject,
            'participants' => $participants,
            'admins' => $admins,
            'fromGetGroup' => $fromGetGroup,
        ];

        $this->fire(__FUNCTION__, $parameters);
    }

    public function onGetGroupsSubject($mynumber, $group_jid, $time, $author, $name, $subject) 
    {
        $parameters = [
            'mynumber' => $mynumber,
            'group_jid' => $group_jid,
            'time' => $time,
            'author' => $author,
            'name' => $name,
            'subject' => $subject,
        ];

        $this->fire(__FUNCTION__, $parameters);
    }

    public function onGetImage($mynumber, $from, $id, $type, $time, $name, $size, $url, $file, $mimeType, $fileHash, $width, $height, $preview, $caption) 
    {
        $parameters = [
            'mynumber' => $mynumber,
            'from' => $from,
            'id' => $id,
            'type' => $type,
            'time' => $time,
            'name' => $name,
            'size' => $size,
            'url' => $url,
            'file' => $file,
            'mimeType' => $mimeType,
            'fileHash' => $fileHash,
            'width' => $width,
            'height' => $height,
            'preview' => $preview,
            'caption' => $caption,
        ];

        $this->fire(__FUNCTION__, $parameters);
    }

    public function onGetGroupImage($mynumber, $from_group_jid, $from_user_jid, $id, $type, $time, $name, $size, $url, $file, $mimeType, $fileHash, $width, $height, $preview, $caption) 
    {
        $parameters = [
            'mynumber' => $mynumber,
            'from_group_jid' => $from_group_jid,
            'from_user_jid' => $from_user_jid,
            'id' => $id,
            'type' => $type,
            'time' => $time,
            'name' => $name,
            'size' => $size,
            'url' => $url,
            'file' => $file,
            'mimeType' => $mimeType,
            'fileHash' => $fileHash,
            'width' => $width,
            'height' => $height,
            'preview' => $preview,
            'caption' => $caption,
        ];

        $this->fire(__FUNCTION__, $parameters);
    }

    public function onGetGroupVideo($mynumber, $from_group_jid, $from_user_jid, $id, $type, $time, $name, $url, $file, $size, $mimeType, $fileHash, $duration, $vcodec, $acodec, $preview, $caption) 
    {
        $parameters = [
            'mynumber' => $mynumber,
            'from_group_jid' => $from_group_jid,
            'from_user_jid' => $from_user_jid,
            'id' => $id,
            'type' => $type,
            'time' => $time,
            'name' => $name,
            'url' => $url,
            'file' => $file,
            'size' => $size,
            'mimeType' => $mimeType,
            'fileHash' => $fileHash,
            'duration' => $duration,
            'vcodec' => $vcodec,
            'acodec' => $acodec,
            'preview' => $preview,
            'caption' => $caption,
        ];

        $this->fire(__FUNCTION__, $parameters);
    }

    public function onGetLocation($mynumber, $from, $id, $type, $time, $name, $author, $longitude, $latitude, $url, $preview, $fromJID_ifGroup = null) 
    {
        $parameters = [
            'mynumber' => $mynumber,
            'from' => $from,
            'id' => $id,
            'type' => $type,
            'time' => $time,
            'name' => $name,
            'author' => $author,
            'longitude' => $longitude,
            'latitude' => $latitude,
            'url' => $url,
            'preview' => $preview,
            'fromJID_ifGroup' => $fromJID_ifGroup,
        ];

        $this->fire(__FUNCTION__, $parameters);
    }

    public function onGetMessage($mynumber, $from, $id, $type, $time, $name, $body) 
    {
        $parameters = [
            'mynumber' => $mynumber,
            'from' => $from,
            'id' => $id,
            'type' => $type,
            'time' => $time,
            'name' => $name,
            'body' => $body,
        ];

        $this->fire(__FUNCTION__, $parameters);
    }

    public function onGetNormalizedJid($mynumber, $data) 
    {
        $parameters = [
            'mynumber' => $mynumber,
            'data' => $data,
        ];

        $this->fire(__FUNCTION__, $parameters);
    }

    public function onGetPrivacyBlockedList($mynumber, $data) 
    {
        $parameters = [
            'mynumber' => $mynumber,
            'data' => $data,
        ];

        $this->fire(__FUNCTION__, $parameters);
    }

    public function onGetProfilePicture($mynumber, $from, $type, $data) 
    {
        $parameters = [
            'mynumber' => $mynumber,
            'from' => $from,
            'type' => $type,
            'data' => $data,
        ];

        $this->fire(__FUNCTION__, $parameters);
    }

    public function onGetReceipt($from, $id, $offline, $retry) 
    {
        $parameters = [
            'from' => $from,
            'id' => $id,
            'offline' => $offline,
            'retry' => $retry,
        ];

        $this->fire(__FUNCTION__, $parameters);
    }

    public function onGetRequestLastSeen($mynumber, $from, $id, $seconds) 
    {
        $parameters = [
            'mynumber' => $mynumber,
            'from' => $from,
            'id' => $id,
            'seconds' => $seconds,
        ];

        $this->fire(__FUNCTION__, $parameters);
    }

    public function onGetServerProperties($mynumber, $version, $props) 
    {
        $parameters = [
            'mynumber' => $mynumber,
            'version' => $version,
            'props' => $props,
        ];

        $this->fire(__FUNCTION__, $parameters);
    }

    public function onGetServicePricing($mynumber, $price, $cost, $currency, $expiration) 
    {
        $parameters = [
            'mynumber' => $mynumber,
            'price' => $price,
            'cost' => $cost,
            'currency' => $currency,
            'expiration' => $expiration,
        ];

        $this->fire(__FUNCTION__, $parameters);
    }

    public function onGetStatus($mynumber, $from, $requested, $id, $time, $data) 
    {
        $parameters = [
            'mynumber' => $mynumber,
            'from' => $from,
            'requested' => $requested,
            'id' => $id,
            'time' => $time,
            'data' => $data,
        ];

        $this->fire(__FUNCTION__, $parameters);
    }

    public function onGetSyncResult($result) 
    {
        $sync = 'Index: ' . $result->index . "\n";
        $sync .= 'Sync Id: ' . $result->syncId . "\n";
        $sync .= "Existing: \n\t\t" . preg_replace('/[a-z\.@]+/i', '', implode("\n\t\t", (array) $result->existing));
        $sync .= "\n\tNon existing: \n\t\t" . implode("\n\t\t", (array) $result->nonExisting);

        $parameters = [
            'result' => $sync
        ];

        $this->session->put($result);

        $this->fire(__FUNCTION__, $parameters);
    }

    public function onGetVideo($mynumber, $from, $id, $type, $time, $name, $url, $file, $size, $mimeType, $fileHash, $duration, $vcodec, $acodec, $preview, $caption) 
    {
        $parameters = [
            'mynumber' => $mynumber,
            'from' => $from,
            'id' => $id,
            'type' => $type,
            'time' => $time,
            'name' => $name,
            'url' => $url,
            'file' => $file,
            'size' => $size,
            'mimeType' => $mimeType,
            'fileHash' => $fileHash,
            'duration' => $duration,
            'vcodec' => $vcodec,
            'acodec' => $acodec,
            'preview' => $preview,
            'caption' => $caption,
        ];

        $this->fire(__FUNCTION__, $parameters);
    }

    public function onGetvCard($mynumber, $from, $id, $type, $time, $name, $vcardname, $vcard, $fromJID_ifGroup = null) 
    {
        $parameters = [
            'mynumber' => $mynumber,
            'from' => $from,
            'id' => $id,
            'type' => $type,
            'time' => $time,
            'name' => $name,
            'vcardname' => $vcardname,
            'vcard' => $vcard,
            'fromJID_ifGroup' => $fromJID_ifGroup,
        ];

        $this->fire(__FUNCTION__, $parameters);
    }

    public function onGroupCreate($mynumber, $groupId) 
    {
        $parameters = [
            'mynumber' => $mynumber,
            'groupId' => $groupId,
        ];

        $this->fire(__FUNCTION__, $parameters);
    }

    public function onGroupisCreated($mynumber, $creator, $gid, $subject, $admin, $creation, $members = array()) 
    {
        $parameters = [
            'mynumber' => $mynumber,
            'creator' => $creator,
            'gid' => $gid,
            'subject' => $subject,
            'admin' => $admin,
            'creation' => $creation,
            'members' => $members,
        ];

        $this->fire(__FUNCTION__, $parameters);
    }

    public function onGroupsChatCreate($mynumber, $gid) 
    {
        $parameters = [
            'mynumber' => $mynumber,
            'gid' => $gid,
        ];

        $this->fire(__FUNCTION__, $parameters);
    }

    public function onGroupsChatEnd($mynumber, $gid) 
    {
        $parameters = [
            'mynumber' => $mynumber,
            'gid' => $gid,
        ];

        $this->fire(__FUNCTION__, $parameters);
    }

    public function onGroupsParticipantsAdd($mynumber, $groupId, $jid) 
    {
        $parameters = [
            'mynumber' => $mynumber,
            'groupId' => $groupId,
            'jid' => $jid,
        ];

        $this->fire(__FUNCTION__, $parameters);
    }

    public function onGroupsParticipantChangedNumber($mynumber, $groupId, $time, $oldNumber, $notify, $newNumber) 
    {
        $parameters = [
            'mynumber' => $mynumber,
            'groupId' => $groupId,
            'time' => $time,
            'oldNumber' => $oldNumber,
            'notify' => $notify,
            'newNumber' => $newNumber,
        ];

        $this->fire(__FUNCTION__, $parameters);
    }

    public function onGroupsParticipantsPromote($myNumber, $groupJID, $time, $issuerJID, $issuerName, $promotedJIDs = array()) 
    {
        $parameters = [
            'myNumber' => $myNumber,
            'groupJID' => $groupJID,
            'time' => $time,
            'issuerJID' => $issuerJID,
            'issuerName' => $issuerName,
            'promotedJIDs' => $promotedJIDs,
        ];

        $this->fire(__FUNCTION__, $parameters);
    }

    public function onGroupsParticipantsRemove($mynumber, $groupId, $jid) 
    {
        $parameters = [
            'mynumber' => $mynumber,
            'groupId' => $groupId,
            'jid' => $jid,
        ];

        $this->fire(__FUNCTION__, $parameters);
    }

    public function onLoginFailed($mynumber, $data) 
    {
        $parameters = [
            'mynumber' => $mynumber,
            'data' => $data,
        ];

        $this->fire(__FUNCTION__, $parameters);
    }

    public function onLoginSuccess($mynumber, $kind, $status, $creation, $expiration) 
    {
        $parameters = [
            'mynumber' => $mynumber,
            'kind' => $kind,
            'status' => $status,
            'creation' => $creation,
            'expiration' => $expiration,
        ];

        $this->fire(__FUNCTION__, $parameters);
    }

    public function onAccountExpired($mynumber, $kind, $status, $creation, $expiration )
    {
        $parameters = [
            'mynumber' => $mynumber,
            'kind' => $kind,
            'status' => $status,
            'creation' => $creation,
            'expiration' => $expiration,
        ];

        $this->fire(__FUNCTION__, $parameters);
    }

    public function onMediaMessageSent($mynumber, $to, $id, $filetype, $url, $filename, $filesize, $filehash, $caption, $icon) 
    {
        $parameters = [
            'mynumber' => $mynumber,
            'to' => $to,
            'id' => $id,
            'filetype' => $filetype,
            'url' => $url,
            'filename' => $filename,
            'filesize' => $filesize,
            'filehash' => $filehash,
            'caption' => $caption,
            'icon' => $icon,
        ];

        $this->fire(__FUNCTION__, $parameters);
    }

    public function onMediaUploadFailed($mynumber, $id, $node, $messageNode, $statusMessage) 
    {
        $parameters = [
            'mynumber' => $mynumber,
            'id' => $id,
            'node' => $node,
            'messageNode' => $messageNode,
            'statusMessage' => $statusMessage,
        ];

        $this->fire(__FUNCTION__, $parameters);
    }

    public function onMessageComposing($mynumber, $from, $id, $type, $time) 
    {
        $parameters = [
            'mynumber' => $mynumber,
            'from' => $from,
            'id' => $id,
            'type' => $type,
            'time' => $time,
        ];

        $this->fire(__FUNCTION__, $parameters);
    }

    public function onMessagePaused($mynumber, $from, $id, $type, $time) 
    {
        $parameters = [
            'mynumber' => $mynumber,
            'from' => $from,
            'id' => $id,
            'type' => $type,
            'time' => $time,
        ];

        $this->fire(__FUNCTION__, $parameters);
    }

    public function onMessageReceivedClient($mynumber, $from, $id, $type, $time, $participant) 
    {
        $parameters = [
            'mynumber' => $mynumber,
            'from' => $from,
            'id' => $id,
            'type' => $type,
            'time' => $time,
            'participant' => $participant,
        ];

        $this->fire(__FUNCTION__, $parameters);
    }

    public function onMessageReceivedServer($mynumber, $from, $id, $type, $time) 
    {
        $parameters = [
            'mynumber' => $mynumber,
            'from' => $from,
            'id' => $id,
            'type' => $type,
            'time' => $time,
        ];

        $this->fire(__FUNCTION__, $parameters);
    }

    public function onNumberWasAdded($mynumber, $jid) 
    {
        $parameters = [
            'mynumber' => $mynumber,
            'jid' => $jid,
        ];

        $this->fire(__FUNCTION__, $parameters);
    }

    public function onNumberWasRemoved($mynumber, $jid) 
    {
        $parameters = [
            'mynumber' => $mynumber,
            'jid' => $jid,
        ];

        $this->fire(__FUNCTION__, $parameters);
    }

    public function onNumberWasUpdated($mynumber, $jid) 
    {
        $parameters = [
            'mynumber' => $mynumber,
            'jid' => $jid,
        ];

        $this->fire(__FUNCTION__, $parameters);
    }

    public function onPaidAccount($mynumber, $author, $kind, $status, $creation, $expiration) 
    {
        $parameters = [
            'mynumber' => $mynumber,
            'author' => $author,
            'kind' => $kind,
            'status' => $status,
            'creation' => $creation,
            'expiration' => $expiration,
        ];

        $this->fire(__FUNCTION__, $parameters);
    }

    public function onPaymentRecieved($mynumber, $kind, $status, $creation, $expiration) 
    {
        $parameters = [
            'mynumber' => $mynumber,
            'kind' => $kind,
            'status' => $status,
            'creation' => $creation,
            'expiration' => $expiration,
        ];

        $this->fire(__FUNCTION__, $parameters);
    }

    public function onPing($mynumber, $id) 
    {
        $parameters = [
            'mynumber' => $mynumber,
            'id' => $id,
        ];

        $this->fire(__FUNCTION__, $parameters);
    }

    public function onPresenceAvailable($mynumber, $from) 
    {
        $parameters = [
            'mynumber' => $mynumber,
            'from' => $from,
        ];

        $this->fire(__FUNCTION__, $parameters);
    }

    public function onPresenceUnavailable($mynumber, $from, $last) 
    {
        $parameters = [
            'mynumber' => $mynumber,
            'from' => $from,
            'last' => $last,
        ];

        $this->fire(__FUNCTION__, $parameters);
    }

    public function onProfilePictureChanged($mynumber, $from, $id, $time) 
    {
        $parameters = [
            'mynumber' => $mynumber,
            'from' => $from,
            'id' => $id,
            'time' => $time,
        ];

        $this->fire(__FUNCTION__, $parameters);
    }

    public function onProfilePictureDeleted($mynumber, $from, $id, $time) 
    {
        $parameters = [
            'mynumber' => $mynumber,
            'from' => $from,
            'id' => $id,
            'time' => $time,
        ];

        $this->fire(__FUNCTION__, $parameters);
    }

    public function onSendMessage($mynumber, $target, $messageId, $node) 
    {
        $parameters = [
            'mynumber' => $mynumber,
            'target' => $target,
            'messageId' => $messageId,
            'node' => $node,
        ];

        $this->fire(__FUNCTION__, $parameters);
    }

    public function onSendMessageReceived($mynumber, $id, $from, $type) 
    {
        $parameters = [
            'mynumber' => $mynumber,
            'id' => $id,
            'from' => $from,
            'type' => $type,
        ];

        $this->fire(__FUNCTION__, $parameters);
    }

    public function onSendPong($mynumber, $msgid) 
    {
        $parameters = [
            'mynumber' => $mynumber,
            'msgid' => $msgid,
        ];

        $this->fire(__FUNCTION__, $parameters);
    }

    public function onSendPresence($mynumber, $type, $name ) 
    {
        $parameters = [
            'mynumber' => $mynumber,
            'type' => $type,
            'name' => $name,
        ];

        $this->fire(__FUNCTION__, $parameters);
    }

    public function onSendStatusUpdate($mynumber, $txt ) 
    {
        $parameters = [
            'mynumber' => $mynumber,
            'txt' => $txt,
        ];

        $this->fire(__FUNCTION__, $parameters);
    }

    public function onStreamError($data) 
    {
        $parameters = [
            'data' => $data
        ];

        $this->fire(__FUNCTION__, $parameters);
    }

    public function onWebSync($mynumber, $from, $id, $syncData, $code, $name) 
    {
        $parameters = [
            'mynumber' => $mynumber,
            'from' => $from,
            'id' => $id,
            'syncData' => $syncData,
            'code' => $code,
            'name' => $name,
        ];

        $this->fire(__FUNCTION__, $parameters);
    }
}