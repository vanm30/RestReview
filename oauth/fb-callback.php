<?php

require_once '../inc/user.php';

require_once '../inc/facebook.php';


$fbHelper = $fb->getRedirectLoginHelper();

try {
    $accessToken = $fbHelper->getAccessToken();
} catch(Exception $e) {
    echo 'Přihlášení pomocí Facebooku selhalo. Chyba: ' . $e->getMessage();
    exit();
}

if (!$accessToken){
    exit('Přihlášení pomocí Facebooku se nezdařilo. Zkuste to znovu.');
}

$oAuth2Client = $fb->getOAuth2Client();

$accessTokenMetadata = $oAuth2Client->debugToken($accessToken);

$fbUserId = $accessTokenMetadata->getUserId();

$response = $fb->get('/me?fields=name,email',$accessToken);
$graphUser = $response->getGraphUser();

$fbUserEmail = $graphUser->getEmail();
$fbUserName = $graphUser->getName();


$query = $db->prepare('SELECT * FROM users WHERE facebook_id=:facebookId LIMIT 1;');
$query->execute([
    ':facebookId'=>$fbUserId
]);

if  ($query->rowCount()>0){
    $user = $query->fetch(PDO::FETCH_ASSOC);
} else {
    $query = $db->prepare('SELECT * FROM users WHERE email=:email LIMIT 1;');
    $query->execute([
        ':email' => $fbUserEmail
    ]);


    if ($query->rowCount() > 0) {
        $user = $query->fetch(PDO::FETCH_ASSOC);

        $updateQuery = $db->prepare('UPDATE users SET facebook_id=:facebookId WHERE user_id=:id LIMIT 1;');
        $updateQuery->execute([
            ':facebookId' => $fbUserId,
            ':id' => $user['user_id']
        ]);
    } else {
        $insertQuery = $db->prepare('INSERT INTO users (name, email, facebook_id) VALUES (:name, :email, :facebookId);');
        $insertQuery->execute([
            ':name' => $fbUserName,
            ':email' => $fbUserEmail,
            ':facebookId' => $fbUserId
        ]);

        $query = $db->prepare('SELECT * FROM users WHERE facebook_id=:facebookId LIMIT 1;');
        $query->execute([
            ':facebookId' => $fbUserId
        ]);
        $user = $query->fetch(PDO::FETCH_ASSOC);
    }
}


if (!empty($user)){
    $_SESSION['user_id']=$user['user_id'];
    $_SESSION['user_name']=$user['name'];
    $_SESSION['user_role']=$user['role'];
}

header('Location: ../index.php');


