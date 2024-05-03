<?php

$fb = new Facebook\Facebook([
    'app_id' => $_ENV('FB_APP_ID'),
    'app_secret' => $_ENV('FB_SECRET'),
    'default_graph_version' => 'v4.0',
]);