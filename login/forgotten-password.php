<?php
  //load db and init session
  require_once '../inc/user.php';

  use PHPMailer\PHPMailer\PHPMailer;

  if (!empty($_SESSION['user_id'])){
    header('Location: ../index.php');
    exit();
  }

  $errors=false;
  if (!empty($_POST) && !empty($_POST['email'])){
    $userQuery=$db->prepare('SELECT * FROM users WHERE email=:email LIMIT 1;');
    $userQuery->execute([
      ':email'=>trim($_POST['email'])
    ]);
    if ($user=$userQuery->fetch(PDO::FETCH_ASSOC)){

      $code='xx'.rand(100000,993952);

      $saveQuery=$db->prepare('INSERT INTO forgotten_passwords (user_id, code) VALUES (:user, :code)');
      $saveQuery->execute([
        ':user'=>$user['user_id'],
        ':code'=>$code
      ]);

      $requestQuery=$db->prepare('SELECT * FROM forgotten_passwords WHERE user_id=:user AND code=:code ORDER BY forgotten_password_id DESC LIMIT 1;');
      $requestQuery->execute([
        ':user'=>$user['user_id'],
        ':code'=>$code
      ]);
      $request=$requestQuery->fetch(PDO::FETCH_ASSOC);

      $link='https://eso.vse.cz/~vanm30/sp/login/renew-password.php';
      $link.='?user='.$request['user_id'].'&code='.$request['code'].'&request='.$request['forgotten_password_id'];

      $mailer=new PHPMailer(false);
      $mailer->isSendmail();

      $mailer->addAddress($user['email'],$user['name']);
      $mailer->setFrom($_ENV(EMAIL));

      $mailer->CharSet='utf-8';
      $mailer->Subject='Obnova zapomenutého hesla';

      $mailer->isHTML(true);
      $mailer->Body ='<html>
                        <head><meta charset="utf-8" /></head>
                        <body>Pro obnovu hesla do Ukázkové aplikace klikněte na následující odkaz: <a href="'.htmlspecialchars($link).'">'.htmlspecialchars($link).'</a></body>
                      </html>';
      $mailer->AltBody='Pro obnovu hesla do aplikace klikněte na následující odkaz: '.$link;

      $mailer->send();

      header('Location: forgotten-password.php?mailed=ok');
    }else{
      $errors=true;
    }
  }

  include '../inc/header.php';
?>
<div class="container py-4">
    <div class="w-50">
      <h2>Obnova zapomenutého hesla</h2>
      <?php
        if (@$_GET['mailed']=='ok'){

          echo '<p>Zkontrolujte svoji e-mailovou schránku a klikněte na odkaz, který vám byl zaslán mailem.</p>';
          echo '<a href="index.php" class="btn btn-light">zpět na homepage</a>';

        }else{
      ?>
          <form method="post">
            <div class="form-group">
              <label for="email">E-mail:</label>
              <input type="email" name="email" id="email" required class="form-control <?php echo ($errors?'is-invalid':''); ?>"
                     value="<?php echo htmlspecialchars(@$_POST['email'])?>"/>
              <?php
                echo ($errors?'<div class="invalid-feedback">Neplatný e-mail.</div>':'');
              ?>
            </div>
            <button type="submit" class="btn btn-primary">potvrdit</button>
            <a href="login/login.php" class="btn btn-light">zpět k přihlášení</a>
            <a href="index.php" class="btn btn-light">zrušit</a>
          </form>
      <?php
        }
      ?>
    </div>
</div>
<?php
  //vložíme do stránek patičku
  include '../inc/footer.php';