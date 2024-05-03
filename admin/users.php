<?php

require_once '../inc/user.php';

include '../inc/header.php';

#admin check
if (isset($_SESSION['user_id'])){
    if ($_SESSION['user_role'] != 'admin'){
        header('Location: ../index.php');
        exit('Pro úpravu restaurací musíte být přihlášen(a) jako admin.');
    }
} else{
    header('Location: ../index.php');
    exit('Pro úpravu se nejprve musíte přihlásit jako admin.');
}

#posting changes
if (!empty($_POST)){
    if (isset($_POST['delete'])){
        $query = $db->prepare('SELECT * FROM users WHERE user_id=:id LIMIT 1');
        $query->execute([
            ':id' => $_POST['delete']
        ]);
        $user = $query->fetch(PDO::FETCH_ASSOC);

        $deleteQuery = $db->prepare('DELETE FROM users WHERE restaurant_id=?');
        $deleteQuery->execute([
            $_POST['delete']
        ]);

        echo '<div class="alert alert-info">Restaurace '.$user['name'].' byla smazána.</div>';
    }
    if (isset($_POST['edit'])){
        header('Location: users-edit.php?user_id='.$_POST['edit']);
    }
}

#loading data from DB
$usersQuery = $db->prepare('SELECT * from users WHERE role != "admin"');
$usersQuery->execute();
$users=$usersQuery->fetchAll(PDO::FETCH_ASSOC);

$adminsQuery = $db->prepare('SELECT * from users WHERE role = "admin"');
$adminsQuery->execute();
$admins=$adminsQuery->fetchAll(PDO::FETCH_ASSOC);



#creating table of admins and other users

#admins
echo '<div class="container py-4">';
if (!empty($admins)){
    echo '
    <h2>Administrátoři:</h2>
        <form method="post">
        <table class="bg-white table table-bordered">
        <thead>
            <tr>
                <th>Jméno</th>
                <th>E-mail</th>
                <th>Role</th>
                <th>Úpravy</th>
            </tr>
        </thead>
        <tbody>
    ';
    foreach ($admins as $admin){
        echo '
        <tr id="'.htmlspecialchars($admin['user_id']).'">
            <td>'.htmlspecialchars($admin['name']).'</td>
            <td>'.htmlspecialchars($admin['email']).'</td>
            <td>'.htmlspecialchars($admin['role']).'</td> 
            <td class="text-center" width="150px">
            <button class="btn btn-secondary" value="'.$admin['user_id'].'" name="edit" type="submit"><i class="fa-solid fa-pen-to-square"></i></button>';
        echo '
            </td>
        </tr>
        ';
    }
    echo '
      </tbody>
      </table>
      </form>
  ';
}else{
    echo '<div class="alert alert-info">Nebyli nalezeni žádní administrátoři.</div>';
}

#users
if (!empty($users)){
    echo '
    <h2>Uživatelé:</h2>
        <form method="post">
        <table class="bg-white table table-bordered">
        <thead>
            <tr>
                <th>Jméno</th>
                <th>E-mail</th>
                <th>Role</th>
                <th>OAuth</th>
                <th>Úpravy</th>
            </tr>
        </thead>
        <tbody>
    ';
    foreach ($users as $user){
        echo '
            <tr id="'.htmlspecialchars($user['user_id']).'">
                <td>'.htmlspecialchars($user['name']).'</td>
                <td>'.htmlspecialchars($user['email']).'</td>
                <td>'.htmlspecialchars($user['role']).'</td> 
                <td>';
        echo  (!empty($user['facebook_id'])) ? '<p> facebook </p>' : '' ;
        echo (!empty($user['google_id'])) ? '<p> google </p>' : '';
        echo '</td> 
                <td class="text-center" width="150px">
                <button class="btn btn-secondary" value="'.$user['user_id'].'" name="delete" type="submit"><i class="fas fa-trash"></i></button>
                <button class="btn btn-secondary" value="'.$user['user_id'].'" name="edit" type="submit"><i class="fa-solid fa-pen-to-square"></i></button>
                </td>
            </tr> 
        ';
    }
    echo '
      </tbody>
      </table>
      </form>
  ';
}else{
    echo '<div class="alert alert-info">Nebyli nalezeni žádní uživatelé.</div>';
}
echo '</div>';

include '../inc/footer.php';