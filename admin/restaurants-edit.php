<?php

require_once '../inc/user.php';

include '../inc/header.php';


if(empty($restaurantId = $_GET['restaurant_id'])){
    header('Location: ../index.php');
    exit('Nebyla zadaná žádná restaurace.');
}

$restaurantQuery = $db->prepare('SELECT * FROM restaurants WHERE restaurant_id=:id LIMIT 1');
$restaurantQuery->execute([
    ':id' => $restaurantId
]);
$restaurant = $restaurantQuery->fetch(PDO::FETCH_ASSOC);

if (isset($_SESSION['user_id'])){
    if ($_SESSION['user_role'] != 'admin' && $restaurant['owner'] != $_SESSION['user_id']){
        header('Location: ../index.php');
        exit('Pro úpravu musíte být přihlášen(a) jako admin.');
    }
} else{
    header('Location: ../index.php');
    exit('Pro úpravu se nejprve musíte přihlásit jako admin.');
}


$errors=[];
if (!empty($_POST)){
#form check
#name check
    $name=trim(@$_POST['name']);
    if (empty($name)){
        $errors['name']='Musíte zadat název restaurace.';
    }
#about check
    $about=trim(@$_POST['about']);
    if (empty($about)){
        $errors['about']='Musíte zadat popis restaurace.';
    }
#owner check
    $ownerQuery=$db->prepare('SELECT * FROM users WHERE user_id=:id LIMIT 1;');
    $ownerQuery->execute([
        ':id'=>$_POST['owner']
    ]);
    $ownerCheck = $ownerQuery->fetch(PDO::FETCH_ASSOC);
    if ($ownerQuery->rowCount()==0){
        $errors['owner']='Tento uživatel neexistuje!';
    } else if ($ownerCheck['role'] != 'owner' && $ownerCheck['role'] != 'admin' ){
        $errors['owner']='Uživatel nemá roli vlastníka!';
    }
#address check
    #city
    $city=trim(@$_POST['city']);
    if (empty($city)){
        $errors['city']='Musíte zadat město, kde se restaurace nachází.';
    }
    #zip
    $zip=trim(@$_POST['zip']);
    if (empty($zip)){
        $errors['zip']='Musíte zadat PSČ města.';
    }
    #street
    $street=trim(@$_POST['street']);
    if (empty($street)){
        $errors['street']='Musíte zadat ulici, kde se restaurace nachází.';
    }
#file check
    #getting file info
    if($_FILES['image']['size'] == 0 && $_FILES['image']['error'] == 0) {
        $file = $_FILES['image'];
        $fileName = $_FILES['image']['name'];
        $fileTmpName = $_FILES['image']['tmp_name'];
        $fileSize = $_FILES['image']['size'];
        $fileError = $_FILES['image']['error'];
        $fileType = $_FILES['image']['type'];

        $fileExt = explode('.',$fileName);
        $fileActualExt = strtolower(end($fileExt));
        $allowed = array('jpg','jpeg','png','gif');

        if(!in_array($fileActualExt, $allowed)) {
            $errors['file'] = 'Pouze JPG, JPEG, PNG & GIF soubory jsou povoleny.';
        }
        if ($fileError !== 0){
            $errors['file'] = 'Došlo k chybě při nahrávání obrázku.';
        }
        if ($fileSize > 1000000) {
            $errors['file'] = 'Váš obrázek je příliš velký. ';
        }
    }

#checking items
    if (!empty($_POST['itemsName'])) {
        foreach (array_combine($_POST['itemsName'], $_POST['itemsPrice']) as $name => $price) {
            if (empty($name) || empty($price)) {
                $errors['items'] = 'Vyplňte všechna pole.';
            } else if (!is_numeric($price)) {
                $errors['items'] = 'Ceny musí být číslo.';
            }
        }
    }

    if (empty($errors)){
        $address = $street.', '.$city.', '.$zip;
        $updateQuery = $db->prepare('UPDATE restaurants SET name=:name, owner=:owner, about=:about, address=:address WHERE restaurant_id=:id LIMIT 1');
        $updateQuery->execute([
            ':name'=>$_POST['name'],
            ':owner'=>$_POST['owner'],
            ':about'=>$_POST['about'],
            ':address'=>$address,
            ':id' => $_POST['id']
        ]);

        #schedule upload
        $days = array('po','ut','st','ct','pa','so','ne');

        foreach ($days as $day) {
            if (!empty(@$_POST[$day.'Open']) && !empty(@$_POST[$day.'Closed'])){
                $testQuery = $db->prepare('SELECT * FROM schedules WHERE restaurant_id=:id AND day_of_week=:day');
                $testQuery->execute([
                    ':id'=>$_POST['id'],
                    ':day'=> $day
                ]);
                if ($testQuery->rowCount() > 0){
                    $scheduleQuery = $db->prepare('UPDATE schedules SET restaurant_id=:id,day_of_week=:day,time_open=:open,time_closed=:closed WHERE restaurant_id=:id AND day_of_week=:day');
                    $scheduleQuery->execute([
                        ':id'=> $_POST['id'],
                        ':day'=> $day,
                        ':open'=> $_POST[$day.'Open'],
                        ':closed'=> $_POST[$day.'Closed']

                    ]);
                } else {
                    $scheduleQuery = $db->prepare('INSERT INTO schedules (restaurant_id,day_of_week,time_open,time_closed) VALUES (:id,:day,:open,:closed)');
                    $scheduleQuery->execute([
                        ':id'=> $_POST['id'],
                        ':day'=> $day,
                        ':open'=> $_POST[$day.'Open'],
                        ':closed'=> $_POST[$day.'Closed']
                    ]);
                }

            }
        }
        #image upload
        if($_FILES['image']['size'] == 0 && $_FILES['image']['error'] == 0) {
            $imageQuery = $db->prepare('SELECT avatar,avatar_type FROM restaurants WHERE restaurant_id=:id LIMIT 1');
            $imageQuery->execute([
                    ':id'=>$restaurantId
            ]);
            $dbFileName = $imageQuery->fetch(PDO::FETCH_ASSOC);
            if(file_exists('../uploads/rest-images/' . $dbFileName['avatar'] . '.' . $dbFileName['avatar_type'])){
                unlink('../uploads/rest-images/' . $dbFileName['avatar'] . '.' . $dbFileName['avatar_type']);
            }

            $fileId = uniqid('',true);
            $fileNameNew = $fileId.".".$fileActualExt;
            $fileDest = '../uploads/rest-images/'.$fileNameNew;

            $uploadImageQuery = $db->prepare('UPDATE restaurants SET avatar=:avatar, avatar_type=:avatar_type WHERE restaurant_id=:id LIMIT 1');
            $uploadImageQuery->execute([
                ':id'=>$restaurantId,
                ':avatar'=>$fileId,
                ':avatar_type'=>$fileActualExt
            ]);
            move_uploaded_file($fileTmpName,$fileDest);
        }
        #items upload
        if (!empty(@$_POST['itemsName'])) {
            $i = 0;
            if (!empty($_POST['itemsId'])) {
                for ($i; $i <= count($_POST['itemsId']); $i++) {
                    $updateItemsQuery = $db->prepare('UPDATE items SET name=:name,price=:price WHERE item_id=:id ');
                    $updateItemsQuery->execute([
                        ':id' => @$_POST['itemsId'][$i],
                        ':name' => @$_POST['itemsName'][$i],
                        ':price' => @$_POST['itemsPrice'][$i]
                    ]);
                }
                $i--;
            }
            for ($i; $i < count($_POST['itemsName']); $i++) {
                $updateItemsQuery = $db->prepare('INSERT INTO items (name,price,restaurant_id) VALUES (:name,:price,:id) ');
                $updateItemsQuery->execute([
                    ':id' => $restaurant['restaurant_id'],
                    ':name' => @$_POST['itemsName'][$i],
                    ':price' => @$_POST['itemsPrice'][$i]
                ]);
            }
        }
    }
    if (empty($errors)){
        echo '<div class="alert alert-info">Úpravy odeslány.</div>';
    } else  echo '<div class="alert alert-danger">';
            foreach ($errors as $error){
                echo $error;
            }
            echo '</div>';
}
if (!empty($restaurant['address'])){
    $addressExp = explode(', ',$restaurant['address']);
    $restaurantStreet = $addressExp[0];
    $restaurantCity = $addressExp[1];
    $restaurantZip = $addressExp[2];
}
#getting items(food menu)
$itemsQuery = $db->prepare('SELECT * FROM items WHERE restaurant_id=:id');
$itemsQuery->execute([
    ':id'=>$restaurantId
]);
$items = $itemsQuery->fetchAll(PDO::FETCH_ASSOC);

