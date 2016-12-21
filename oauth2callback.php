<?php
	require_once 'config.php';
	require_once 'google-api-php-client/vendor/autoload.php';

	session_start();

	$client = new Google_Client();
	$client->setAuthConfigFile('client.secret.json');
	$client->setRedirectUri($config->Client->URL . '/oauth2callback.php');
	$client->addScope('https://www.googleapis.com/auth/drive');
	$client->setAccessType('offline');

	if (isset($_GET['code'])) {
		$client->authenticate($_GET['code']);
		$_SESSION['access_token'] = $client->getAccessToken();
		$client->getAccessToken(['refreshToken']);
		header('Location: ' . $config->Client->URL, FILTER_SANITIZE_URL);
	} else {
		header('Location: ' . filter_var($client->createAuthUrl(), FILTER_SANITIZE_URL));
	}