<?php

require_once '../include.php';

function getConnection($service)
{
    global $cfg;

    switch ($service) {
        case 'facebook': return new Social\Facebook\Connection($cfg->facebook->appid, $cfg->facebook->secret, $_SESSION);
        case 'twitter':  return new Social\Twitter\Connection($cfg->twitter->consumer_key, $cfg->twitter->consumer_secret, $_SESSION);
        case 'linkedin': return new Social\LinkedIn\Connection($cfg->linkedin->client_id, $cfg->linkedin->client_secret, $_SESSION);
        case 'google':   return new Social\Google\Connection($cfg->google->api_key, $cfg->google->client_id, $cfg->google->client_secret, $_SESSION);
    }
    
    throw new Exception("Unknown service '$service'");
}

$conn = getConnection($_GET['service']);
$conn->auth($cfg->{$_GET['service']}->scope);

$me = $conn->me();
$user = (object)[];

// Add (missing) profile information
$me = $conn->me();

$user->name = $me->getName();
$user->last_name = $me->getLastName();
$user->gender = $me->getGender();
$user->date_of_birth = $me->getDateOfBirth();
$user->company = $me->getCompany();
$user->email = $me->getEmail();
$user->website = $me->getWebsite();
$user->description = $me->getDescription();

var_dump($user);
