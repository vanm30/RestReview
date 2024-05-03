<?php
require_once '../inc/user.php';

include '../inc/header.php';

if (!isset($_SESSION['user_id'])){
    header('Location: ../index.php');
    exit();
}
$errors = [];
if (isset($_POST['add'])){

    $name=trim(@$_POST['name']);
    if (empty($name)){
        $errors['name']='Musíte zadat název restaurace.';
    }

    $about=trim(@$_POST['about']);
    if (empty($about)){
        $errors['about']='Musíte zadat popis restaurace.';
    }

    $city=trim(@$_POST['city']);
    if (empty($city)){
        $errors['city']='Musíte zadat město, kde se restaurace nachází.';
    }

    $zip=trim(@$_POST['zip']);
    if (empty($zip)){
        $errors['zip']='Musíte zadat PSČ města.';
    }
    if (!is_numeric($zip)){
        $errors['zip']='Zadejte správné PSČ. PSČ mohou být jen čísla.';
    }
 
    $street=trim(@$_POST['street']);
    if (empty($street)){
        $errors['street']='Musíte zadat ulici, kde se restaurace nachází.';
    }

    if(isset($_FILES['image'])) {
        $file = $_FILES['image'];
        $fileName = $_FILES['image']['name'];
        $fileTmpName = $_FILES['image']['tmp_name'];
        $fileSize = $_FILES['image']['size'];
        $fileError = $_FILES['image']['error'];
        $fileType = $_FILES['image']['type'];
        $uploadOk = 0;

        $fileExt = explode('.',$fileName);
        $fileActualExt = strtolower(end($fileExt));
        $allowed = array('jpg','jpeg','png','gif');

        if(!in_array($fileActualExt, $allowed)) {
            $errors['file'] =  'Pouze JPG, JPEG, PNG & GIF soubory jsou povoleny.';
        }
        if ($fileError !== 0){
            $errors['file'] =  'Došlo k chybě při nahrávání obrázku.';
        }
        if ($fileSize > 1000000) {
            $errors['file'] =  'Váš obrázek je příliš velký.';
        }
    } else $errors['file'] = 'Obrázek nebyl vybrán.';

    if (empty($errors)){
        $address = $_POST['street'].', '.$_POST['city'].', '.$_POST['zip'];

        $fileId = uniqid('',true);
        $fileNameNew = $fileId.".".$fileActualExt;
        $fileDest = '../uploads/rest-images/'.$fileNameNew;
        move_uploaded_file($fileTmpName,$fileDest);

        $addQuery = $db->prepare('INSERT INTO restaurants (name,owner,about,address,avatar,avatar_type) VALUES (:name,:owner,:about,:address,:avatar,:avatar_type)');
        $addQuery->execute([
            ':name' => $_POST['name'],
            ':owner' => $_SESSION['user_id'],
            ':about' => $_POST['about'],
            ':address' => $address,
            ':avatar'=>$fileId,
            ':avatar_type'=>$fileActualExt
        ]);
        $restaurant_id = $db->lastInsertId();
        $days = array('po','ut','st','ct','pa','so','ne');
        foreach ($days as $day) {
            if (!empty(@$_POST[$day.'Open']) && !empty(@$_POST[$day.'Closed'])){
                $testQuery = $db->prepare('SELECT * FROM schedules WHERE restaurant_id=:id AND day_of_week=:day');
                $testQuery->execute([
                    ':id'=>$restaurant_id,
                    ':day'=> $day
                ]);
                if ($testQuery->rowCount() > 0){
                    $scheduleQuery = $db->prepare('UPDATE schedules SET restaurant_id=:id,day_of_week=:day,time_open=:open,time_closed=:closed WHERE restaurant_id=:id AND day_of_week=:day');
                    $scheduleQuery->execute([
                        ':id'=> $restaurant_id,
                        ':day'=> $day,
                        ':open'=> $_POST[$day.'Open'],
                        ':closed'=> $_POST[$day.'Closed']

                    ]);
                } else {
                    $scheduleQuery = $db->prepare('INSERT INTO schedules (restaurant_id,day_of_week,time_open,time_closed) VALUES (:id,:day,:open,:closed)');
                    $scheduleQuery->execute([
                        ':id'=> $restaurant_id,
                        ':day'=> $day,
                        ':open'=> $_POST[$day.'Open'],
                        ':closed'=> $_POST[$day.'Closed']
                    ]);
                }

            }
        }

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
                    ':id' => $restaurant_id,
                    ':name' => @$_POST['itemsName'][$i],
                    ':price' => @$_POST['itemsPrice'][$i]
                ]);
            }
        }
        if ($_SESSION['user_role'] != 'admin'){
            $updateUserQuery = $db->prepare('UPDATE users SET role=:role WHERE user_id=:id');
            $updateUserQuery->execute([
                ':id' => $_SESSION['user_id'],
                ':role' => 'owner'
            ]);
        }

        echo '<div class="alert alert-info">Restaurace '.$_POST['name'].' byla přidána.</div>';
    }
        echo '<div class="alert alert-danger">Vyskytla se chyba. Prosím, zkontrolujte formulář..</div>';
}
?>

<div class="container">
    <h2>Přidání restaurace:</h2>

    <form method="post" enctype="multipart/form-data">
        <h3>Základní info</h3>
        <div class="form-group">
            <label for="name">Název restaurace:</label>
            <input value="<?php echo (!empty($_POST['name'])) ? $_POST['name'] : '' ?>" required class="form-control<?php echo (!empty($errors['name'])?'is-invalid':''); ?>" name="name" type="text"/>
            <?php
            if (!empty($errors['name'])){
                echo '<div class="invalid-feedback">'.$errors['name'].'</div>';
            }
            ?>
        </div>
        <div class="form-group">
            <label for="about">Stručný popis:</label>
            <textarea rows="5" required class="form-control" name="about"><?php echo (!empty($_POST['about'])) ? $_POST['about'] : '' ?></textarea>
            <?php
            if (!empty($errors['about'])){
                echo '<div class="invalid-feedback">'.$errors['about'].'</div>';
            }
            ?>
        </div>
        <h3>Adresa</h3>
        <div class="form-group">
            <label for="street">Ulice:</label>
            <input value="<?php echo (!empty($_POST['street'])) ? $_POST['street'] : '' ?>" class="form-control" required id="street" name="street" type="text" <?php echo (!empty($errors['street'])?'is-invalid':''); ?>/>
            <?php
            if (!empty($errors['street'])){
                echo '<div class="text-danger">'.$errors['street'].'</div>';
            }
            ?>

            <label for="city">Město:</label>
            <input value="<?php echo (!empty($_POST['city'])) ? $_POST['city'] : '' ?>" class="form-control" required id="city" name="city" type="text" <?php echo (!empty($errors['city'])?'is-invalid':''); ?>/>
            <?php
            if (!empty($errors['city'])){
                echo '<div class="text-danger">'.$errors['city'].'</div>';
            }
            ?>

            <label for="zip">PSČ:</label>
            <input value="<?php echo (!empty($_POST['zip'])) ? $_POST['zip'] : '' ?>" class="form-control" required id="zip" name="zip" type="text" <?php echo (!empty($errors['zip'])?'is-invalid':''); ?>/>
            <?php
            if (!empty($errors['zip'])){
                echo '<div class="text-danger">'.$errors['zip'].'</div>';
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
                            <tr id="addr0"></tr>
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
            <input required id="image" name="image" type="file"/>
        </div>
        <button name="add" class="btn btn-primary" type="submit">Odeslat</button>
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
