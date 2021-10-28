<?php

require_once __DIR__ . '/../../bin/functions/theming.php';
require_once __DIR__ . '/../../bin/objects/theme.php';

if (isset($_POST["action"]))
{
	$action = strtolower($_POST['action']);

	if (!$context->allowToUseUnt()) die(json_encode(array('error' => 1)));

	switch ($action) {
		case 'get_themes':
			$themes = get_themes($connection, $context->getCurrentUser()->getId(), intval($_POST['count']), intval($_POST['offset']));
			$result = [];

			foreach ($themes as $index => $theme) {
				$result[] = $theme->toArray();
			}

			die(json_encode(array('response' => $result)));
		break;

		case 'apply_theme':
			die(json_encode(array('success'=>intval(apply_theme($connection, $context->getCurrentUser()->getId(), (new AttachmentsParser())->getObject($_POST['credentials']))))));
		break;

		case 'reset_theme':
			die(json_encode(array('success'=>intval(apply_theme($connection, $context->getCurrentUser()->getId())))));
		break;

		case 'create_theme':
			$title = trim(strval($_POST['theme_title']));
			$desc  = trim(strval($_POST['theme_description']));
			$is_private = intval(boolval(intval($_POST['is_private'])));

			$result = create_theme($connection, $context->getCurrentUser()->getId(), $title, $desc, $is_private);
			if (!$result)
				die(json_encode(array('error'=>1)));

			die(json_encode($result->toArray()));
		break;

		case 'delete_theme':
			$theme_id = intval($_POST['theme_id']);

			$result = delete_theme($connection, $context->getCurrentUser()->getId(), intval($_SESSION['user_id']), $theme_id);
			if (!$result)
				die(json_encode(array('error'=>1)));

			die(json_encode(array('success'=>1)));
		break;

		case 'update_theme_info':
			$theme = new Theme(intval($_POST['owner_id']), intval($_POST['theme_id']));
			if (!$theme->valid())
				die(json_encode(array('error'=>1)));

			$result = update_theme($connection, $theme, $context->getCurrentUser()->getId(), strval($_POST["new_title"]), strval($_POST["new_description"]), intval($_POST["private_mode"]));
			
			if (!$result)
				die(json_encode(array('error'=>1)));

			die(json_encode(array('success'=>1)));
		break;

		case 'update_theme_code':
			$theme_id  = intval($_POST['theme_id']);
			$owner_id  = intval($_POST['owner_id']);
			$code_type = strtolower(trim($_POST['code_type']));
			$new_code  = trim(strval($_POST['new_code']));

			$theme = new Theme($owner_id, $theme_id);
			if (!$theme->valid())
				die(json_encode(array('error'=>1)));

			$result = update_theme_code($theme, $context->getCurrentUser()->getId(), $code_type, $new_code);
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