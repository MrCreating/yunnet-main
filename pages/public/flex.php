<?php

/**
 *
 * file to get users info
 *
*/

header("Content-Type: application/json");
//if ($current_user->level < 4) die(json_encode(array('error'=>1)));

// current action is a POST value
$action = strtolower($_POST['action']);

// redirecting if it needed
if ($action === "") die(header("Location: /"));

// api whitelist
//$unblocked_users = [1, 2, 3, 4, 5, 6, 8, 36, 50, 51, 52];
//if (!in_array($context->getCurrentUser()->getId(), $unblocked_users) && $context->isLogged()) die(json_encode(array('blocked'=>1)));

/**
 * API blocking test
*/
if ($action === "test")
{
	if (!function_exists('is_project_closed'))
		require __DIR__ . '/../../bin/functions/management.php';

	$result = is_project_closed();

	if (!$context->isLogged() || $context->getCurrentUser()->getAccessLevel() === 4)
		$result = 0;

	die(json_encode(array('blocked' => $result)));
}

/**
 * Gets the value from current language,
 * Check values in bin/languages/<CODE>
*/
if ($action === "get_language_value")
{
	$value         = strtolower($_POST["value"]);
	$languageValue = $context->getLanguage()->{$value};
	if ($value === '*')
	{
		die(json_encode($context->getLanguage()));
	}

	if (!$languageValue) die(json_encode(array('error'=>1)));

	die(json_encode(array('response'=>$languageValue)));
}

/**
 * Gets the user instance by id
 * and returns it array object
*/
if ($action === "get_user_data")
{
	if (!function_exists('get_database_connection')) require __DIR__ . '/../../bin/base_functions.php';
	if (!class_exists('Entity')) require __DIR__ . '/../../bin/objects/entities.php';

	$entity_id = intval($_POST["id"]);
	$fields    = strval($_POST["fields"]);

	if ($entity_id === 0) $entity_id = intval($context->getCurrentUser() === NULL ? 0 : $context->getCurrentUser()->getId());
	if ($context->getCurrentUser() && $context->getCurrentUser()->isBanned()) $entity_id = intval($context->getCurrentUser()->getId());

	$user = $entity_id > 0 ? 
				new User($entity_id) 
			:
				new Bot($entity_id*-1);

	if (!$user->valid()) die(json_encode(array("error" => 1)));
	die(json_encode(array("response" => $user->toArray($fields))));
}

/**
 * Resolves id from screen_name
*/
if ($action === "get_user_data_by_link")
{
	$screen_name = strtolower($_POST["screen_name"]);
	if ($context->getCurrentUser() && $context->getCurrentUser()->isBanned())
		$screen_name = 'id' . $context->getCurrentUser()->getId();

	if (!function_exists('get_database_connection')) require __DIR__ . '/../../bin/base_functions.php';
	if (is_empty($screen_name)) die(json_encode(array("error"=>1)));

	$result = resolve_id_by_name(get_database_connection(), $screen_name);
	if (!$result) die(json_encode(array("error"=>1)));

	die(json_encode(array("id"=>intval($result["id"]))));
}

/**
 * Gets the user settings
*/
if ($action === "get_settings")
{
	if (!function_exists('get_menu_items_data'))
		require __DIR__ . '/../../bin/functions/theming.php';

	if (!$context->isLogged()) die(json_encode(array('unauth' => 1)));
	if ($context->getCurrentUser()->isBanned()) die(json_encode(array('error' => 1)));

	// check the settings
	$settings = $context->getCurrentUser()->getSettings()->getValues();

	// prepared response
	$result = [
		'account' => [
			'language'  => strval($settings->lang),
			'is_closed' => boolval($settings->closed_profile),
			'balance'   => [
				'cookies'      => intval($context->getCurrentUser()->getBalance()->cookies),
				'half_cookies' => intval($context->getCurrentUser()->getBalance()->halfCookies)
			],
		],
		'privacy' => [
			'can_write_messages'  => intval($settings->privacy->can_write_messages),
			'can_write_on_wall'   => intval($settings->privacy->can_write_on_wall),
			'can_invite_to_chats' => intval($settings->privacy->can_invite_to_chats),
			'can_comment_posts'   => intval($settings->privacy->can_comment_posts)
		],
		'push'    => [
			'notifications' => boolval($settings->notifications->notifications),
			'sound'         => boolval($settings->notifications->sound)
		],
		'theming'           => [
			'menu_items'    => get_menu_items_data($connection, $context->getCurrentUser()->getId()),
			'new_design'    => $context->getCurrentUser()->isNewDesignUsed(),
			'backButton'    => true,
			'js_allowed'    => themes_js_allowed($connection, $context->getCurrentUser()->getId()),
			'current_theme' => get_current_theme_credentials($connection, $context->getCurrentUser()->getId())
		]
	];	

	die(json_encode($result));
}

