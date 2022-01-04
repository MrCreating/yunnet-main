<?php

function check_the_domain () 
{
	return explode('.', strtolower($_SERVER['HTTP_HOST']))[0];
}

function default_page_template ($is_mobile, $lang = "en", $user)
{
	$userlevel  = $user ? $user->getAccessLevel() : 0;

	$domain = check_the_domain();

	$devChecked  = $domain === 'dev';
	$authChecked = $domain === 'auth';
	$testChecked = $domain === 'test';

	if (getenv('UNT_PRODUCTION') !== '1')
		$test_div = '
<div class="card waves-effect waves-light" style="padding: 15px; position: fixed !important; bottom: 20px; left: 25px;">
	<b>Режим разработки.</b>
</div>
		';


	if (!$user || ($user && $user->getSettings()->getSettingsGroup('theming')->isNewDesignUsed()) || ($devChecked || $authChecked || $testChecked))
	{
		$scripts_list = '
<script src="' . Project::getDevDomain() . '/js/platform-loader.js"></script>
<script src="' . Project::getDevDomain() . '/js/platform-content.js"></script>
<script src="' . Project::getDevDomain() . '/js/platform-actions.js"></script>

<script src="' . Project::getDevDomain() . '/js/platform-modules-settings.js"></script>
<script src="' . Project::getDevDomain() . '/js/platform-modules-accounts.js"></script>
<script src="' . Project::getDevDomain() . '/js/platform-modules-messenger.js"></script>
<script src="' . Project::getDevDomain() . '/js/platform-modules-edit.js"></script>
<script src="' . Project::getDevDomain() . '/js/platform-modules-uploader.js"></script>
		';

		if ($devChecked)
		{
			$scripts_list = '
<script src="' . Project::getDevDomain() . '/js/dev-platform-loader.js"></script>
<script src="' . Project::getDevDomain() . '/js/dev-platform-content.js"></script>
<script src="' . Project::getDevDomain() . '/js/dev-platform-actions.js"></script>
		';
		}
		if ($authChecked)
		{
			$scripts_list = '
<script src="' . Project::getDevDomain() . '/js/auth-platform-loader.js"></script>
<script src="' . Project::getDevDomain() . '/js/auth-platform-content.js"></script>
<script src="' . Project::getDevDomain() . '/js/auth-platform-actions.js"></script>
		';
		}
		if ($testChecked)
		{
			$scripts_list = '
<script src="' . Project::getDevDomain() . '/js/test-platform-loader.js"></script>
<script src="' . Project::getDevDomain() . '/js/test-platform-content.js"></script>
<script src="' . Project::getDevDomain() . '/js/test-platform-actions.js"></script>
		';
		}

		$result = '
<!DOCTYPE html>
<html>
	<head>
		<title>yunNet.</title>

		<link rel="shortcut icon" href="' . Project::getDefaultDomain() . '/favicon.ico"/>
     	<link rel="apple-touch-icon" sizes="180x180" href="' . Project::getDefaultDomain() . '/favicon/apple-touch-icon.png">
     	<link rel="icon" type="image/png" href="' . Project::getDefaultDomain() . '/favicon/favicon-16x16.png" sizes="16x16">
		<link rel="icon" type="image/png" href="' . Project::getDefaultDomain() . '/favicon/favicon-32x32.png" sizes="32x32">

		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
		<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1.0"/>
		<meta name="theme-color" content="#42A5F5">
		<meta name="description" content="yunNet. - is a social network whose purpose is to return the old principles of social networks, while following the new technologies">
	
		<link href="' . Project::getDevDomain() . '/css/default-components.css" type="text/css" rel="stylesheet" media="screen,projection"/>
		<link href="' . Project::getDevDomain() . '/css/additional-components.css" type="text/css" rel="stylesheet" media="screen,projection"/>

		<script src="' . Project::getDevDomain() . '/js/default-components.js"></script>
		<script src="' . Project::getDevDomain() . '/js/additional-components.js"></script>
		
		'.$scripts_list.'

		<script src="' . Project::getDevDomain() . '/js/platform-modules-realtime.js"></script>
	</head>
	<body>
		<div id="load" style="position: fixed; right: 0; bottom: 0; left: 0; top: 0; background-color: white;z-index: 999;">
			<div id="indicator" style="position: absolute; top: 50%; left: 50%; margin-right: -50%; transform: translate(-50%, -50%);">
				<div style="text-align: -webkit-center">
					<img src="/favicon.ico" class="circle" style="width: 150px;">
				</div>
				<div id="load_indicator" style="text-align: center; margin-top: 70%; display: none">
					<svg width="40" height="40" viewBox="0 0 50 50"><path id="loader_ui_spin" transform="rotate(61.2513 25 25)" d="M25,5A20.14,20.14,0,0,1,45,22.88a2.51,2.51,0,0,0,2.49,2.26h0A2.52,2.52,0,0,0,50,22.33a25.14,25.14,0,0,0-50,0,2.52,2.52,0,0,0,2.5,2.81h0A2.51,2.51,0,0,0,5,22.88,20.14,20.14,0,0,1,25,5Z" style="fill: var(--unt-loader-color, #42a5f5);"><animateTransform attributeName="transform" type="rotate" from="0 25 25" to="360 25 25" dur="0.4s" repeatCount="indefinite"></animateTransform></path></svg>
					<br>
					<div id="load_text_info" style="color: black;"></div>
				</div>
			</div>
		</div>

		'. $test_div .'

	</body>
</html>
		';
	} else 
	{
		$content = $is_mobile ? "" : '<div class="row" style="height: 100%; margin-bottom: 0 !important"><div class="col s3"></div><div class="col s6"></div><div class="col s3"></div></div>';

		$scripts_list = '
<script src="'.Project::getDevDomain().'/js/ui.js"></script>
<script src="'.Project::getDevDomain().'/js/poster.js"></script>
<script src="'.Project::getDevDomain().'/js/caches.js"></script>
<script src="'.Project::getDevDomain().'/js/settings.js"></script>
<script src="'.Project::getDevDomain().'/js/pages.js"></script>
<script src="'.Project::getDevDomain().'/js/themes.js"></script>
<script src="'.Project::getDevDomain().'/js/dev.js"></script>
<script src="'.Project::getDevDomain().'/js/messenger.js"></script>
<script src="'.Project::getDevDomain().'/js/codes.js"></script>
';
		if ($userlevel > 0)
		{
			$scripts_list .= '<script src="'.Project::getDevDomain().'/js/management.js"></script>';
		}

		$result = '
<!DOCTYPE html>
<html lang="'.$lang.'">
	<head>
		<title>yunNet.</title>
		<link rel="shortcut icon" href="'.Project::getDefaultDomain().'/favicon.ico"/>
     	<link rel="apple-touch-icon" sizes="180x180" href="'.Project::getDefaultDomain().'/favicon/apple-touch-icon.png">
     	<link rel="icon" type="image/png" href="'.Project::getDefaultDomain().'/favicon/favicon-16x16.png" sizes="16x16">
		<link rel="icon" type="image/png" href="'.Project::getDefaultDomain().'/favicon/favicon-32x32.png" sizes="32x32">
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
	    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1.0"/>
	    <meta name="theme-color" content="#42A5F5">
	    <meta name="description" content="yunNet. - is a social network whose purpose is to return the old principles of social networks, while following the new technologies">
	    <link href="/manifest.json" rel="manifest">
	    <link href="/favicon/apple-touch-icon.png" sizes="180x180" rel="apple-touch-icon">
	    <link href="'.Project::getDevDomain().'/css/ui.css" type="text/css" rel="stylesheet" media="screen,projection"/>
	    <link href="'.Project::getDevDomain().'/css/codes.css" type="text/css" rel="stylesheet" media="screen,projection"/>
	    '.$scripts_list.'
	</head>
	<body>
		<div id="load" style="position: fixed; right: 0; bottom: 0; left: 0; top: 0; background-color: white;z-index: 999;">
			<div id="indicator" style="position: absolute; top: 50%; left: 50%; margin-right: -50%; transform: translate(-50%, -50%);">
				<div style="text-align: -webkit-center">
					<img src="/favicon.ico" class="circle" style="width: 150px;">
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
		'. $test_div .'
	</body>
</html>
';
	}

	return $result;
}

?>