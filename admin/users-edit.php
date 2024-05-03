<?php

require_once '../inc/user.php';

include '../inc/header.php';


if (isset($_SESSION['user_id'])){
    if ($_SESSION['user_role'] != 'admin'){
        header('Location: ../index.php');
        exit('Pro úpravu musíte být přihlášen(a) jako admin.');
    }
} else{
    header('Location: ../index.php');
    exit('Pro úpravu se nejprve musíte přihlásit jako admin.');
}

if(!empty($userId = $_GET['user_id'])){
    $userQuery = $db->prepare('SELECT * FROM users WHERE user_id=:id LIMIT 1');
    $userQuery->execute([
        ':id' => $userId
    ]);
    $user = $userQuery->fetch(PDO::FETCH_ASSOC);
} else{
    header('Location: ../index.php');
    exit('Nebyl nalezen uživatel.');
}

$errors=[];
if (!empty($_POST)){
#form check
#name check
    $name=trim(@$_POST['name']);
    if (empty($name)){
        $errors['name']='Musíte zadat jméno.';
    }
#email check
    $email = trim(@$_POST['email']);
    if (!@$_POST['email']){
        $errors['email']='Musíte zadat email.';
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Musíte zadat platnou e-mailovou adresu.';
    } else {
        $emailQuery = $db->prepare('SELECT email FROM users WHERE NOT user_id=:id LIMIT 1;');
        $emailQuery->execute([
            ':id' => $_POST['id']
        ]);
        $emails = $emailQuery->fetchAll(PDO::FETCH_COLUMN);
        if (in_array($_POST['email'],$emails)) {
            $errors['email'] = 'Uživatelský účet s touto e-mailovou adresou již existuje.';
        }
    }
#role check
    if (isset($_POST['role'])){
        $roleQuery=$db->prepare('SELECT DISTINCT role FROM users');
        $roleQuery->execute();
        $roleCheck = $roleQuery->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array($_POST['role'],$roleCheck)){
            $errors['role']='Zadaná role neexistuje.';
        }
    }

    if (empty($errors)){
        if (!isset($_POST['role'])){
            $updateQuery = $db->prepare('UPDATE users SET name=:name, email=:email WHERE user_id=:id LIMIT 1');
            $updateQuery->execute([
                ':name'=>$_POST['name'],
                ':email'=>$_POST['email'],
                ':id' => $_POST['id']
            ]);
        } else {
            $updateQuery = $db->prepare('UPDATE users SET name=:name, email=:email,role=:role WHERE user_id=:id LIMIT 1');
            $updateQuery->execute([
                ':name' => $_POST['name'],
                ':email' => $_POST['email'],
                ':role' => $_POST['role'],
                ':id' => $_POST['id']
            ]);
        }
        header('Location: users.php');
        exit();
    }
}
?>
<div class="container">
    <h2>Editace uživatele:</h2>

    <form class="w-50" method="post">
        <input name="id" type="hidden" value="<?php echo $userId ?>">
        <div class="form-group">
            <label for="name">Jméno:</label>
            <input class="form-control <?php echo (!empty($errors['name'])?'is-invalid':''); ?>" name="name" type="text" value="<?php echo (!empty($_POST)) ? $_POST['name'] : $user['name'] ?>"/>
            <?php
            if (!empty($errors['name'])){
                echo '<div class="invalid-feedback">'.$errors['name'].'</div>';
            }
            ?>
        </div>
        <div class="form-group">
            <label for="email">E-mail:</label>
            <input class="form-control <?php echo (!empty($errors['email'])?'is-invalid':''); ?>" name="email" type="email" value="<?php echo (!empty($_POST)) ? $_POST['email'] : $user['email'] ?>"/>
            <?php
            if (!empty($errors['email'])){
                echo '<div class="invalid-feedback">'.$errors['email'].'</div>';
            }
            ?>
        </div>
        <div class="form-group">
            <label for="role">Role:</label>
            <select <?php echo ($user['role'] == 'admin') ? 'disabled' : ''?> name="role" class=" form-control <?php echo (!empty($errors['role'])?'is-invalid':''); ?>">
                <option value="">--- Vyberte roli ---</option>
                <?php
                $rolesQuery = $db->prepare('SELECT DISTINCT role from users;');
                $rolesQuery->execute();
                $roles = $rolesQuery->fetchAll();
                foreach ($roles as $role){
                    echo '<option '.($role['role']==$user['role']?'selected="selected"':'').' value="'.$role['role'].'">'.htmlspecialchars($role['role']).'</option>';
                }
                ?>
            </select>
            <?php
            if (!empty($errors['role'])){
                echo '<div class="invalid-feedback">'.$errors['role'].'</div>';
            }
            ?>
        </div>
        <button class="btn btn-primary" type="submit">Odeslat</button>
    </form>
</div>

<?php

include '../inc/footer.php';