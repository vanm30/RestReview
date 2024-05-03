<!DOCTYPE html>
<html lang="cs">
  <head>
    <title><?php echo (!empty($pageTitle)?$pageTitle.' - ':'')?>RestReview</title>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.3/font/bootstrap-icons.css">
    <link rel="stylesheet" type="text/css" href="../style.css" />
    <script src="https://kit.fontawesome.com/bcd8cde6f2.js" crossorigin="anonymous"></script>
    <script type="text/javascript" src="https://api.mapy.cz/loader.js"></script>
    <script type="text/javascript">Loader.lang = "en"; Loader.load()</script>
    <script src="https://code.jquery.com/jquery-3.2.1.js"></script>
    <base href="https://eso.vse.cz/~vanm30/sp/"/>
  </head>
  <body style="background-color: #F2F2F2;">
    <header class="bg-white sticky-top" style="border-bottom: 1px solid #E0E0E0">
        <div class="container d-flex align-items-center py-2">
            <div>
                <a href="index.php" class="h4 text-dark">
                        <i class="fa-solid fa-burger fa-2xl"></i>
                        RestReview
                </a>
            </div>
            <nav class="navbar navbar-expand-lg flex-grow-1">
                <div id="navbarNav">
                    <?php
                    if (@$_SESSION['user_role'] == 'admin') {
                        echo '
                        <ul class="navbar-nav">
                            <li class="nav-item">
                              <a class="nav-link text-dark" href="admin/restaurants.php">Správa restaurací</a>
                            </li>
                            
                            <li class="nav-item">
                              <a class="nav-link text-dark" href="admin/users.php">Správa uživatelů</a>
                            </li>
                        </ul>
                        ';
                    }
                    ?>
                </div>
            </nav>
            <div class="text-right">
                <?php
                if (!empty($_SESSION['user_id'])){
                    echo ($_SESSION['user_role'] == 'admin') ?
                        '<strong class="">'.htmlspecialchars($_SESSION['user_name']).' ('.htmlspecialchars($_SESSION['user_role']).')'.'</strong>' :
                        '<strong>'.htmlspecialchars($_SESSION['user_name']).'</strong>';
                    echo '<a class="text-white btn btn-primary ml-2" href="login/logout.php">odhlásit se</a>';
                }else{
                    echo '<a class="text-white btn btn-primary ml-2" href="login/login.php">přihlásit se</a>';
                }
                ?>
            </div>
        </div>
    </header>