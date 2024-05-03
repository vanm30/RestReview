<?php
  //ndb connection and init session
  require_once 'inc/user.php';

  include __DIR__.'/inc/header.php';


$query = $db->prepare('SELECT restaurants.*, users.name AS user_name, users.email
                            FROM restaurants JOIN users ON restaurants.owner=users.user_id;');
$query->execute();


$restaurants = $query->fetchAll(PDO::FETCH_ASSOC);

if (isset($_SESSION['user_id'])){
    echo'
    <div class="mt-4 d-flex justify-content-center container">
        <a class="btn btn-primary" href="restaurant/add-restaurant.php">Přidat restauraci</a>
    </div>';
}

if (!empty($restaurants)) {
    #region výpis restaurací
    echo '<div class="container">';
    echo '<div class=" py-4 row justify-content-center">';
    foreach ($restaurants as $restaurant) {
        $img = 'uploads/rest-images/' . $restaurant['avatar'] . '.' . $restaurant['avatar_type'];
        echo '<div class="col-4 col-md-4 col-lg-3 col-xxl-4 mx-2 my-2" >';
        echo '<div style="border: 1px solid #E0E0E0 " class="bg-white" >';
        echo '<div style="height: 200px">';
        echo '<img style="width:100%;height:100%;" class="img-fluid" src="' . $img . '" alt="restaurant_img">';
        echo '</div>';
        echo '<a class="stretched-link" href="restaurant/home.php?rest_id='.$restaurant['restaurant_id'].'"></a>';
        echo '<div class=" py-3 row justify-content-center"><p class="h4">' . htmlspecialchars($restaurant['name']) . '</p></div>';
        echo '</div>';
        echo '</div>';
    }
    echo '</div>';
    echo '</div>';
} else echo '<div class="alert alert-info">Žádné restaurace.</div>';


  //vložíme do stránek patičku
  include __DIR__.'/inc/footer.php';
