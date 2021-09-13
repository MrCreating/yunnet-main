<?php
session_start();

if (!class_exists('Session'))
	require __DIR__ . '/../../bin/objects/session.php';

/**
 * Register actions
*/

if (isset($_POST['action']))
{
	if ($context->isLogged()) die(json_encode(array('error' => 1)));

	if (!function_exists('is_register_closed'))
		require __DIR__ . '/../../bin/functions/management.php';

	if (is_register_closed())
		die(json_encode(array('closed' => 1)));

	$action = strtolower($_POST['action']);

	if ($action === "get_state")
	{
		die(json_encode(array('state'=>intval($_SESSION["stage"]))));
	}

	if ($action === "get_data")
	{
		$result = [];

		if (isset($_SESSION['first_name']))
			$result['first_name'] = strval($_SESSION['first_name']);

		if (isset($_SESSION['last_name']))
			$result['last_name'] = strval($_SESSION['last_name']);

		die(json_encode($result));
	}
	
	if ($action === "close_session")
	{
		$_SESSION = [];
		die(json_encode(array('response'=>1)));
	}

	if ($action === "continue")
	{
		$currentStage = intval($_SESSION["stage"]);

		/**
		 * Getting first and last names
		*/
		if ($currentStage === 0)
		{
			// checking the user's credentials.
			$first_name = $_POST["first_name"];
			$last_name  = $_POST["last_name"];

			// base checkout before saving
			if (is_empty($first_name) || strlen($first_name) > 32 || preg_match("/[^a-zA-Zа-яА-ЯёЁ'-]/ui", $first_name))
				die(json_encode(array(
					'error_code' => 0
				)));

			if (is_empty($last_name) || strlen($last_name) > 32 || preg_match("/[^a-zA-Zа-яА-ЯёЁ'-]/ui", $last_name))
				die(json_encode(array(
					'error_code' => 1
				)));

			$_SESSION["first_name"]   = capitalize($first_name);
			$_SESSION["last_name"]    = capitalize($last_name);
			$_SESSION["stage"]        = 1;

			die(json_encode(array(
				"stage" => 1
			)));
		}

		/**
		 * Getting email
		*/
		if ($currentStage === 1)
		{
			$email = strval($_POST["email"]);
			if (is_empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 96)
			{
				die(json_encode(array(
					'error_code' => 2
				)));
			}

			if (user_exists($connection, $email))
			{
				die(json_encode(array(
					'error_code' => 2,
					'error_message' => str_replace('%first_name%', htmlspecialchars($_SESSION["first_name"]), $context->lang['found_user'])
				)));
			}

			$_SESSION["email"] = $email;
			$_SESSION["code"]  = rand(100000, 999999);
			$_SESSION["stage"] = 2;

			mail($email, $context->lang['email_activation'], str_replace(array('%username%', '%code%'), array(htmlspecialchars($_SESSION["first_name"]), strval($_SESSION["code"])), $context->lang['email_message']));

			die(json_encode(array(
				"stage" => 2
			)));
		}

		/**
		 * Verifying email
		*/
		if ($currentStage === 2)
		{
			$code = intval($_POST['email_code']);
			if ($code < 100000)
				die(json_encode(array(
					'error_code' => 3
				)));

			if ($code !== intval($_SESSION['code']))
				die(json_encode(array(
					'error_code' => 3
				)));

			$_SESSION["stage"] = 3;
			die(json_encode(array(
				"stage" => 3
			)));
		}

		/**
		 * Checking the passwords
		 * And creating the account
		*/
		if ($currentStage === 3)
		{
			$password        = strval($_POST["password"]);
			$repeat_password = strval($_POST["repeat_password"]);

			if (is_empty($password) || strlen($password) < 6 || strlen($password) > 64)
				die(json_encode(array(
					'error_code' => 5
				)));

			if (strlen($repeat_password) < 6 || strlen($repeat_password) > 64)
				die(json_encode(array(
					'error_code' => 6
				)));

			if ($password !== $repeat_password)
				die(json_encode(array(
					'error_code' => 6,
					'error_message' => str_replace('%first_name%', htmlspecialchars($_SESSION["first_name"]), $context->lang['found_bad_password'])
				)));

			// temporaly constant.
			$gender = 1;

			$_SESSION["password"] = password_hash($password, PASSWORD_DEFAULT);
			$_SESSION["gender"]   = $gender;

			if (user_exists($connection, $_SESSION["email"]))
			{
				unset($_SESSION["code"]);
				unset($_SESSION["email"]);

				$_SESSION["stage"] = 1;
				die(json_encode(array(
					'error_code' => 4,
					'new_stage'  => 1,
					'error_message' => str_replace('%first_name%', htmlspecialchars($_SESSION["first_name"]), $context->lang['found_user'])
				)));
			}

			// creating the same account...
			$res = $connection->prepare("INSERT INTO users.info (first_name, last_name, password, email, gender, settings, registration_date, is_online) VALUES (:first_name, :last_name, :password, :email, :gender, :settings, :reg_time, :online_time);");

			$reg_time = time();
			$settings = json_encode([
						'lang'    => 'ru',
						'privacy' => [
							'can_write_on_wall' => 0,
							'can_write_messages' => 0,
							'can_invite_to_chats' => 0,
							'can_comment_posts' => 0
						],
						'notifications' => [
							'sound' => 1,
							'notifications' => 0
						],
						'closed_profile' => 0
					]);

			$first_name = strval($_SESSION['first_name']);
			$last_name  = strval($_SESSION['last_name']);
			$password   = strval($_SESSION['password']);
			$email      = strval($_SESSION['email']);

			$res->bindParam(":first_name",  $first_name, PDO::PARAM_STR);
			$res->bindParam(":last_name",   $last_name,  PDO::PARAM_STR);
			$res->bindParam(":password",    $password,   PDO::PARAM_STR);
			$res->bindParam(":email",       $email,      PDO::PARAM_STR);
			$res->bindParam(":gender",      $gender,     PDO::PARAM_INT);
			$res->bindParam(":settings",    $settings,   PDO::PARAM_STR);
			$res->bindParam(":reg_time",    $reg_time,   PDO::PARAM_INT);
			$res->bindParam(":online_time", $reg_time,   PDO::PARAM_INT);
			if (!$res->execute())
			{
				die(json_encode(array(
					'error_code' => 10
				)));
			}

			$res = $connection->prepare("SELECT LAST_INSERT_ID();");
			$res->execute();

			$user_id = intval($res->fetch(PDO::FETCH_ASSOC)["LAST_INSERT_ID()"]);

			$_SESSION["stage"] = 4;
			$_SESSION["user_saved"] = $user_id;

			die(json_encode(array(
				'stage' => 4
			)));
		}

		/**
		 * Auto auth and thanks
		*/
		if ($currentStage === 4)
		{
			$user_id = intval($_SESSION["user_saved"]);

			Session::start($user_id)->setAsCurrent();

			// ok!!!!
			die(json_encode(array(
				'stage' => 5
			)));
		}
	}
}
?>