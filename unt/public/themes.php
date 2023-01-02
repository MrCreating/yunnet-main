<?php

use unt\objects\Context;
use unt\objects\Request;
use unt\objects\Theme;
use unt\parsers\AttachmentsParser;

if (isset(Request::get()->data["action"]))
{
	$action = strtolower(Request::get()->data['action']);

	if (!Context::get()->allowToUseUnt()) die(json_encode(array('error' => 1)));

	switch ($action) {
		case 'get_themes':
			$themes = Theme::getList(intval(Request::get()->data['count']), intval(Request::get()->data['offset']));
			$result = [];

			foreach ($themes as $index => $theme) {
				$result[] = $theme->toArray();
			}

			die(json_encode(array('response' => $result)));
		break;

		case 'apply_theme':
			die(json_encode(array('success' => (new AttachmentsParser())->getObject(Request::get()->data['credentials'])->setAsCurrent())));
            break;

		case 'reset_theme':
			die(json_encode(array('success' => Theme::reset())));
            break;

		case 'create_theme':
			$title = trim(strval(Request::get()->data['theme_title']));
			$desc  = trim(strval(Request::get()->data['theme_description']));
			$is_private = intval(boolval(intval(Request::get()->data['is_private'])));

			$result = Theme::create($title, $desc, $is_private);
			if (!$result)
				die(json_encode(array('error' => 1)));

			die(json_encode($result->toArray()));
		break;

		case 'delete_theme':
			$theme_id = intval(Request::get()->data['theme_id']);

            $theme = new Theme($_SESSION['user_id'], $theme_id);

			$result = $theme->delete();
            if (!$result)
				die(json_encode(array('error'=>1)));

			die(json_encode(array('success'=>1)));
		break;

		case 'update_theme_info':
			$theme = Theme::findById($_SESSION['user_id'], intval(Request::get()->data['theme_id']));
			if (!$theme)
				die(json_encode(array('error' => 1)));

			if (!$theme->setTitle(strval(Request::get()->data["new_title"]))->setDescription(strval(Request::get()->data["new_description"]))->setPrivate(intval(Request::get()->data["private_mode"]))->apply())
				die(json_encode(array('error' => 1)));

			die(json_encode(array('success' => 1)));
		break;

		case 'update_theme_code':
			$theme_id  = intval(Request::get()->data['theme_id']);
			$code_type = strtolower(trim(Request::get()->data['code_type']));
			$new_code  = trim(strval(Request::get()->data['new_code']));

			$theme = Theme::findById($_SESSION['user_id'], $theme_id);
			if (!$theme)
				die(json_encode(array('error' => 1)));

            $result = false;
            switch ($code_type)
            {
                case 'js':
                    $result = $theme->setJSCode($new_code);
                    break;
                case 'css':
                    $result = $theme->setCSSCode($new_code);
                    break;
                default:
                    break;
            }

			if ($result === true)
				die(json_encode(array('success' => 1)));

			if ($result === false)
				die(json_encode(array('error' => 1)));

			die(json_encode(array('error' => 1, 'message' => strval($result))));
		
		default:
		break;
	}

	die(json_encode(array('error' => 1)));
}

?>