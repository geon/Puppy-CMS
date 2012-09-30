<?php

	// Figure out what page ID to use.
	$pageID = isset($_GET['ID']) ? $_GET['ID'] : '';
	if (!preg_match('/[-a-z]+/', $pageID))
		$pageID = 'INDEX';
	if (!is_file('pages/'.$pageID))
		if (!is_file('redirects/'.$pageID)) {
			$requestedPageID = $pageID;
			$pageID = '404';
			header('HTTP/1.1 404 Not Found');
		} else {
			$realID = file_get_contents('redirects/'.$pageID);
			header('HTTP/1.1 301 Moved Permanently');
			header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']).'/../'.$realID);
			exit();
		}
			
	// Read the metadata, overwriting the defaults, if defined.
	$meta = array_merge(
		array(
			'title' => '',
			'keywords' => '',
			'description' => '',
			'contentType' => 'HTML',
		),
		(array) json_decode(file_get_contents('pages/'.$pageID.'_META'), true)
	);

	require_once('class_login.php');
	$admin = new LogIn('admin');


	function renderHead() {
		global $meta;
		?>
			<title><?php print(htmlspecialchars($meta['title'], ENT_QUOTES, 'UTF-8')); ?></title>
			<meta name="keywords" content="<?php print($meta['keywords']); ?>" />
			<meta http-equiv="content-type" content="text/html;charset=utf-8" />
			<meta name="description" content="<?php print($meta['description']); ?>" />
		<?php
	}

	function renderControls() {
		global $admin, $pageID, $requestedPageID;
		
		if ($admin->isLoggedIn()) {

			print('<p><a href="puppy/?logOut">Log out</a></p>');
			print('<p><a href="puppy/dialog_edit_content.php?ID='.rawurlencode($pageID).'" onclick="window.open(this.href, \'\',\'status=0,scrollbars=yes,resizable=yes,modal,dialog,width=800,height=600\'); return false;">Edit content...</a></p>');
			if ($pageID == '404') {
				print('<p><a href="puppy/dialog_edit_content.php?ID='.rawurlencode($requestedPageID).'" onclick="window.open(this.href, \'\',\'status=0,scrollbars=yes,resizable=yes,modal,dialog,width=800,height=600\'); return false;">Create "'.htmlspecialchars($requestedPageID).'"...</a></p>');
			}
		}
	}
	
	function renderContent() {
		global $pageID, $meta;
		
		if ($meta['contentType'] == 'PHP')
			require('pages/'.$pageID);
		else {
			$Content = file_get_contents('pages/'.$pageID);
			if ($meta['contentType'] == 'HTML')
				print($Content);
			else if ($meta['contentType'] == 'plaintext')
				print('<p>'.nl2br(str_replace("\n\n", '</p><p>', htmlspecialchars($Content))).'</p>');
		}
	}
	
	
	if (!is_file('templates/'.$meta['Template'].'/template.php')) {
		$meta['Template'] = 'Crisp';
	}
	
	require('templates/'.$meta['Template'].'/template.php');
	