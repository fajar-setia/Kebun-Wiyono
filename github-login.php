<?php
session_start();
require_once 'vendor/autoload.php';

$provider = new League\OAuth2\Client\Provider\Github([
    'clientId'     => 'Ov23liDLVYMZu75l7hlB',  // ganti dengan clientId kamu
    'clientSecret' => 'aa02e2e595b47bed19a5a006bb6c32599ee085df', // ganti dengan clientSecret kamu
    'redirectUri'  => 'https://kebunkita.shop/github-callback.php',
]);

$authorizationUrl = $provider->getAuthorizationUrl();
$_SESSION['oauth2state'] = $provider->getState();

header('Location: ' . $authorizationUrl);
exit;
