<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="sv">
	<head>
		<?php renderHead(); ?>
		<link rel="stylesheet" title="Standard" href="puppy/templates/Crisp/main.css" media="screen" />
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
							<li><a href="puppy/?logOut">Logga ut</a></li>
						<?php } ?>

					</ul>
					<a class="Logo" href="./"><img src="nancy's-logo.gif" alt="logo" /></a>
				</div>

				<div id="Main">
					<div id="Content">
						<?php renderContent(); ?>
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