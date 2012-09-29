<?php

	// Figure out what page ID to use.
	$PageID = isset($_GET['ID']) ? $_GET['ID'] : '';
	if(!preg_match('/[-a-z]+/', $PageID))
		$PageID = 'INDEX';
	if(!is_file('pages/'.$PageID))
		if(!is_file('redirects/'.$PageID)){
			$RequestedPageID = $PageID;
			$PageID = '404';
			header('HTTP/1.1 404 Not Found');
		}else{
			$RealID = file_get_contents('redirects/'.$PageID);
			header('HTTP/1.1 301 Moved Permanently');
			header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']).'/'.$RealID);
			exit();
		}
			
	// Read the metadata, overwriting the defaults, if defined.
	$Meta = array_merge(
		array(
			'Title' => 'Puppy, the simplistic PHP CMS.',
			'Keywords' => 'CMS, PHP, simplistic',
			'Description' => 'A pretty nice and VERY simplistic CMS.',
			'ContentType' => 'HTML',
		),
		(array) unserialize(file_get_contents('pages/'.$PageID.'_META'))
	);

	require_once('class_login.php');
	$Admin = new cLogIn('admin');


	function renderHead(){
		?>
			<title><?php print(htmlspecialchars($Meta['Title'], ENT_QUOTES, 'UTF-8')); ?></title>
			<meta name="keywords" content="<?php print($Meta['Keywords']); ?>" />
			<meta http-equiv="content-type" content="text/html;charset=utf-8" />
			<meta name="description" content="<?php print($Meta['Description']); ?>" />
		<?php
	}

	function renderContent(){
		global $Admin, $PageID, $RequestedPageID, $Meta;
		
		if($Admin->IsLogedIn()){
			if($PageID == '404'){
				print('<p><a href="dialog_edit_content.php?ID='.rawurlencode($RequestedPageID).'" onclick="window.open(this.href, \'\',\'status=0,scrollbars=yes,resizable=yes,modal,dialog,width=800,height=600\'); return false;">Skapa "'.htmlspecialchars($RequestedPageID).'"…</a></p>');
			}

			print('<p><a href="dialog_edit_content.php?ID='.rawurlencode($PageID).'" onclick="window.open(this.href, \'\',\'status=0,scrollbars=yes,resizable=yes,modal,dialog,width=800,height=600\'); return false;">Redigera text...</a></p>');
		}

		if($Meta['ContentType'] == 'PHP')
			require('pages/'.$PageID);
		else{
			$Content = file_get_contents('pages/'.$PageID);
			if($Meta['ContentType'] == 'HTML')
				print($Content);
			else if($Meta['ContentType'] == 'plaintext')
				print('<p>'.nl2br(str_replace("\n\n", '</p><p>', htmlspecialchars($Content))).'</p>');
		}
	}
	
	
	if(!is_file('templates/'.$Meta['Template'].'/template.php')){
		$Meta['Template'] = 'Crisp';
	}
	
	require('templates/'.$Meta['Template'].'/template.php');
	