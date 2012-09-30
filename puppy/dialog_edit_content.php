<?php

	require_once('class_ww.php');

	require_once('class_login.php');
	$Admin = new cLogin('admin');
	if(!$Admin->IsLogedIn()) die('Not logged in.');

	if(!isset($_GET['ID'])) die('No ID');
	$ContentID = $_GET['ID'];

	class cEditForm extends wwFormBase{
		function Populate(){
			global $ContentID;

			$Meta = array_merge(
				array(
					'Title' => '',
					'Keywords' => '',
					'Description' => '',
					'ContentType' => 'HTML',
				),
				(array) json_decode(@file_get_contents('pages/'.$ContentID.'_META'), true)
			);
			$this->Elements[] = new wwText('Title', 'Title:', false, $Meta['Title']);
			$this->Elements[] = new wwText('Keywords', 'Keywords:', false, $Meta['Keywords']);
			$this->Elements[] = new wwText('Description', 'Description:', false, $Meta['Description']);
			$this->Elements[] = new wwSelectBox('ContentType', 'Content Type:', array(array('Title'=>'Plain Text', 'Value'=>'plaintext'), array('Title'=>'HTML', 'Value'=>'HTML'), array('Title'=>'PHP', 'Value'=>'PHP')), $Meta['ContentType']);

			$this->Elements[] = new wwText('Content', 'Sidinnehåll:', true, @file_get_contents('pages/'.$ContentID));

			$this->Elements[] = new wwSubmitButton('OK', '&nbsp;&nbsp;OK&nbsp;&nbsp;');
		}
		function Process(){
			global $ContentID;
			$Reply = $this->GetReply();

			// Do the update.
			file_put_contents('pages/'.$ContentID, $Reply['Content']);
			unset($Reply['Content']);
			unset($Reply['OK']);
			file_put_contents('pages/'.$ContentID.'_META', json_encode($Reply));

			// Close the dialog.
			print('<script language="JavaScript">window.opener.location.reload(); window.opener.focus(); window.close();</script>');
			exit();			
		}
	}

	$EditForm = new cEditForm();
	$EditForm->Execute();


?>
<html>
	<head>
		<title>Admin</title>
		<meta http-equiv="content-type" content="text/html;charset=utf-8">
		<link rel="stylesheet" title="Standard" href="design/style.css" media="screen">	
	</head>
	<body onload="JavaScript:window.resizeTo(800, 600);">

		<?php
			$EditForm->Render();
		?>

	</body>
</html>
