<?php
require_once '../inc/user.php';

include '../inc/header.php';


if(empty(@$_GET['rest_id'])){
    header('Location: ../index.php');
    exit();
}

$restQuery = $db->prepare('SELECT restaurants.*, users.name AS user_name, users.email
                            FROM restaurants JOIN users ON restaurants.owner=users.user_id 
                            WHERE restaurant_id=:id LIMIT 1');
$restQuery->execute([
    ':id'=>$_GET['rest_id']
]);

if ($restQuery->rowCount() == 0){
    header('Location: ../index.php');
    exit();
}

$restaurant = $restQuery->fetch(PDO::FETCH_ASSOC);
$img = 'uploads/rest-images/' . $restaurant['avatar'] . '.' . $restaurant['avatar_type'];

if (!empty($_POST['deletePost'])){
    if ($_SESSION['user_role'] == 'admin' || $_SESSION['user_id'] == $restaurant['owner']){
        $deleteQuery = $db->prepare('DELETE FROM reviews WHERE review_id=:id');
        $deleteQuery->execute([
            ':id'=>$_POST['deletePost']
        ]);
    } else {
        header('Location: ../index.php');
        exit();
    }
}


$scheduleQuery = $db->prepare('SELECT * FROM schedules WHERE restaurant_id=:id');
$scheduleQuery->execute([
        ':id'=> $_GET['rest_id']
]);
$schedule = $scheduleQuery->fetchAll();


$ratingQuery = $db->prepare('SELECT * FROM ratings WHERE restaurant_id=:id');
$ratingQuery->execute([
        ':id'=> $_GET['rest_id']
]);
$ratings = $ratingQuery->fetchAll(PDO::FETCH_ASSOC);

$foodRating = 0;
$serviceRating = 0;
$priceRating = 0;
$denominator = $ratingQuery->rowCount();

if ($ratingQuery->rowCount()>0){
    foreach ($ratings as $rating) {
        $foodRating += $rating['food_rating'];
        $serviceRating += $rating['service_rating'];
        $priceRating += $rating['price_rating'];
    }

    $foodRating = floor($foodRating/$denominator);
    $serviceRating = floor($serviceRating/$denominator);
    $priceRating = floor($priceRating/$denominator);

    $exactoverallRating = number_format(round(($foodRating + $serviceRating + $priceRating)/3,1),1);
    $overallRating = number_format(floor((($foodRating + $serviceRating + $priceRating)/3) * 2) / 2, 1);
}


$reviewQuery = $db->prepare('SELECT reviews.*,users.name as user_name FROM reviews LEFT JOIN users ON reviews.user_id=users.user_id WHERE restaurant_id=:id');
$reviewQuery->execute([
    ':id'=> $_GET['rest_id']
]);
$reviews = $reviewQuery->fetchAll(PDO::FETCH_ASSOC);
$reviewCount = $reviewQuery->rowCount();


$itemsQuery = $db->prepare('SELECT * FROM items WHERE restaurant_id=:id');
$itemsQuery->execute([
        ':id'=>$_GET['rest_id']
]);
$items = $itemsQuery->fetchAll(PDO::FETCH_ASSOC);


if (isset($_SESSION['user_id'])){
    $userQuery = $db->prepare('SELECT * FROM users WHERE user_id=:id LIMIT 1');
    $userQuery->execute([
        ':id'=>$_SESSION['user_id']
    ]);
    $user = $userQuery->fetch(PDO::FETCH_ASSOC);
}


$reviewed = 0;
if (!empty(@$_SESSION['user_id'])) {
    $ratingQuery = $db->prepare('SELECT * FROM ratings WHERE user_id=:id AND restaurant_id=:rest_id LIMIT 1');
    $ratingQuery->execute([
        ':id' => $_SESSION['user_id'],
        ':rest_id' => $restaurant['restaurant_id']
    ]);
    $ratings = $ratingQuery->fetch(PDO::FETCH_ASSOC);
    $reviewed = 1;
}

?>


<div class="bg-white" style="border: 1px solid #E0E0E0">
<div class="container">
    <div class="row">
        <div class="col-6 d-flex align-items-center ">
            <div>
            <h1 class="py-3"><?php echo htmlspecialchars($restaurant['name']) ?></h1>
            <p><?php echo htmlspecialchars($restaurant['about']) ?></p>
            </div>
        </div>
        <div class="col-3 d-flex align-items-center ">
            <img class="img-fluid" src="<?php echo $img ?> " alt="restaurant_img">
        </div>
        <div class="col-3">
            <table class="table table-sm">
                <tr>
                <th class="text-center" colspan="2">Otevírací doba</th>
                </tr>
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
<?php
if (isset($_SESSION['user_role'])){
        echo '<div class="container d-flex justify-content-center bg-white mt-4" style="border: 1px solid #E0E0E0">';
        if ($_SESSION['user_id'] != $restaurant['owner']){
            if($reviewed == 1){
                echo '<div class="alert alert-info">Restaurace ohodnocena.</div>';
                echo '<a href="./restaurant/review.php?restaurant_id=' . $restaurant['restaurant_id'] . '" class="mx-4 btn btn-primary text-white my-4">Upravit hodnocení</a>';
            } else echo ' <a href="./restaurant/review.php?restaurant_id=' . $restaurant['restaurant_id'] . '" class="mx-4 btn btn-primary text-white my-4">Ohodnotit</a>';
        }
        if ($_SESSION['user_role'] == 'admin' || $_SESSION['user_id'] == $restaurant['owner']){
            echo '<a href="./admin/restaurants-edit.php?restaurant_id=' . $restaurant['restaurant_id'] . '" class="mx-4 btn btn-primary text-white my-4">Upravit restauraci</a>';
        }
        echo '</div>';
}
?>


<div class="container my-4 ">
   <div class="row">

    <div class="mr-5 py-3 px-3 bg-white col" style="border: 1px solid #E0E0E0">
        <p class="h5">Hodnocení a recenze</p>
        <div class="row justify-content-center">
            <?php
            if (isset($overallRating)) {
                echo '<p class="mx-4">' . htmlspecialchars($exactoverallRating) . '</p>';
                if (floor($overallRating) == $overallRating) {
                    for ($i = 0; $i < $overallRating; $i++) {
                        echo '<i class="bi bi-star-fill"></i>';
                    }
                    for ($i = $overallRating; $i < 5; $i++) {
                        echo '<i class="bi bi-star"></i>';
                    }
                } else {
                    for ($i = 0; $i < floor($overallRating); $i++) {
                        echo '<i class="bi bi-star-fill"></i>';
                    }
                    echo '<i class="bi bi-star-half"></i>';
                    for ($i = floor($overallRating) + 1; $i < 5; $i++) {
                        echo '<i class="bi bi-star"></i>';
                    }
                }
            } else echo '<div class="alert alert-info">Bez hodnocení</div>';
            ?>
           <p class="ml-4 mr-4"><?php echo ((1 <= $reviewCount) && ($reviewCount <= 4)) ? $reviewCount . ' recenze' : $reviewCount . ' recenzí'; ?></p>
        </div>
        <hr/>
        <table class="w-100">
            <tr>
                <th><i class="bi bi-egg-fried mr-2"></i>Jídlo</th>
                <td class="text-center">
                    <?php
                    if ($ratingQuery->rowCount()>0) {
                        for ($i = 0; $i < $foodRating; $i++) {
                            echo '<i class="bi bi-star-fill"></i>';
                        }
                        for ($i = $foodRating; $i < 5; $i++) {
                            echo '<i class="bi bi-star"></i>';
                        }
                    } else echo '-';
                    ?>
                </td>
            </tr>
            <tr>
                <th><i class="bi bi-person-heart mr-2"></i>Obsluha</th>
                <td class="text-center">
                    <?php
                    if ($ratingQuery->rowCount()>0) {
                    for ($i = 0; $i<$serviceRating; $i++){
                        echo '<i class="bi bi-star-fill"></i>';
                    }
                    for ($i = $serviceRating; $i<5; $i++){
                        echo '<i class="bi bi-star"></i>';
                    }
                    }else echo '-';
                    ?>
                </td>
            </tr>
            <tr>
                <th><i class="bi bi-wallet2 mr-2"></i>Cena</th>
                <td class="text-center">
                    <?php
                    if ($ratingQuery->rowCount()>0) {
                    for ($i = 0; $i<$priceRating; $i++){
                        echo '<i class="bi bi-star-fill"></i>';
                    }
                    for ($i = $priceRating; $i<5; $i++){
                        echo '<i class="bi bi-star"></i>';
                    }
                    }else echo '-';
                    ?>
                </td>
            </tr>
        </table>
        <?php
            if($reviewed == 1){
            echo '<hr/>';
            echo '<p class="h5">Moje hodnocení</p>';
            echo '
            <table class="w-100">
            <tr>
                <th><i class="bi bi-egg-fried mr-2"></i>Jídlo</th>
                <td class="text-center">';
                if (isset($ratings['food_rating'])) {
                    for ($i = 0; $i < $ratings['food_rating']; $i++) {
                        echo '<i class="bi bi-star-fill"></i>';
                    }
                    for ($i = $ratings['food_rating']; $i < 5; $i++) {
                        echo '<i class="bi bi-star"></i>';
                    }
                }
             echo '       
                </td>
            </tr>
            <tr>
                <th><i class="bi bi-person-heart mr-2"></i>Obsluha</th>
                <td class="text-center">';

                    if (isset($ratings['service_rating'])) {
                    for ($i = 0; $i<$ratings['service_rating']; $i++){
                        echo '<i class="bi bi-star-fill"></i>';
                    }
                    for ($i = $ratings['service_rating']; $i<5; $i++){
                        echo '<i class="bi bi-star"></i>';
                    }
                    }
                  echo '
                </td>
            </tr>
            <tr>
                <th><i class="bi bi-wallet2 mr-2"></i>Cena</th>
                <td class="text-center">';
                    if (isset($ratings['price_rating'])) {
                    for ($i = 0; $i<$ratings['price_rating']; $i++){
                        echo '<i class="bi bi-star-fill"></i>';
                    }
                    for ($i = $ratings['price_rating']; $i<5; $i++){
                        echo '<i class="bi bi-star"></i>';
                    }
                    }else echo '-';
                   echo '
                </td>
            </tr>
        </table>';
            }
        ?>
    </div >
    <!--Details (prices)-->
    <div class="mr-5 py-2 bg-white col" style="border: 1px solid #E0E0E0">
        <p class="h5 mb-4 my-2">Detail</p>
        <div class="alert alert-info text-center">Zatím žádné detaily.</div>
    </div>
    <!--Location and contact-->
    <div class="py-3 px-3 bg-white col" style="border: 1px solid #E0E0E0">
        <p class="h5 mb-4">Místo a kontakt</p>
        <div class="mx-2" style="width:300px; height:150px;">
        <div id="m" style="width:300px; height:150px;position: absolute;"></div>
        <div style="position: absolute;" class="mx-5 my-5 alert alert-info">Mapa není k dispozici.</div>
        </div>
        <p class="mt-4"><i class="mr-3 bi bi-geo-alt-fill"></i><?php echo htmlspecialchars($restaurant['address']) ?></p>
        <p><i class="mr-3 bi bi-envelope-fill"></i><?php echo '<a href = "mailto:' . $restaurant['email'] . '">' . htmlspecialchars($restaurant['email']) . '</a>' ?></p>

    </div>
</div>
</div>

<div class="container my-4 bg-white py-4 px-5">
    <p class="h5 mb-4 ml-2">Hodnocení jídel</p>
    <?php
    if (!empty($items)){
        echo '<table class="table table-sm">
            <thead>
            <tr>
                <th>Jídlo</th>
                <th>Cena</th>
                <th>Hodnocení</th>';
                echo ($reviewed == 1)? '<th>Moje hodnocení</th>' : '';
            echo '    
            </tr>
            </thead>
            <tbody>';
                    foreach ($items as $item) {
                        $ratingQuery = $db->prepare('SELECT * FROM items_ratings WHERE item_id=:id');
                        $ratingQuery->execute([
                            ':id' => $item['item_id']
                        ]);
                        $ratings = $ratingQuery->fetchAll(PDO::FETCH_ASSOC);
                        $sumRating = 0;
                        foreach ($ratings as $rating) {
                            $sumRating += $rating['rating'];
                        }
                        if (count($ratings) != 0) {
                            $realRating = floor($sumRating / count($ratings));
                        } else $realRating = 'Bez hodnocení';

                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($item['name']) . '</td>';
                        echo '<td>' . htmlspecialchars($item['price']) . ' Kč' . '</td>';
                        echo '<td>';
                        if (!is_string($realRating)) {
                            for ($i = 0; $i < $realRating; $i++) {
                                echo '<i class="bi bi-star-fill"></i>';
                            }
                            for ($i = $realRating; $i < 5; $i++) {
                                echo '<i class="bi bi-star"></i>';
                            }
                        } else {
                            echo $realRating;
                        }
                        echo '</td>';
                        if (isset($_SESSION['user_id'])) {
                            $itemRatingQuery = $db->prepare('SELECT rating FROM items_ratings WHERE user_id=:id AND item_id=:item_id');
                            $itemRatingQuery->execute([
                                ':id' => $user['user_id'],
                                ':item_id' => $item['item_id']
                            ]);
                            $itemRating = $itemRatingQuery->fetch(PDO::FETCH_ASSOC);
                            $itemRating = $itemRating['rating'];

                            if (isset($itemRating)) {
                                echo '<td>';
                                for ($i = 0; $i < $itemRating; $i++) {
                                    echo '<i class="bi bi-star-fill"></i>';
                                }
                                for ($i = $itemRating; $i < 5; $i++) {
                                    echo '<i class="bi bi-star"></i>';
                                }
                                echo '</td>';
                            } else {
                                echo '<td>';
                                echo 'Neohodnoceno';
                                echo '</td>';
                            }
                        }
                        echo '</tr>';
                    }

           echo '</tbody>
        </table>';
    } else  echo '<div class="alert alert-info">Tato restaurace nemá vypsaná jídla.</div>';
    ?>
</div>


<div class="container my-4 bg-white py-4 px-5">
    <p class="h5 mb-4 ml-2">Recenze</p>
    <hr/>
    <?php
        if (!empty($reviews)){
            foreach ($reviews as $review){
            echo '<div class="row">';
            echo '<article class="col-12 mx-3 px-2">';
                echo '  <div><span class="badge badge-secondary">'.htmlspecialchars($review['user_name']).'</span></div>';
                echo '  <div>'.nl2br(htmlspecialchars($review['review'])).'</div>';
                echo '  <div class="small text-muted mt-1">';
                    echo date('d.m.Y H:i:s',strtotime($review['updated']));
                    if  (!empty(@$_SESSION['user_id'])){
                        if (@$_SESSION['user_role'] == 'admin' || @$_SESSION['user_id'] == $restaurant['owner'] || $review['user_id'] == $user['user_id'] ) {
                            echo '<form method="post">';
                            echo '<button type="submit" name="deletePost" value="' . $review['review_id'] . '" class="btn btn-sm btn-secondary">smazat</button>';
                            echo '</form>';
                        }
                    }
                    echo '  </div>';
                echo '</article>';
                echo '</div>';
                echo '<hr/>';
            }
        }else{
        echo '<div class="alert alert-info">Tato restaurace zatím nemá žádné recenze.</div>';
        }
    ?>
</div>

<script type="text/javascript">
    var query = <?php echo json_encode($restaurant['address']) ?>;
    new SMap.Geocoder(query, odpoved);

    function odpoved(geocoder) {
        if (!geocoder.getResults()[0].results.length) {
            return;
        }
        var vysledky = geocoder.getResults()[0].results;
        var data = [];
        while (vysledky.length) {
            var item = vysledky.shift();
            data.push(item.coords.toWGS84().toString());
        }
        var split = data[0].split(',');
        var E = split[0];
        var N = split[1];

        var center = SMap.Coords.fromWGS84(E,N);
        var m = new SMap(JAK.gel("m"), center, 13);
        m.addDefaultLayer(SMap.DEF_BASE).enable();
        m.addDefaultControls();

        var layer = new SMap.Layer.Marker();
        m.addLayer(layer);
        layer.enable();

        var options = {};
        var marker = new SMap.Marker(center, "myMarker", options);
        layer.addMarker(marker);
    }
</script>


<?php
include '../inc/footer.php';
?>
