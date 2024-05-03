<?php
  require_once '../inc/user.php';

  require_once '../inc/facebook.php';
  require_once '../inc/google.php';

  if (!empty($_SESSION['user_id'])){
    header('Location: ../index.php');
    exit();
  }

  $errors=false;
  if (!empty($_POST)){
    $userQuery=$db->prepare('SELECT * FROM users WHERE email=:email LIMIT 1;');
    $userQuery->execute([
      ':email'=>trim($_POST['email'])
    ]);
    if ($user=$userQuery->fetch(PDO::FETCH_ASSOC)){

      if (password_verify($_POST['password'],$user['password'])){
        $_SESSION['user_id']=$user['user_id'];
        $_SESSION['user_name']=$user['name'];
        $_SESSION['user_role']=$user['role'];

        $forgottenDeleteQuery=$db->prepare('DELETE FROM forgotten_passwords WHERE user_id=:user;');
        $forgottenDeleteQuery->execute([':user'=>$user['user_id']]);

        header('Location: ../index.php');
        exit();
      }else{
        $errors=true;
      }

    }else{
      $errors=true;
    }
  }

  include '../inc/header.php';

  $fbHelper = $fb->getRedirectLoginHelper();
  $permissions = ['email'];
  $callbackUrl = htmlspecialchars('https://eso.vse.cz/~vanm30/sp/oauth/fb-callback.php');
  $fbLoginUrl = $fbHelper->getLoginUrl($callbackUrl, $permissions);
?>
<div class="container py-4">
    <div class="w-50">
      <h2>Přihlásit se</h2>
      <form method="post">
        <div class="form-group">
          <label for="email">E-mail:</label>
          <input type="email" name="email" id="email" required class="form-control <?php echo ($errors?'is-invalid':''); ?>" value="<?php echo htmlspecialchars(@$_POST['email'])?>"/>
          <?php
            echo ($errors?'<div class="invalid-feedback">Neplatná kombinace přihlašovacího e-mailu a hesla.</div>':'');
          ?>
        </div>
        <div class="form-group">
          <label for="password">Heslo:</label>
          <input type="password" name="password" id="password" required class="form-control <?php echo ($errors?'is-invalid':''); ?>" />
        </div>
        <button type="submit" class="btn btn-primary">přihlásit se</button>
        <a href="login/registration.php" class="btn btn-light">registrovat se</a>
        <a href="index.php" class="btn btn-light">zrušit</a>
      </form>

<?php
echo '<hr/>';
echo '<p class="h5">Přihlásit přes:</p>';
echo '<div class="text-center">';
echo '<a class="btn btn-secondary my-2" href="'.$fbLoginUrl.'"><i class="bi bi-facebook"></i> Facebook</a>';
echo '</br>';
echo '<a class="btn btn-secondary my-2" href="'.$google->createAuthUrl().'"><i class="bi bi-google"></i> Google<a>';
echo '</br>';
echo '<hr/>';
echo '<a href="login/forgotten-password.php" class="btn btn-secondary">obnova hesla</a>';
echo '</div>';
?>

    </div>
</div>
<?php
include'../inc/footer.php';
?>