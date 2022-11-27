<?php
session_start();

require_once __DIR__ . '/../../bin/objects/Session.php';

// restore actions!
if (isset(Request::get()->data['action']))
{
	$action = strtolower(Request::get()->data['action']);

	if ($context->allowToUseUnt()) die(json_encode(array('error' => 1)));

	switch ($action) {
		case 'get_state':
			die(json_encode(array('state' => intval($_SESSION["restore_stage"]))));
		break;

		case 'close_session':
			$_SESSION = [];
		
			die(json_encode(array('response' => 1)));
		break;

		case 'check_query':
			$query = strval(Request::get()->data["query"]);
			$exp_t = intval(time());

			$saved_query = strval($_SESSION['restore_query']);
			if ($query === $saved_query)
			{
				$_SESSION["restore_stage"] = 3;
				$_SESSION["restore_query"] = NULL;
				$_SESSION["expire_query"]  = NULL;
			
				die(json_encode(array('stage' => 3)));
			}

			if ($exp_t > intval($_SESSION['expire_query']))
			{
				$_SESSION = [];

				die(json_encode(array('stage' => 0)));
			}

			die(json_encode(array('error' => 1)));
		break;

		case 'continue':
			$currentStage = intval($_SESSION["restore_stage"]);

			/**
			 * Getting email
			*/
			if ($currentStage === 0)
			{
				$email = strval(Request::get()->data["email"]);
				if (unt\functions\is_empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 96)
				{
					die(json_encode(array(
						'error_code' => 2
					)));
				}

				$_SESSION["restore_email"] = $email;
				$_SESSION["restore_stage"] = 1;

				die(json_encode(array(
					"stage" => 1
				)));
			}

			/**
			 * Getting last name and send email for recoveryinh
			*/
			if ($currentStage === 1)
			{
				$last_name  = Request::get()->data["last_name"];
				if (unt\functions\is_empty($last_name) || strlen($last_name) > 32 || preg_match("/[^a-zA-Zа-яА-ЯёЁ'-]/ui", $last_name))
					die(json_encode(array(
						'error_code' => 1
					)));

				$email = $_SESSION["restore_email"];
				$user  = User::findByEMAIL($email);

				if (!$user || $user->getLastName() !== $last_name)
					die(json_encode(array(
						'error_code' => 1,
						'error_message' => Context::get()->getLanguage()->i_not_found_user
					)));

				$key = str_shuffle('abcdefghijklmnopqrstuvwxyz0123456789abcdefghijklmnopqrstuvwxyz0123456789abcdefghijklmnopqrstuvwxyz0123456789abcdefghijklmnopqrstuvwxyz');
				$link = 'https://yunnet.ru/restore?query='.$key;

				$text = str_replace('%ip%', $_SERVER['REMOTE_ADDR'], str_replace('%fn%', $user->getFirstName(), str_replace('%link%', $link, Context::get()->getLanguage()->restore_text)));

				$_SESSION["restore_stage"] = 2;
				$_SESSION["restore_query"] = $key;
				$_SESSION["expire_query"]  = time() + 86400;
				$_SESSION["waiting_id"]    = $user->getId();

				$letter = new Letter(Context::get()->getLanguage()->restore_account, $text);
				if ($letter->send($email))
				{
					die(json_encode(array(
						"stage" => 2
					)));
				}

				die(json_encode(array(
					'error_code' => 1
				)));
			}

			/**
			 * Setting up the passwords
			*/
			if ($currentStage === 3)
			{
				$password        = strval(Request::get()->data["password"]);
				$repeat_password = strval(Request::get()->data["repeat_password"]);

				if (strlen($password) < 6 || strlen($password) > 64)
					die(json_encode(array(
						'error_code' => 5
					)));

				if (strlen($password) < 6 || strlen($password) > 64)
					die(json_encode(array(
						'error_code' => 6
					)));

				if ($password !== $repeat_password)
					die(json_encode(array(
						'error_code' => 6,
						'error_message' => Context::get()->getLanguage()->found_bad_password
					)));

				$user_id = intval($_SESSION{"waiting_id"});
				if ($user_id > 0)
				{
					$user = new User($user_id);

					if ($user->getSettings()->getSettingsGroup('security')->setPassword($password))
					{
						$_SESSION = [];
						Session::start($user_id)->setAsCurrent();
						
						die(json_encode(array(
							"stage" => 4
						)));
					}
				}
			}
		break;
		
		default:
		break;
	}

	die(json_encode(array('error' => 1)));
}

?>