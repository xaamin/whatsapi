<?php

return [
    'debug' => false,
    
    /**
     * Next paths must be writable by webserver
     */
    'challenge-path' => storage_path() . '/app/whatsapi', 
   
    'media-path' => public_path() . '/media', 

    'log-path' => storage_path() . '/logs',
        
    // Max contacts to broadcast messages
    'broadcast-limit' => 20,

    'listen-events' => true,

    'listen-type' => 'echo',
    
    // Default account to use for sending messages
    'default' => 'default',

    /**
     * These are fake credentials below. Don't even bother trying to use them.
     *
     * Now listen up. The identity field seems to screw everyone up. This is how it works.
     * Whatsapp needs a unique string, 20 characters long when you register, for it to keep track of the device using the service.
     * When you use THIS API, the identity token provided gets urldecoded to see if it's 20 characters long.
     *
     * If it is NOT 20 characters long, the identity token gets hashed using sha1, then urlencoded (so we can save/use it easily as a string)
     * and finally converted to lower case. This now gives us a unique (to us) 20 character long string.
     *
     * If you provide a string (either already URLencoded or a string that when urlDecoded is 20 characters long) that will be used instead of
     * any processing by the API. This allows you to use an identity that you might already know or have received using another problem eg WART.
     *
     * It's up to you.
     */
    'accounts'    => array(
        'default'   => array(
            'nickname' => 'Itnovado',
            'number'   => '5219512132132',
            'password' => '==87Vf4plh+lvOAvoURjBoKDKwciw=',
            'identity' => ''
        ),
        /*
        'another'    => array(
            'nickname' => '',
            'number'   => '',
            'password' => '',
            'identity' => ''
        ),
        'yetanother' => array('nickname' => '',
            'number'   => '',
            'password' => '',
            'identity' => ''
        )
        */
    ),
    
    /**
     * This is a list of all current events. Uncomment the ones you wish to listen to.
     */
    'events-to-listen' => [
        'onClose',
        'onCodeRegister',
        'onCodeRegisterFailed',
        'onCodeRequest',
        'onCodeRequestFailed',
        'onCodeRequestFailedTooRecent',
        'onConnect',
        'onConnectError',
        'onCredentialsBad',
        'onCredentialsGood',
        'onDisconnect',
        'onDissectPhone',
        'onDissectPhoneFailed',
        'onGetAudio',
        'onGetBroadcastLists',
        'onGetError',
        'onGetExtendAccount',
        'onGetGroupMessage',
        'onGetGroupParticipants',
        'onGetGroups',
        'onGetGroupsInfo',
        'onGetGroupsSubject',
        'onGetImage',
        'onGetGroupImage',
        'onGetLocation',
        'onGetMessage',
        'onGetNormalizedJid',
        'onGetPrivacyBlockedList',
        'onGetProfilePicture',
        'onGetReceipt',
        'onGetServerProperties',
        'onGetServicePricing',
        'onGetStatus',
        'onGetSyncResult',
        'onGetVideo',
        'onGetGroupVideo',
        'onGetGroupV2Info',
        'public',
        'onGetvCard',
        'onGroupCreate',
        'onGroupisCreated',
        'onGroupsChatCreate',
        'onGroupsChatEnd',
        'onGroupsParticipantsAdd',
        'onGroupsParticipantsRemove',
        'onLoginFailed',
        'onLoginSuccess',
        'onMediaMessageSent',
        'onMediaUploadFailed',
        'onMessageComposing',
        'onMessagePaused',
        'onMessageReceivedClient',
        'onMessageReceivedServer',
        'onPaidAccount',
        'onPaymentRecieved',
        'onPing',
        'onPresenceAvailable',
        'onPresenceUnavailable',
        'onProfilePictureChanged',
        'onProfilePictureDeleted',
        'onSendMessage',
        'onSendMessageReceived',
        'onSendPong',
        'onSendPresence',
        'onSendStatusUpdate',
        'onStreamError',
        'onUploadFile',
        'onUploadFileFailed',
    ],
    
    // Default messages for Whatsapi events
    'messages' => [
        'onConnect' => "{mynumber} Connected successfully!",
        'onConnectError' => "{mynumber} Connect error throught socket {socket}",
        'onCredentialsBad' => "{mynumber} Bad credential provided. \n\tStatus: {status}. \n\tReason: {reason}",
        'onCredentialsGood' => "{mynumber} Good credentials \n\tLogin; {login} \n\tPassword: {password} \n\tType: {type} \n\tExpiration: {expiration} \n\tKind: {kind} \n\tPrice: {price} \n\tCost: {cost} \n\tCurrency: {currency} \n\tPrice expiration: {price_expiration}",
        'onDisconnect' => "{mynumber} Disconnected!",
        'onMessageComposing' => "{mynumber} Typing. \n\Target: {from} \n\tID: {id} \n\tType: {type} \n\tTime: {time}",
        'onMessagePaused' => "{mynumber} Typing paused. \n\Target: {from} \n\tID: {id} \n\tType: {type} \n\tTime: {time}",
        'onPresence' => "{mynumber} get presence. \n\tFrom: {from} \n\tStatus: {status}",
        'onSendMessage' => "{mynumber} send a message. \n\tTarget: {target} \n\tID: {messageId} \n\tNode: {node}",
        'onSendPresence' => "{mynumber} sends presence. Type: {type}. Name: {name}"
    ]
];