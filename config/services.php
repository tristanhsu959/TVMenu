<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],
	
	'webcomm' => [
		'client_id'     => env('OETH_CLIENT_ID'),
		'client_secret' => env('OETH_CLIENT_SECRET'),
		'redirect'      => env('OETH_REDIRECT_URI', 'https://report-stag.8way.com.tw:8443/oeth/auth/callback'),
		'base_url'      => env('OETH_BASE_URL', 'https://demo.oeth-uat.webcomm.com.tw/auth/realms/demo'),
		'verify_jwt'    => TRUE, 
	],
	
	/*
	"issuer": "https://demo.oeth-uat.webcomm.com.tw/auth/realms/demo",
	"authorization_endpoint": "https://demo.oeth-uat.webcomm.com.tw/auth/realms/demo/protocol/openid-connect/auth",
	"end_session_endpoint": "https://demo.oeth-uat.webcomm.com.tw/auth/realms/demo/protocol/openid-connect/logout",
	  
		 
	"mtls_endpoint_aliases": {
		"token_endpoint": "https://demo.oeth-uat.webcomm.com.tw/auth/realms/demo/protocol/openid-connect/token",
		"revocation_endpoint": "https://demo.oeth-uat.webcomm.com.tw/auth/realms/demo/protocol/openid-connect/revoke",
		"introspection_endpoint": "https://demo.oeth-uat.webcomm.com.tw/auth/realms/demo/protocol/openid-connect/token/introspect",
		"device_authorization_endpoint": "https://demo.oeth-uat.webcomm.com.tw/auth/realms/demo/protocol/openid-connect/auth/device",
		"registration_endpoint": "https://demo.oeth-uat.webcomm.com.tw/auth/realms/demo/clients-registrations/openid-connect",
		"userinfo_endpoint": "https://demo.oeth-uat.webcomm.com.tw/auth/realms/demo/protocol/openid-connect/userinfo",
		"pushed_authorization_request_endpoint": "https://demo.oeth-uat.webcomm.com.tw/auth/realms/demo/protocol/openid-connect/ext/par/request",
		"backchannel_authentication_endpoint": "https://demo.oeth-uat.webcomm.com.tw/auth/realms/demo/protocol/openid-connect/ext/ciba/auth"
	}
	*/
];
