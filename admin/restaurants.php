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

$errors=[];
if (!empty($_POST)){
    if (isset($_POST['delete'])){
        $query = $db->prepare('SELECT * FROM restaurants WHERE restaurant_id=:id LIMIT 1');
        $query->execute([
            ':id' => $_POST['delete']
        ]);
        $restaurant = $query->fetch(PDO::FETCH_ASSOC);

        if (!empty($restaurant)) {
            $deleteQuery = $db->prepare('DELETE FROM restaurants WHERE restaurant_id=?');
            $deleteQuery->execute([
                $_POST['delete']
            ]);
            echo '<div class="alert alert-info">Restaurace ' . htmlspecialchars($restaurant['name']) . ' byla smazána.</div>';
        } else echo '<div class="alert alert-info">Restaurace neexistuje v databázi.</div>';
    }
    if (isset($_POST['edit'])){
        header('Location: restaurants-edit.php?restaurant_id='.$_POST['edit']);
    }
}
    if (!empty(@$_GET['sort'])){
        $sort = $_GET['sort'];
        $restaurantsQuery = $db->prepare('SELECT *,users.name as user_name,restaurants.name as restaurant_name FROM restaurants LEFT JOIN users ON restaurants.owner=users.user_id ORDER BY :sort');
        $restaurantsQuery->execute([':sort' => $sort]);
        $restaurants=$restaurantsQuery->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $restaurantsQuery = $db->prepare('SELECT *,users.name as user_name,restaurants.name as restaurant_name FROM restaurants LEFT JOIN users ON restaurants.owner=users.user_id');
        $restaurantsQuery->execute();
        $restaurants=$restaurantsQuery->fetchAll(PDO::FETCH_ASSOC);
    }


if (!empty($restaurants)){
    echo '
    <div class="container">
    <h2>Restaurace:</h2>';

    echo '<form method="get" id="sortForm">
          <label for="sort">Řadit podle:</label>
          <select name="sort" id="restaurent" onchange="document.getElementById(\'sortForm\').submit();">
            <option value="">--nerozhoduje--</option>';

            echo '<option value="restaurants.name"';
            if (@$_GET['sort'] == 'restaurants.name'){
                echo ' selected="selected" ';
            }
            echo '>Jméno restaurace</option>';

            echo '<option value="users.name"';
            if (@$_GET['sort'] == 'users.name'){
                echo ' selected="selected" ';
            }
            echo '>Vlastník</option>';

    echo '  </select>
          <input type="submit" value="OK" class="d-none" />
        </form>';


     echo '<form method="post">
        <table class="table table-bordered bg-white">
        <thead>
            <tr>
                <th>Id</th>
                <th>Jméno</th>
                <th>Vlastník</th>
                <th>Úpravy</th>
            </tr>
        </thead>
        <tbody>
    ';
    foreach ($restaurants as $restaurant){
        echo '
        <tr id="'.htmlspecialchars($restaurant['restaurant_id']).'">
            <td>'.htmlspecialchars($restaurant['restaurant_id']).'</td>
            <td>'.htmlspecialchars($restaurant['restaurant_name']).'</td>
            <td>'.htmlspecialchars($restaurant['user_name']).'</td> 
            <td class="text-center" width="150px">
            <button class="btn btn-secondary" value="'.$restaurant['restaurant_id'].'" name="delete" type="submit"><i class="fas fa-trash"></i></button>
            <button class="btn btn-secondary" value="'.$restaurant['restaurant_id'].'" name="edit" type="submit"><i class="fa-solid fa-pen-to-square"></i></button>
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
    echo '<div class="alert alert-info">Nebyly nalezeny žádné restaurace.</div>';
}


include '../inc/footer.php';