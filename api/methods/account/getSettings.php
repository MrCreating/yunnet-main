<?php

/**
 * Getting settings of the account
*/

if ($only_params)
	return $params;

if (!in_array('4', $context['permissions']))
	die(create_json_error(-1, "Authentication failed: this access key don't have permission to call this method"));

if ($context['owner_object']->getType() === "bot")
	die(create_json_error(-10, 'This method is not available from bot account'));

// check the settings
$settings = $context['owner_object']->getSettings()->getValues();

if (!function_exists('get_menu_items_data'))
	require __DIR__ . '/../../../bin/functions/theming.php';

// prepared response
$result = [
	'account' => [
		'language'  => strval($settings->lang),
		'is_closed' => boolval($settings->closed_profile),
		'balance'   => [
			'cookies'      => intval($context['owner_object']->cookies),
			'half_cookies' => intval($context['owner_object']->halfCookies)
		]
	],
	'privacy' => [
		'can_write_messages'  => intval($settings->privacy->can_write_messages),
		'can_write_on_wall'   => intval($settings->privacy->can_write_on_wall),
		'can_invite_to_chats' => intval($settings->privacy->can_invite_to_chats),
		'can_comment_posts'   => intval($settings->privacy->can_comment_posts)
	],
	'push' => [
		'notifications' => boolval($settings->notifications->notifications),
		'sound'         => boolval($settings->notifications->sound)
	],
	'theming' => [
		'menu_items'    => get_menu_items_data($connection, $context['user_id']),
		'backButton'    => true,
		'js_allowed'    => themes_js_allowed($connection, $context['user_id']),
		'current_theme' => get_current_theme_credentials($connection, $context['user_id'])
	]
];

die(
	json_encode(['response' => $result])
);
?>