$scheduleQuery = $db->prepare('SELECT * FROM schedules WHERE restaurant_id=:id');
$scheduleQuery->execute([
        ':id'=>$restaurantId
]);
$schedule = $scheduleQuery->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="container">
<h2>Editace restaurace:</h2>

<form method="post" enctype="multipart/form-data">
    <h3>Základní info</h3>
    <input name="id" type="hidden" value="<?php echo $restaurantId ?>">
    <div class="form-group">
        <label for="name">Název restaurace:</label>
        <input required class="form-control<?php echo (!empty($errors['name'])?'is-invalid':''); ?>" name="name" type="text" value="<?php echo $restaurant['name'] ?>"/>
        <?php
        if (!empty($errors['name'])){
            echo '<div class="invalid-feedback">'.$errors['name'].'</div>';
        }
        ?>
    </div>
    <div class="form-group">
        <label for="about">Stručný popis:</label>
        <textarea rows="5" required class="form-control" name="about"><?php echo htmlspecialchars($restaurant['about']) ?></textarea>
        <?php
        if (!empty($errors['about'])){
            echo '<div class="invalid-feedback">'.$errors['about'].'</div>';
        }
        ?>
    </div>
    <div class="form-group">
        <label for="owner">Vlastník:</label>
        <select required name="owner" class="form-control <?php echo (!empty($errors['owner'])?'is-invalid':''); ?>">
            <option value="">--- Vyberte vlastníka ---</option>
            <?php
            $ownersQuery = $db->prepare('SELECT * from users WHERE role="owner" OR  role="admin";');
            $ownersQuery->execute();
            $owners = $ownersQuery->fetchAll();
            foreach ($owners as $owner){
                echo '<option '.($owner['user_id']==$restaurant['owner']?'selected="selected"':'').' value="'.$owner['user_id'].'">'.htmlspecialchars($owner['name']).'</option>';
            }
            ?>
        </select>
        <?php
        if (!empty($errors['owner'])){
            echo '<div class="invalid-feedback">'.$errors['owner'].'</div>';
        }
        ?>
    </div>
    <h3>Adresa</h3>
    <div class="form-group">
        <label for="street">Ulice:</label>
        <input value="<?php echo (!empty($restaurantStreet)) ? $restaurantStreet : ''?>" class="form-control" required id="street" name="street" type="text" <?php echo (!empty($errors['street'])?'is-invalid':''); ?>/>
        <?php
        if (!empty($errors['street'])){
            echo '<div class="invalid-feedback">'.$errors['street'].'</div>';
        }
        ?>

        <label for="city">Město:</label>
        <input value="<?php echo (!empty($restaurantCity)) ? $restaurantCity : ''?>" class="form-control" required id="city" name="city" type="text" <?php echo (!empty($errors['city'])?'is-invalid':''); ?>/>
        <?php
        if (!empty($errors['city'])){
            echo '<div class="invalid-feedback">'.$errors['city'].'</div>';
        }
        ?>

        <label for="zip">PSČ:</label>
        <input value="<?php echo (!empty($restaurantZip)) ? $restaurantZip : ''?>" class="form-control" required id="zip" name="zip" type="text" <?php echo (!empty($errors['zip'])?'is-invalid':''); ?>/>
        <?php
        if (!empty($errors['zip'])){
            echo '<div class="invalid-feedback">'.$errors['zip'].'</div>';
        }
        ?>
    </div>
    <h3>Otevírací doba</h3>
        <table class="w-25 table table-borderless table-sm">
            <tr>
                <th></th>
                <th class="text-center">Od</th>
                <th class="text-center">Do</th>
            </tr>
            <tr>
                <th class="align-middle">Po</th>
                <td>
                    <input class="form-control" id="poOpen" name="poOpen" type="time">
                </td>
                <td>
                    <input class="form-control" id="poClosed" name="poClosed" type="time">
                </td>
            </tr>
            <tr>
                <th class="align-middle">Út</th>
                <td>
                    <input class="form-control " id="utOpen" name="utOpen" type="time">
                </td>
                <td>
                    <input class="form-control " id="utClosed" name="utClosed" type="time">
                </td>
            </tr>
            <tr>
                <th class="align-middle">St</th>
                <td>
                    <input class="form-control " id="stOpen" name="stOpen" type="time">
                </td>
                <td>
                    <input class="form-control " id="stClosed" name="stClosed" type="time">
                </td>
            </tr>
            <tr>
                <th class="align-middle">Čt</th>
                <td>
                    <input class="form-control " id="ctOpen" name="ctOpen" type="time">
                </td>
                <td>
                    <input class="form-control " id="ctClosed" name="ctClosed" type="time">
                </td>
            </tr>
            <tr>
                <th class="align-middle">Pá</th>
                <td>
                    <input class="form-control " id="paOpen" name="paOpen" type="time">
                </td>
                <td>
                    <input class="form-control " id="paClosed" name="paClosed" type="time">
                </td>
            </tr>
            <tr>
                <th class="align-middle">So</th>
                <td>
                    <input class="form-control " id="soOpen" name="soOpen" type="time">
                </td>
                <td>
                    <input class="form-control " id="soClosed" name="soClosed" type="time">
                </td>
            </tr>
            <tr>
                <th class="align-middle">Ne</th>
                <td>
                    <input class="form-control " id="neOpen" name="neOpen" type="time">
                </td>
                <td>
                    <input class="form-control " id="neClosed" name="neClosed" type="time">
                </td>
            </tr>
        </table>
    <h3>Menu</h3>
    <div class="form-group">
        <div class="container">
            <div class="row clearfix">
                <div class="col-md-12 column">
                    <table class="w-50 table" id="tab_logic">
                        <thead>
                           <th>Jídlo</th>
                           <th>Cena (Kč)</th>
                        </thead>
                        <tbody>
                        <?php
                        $r = 0;
                        foreach ($items as $item){
                            echo '
                             <tr>
                             <input name="itemsId[]" hidden value="' . $item['item_id'] .'">
                                <td><input class="form-control" name="itemsName[]" value="' . $item['name'] .'"></td>
                                <td><input class="form-control" name="itemsPrice[]" value="' . $item['price'] .'"></td>
                            </tr>
                           ';
                            $r++;
                        }
                        echo '<tr id="addr0"></tr>';
                        ?>
                        </tbody>
                    </table>
                    <?php echo (!empty($errors['items'])) ? '<div class="alert alert-danger">' . $errors['items'] . '</div>' : ''?>
                    <button type="button" id="add_row" class="btn btn-primary">Přidat řádek</button>
                </div>
            </div>
        </div>
    </div>
    <h3>Titulní obrázek:</h3>
    <div class="form-group">
        <input id="image" name="image" type="file"/>
    </div>
    <button class="btn btn-primary" type="submit">Odeslat</button>
</form>
</div>
<?php
include '../inc/footer.php';
?>

<script>
    $(document).ready(function() {
        var i = 0;
        $("#add_row").click(function() {
            $('#addr' + i).html('<td><input class="form-control" name="itemsName[]"></td> <td><input class="form-control" name="itemsPrice[]"></td>');
            $('#tab_logic').append('<tr id="addr' + (i + 1) + '"></tr>');
            i++;
        });
    });
</script>

