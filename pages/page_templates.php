<?php

function default_page_template ($is_mobile, $lang = "en", $user)
{
	$userlevel = $user ? $user->getAccessLevel() : 0;

	if ($user && $user->isNewDesignUsed())
	{
		$result = '
<!DOCTYPE html>
<html>
	<head>
		<title>yunNet.</title>

		<link rel="shortcut icon" href="'.DEFAULT_URL.'/favicon.ico"/>
     	<link rel="apple-touch-icon" sizes="180x180" href="'.DEFAULT_URL.'/favicon/apple-touch-icon.png">
     	<link rel="icon" type="image/png" href="'.DEFAULT_URL.'/favicon/favicon-16x16.png" sizes="16x16">
		<link rel="icon" type="image/png" href="'.DEFAULT_URL.'/favicon/favicon-32x32.png" sizes="32x32">

		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
		<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1.0"/>
		<meta name="theme-color" content="#42A5F5">
		<meta name="description" content="yunNet. - is a social network whose purpose is to return the old principles of social networks, while following the new technologies">
	
		<link href="'.DEFAULT_SCRIPTS_URL.'/css/default-components.css" type="text/css" rel="stylesheet" media="screen,projection"/>
		<link href="'.DEFAULT_SCRIPTS_URL.'/css/additional-components.css" type="text/css" rel="stylesheet" media="screen,projection"/>

		<script src="'.DEFAULT_SCRIPTS_URL.'/js/default-components.js"></script>
		<script src="'.DEFAULT_SCRIPTS_URL.'/js/additional-components.js"></script>
		' .(explode('.', strtolower($_SERVER['HTTP_HOST']))[0] === 'dev' ? ('<script src="'.DEFAULT_SCRIPTS_URL.'/js/dev-platform-loader.js"></script>') : ('<script src="'.DEFAULT_SCRIPTS_URL.'/js/platform-loader.js"></script>')). '
		' .(explode('.', strtolower($_SERVER['HTTP_HOST']))[0] === 'dev' ? ('<script src="'.DEFAULT_SCRIPTS_URL.'/js/dev-platform-content.js"></script>') : ('<script src="'.DEFAULT_SCRIPTS_URL.'/js/platform-content.js"></script>')). '
	</head>
	<body>
		<div id="load" style="position: fixed; right: 0; bottom: 0; left: 0; top: 0; background-color: white;z-index: 999;">
			<div id="indicator" style="position: absolute; top: 50%; left: 50%; margin-right: -50%; transform: translate(-50%, -50%);">
				<div style="text-align: -webkit-center">
					<img src="https://yunnet.ru/favicon.ico" class="circle" style="width: 150px;">
				</div>
				<div id="load_indicator" style="text-align: center; margin-top: 70%; display: none">
					<svg width="40" height="40" viewBox="0 0 50 50"><path id="loader_ui_spin" transform="rotate(61.2513 25 25)" d="M25,5A20.14,20.14,0,0,1,45,22.88a2.51,2.51,0,0,0,2.49,2.26h0A2.52,2.52,0,0,0,50,22.33a25.14,25.14,0,0,0-50,0,2.52,2.52,0,0,0,2.5,2.81h0A2.51,2.51,0,0,0,5,22.88,20.14,20.14,0,0,1,25,5Z" style="fill: var(--unt-loader-color, #42a5f5);"><animateTransform attributeName="transform" type="rotate" from="0 25 25" to="360 25 25" dur="0.4s" repeatCount="indefinite"></animateTransform></path></svg>
					<br>
					<div id="load_text_info" style="color: black;"></div>
				</div>
			</div>
		</div>
	</body>
</html>
		';
	} else 
	{
		$content = $is_mobile ? "" : '<div class="row" style="height: 100%; margin-bottom: 0 !important"><div class="col s3"></div><div class="col s6"></div><div class="col s3"></div></div>';

		$scripts_list = '
<script src="'.DEFAULT_SCRIPTS_URL.'/js/ui.js"></script>
<script src="'.DEFAULT_SCRIPTS_URL.'/js/poster.js"></script>
<script src="'.DEFAULT_SCRIPTS_URL.'/js/caches.js"></script>
<script src="'.DEFAULT_SCRIPTS_URL.'/js/settings.js"></script>
<script src="'.DEFAULT_SCRIPTS_URL.'/js/pages.js"></script>
<script src="'.DEFAULT_SCRIPTS_URL.'/js/themes.js"></script>
<script src="'.DEFAULT_SCRIPTS_URL.'/js/dev.js"></script>
<script src="'.DEFAULT_SCRIPTS_URL.'/js/messenger.js"></script>
<script src="'.DEFAULT_SCRIPTS_URL.'/js/codes.js"></script>
';
		if ($userlevel > 0)
		{
			$scripts_list .= '<script src="'.DEFAULT_SCRIPTS_URL.'/js/management.js"></script>';
		}

		$result = '
<!DOCTYPE html>
<html lang="'.$lang.'">
	<head>
		<title>yunNet.</title>
		<link rel="shortcut icon" href="'.DEFAULT_URL.'/favicon.ico"/>
     	<link rel="apple-touch-icon" sizes="180x180" href="'.DEFAULT_URL.'/favicon/apple-touch-icon.png">
     	<link rel="icon" type="image/png" href="'.DEFAULT_URL.'/favicon/favicon-16x16.png" sizes="16x16">
		<link rel="icon" type="image/png" href="'.DEFAULT_URL.'/favicon/favicon-32x32.png" sizes="32x32">
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
	    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1.0"/>
	    <meta name="theme-color" content="#42A5F5">
	    <meta name="description" content="yunNet. - is a social network whose purpose is to return the old principles of social networks, while following the new technologies">
	    <link href="/manifest.json" rel="manifest">
	    <link href="/favicon/apple-touch-icon.png" sizes="180x180" rel="apple-touch-icon">
	    <link href="'.DEFAULT_SCRIPTS_URL.'/css/ui.css" type="text/css" rel="stylesheet" media="screen,projection"/>
	    <link href="'.DEFAULT_SCRIPTS_URL.'/css/codes.css" type="text/css" rel="stylesheet" media="screen,projection"/>
	    '.$scripts_list.'
	</head>
	<body>
		<div id="load" style="position: fixed; right: 0; bottom: 0; left: 0; top: 0; background-color: white;z-index: 999;">
			<div id="indicator" style="position: absolute; top: 50%; left: 50%; margin-right: -50%; transform: translate(-50%, -50%);">
				<div style="text-align: -webkit-center">
					<img src="https://yunnet.ru/favicon.ico" class="circle" style="width: 150px;">
				</div>
				<div id="load_indicator" style="text-align: center; margin-top: 70%; display: none">
					<svg width="40" height="40" viewBox="0 0 50 50"><path id="loader_ui_spin" transform="rotate(61.2513 25 25)" d="M25,5A20.14,20.14,0,0,1,45,22.88a2.51,2.51,0,0,0,2.49,2.26h0A2.52,2.52,0,0,0,50,22.33a25.14,25.14,0,0,0-50,0,2.52,2.52,0,0,0,2.5,2.81h0A2.51,2.51,0,0,0,5,22.88,20.14,20.14,0,0,1,25,5Z" style="fill: var(--unt-loader-color, #42a5f5);"><animateTransform attributeName="transform" type="rotate" from="0 25 25" to="360 25 25" dur="0.4s" repeatCount="indefinite"></animateTransform></path></svg>
					<br>
					<div id="load_text_info" style="color: black;"></div>
				</div>
			</div>
		</div>
		<div class="navbar-fixed">
			<nav>
			</nav>
		</div>
		<div id="menu" style="height: calc(100% - 56px)">
			'.$content.'
		</div>
	</body>
</html>
';
	}

	return $result;
}

?>