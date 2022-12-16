<?php

use unt\objects\Context;
use unt\objects\Request;

require_once __DIR__ . '/../../bin/functions/theming.php';

if (isset(Request::get()->data["action"]))
{
	$action = strtolower(Request::get()->data['action']);

	if (!Context::get()->allowToUseUnt()) die(json_encode(array('error' => 1)));

	switch ($action) {
		case 'get_themes':
			$themes = get_themes(\unt\platform\DataBaseManager::getConnection(), Context::get()->getCurrentUser()->getId(), intval(Request::get()->data['count']), intval(Request::get()->data['offset']));
			$result = [];

			foreach ($themes as $index => $theme) {
				$result[] = $theme->toArray();
			}

			die(json_encode(array('response' => $result)));
		break;

		case 'apply_theme':
			die(json_encode(array('success'=>intval(apply_theme(\unt\platform\DataBaseManager::getConnection(), Context::get()->getCurrentUser()->getId(), (new AttachmentsParser())->getObject(Request::get()->data['credentials']))))));
		break;

		case 'reset_theme':
			die(json_encode(array('success'=>intval(apply_theme(\unt\platform\DataBaseManager::getConnection(), Context::get()->getCurrentUser()->getId())))));
		break;

		case 'create_theme':
			$title = trim(strval(Request::get()->data['theme_title']));
			$desc  = trim(strval(Request::get()->data['theme_description']));
			$is_private = intval(boolval(intval(Request::get()->data['is_private'])));

			$result = create_theme(\unt\platform\DataBaseManager::getConnection(), Context::get()->getCurrentUser()->getId(), $title, $desc, $is_private);
			if (!$result)
				die(json_encode(array('error'=>1)));

			die(json_encode($result->toArray()));
		break;

		case 'delete_theme':
			$theme_id = intval(Request::get()->data['theme_id']);

			$result = delete_theme(\unt\platform\DataBaseManager::getConnection(), Context::get()->getCurrentUser()->getId(), intval($_SESSION['user_id']), $theme_id);
			if (!$result)
				die(json_encode(array('error'=>1)));

			die(json_encode(array('success'=>1)));
		break;

		case 'update_theme_info':
			$theme = new Theme(intval(Request::get()->data['owner_id']), intval(Request::get()->data['theme_id']));
			if (!$theme->valid())
				die(json_encode(array('error'=>1)));

			$result = update_theme(\unt\platform\DataBaseManager::getConnection(), $theme, Context::get()->getCurrentUser()->getId(), strval(Request::get()->data["new_title"]), strval(Request::get()->data["new_description"]), intval(Request::get()->data["private_mode"]));
			
			if (!$result)
				die(json_encode(array('error'=>1)));

			die(json_encode(array('success'=>1)));
		break;

		case 'update_theme_code':
			$theme_id  = intval(Request::get()->data['theme_id']);
			$owner_id  = intval(Request::get()->data['owner_id']);
			$code_type = strtolower(trim(Request::get()->data['code_type']));
			$new_code  = trim(strval(Request::get()->data['new_code']));

			$theme = new Theme($owner_id, $theme_id);
			if (!$theme->valid())
				die(json_encode(array('error'=>1)));

			$result = update_theme_code($theme, Context::get()->getCurrentUser()->getId(), $code_type, $new_code);
			if ($result === true)
				die(json_encode(array('success'=>1)));

			if ($result === false)
				die(json_encode(array('error'=>1)));

			die(json_encode(array('error'=>1, 'message'=>strval($result))));
		break;
		
		default:
		break;
	}

	die(json_encode(array('error' => 1)));
}

?>