<?php
require_once '../inc/user.php';

require_once '../vendor/autoload.php';

require_once '../inc/google.php';

if (isset($_GET['code'])) {
    $token = $google->fetchAccessTokenWithAuthCode($_GET['code']);
    $google->setAccessToken($token['access_token']);

    $google_oauth = new Google_Service_Oauth2($google);
    $google_account_info = $google_oauth->userinfo->get();

    $email =  $google_account_info->email;
    $name =  $google_account_info->name;
    $id = $google_account_info->id;

    echo $id;

    $query = $db->prepare('SELECT * FROM users WHERE google_id=:googleId LIMIT 1;');
    $query->execute([
        ':googleId'=>$id
    ]);

    if  ($query->rowCount()>0){
        $user = $query->fetch(PDO::FETCH_ASSOC);
    } else {
        $query = $db->prepare('SELECT * FROM users WHERE email=:email LIMIT 1;');
        $query->execute([
            ':email' => $email
        ]);

        if ($query->rowCount() > 0) {
            $user = $query->fetch(PDO::FETCH_ASSOC);

            $updateQuery = $db->prepare('UPDATE users SET google_id=:googleId WHERE user_id=:id LIMIT 1;');
            $updateQuery->execute([
                ':googleId' => $id,
                ':id' => $user['user_id']
            ]);
        } else {
            $insertQuery = $db->prepare('INSERT INTO users (name, email, google_id) VALUES (:name, :email, :googleId);');
            $insertQuery->execute([
                ':name' => $name,
                ':email' => $email,
                ':googleId' => $id
            ]);

            $query = $db->prepare('SELECT * FROM users WHERE google_id=:googleId LIMIT 1;');
            $query->execute([
                ':googleId' => $id
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

} else {
    echo "<a href='".$google->createAuthUrl()."'>Google Login</a>";
}
