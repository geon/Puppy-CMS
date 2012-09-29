<?php


	// Figure out what page ID to use.
	$PageID = isset($_GET['PageID']) ? $_GET['PageID'] : '';
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
	$Admin = new cLogIn('Admin');




?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="sv">
	<head>
		<title><?php print(htmlspecialchars($Meta['Title'], ENT_QUOTES, 'UTF-8')); ?></title>
		<meta name="keywords" content="<?php print($Meta['Keywords']); ?>" />
		<meta http-equiv="content-type" content="text/html;charset=utf-8" />
		<meta name="description" content="<?php print($Meta['Description']); ?>" />
		<link rel="stylesheet" title="Standard" href="design/style.css" media="screen" />
		<!--[if IE]><link rel="stylesheet" href="design/ie.css" media="screen" /><![endif]-->
	</head>
	<body>

		<div id="<?php print(PAGE_ID); ?>">

			<div id="Center">
				<div id="Header">
					<ul class="Menu">
						<li><a href="quinoa">Quinoa</a></li>
						<li><a href="lunch">Lunch</a></li>
						<li><a href="./">Hem</a></li>
						
						<?php if($Admin->IslogedIn()){ ?>
							<li><a href="log_out.php">Logga ut</a></li>
						<?php } ?>

					</ul>
					<a class="Logo" href="./"><img src="nancy's-logo.gif" alt="logo" /></a>
				</div>

				<div id="Main">
					<div id="Content">
						<?php
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
#									if(function_exists('tidy_clean_repair'))
#										print(tidy_clean_repair(tidy_parse_string($Content, array(
#											'clean' => true,
#											'output-xhtml' => true,
#											'show-body-only' => true,
#											'wrap' => 0,
#										), 'UTF8')));
#									else
										print($Content);
								else if($Meta['ContentType'] == 'plaintext')
									print('<p>'.nl2br(str_replace("\n\n", '</p><p>', htmlspecialchars($Content))).'</p>');
							}
						?>
					</div>
				</div>

				<div id="Footer">
					<ul class="Menu">
						<li class="address" title="Visa karta"><a href="http://www.hitta.se/ViewDetailsPink.aspx?vad=nancys&amp;var=storgatan+3+s%f6dert%e4lje&amp;Vkiid=X3ubiW1Dw78HAJNLuX1FmQ%253d%253d&amp;Vkid=22890789&amp;isAlternateNumberResult=False">Storgatan 3, 151 73 Södertälje</a></li>
						<li class="phone" title="Ring oss">08-550 339 55</li>
						<li class="email" title="Skicka e-post"><a href="mailto:info@nancysfreshfood.se">info@nancysfreshfood.se</a></li>
						<li class="franchise" title="Bli en franchisetagare!"><a href="franchise">Franchise</a></li>
					</ul>
				</div>

			</div>
		</div>
	</body>
</html>
