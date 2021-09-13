<?php

// handle themes actions here!!!
if (isset($_POST["action"]))
{
	if (!$context->isLogged()) die(json_encode(array('unauth'=>1)));
	if ($context->getCurrentUser()->isBanned()) die(json_encode(array('error' => 1)));

	$action = strtolower($_POST['action']);
	if ($action === 'get_themes')
	{
		if (!function_exists('get_themes'))
			require __DIR__ . '/../../bin/functions/theming.php';

		$themes = get_themes($connection, $context->getCurrentUser()->getId(), intval($_POST['count']), intval($_POST['offset']));
		$result = [];

		foreach ($themes as $index => $theme) {
			$result[] = $theme->toArray();
		}

		die(json_encode(array('response'=>$result)));
	}
	if ($action === 'apply_theme')
	{
		if (!function_exists('apply_theme'))
			require __DIR__ . '/../../bin/functions/theming.php';
		if (!class_exists('Theme'))
			require __DIR__ . '/../../bin/objects/theme.php';

		die(json_encode(array('success'=>intval(apply_theme($connection, $context->getCurrentUser()->getId(), (new AttachmentsParser())->getObject($_POST['credentials']))))));
	}
	if ($action === 'reset_theme')
	{
		if (!function_exists('apply_theme'))
			require __DIR__ . '/../../bin/functions/theming.php';

		die(json_encode(array('success'=>intval(apply_theme($connection, $context->getCurrentUser()->getId())))));
	}
	if ($action === 'create_theme')
	{
		if (!function_exists('create_theme'))
			require __DIR__ . '/../../bin/functions/theming.php';

		$title = trim(strval($_POST['theme_title']));
		$desc  = trim(strval($_POST['theme_description']));
		$is_private = intval(boolval(intval($_POST['is_private'])));

		$result = create_theme($connection, $context->getCurrentUser()->getId(), $title, $desc, $is_private);
		if (!$result)
			die(json_encode(array('error'=>1)));

		die(json_encode($result->toArray()));
	}
	if ($action === 'delete_theme')
	{
		if (!function_exists('delete_theme'))
			require __DIR__ . '/../../bin/functions/theming.php';

		$theme_id = intval($_POST['theme_id']);

		$result = delete_theme($connection, $context->getCurrentUser()->getId(), intval($_SESSION['user_id']), $theme_id);
		if (!$result)
			die(json_encode(array('error'=>1)));

		die(json_encode(array('success'=>1)));
	}
	if ($action === 'update_theme_info')
	{
		if (!function_exists('update_theme'))
			require __DIR__ . "/../../bin/functions/theming.php";
		if (!class_exists('Theme'))
			require __DIR__ . '/../../bin/objects/theme.php';

		$theme = new Theme(intval($_POST['owner_id']), intval($_POST['theme_id']));
		if (!$theme->valid())
			die(json_encode(array('error'=>1)));

		$result = update_theme($connection, $theme, $context->getCurrentUser()->getId(), strval($_POST["new_title"]), strval($_POST["new_description"]), intval($_POST["private_mode"]));
		
		if (!$result)
			die(json_encode(array('error'=>1)));

		die(json_encode(array('success'=>1)));
	}
	if ($action === "update_theme_code")
	{
		if (!function_exists('update_theme_code'))
			require __DIR__ . "/../../bin/functions/theming.php";
		if (!class_exists('Theme'))
			require __DIR__ . '/../../bin/objects/theme.php';

		$theme_id  = intval($_POST['theme_id']);
		$owner_id  = intval($_POST['owner_id']);
		$code_type = strtolower(trim($_POST['code_type']));
		$new_code  = trim(strval($_POST['new_code']));

		$theme = new Theme($connection, 'theme'.$owner_id.'_'.$theme_id);
		if (!$theme->isValid)
			die(json_encode(array('error'=>1)));

		$result = update_theme_code($theme, $context->getCurrentUser()->getId(), $code_type, $new_code);
		if ($result === true)
			die(json_encode(array('success'=>1)));

		if ($result === false)
			die(json_encode(array('error'=>1)));

		die(json_encode(array('error'=>1, 'message'=>strval($result))));
	}
}

?>