<?php

require_once '../inc/user.php';

include '../inc/header.php';

if(empty(@$_GET['restaurant_id'])){
    header('Location: ../index.php');
    exit();
}


if (!isset($_SESSION['user_id'])){
    header('Location: ../index.php');
    exit();
}
$rest_id = $_GET['restaurant_id'];
$user_id = $_SESSION['user_id'];

$restQuery = $db->prepare('SELECT restaurants.*, users.name AS user_name, users.email
                            FROM restaurants JOIN users ON restaurants.owner=users.user_id 
                            WHERE restaurant_id=:id LIMIT 1');
$restQuery->execute([
    ':id'=>$rest_id
]);


if ($restQuery->rowCount() == 0){
    header('Location: ../index.php');
    exit();
}

$restaurant = $restQuery->fetch(PDO::FETCH_ASSOC);
$img = 'uploads/rest-images/' . $restaurant['avatar'] . '.' . $restaurant['avatar_type'];


$scheduleQuery = $db->prepare('SELECT * FROM schedules WHERE restaurant_id=:id');
$scheduleQuery->execute([
    ':id'=> $rest_id
]);
$schedule = $scheduleQuery->fetchAll();


$itemsQuery = $db->prepare('SELECT * FROM items WHERE restaurant_id=:id');
$itemsQuery->execute([
    ':id'=> $rest_id
]);
$items = $itemsQuery->fetchAll(PDO::FETCH_ASSOC);

if (isset($_POST['submit'])){

    $ratingsQuery = $db->prepare('SELECT * FROM ratings WHERE restaurant_id=:rest_id AND user_id=:user_id');
    $ratingsQuery->execute([
            ':rest_id' => $rest_id,
            ':user_id' => $user_id
    ]);
    if ($ratingsQuery->rowCount() != 0){

        $addQuery = $db->prepare('UPDATE ratings SET food_rating=:food,service_rating=:service,price_rating=:price WHERE restaurant_id=:id AND user_id=:user');
        $addQuery->execute([
            ':id' => $rest_id,
            ':user' => $user_id,
            ':food' => $_POST['food'],
            ':service' => $_POST['service'],
            ':price' => $_POST['price']
        ]);
    } else {

        $addQuery = $db->prepare('INSERT INTO ratings (restaurant_id,user_id,food_rating,service_rating,price_rating) VALUES (:id,:user,:food,:service,:price)');
        $addQuery->execute([
                ':id' => $rest_id,
                ':user' => $user_id,
                ':food' => $_POST['food'],
                ':service' => $_POST['service'],
                ':price' => $_POST['price']
        ]);
    }


    if (!empty($_POST['text'])) {
        $reviewsQuery = $db->prepare('SELECT * FROM reviews WHERE restaurant_id=:rest_id AND user_id=:user_id');
        $reviewsQuery->execute([
            ':rest_id' => $rest_id,
            ':user_id' => $user_id
        ]);
        if ($reviewsQuery->rowCount() != 0) {

            $addQuery = $db->prepare('UPDATE reviews SET review=:review WHERE restaurant_id=:id AND user_id=:user');
            $addQuery->execute([
                ':id' => $rest_id,
                ':user' => $user_id,
                ':review' => $_POST['text']
            ]);
        } else {

            $addQuery = $db->prepare('INSERT INTO reviews (restaurant_id,user_id,review) VALUES (:id,:user,:review)');
            $addQuery->execute([
                ':id' => $rest_id,
                ':user' => $user_id,
                ':review' => $_POST['text'],
            ]);
        }
    }

    foreach ($items as $item) {
        if (is_numeric($_POST[$item['item_id']])){
        $ratingsQuery = $db->prepare('SELECT * FROM items_ratings WHERE item_id=:item_id AND user_id=:user_id');
        $ratingsQuery->execute([
            ':item_id' => $item['item_id'],
            ':user_id' => $user_id
        ]);
        if ($ratingsQuery->rowCount() != 0){

            $ratingsQuery = $db->prepare('UPDATE items_ratings SET rating=:rating WHERE item_id=:item_id AND user_id=:user_id');
            $ratingsQuery->execute([
                ':rating' => $_POST[$item['item_id']],
                ':item_id' => $item['item_id'],
                ':user_id' => $user_id
            ]);
        } else {

            $ratingsQuery = $db->prepare('INSERT INTO items_ratings (item_id,user_id,rating) VALUES (:item_id,:user_id,:rating) ');
            $ratingsQuery->execute([
                ':item_id' => $item['item_id'],
                ':user_id' => $user_id,
                ':rating' => $_POST[$item['item_id']]
            ]);
        }
        }
    }
    echo '<div class="alert alert-info">Hodnocení odesláno.</div>';
}

$ratingsQuery = $db->prepare('SELECT * FROM ratings WHERE restaurant_id=:rest_id AND user_id=:user_id');
$ratingsQuery->execute([
    ':rest_id' => $rest_id,
    ':user_id' => $user_id
]);
$ratings = $ratingsQuery->fetch(PDO::FETCH_ASSOC);

$reviewsQuery = $db->prepare('SELECT * FROM reviews WHERE restaurant_id=:rest_id AND user_id=:user_id');
$reviewsQuery->execute([
    ':rest_id' => $rest_id,
    ':user_id' => $user_id
]);
$review = $reviewsQuery->fetch(PDO::FETCH_ASSOC);


$ratingsQuery = $db->prepare('SELECT * FROM items_ratings RIGHT JOIN items ON items.item_id=items_ratings.item_id WHERE user_id=:user_id and restaurant_id =:rest_id');
$ratingsQuery->execute([
    ':rest_id' => $rest_id,
    ':user_id' => $user_id
]);
$itemRating = $ratingsQuery->fetchAll();
?>

    <div class="bg-white" style="border: 1px solid #E0E0E0">
        <div class="container">
            <div class="row">
                <div class="col-6 d-flex align-items-center ">
                    <div>
                        <h1 class="py-3"><a href="restaurant/home.php?rest_id=<?php echo $rest_id ?>"><?php echo htmlspecialchars($restaurant['name']) ?></a></h1>
                       <p><?php echo htmlspecialchars($restaurant['about']) ?></p>
                    </div>
                </div>
                <div class="col-3 d-flex align-items-center ">
                    <img class="img-fluid" src="<?php echo $img ?> " alt="restaurant_img">
                </div>
                <div class="col-3">
                    <table class="table table-sm">
                        <th class="text-center" colspan="2">Otevírací doba</th>
                        <?php
                        $days = array("Po","Út","St","Čt","Pá","So","Ne");
                        for ($i = 0; $i <= 6; $i++){
                            echo '<tr>';
                            echo '<th>' . $days[$i] .'</th>';
                            echo '<td>';
                            echo  (!empty($schedule[$i])) ? date('H:i', strtotime($schedule[$i]['time_open'])) . ' - ' . date('H:i', strtotime($schedule[$i]['time_closed'])) : 'neuvedeno';
                            echo '</td>';
                            echo '</tr>';
                        }
                        ?>
                    </table>
                </div>
            </div>
        </div>
    </div>

<div class="container">
    <form method="post">
    <div class="container bg-white my-4 px-4 py-4">
        <p class="h3">Hodnocení restaurace</p>
        <p>Hodnotit můžete na stupnici od 0 do 5.
           Hodnotíte dle skóre, to znamená, že 0 je nejhorší a 5 je nejlepší.<br/>
           Pro validní hodnocení, prosím ohodnoťte všechny kategorie.
        </p>
        <div class="w-50">
            <div class="form-group">
                <label class="h5" for="food">Jídlo</label>
                <input value="<?php echo $ratings['food_rating'] ?>" class="form-control" type="range" max="5" min="0" name="food">
            </div>
            <div class="form-group">
                <label class="h5" for="service">Obsluha</label>
                <input value="<?php echo $ratings['service_rating'] ?>" class="form-control" type="range" max="5" min="0" name="service">
            </div>
            <div class="form-group">
                <label class="h5" for="price">Cena</label>
                <input value="<?php echo $ratings['price_rating'] ?>" class="form-control" type="range" max="5" min="0" name="price">
            </div>
        </div>
    </div>

    <div class="container bg-white my-4 px-4 py-4">
        <p class="h3">Hodnocení jídel</p>
        <p>Hodnotit můžete na stupnici od 0 do 5.
            Hodnotíte dle skóre, to znamená, že 0 je nejhorší a 5 je nejlepší.<br/>
            Můžete ohodnotit jen některá Vámi zvolená jídla. Pokud nechcete jídlo hodnotit, pole nechte prázdné.
        </p>
        <div class="w-50">
            <table class="table">
                <thead class="table-primary">
                    <th>Jídlo</th>
                    <th>Cena</th>
                    <th width="25px">Hodnocení</th>
                </thead>
                <tbody>
                    <?php
                    foreach ($items as $item) {
                        $ratingQuery = $db->prepare('SELECT rating FROM items_ratings WHERE item_id=:item_id AND user_id=:user_id');
                        $ratingQuery->execute([
                                ':item_id'=> $item['item_id'],
                                ':user_id'=>$user_id
                        ]);
                        $rating = $ratingQuery->fetch(PDO::FETCH_ASSOC);
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($item['name']) . '</td>';
                        echo '<td>' . htmlspecialchars($item['price']) . '</td>';
                        echo '<td>' . '<input value="'. $rating['rating'] .'" class="form-control" type="number" max="5" min="0" name="' . $item['item_id'] . '">' . '</td>';
                        echo '</tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="container bg-white my-4 px-4 py-4">
        <p class="h3">Recenze</p>
        <p>Dále můžete zanechat slovní zhodnocení restaurace.
            Recenze bude vyvěšena na stránce restaurace s vaším jménem.
        </p>
        <div class="w-50">
            <div class="form-group">
                <label for="text">Text recenze:</label>
                <textarea rows="4" class="form-control" name="text"><?php echo htmlspecialchars($review['review']) ?></textarea>
            </div>
        </div>
    </div>
    <div class="d-flex justify-content-center container bg-white my-4 px-4 py-4">
        <button type="submit" name="submit" class="btn btn-primary">Odeslat</button>
    </div>
</form>
</div>