/**
 * Publishes a post.
*/
if ($action === "publish_post")
{
	// only logged users can access this.
	if (!$context->isLogged()) die(json_encode(array('unauth'=>1)));
	if ($context->getCurrentUser()->isBanned()) die(json_encode(array('error'=>1)));

	// finding the posts api.
	if (!function_exists('create_post'))
		require __DIR__ . '/../../bin/functions/wall.php';

	$text = strval($_POST['text']);
	$atts = strval($_POST['attachments']);
	$wall = intval($_POST['wall_id']) !== 0 ? intval($_POST['wall_id']) : $context->getCurrentUser()->getId();

	$result = create_post($connection, $context->getCurrentUser()->getId(), $wall, $text, $atts);

	// if not post created - show this.
	if (!$result) die(json_encode(array('error'=>1)));

	// fetching the new post data
	$post = get_post_by_id($connection, intval($wall), intval($result), $context->getCurrentUser()->getId());

	// if not post found - it not be created.
	if (!$post->valid()) die(json_encode(array('error'=>1)));

	// all data is ok!
	die(json_encode(array('response'=>$post->toArray())));
}

/**
 * Edit the post
*/
if ($action === "edit_post")
{
	// only logged users can access this.
	if (!$context->isLogged()) die(json_encode(array('unauth'=>1)));
	if ($context->getCurrentUser()->isBanned()) die(json_encode(array('error'=>1)));

	// finding the posts api.
	if (!function_exists('update_post_data'))
		require __DIR__ . '/../../bin/functions/wall.php';

	$text = strval($_POST['text']);
	$atts = strval($_POST['attachments']);
	$wall = intval($_POST['wall_id']);
	$post = intval($_POST['post_id']);

	$result = update_post_data($context->getConnection(), $context->getCurrentUser()->getId(), $wall, $post, $text, $atts);

	// if not post updated - show this.
	if (!$result) die(json_encode(array('error'=>1)));

	// fetching the new post data
	$post = get_post_by_id($context->getConnection(), intval($wall), intval($post), $context->getCurrentUser()->getId());

	// if not post found - it not be created.
	if (!$post->valid()) die(json_encode(array('error'=>1)));

	// all data is ok!
	die(json_encode(array('response'=>$post->toArray())));
}

/**
 * Getting chat info by peer
*/
if ($action === "get_chat_by_peer")
{
	// only logged users can access this.
	if (!$context->isLogged()) die(json_encode(array('unauth'=>1)));
	if ($context->getCurrentUser()->isBanned()) die(json_encode(array('error'=>1)));

	// funding the messages api.
	if (!function_exists('parse_id_from_string'))
		require __DIR__ . '/../../bin/functions/messages.php';

	$sel = strval($_POST['peer_id']);
	$chat_data = parse_id_from_string($sel);

	// if format is invalid
	if (!$chat_data) die(json_encode(array('error'=>1)));

	$uid = intval(get_uid_by_lid($context->getConnection(), $chat_data['chat_id'], $chat_data['is_bot'], $context->getCurrentUser()->getId()));
	$dialog = get_chat_data_by_uid($context->getConnection(), $uid, $context->getCurrentUser()->getId(), $chat_data);
	if (!$dialog) die(json_encode(array('error'=>1)));

	die(json_encode($dialog));
}

/**
 * Getting chat permissions
*/
if ($action === "get_chat_permissions")
{
	// only logged users can access this.
	if (!$context->isLogged()) die(json_encode(array('unauth'=>1)));
	if ($context->getCurrentUser()->isBanned()) die(json_encode(array('error'=>1)));

	if (!function_exists('parse_id_from_string'))
		require __DIR__ . '/../../bin/functions/messages.php';
	if (!class_exists('Chat'))
		require __DIR__ . '/../../bin/objects/chats.php';

	$chat_data = parse_id_from_string($_POST['peer_id']);
	if (!$chat_data)
		die(json_encode(array('error'=>1)));

	$sel    = intval($chat_data["chat_id"]);
	$is_bot = boolval($chat_data["is_bot"]);
	$uid    = get_uid_by_lid($connection, $sel, $is_bot, $context->getCurrentUser()->getId());
	if (!$uid)
		die(json_encode(array('error'=>1)));

	$chat = new Chat($connection, $uid);
	if (!$chat->isValid)
		die(json_encode(array('error'=>1)));

	$members = $chat->getMembers(true);
	$me = $members["users"]["user_".$context->getCurrentUser()->getId()];
	if (!$me || $me['flags']['is_kicked']) die(json_encode(array('error'=>1)));

	$permissions = $chat->getPermissions();
	die(json_encode($permissions->getAll()));
}

