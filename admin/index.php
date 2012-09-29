<?php

  require_once('../class_login.php');
  require_once('../class_ww.php');



  $Admin = new cLogIn('Admin');
  

  class cAdminLoginForm extends wwFormBase{
    function Populate(){
      $this->Elements[] = new wwPassWord('p', 'Lösenord:', 'password');
      $this->Elements[] = new wwSubmitButton('s', '&nbsp;&nbsp;OK&nbsp;&nbsp;');
    }
    function Process(){
      // Password OK, so log in.
      global $Admin;
      $Admin->LogIn();

      // Redirect.
      header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']).'/../');
    }
  }
  $AdminLoginForm = new cAdminLoginForm('AdminLoginForm');
  $AdminLoginForm->Execute();




?>
<html>
  <head>
    <title>Admin</title>
		<meta http-equiv="content-type" content="text/html;charset=iso-8859-1">
    <link rel="stylesheet" title="Standard" href="../design/style.css" media="screen">  
  </head>
  <body>

    <?php $AdminLoginForm->Render(); ?>

  </body>
</html>
