<?php

$cfg = (object)array();

/*
 * Facebook OAuth settings
 * 
 * 1.) Register your application at https://developers.facebook.com/apps
 * 2.) From the 'Settings' sections of the application page, copy/pase 'App ID/API Key' and 'App Secret'
 * 3.) (Optional, only for unit tests)
 *  a.) Visit https://developers.facebook.com/tools/explorer/
 *  b.) Select your Application (right top corner)
 *  c.) Click on 'Get Access token' and leave default permissions
 *  d.) Copy/paste the access token
 */
$cfg->facebook = (object)[
    'appid' => '',
    'secret' => '',
    'access_token' => ''
];

/*
 * Twitter OAuth settings
 * 
 * 1.) Register your application at https://dev.twitter.com/apps/new
 * 2.) From the 'Details' tab of the application page, copy/paste the 'Consumer key' and 'Consumer secret' settings
 * 3.) (Optional, only for unit tests)
 *  a.) Click on 'create my access tokens'
 *  b.) Copy/paste 'Access token' and 'Access token secret'
 */
$cfg->twitter = (object)[
    'consumer_key' => '',
    'consumer_secret' => '',
    'access_token' => '',
    'access_secret' => '',
];

/*
 * Google OAuth settings and API key
 * 
 * 1.) Register your application at https://code.google.com/apis/console/
 * 2.) Register the services to test on the 'Services' page.
 * 3.) From the 'API Access' page, copy/paste the 'Client ID' and 'Client Secret' settings
 * 4.) From the 'API Access' page, copy/paste the 'API key'
 */
$cfg->google = (object)[
    'client_id' => '',
    'client_secret' => '',
    'api_key' => '',
];

/* 
 * LinkedIn OAuth settings and API key
 * 
 * 1.) Register your application at https://www.linkedin.com/secure/developer
 * 2.) From the next screen, copy/paste the 'API key' as 'client_id' and 'Secret key' as 'client_secret'
 * 3.) Also copy/paste the 'User token' as 'access_token' and 'User secret' as 'access_secret' 
 */
$cfg->linkedin = (object)[
    'client_id' => '',
    'client_secret' => '',
    'access_token' => '',
    'access_secret' => ''
];

/*
 * SoundCloud OAuth settings
 * 
 * 1.) Register your application at http://soundcloud.com/you/apps
 * 2.) Copy/paste the 'Client ID' and 'Client Secret' settings
 */
$cfg->soundcloud = (object)[
    'client_id' => '',
    'client_secret' => '',
];

/* 
 * Postcode API key
 * 
 * 1.) Register you application at http://www.postcodeapi.nu/#request
 * 2.) Copy/paste your received API key
 */
$cfg->postcode_api = (object)[
    'api_key' => ''
];
