<?php

	require_once('class_login.php');
	require_once('wwForm.php');


	$Admin = new cLogIn('admin');
	
	if(isset($_GET['logOut'])){
		$Admin->LogOut();
		header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']).'/../');
	}

	class cAdminLoginForm extends wwFormBase{
		function Populate(){
			$this->Elements[] = new wwSimplePasswordCheck('p', 'Password:', 'password');
			$this->Elements[] = new wwSubmitButton('s', 'Login');
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
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
	<head>
		<title>Login</title>
		<meta http-equiv="content-type" content="text/html;charset=utf-8" />
		<link rel="stylesheet" title="Standard" href="wwForm.css" media="screen" />
		<link rel="stylesheet" title="Standard" href="admin.css" media="screen" />
	</head>
	<body>

		<?php $AdminLoginForm->Render(); ?>

	</body>
</html>