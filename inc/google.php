<?php

// init configuration
$clientID = $_ENV('GL_ID');
$clientSecret = $_ENV('GL_SECRET');
$redirectUri = 'https://eso.vse.cz/~vanm30/sp/oauth/google-redirect.php';

// create Client Request to access Google API
$google = new Google_Client();
$google->setClientId($clientID);
$google->setClientSecret($clientSecret);
$google->setRedirectUri($redirectUri);
$google->addScope("email");
$google->addScope("profile");