<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="sv">
	<head>
		<?php renderHead(); ?>
		<link rel="stylesheet" title="Standard" href="puppy/templates/Crisp/main.css" media="screen" />
		<?php if(!empty($Meta['Variation'])){ ?>
			<link rel="stylesheet" title="Standard" href="puppy/templates/Crisp/<?= $Meta['Variation'] ?>" media="screen" />
		<?php } ?>
	</head>
	<body>

		<div class="main">
			<div class="center header">
				<ul class="menu">
					<li><a href="./">About Us</a></li>
					<li><a href="lunch">Our Mission</a></li>
					<li><a href="quinoa">Green Environment</a></li>
					
					<?php if($Admin->IslogedIn()){ ?>
						<li><a href="puppy/?logOut">Logga ut</a></li>
					<?php } ?>
	
				</ul>
			</div>
	
			<div id="content" class="center">
				<?php renderContent(); ?>
			</div>
		</div>

		<div class="footer">
			<ul class="menu">
				<li class="address" title="Show map"><a href="https://maps.google.com/maps?q=560+25+Bottnaryd,+Sweden&hl=en&ie=UTF8&sll=57.771788,13.825738&sspn=0.03996,0.033731&oq=560+25+bottnaryd&t=h&hnear=Bottnaryd,+J%C3%B6nk%C3%B6ping+County,+Sweden&z=15">Some Street, 560 25 Bottnaryd, Sweden</a></li>
				<li class="phone" title="Call us">1234 - 567 890</li>
				<li class="email" title="E-mail us"><a href="mailto:victor@topmost.se">victor@topmost.se</a></li>
			</ul>
		</div>
	</body>
</html>