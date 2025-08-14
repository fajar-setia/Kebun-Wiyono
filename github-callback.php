<?php
require_once 'vendor/autoload.php';
session_start();

$provider = new League\OAuth2\Client\Provider\Github([
    'clientId'     => 'Ov23liDLVYMZu75l7hlB',
    'clientSecret' => 'aa02e2e595b47bed19a5a006bb6c32599ee085df',
    'redirectUri'  => 'https://kebunkita.shop/github-callback.php',
]);

// Validasi state
if (!isset($_GET['state']) || $_GET['state'] !== $_SESSION['oauth2state']) {
    unset($_SESSION['oauth2state']);
    exit('Invalid OAuth state.');
}

if (isset($_GET['code'])) {
    try {
        $token = $provider->getAccessToken('authorization_code', [
            'code' => $_GET['code']
        ]);

        $user = $provider->getResourceOwner($token);
        $githubData = $user->toArray();

        $_SESSION['user_name'] = $githubData['name'] ?? $githubData['login'];
        $_SESSION['user_email'] = $githubData['email'] ?? 'email@unknown.com';

        // ✅ Redirect ke halaman user
        header("Location: user.php");
        exit();

    } catch (Exception $e) {
        echo "GitHub Login Failed: " . $e->getMessage();
        exit();
    }
} else {
    echo "Code not found in callback URL";
}