/**
 * getting my permissions level
*/
if ($action === "get_my_permissions_level")
{
	// only logged users can access this.
	if (!$context->isLogged()) die(json_encode(array('unauth'=>1)));
	if ($context->getCurrentUser()->isBanned()) die(json_encode(array('error'=>1)));

	if (!function_exists('parse_id_from_string'))
		require __DIR__ . '/../../bin/functions/messages.php';
	if (!class_exists('Chat'))
		require __DIR__ . '/../../bin/objects/chats.php';

	$chat_data = parse_id_from_string($_POST['peer_id']);
	if (!$chat_data)
		die(json_encode(array('error'=>1)));

	$sel    = intval($chat_data["chat_id"]);
	$is_bot = boolval($chat_data["is_bot"]);
	$uid    = get_uid_by_lid($connection, $sel, $is_bot, $context->getCurrentUser()->getId());
	if (!$uid)
		die(json_encode(array('error'=>1)));

	$chat = new Chat($connection, $uid);
	if (!$chat->isValid)
		die(json_encode(array('error'=>1)));

	$members = $chat->getMembers(true);
	$me = $members["users"]["user_".$context->getCurrentUser()->getId()];
	if (!$me || $me['flags']['is_kicked']) die(json_encode(array('error'=>1)));

	die(json_encode(array('level'=>intval($me["flags"]["level"]))));
}

/**
 * Receives a user's friends list.
*/
if ($action === "get_friends")
{
	// only logged users can access this.
	if (!$context->isLogged()) die(json_encode(array('unauth'=>1)));
	if ($context->getCurrentUser()->isBanned()) die(json_encode(array('error'=>1)));

	// finding the API
	if (!function_exists('get_friends_list'))
		require __DIR__ . '/../../bin/functions/users.php';

	$user_id = intval($_POST['user_id']) > 0 ? intval($_POST['user_id']) : $context->getCurrentUser()->getId();

	if (!user_exists($connection, $user_id) || !can_access_closed($connection, $context->getCurrentUser()->getId(), $user_id) || in_blacklist($connection, $user_id, $context->getCurrentUser()->getId()))
		die(json_encode(array('error'=>1)));

	$section = strval($_POST['section']);
	if (!in_array($section, ['friends', 'subscribers', 'outcoming'])) $section = 'friends';

	$friends_list = get_friends_list($connection, $user_id, $section, true);
	$result = [];

	foreach ($friends_list as $key => $friend) {
		$result[] = $friend->toArray('*');
	}

	die(json_encode($result));
}

/**
 * Get current user counters
*/
if ($action === 'get_counters')
{
	// only logged users can access this.
	if (!$context->isLogged()) die(json_encode(array('unauth'=>1)));
	if ($context->getCurrentUser()->isBanned()) die(json_encode(array('error'=>1)));

	if (!function_exists('get_counters'))
		require __DIR__ . '/../../bin/functions/users.php';

	$result = get_counters($connection, $context->getCurrentUser()->getId());

	die(json_encode($result));
}

/**
 * Gets attachment info
*/
if ($action === 'get_attachment_info')
{
	if ($context->getCurrentUser() && $context->getCurrentUser()->isBanned()) die(json_encode(array('error'=>1)));

	$attachmentCredentials = strval(trim($_POST['credentials']));

	if (!class_exists('AttachmentsParser'))
		require __DIR__ . '/../../bin/objects/attachment.php';

	$resultedAttachment = (new AttachmentsParser())->getObject($attachmentCredentials);
	if (!$resultedAttachment)
		die(json_encode(array('error'=>1)));

	die(json_encode(array('attachment'=>$resultedAttachment->toArray())));
}

/**
 * Set the user's status
*/
if ($action === "set_status")
{
	if (!$context->isLogged()) die(json_encode(array('unauth'=>1)));
	if ($context->getCurrentUser()->isBanned()) die(json_encode(array('error'=>1)));

	$requested_status = trim(strval($_POST['new_status']));
	if (strlen($requested_status) > 128)
		die(json_decode(array('error'=>1)));

	if (!function_exists('set_user_status'))
		require __DIR__ . '/../../bin/functions/wall.php';

	$result = set_user_status($connection, $context->getCurrentUser()->getId(), $requested_status);
	if ($result)
		die(json_encode(array(
			'success' => 1,
			'status'  => $requested_status
		)));

	die(json_encode(array('error'=>1)));
}

if (!$dev) die(json_encode(array('flex'=>1)));
?>