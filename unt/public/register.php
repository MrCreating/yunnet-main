<?php
session_start();

require_once __DIR__ . '/../../bin/objects/Session.php';
require_once __DIR__ . '/../../bin/functions/management.php';

/**
 * Register actions
*/

if (isset(Request::get()->data['action']))
{
	$action = strtolower(Request::get()->data['action']);
	
	if (Context::get()->allowToUseUnt()) die(json_encode(array('error' => 1)));
	if (is_register_closed()) die(json_encode(array('closed' => 1)));

	switch ($action) {
		case 'get_state':
			die(json_encode(array('state' => intval($_SESSION["stage"]))));
		break;

		case 'get_data':
			$result = [];

			if (isset($_SESSION['first_name']))
				$result['first_name'] = strval($_SESSION['first_name']);

			if (isset($_SESSION['last_name']))
				$result['last_name'] = strval($_SESSION['last_name']);

			die(json_encode($result));
		break;

		case 'close_session':
			$_SESSION = [];
		
			die(json_encode(array('response' => 1)));
		break;

		case 'continue':
			$currentStage = intval($_SESSION["stage"]);

			/**
			 * Getting first and last names
			*/
			if ($currentStage === 0)
			{
				// checking the user's credentials.
				$first_name = Request::get()->data["first_name"];
				$last_name  = Request::get()->data["last_name"];

				// base checkout before saving
				if (unt\functions\is_empty($first_name) || strlen($first_name) > 32 || preg_match("/[^a-zA-Zа-яА-ЯёЁ'-]/ui", $first_name))
					die(json_encode(array(
						'error_code' => 0
					)));

				if (unt\functions\is_empty($last_name) || strlen($last_name) > 32 || preg_match("/[^a-zA-Zа-яА-ЯёЁ'-]/ui", $last_name))
					die(json_encode(array(
						'error_code' => 1
					)));

				$_SESSION["first_name"]   = unt\functions\capitalize($first_name);
				$_SESSION["last_name"]    = unt\functions\capitalize($last_name);

				die(json_encode(array(
					"stage" => ($_SESSION["stage"] = 1)
				)));
			}

			/**
			 * Getting email
			*/
			if ($currentStage === 1)
			{
				$email = strval(trim(Request::get()->data["email"]));
				if (unt\functions\is_empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 96)
				{
					die(json_encode(array(
						'error_code' => 2
					)));
				}

				if (User::findByEMAIL($email) !== NULL)
				{
					die(json_encode(array(
						'error_code' => 2,
						'error_message' => Context::get()->getLanguage()->found_user
					)));
				}

				$_SESSION["email"] = $email;
				$_SESSION["code"]  = rand(100000, 999999);
				$_SESSION["next_code_time"] = time() + 300;

				$letter = new Letter(Context::get()->getLanguage()->email_activation, str_replace(array('%username%', '%code%'), array(htmlspecialchars($_SESSION["first_name"]), strval($_SESSION["code"])), Context::get()->getLanguage()->email_message));

				if ($letter->send($email))
				{
					die(json_encode(array(
						"stage" => ($_SESSION["stage"] = 2)
					)));
				} else
				{
					die(json_encode(array('error' => 1)));
				}
			}

			/**
			 * Verifying email
			*/
			if ($currentStage === 2)
			{
				$code = intval(Request::get()->data['email_code']);
				if ($code < 100000 || $code > 999999)
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
				$password        = strval(Request::get()->data["password"]);
				$repeat_password = strval(Request::get()->data["repeat_password"]);

				if (unt\functions\is_empty($password) || strlen($password) < 6 || strlen($password) > 64)
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
						'error_message' => Context::get()->getLanguage()->found_bad_password
					)));

				// temporaly constant.
				$gender = intval(Request::get()->data['gender']) !== 1 && intval(Request::get()->data['gender']) !== 2 ? 1 : intval(Request::get()->data['gender']);

				$_SESSION["password"] = password_hash($password, PASSWORD_DEFAULT);
				$_SESSION["gender"]   = $gender;

				if (User::findByEMAIL($_SESSION['email']))
				{
					unset($_SESSION["code"]);
					unset($_SESSION["email"]);
					unset($_SESSION["password"]);
					unset($_SESSION["gender"]);

					die(json_encode(array(
						'error_code' => 4,
						'stage'  => ($_SESSION["stage"] = 1),
						'error_message' => Context::get()->getLanguage()->found_user
					)));
				}

				$_SESSION["stage"] = 4;
				die(json_encode(array(
					'stage' => 4
				)));
			}

			/**
			 * Auto auth and thanks
			*/
			if ($currentStage === 4)
			{
				$first_name = strval($_SESSION['first_name']);
				$last_name  = strval($_SESSION['last_name']);
				$password   = strval($_SESSION['password']);
				$email      = strval($_SESSION['email']);
				$gender     = intval($_SESSION['gender']);

				$user = User::create($first_name, $last_name, $email, $password, $gender);

				$_SESSION = [];
				if ($user && $user->valid())
					Session::start($user->getId())->setAsCurrent();

				// ok!!!!
				die(json_encode(array(
					'stage' => 5
				)));
			}
		break;
		
		default:
		break;
	}
	
	die(json_encode(array('error' => 1)));
}
?>