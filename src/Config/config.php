<?php

return [
    /**
     * Debug on or off?
     */
    'debug' => true,

    /**
     * Enable log?
     */
    'log' => false,
    
    /**
     * The path for whatsapp data like media, pictures, etc.. 
     * The path must be writable for webserver
     */
    'data-storage' => storage_path() . '/whatsapi',
           
    // Max contacts to broadcast messages
    'broadcast-limit' => 20,

    'listen-events' => true,

    'listen-type' => 'echo',
    
    // Default account to use for sending messages
    'default' => 'default',

    /**
     * These are fake credentials below. Don't even bother trying to use them.
     */
    'accounts'    => array(
        'default'   => array(
            'nickname' => 'Itnovado',
            'number'   => '5219512132132',
            'password' => '==87Vf4plh+lvOAvoURjBoKDKwciw='
        ),
        /*
        'another'    => array(
            'nickname' => '',
            'number'   => '',
            'password' => ''
        ),
        'yetanother' => array(
            'nickname' => '',
            'number'   => '',
            'password' => ''
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
];