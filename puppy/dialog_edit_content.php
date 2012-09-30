<?php

	require_once('wwForm.php');

	require_once('class_login.php');
	$admin = new LogIn('admin');
	if (!$admin->isLoggedIn()) die('Not logged in.');

	if (!isset($_GET['ID'])) die('No ID');
	$contentID = $_GET['ID'];

	class EditForm extends wwFormBase{
		function populate() {
			global $contentID;

			$meta = array_merge(
				array(
					'title' => '',
					'keywords' => '',
					'description' => '',
					'contentType' => 'HTML',
				),
				(array) json_decode(@file_get_contents('pages/'.$contentID.'_META'), true)
			);
			$this->elements[] = new wwText('title', 'Title:', false, $meta['title']);
			$this->elements[] = new wwText('keywords', 'Keywords:', false, $meta['keywords']);
			$this->elements[] = new wwText('description', 'Description:', false, $meta['description']);
			$this->elements[] = new wwSelectBox('contentType', 'Content Type:', array(
				array('title'=>'Plain Text',	'value'=>'plaintext'),
				array('title'=>'HTML',			'value'=>'HTML'),
				array('title'=>'PHP',			'value'=>'PHP')
			), $meta['contentType']);

			$templates = array();
			if ($handle = opendir('templates')) {
				while (false !== ($entry = readdir($handle))) {
					if ($entry != "." && $entry != ".." && is_dir('templates/'.$entry)) {

						$templates[] = array('title' => $entry, 'value' => array('template' => $entry));

						$variations = json_decode(file_get_contents('templates/'.$entry.'/variations.json'), true);
						foreach ($variations as $variationTitle => $variationFileName) {
							$templates[] = array('title' => $entry.' - '.$variationTitle, 'value' => array('template' => $entry, 'variation' => $variationFileName));
						}
					}
				}
				closedir($handle);
			}
			
			$this->elements[] = new wwSelectBox('template', 'Template:', $templates, array('template' => $meta['template'], 'variation' => $meta['variation']));

			$this->elements[] = new wwText('Content', 'Sidinnehåll:', true, @file_get_contents('pages/'.$contentID));

			$this->elements[] = new wwSubmitButton('OK', '&nbsp;&nbsp;OK&nbsp;&nbsp;');
		}
		function process() {
			global $contentID;
			$reply = $this->GetReply();

			// Do the update.
			file_put_contents('pages/'.$contentID, $reply['Content']);
			$reply['variation'] = $reply['template']['variation'];
			$reply['template'] = $reply['template']['template'];
			unset($reply['Content']);
			unset($reply['OK']);
			file_put_contents('pages/'.$contentID.'_META', json_encode($reply));

			// Close the dialog.
			print('<script language="JavaScript">window.opener.location.reload(); window.opener.focus(); window.close();</script>');
			exit();			
		}
	}

	$editForm = new EditForm();
	$editForm->execute();


?>
<html>
	<head>
		<title>Admin</title>
		<meta http-equiv="content-type" content="text/html;charset=utf-8">
		<link rel="stylesheet" title="Standard" href="wwForm.css" media="screen">	
	</head>
	<body onload="JavaScript:window.resizeTo(320, 600);">

		<?php
			$editForm->render();
		?>

	</body>
</html>
