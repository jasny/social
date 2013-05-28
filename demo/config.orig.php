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
$cfg->facebook = (object)array(
    'appid' => '',
    'secret' => '',
    'access_token' => ''
);

/*
 * Twitter OAuth settings
 * 
 * 1.) Register your application at https://dev.twitter.com/apps/new
 * 2.) From the 'Details' tab of the application page, copy/paste the 'Consumer key' and 'Consumer secret' settings
 * 3.) (Optional, only for unit tests)
 *  a.) Click on 'create my access tokens'
 *  b.) Copy/paste 'Access token' and 'Access token secret'
 */
$cfg->twitter = (object)array(
    'consumer_key' => '',
    'consumer_secret' => '',
    'access_token' => '',
    'access_secret' => '',
);
