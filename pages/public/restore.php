<?php
session_start();

require_once __DIR__ . '/../../bin/objects/session.php';

// restore actions!
if (isset($_POST['action']))
{
	$action = strtolower($_POST['action']);

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
			$query = strval($_POST["query"]);
			$exp_t = intval(time());

			$res = $connection->prepare("SELECT user_id FROM users.restore WHERE query = :query AND is_used = 0 AND expiration_time >= :exp_time LIMIT 1;");
			$res->bindParam(":query",    $query, PDO::PARAM_STR);
			$res->bindParam(":exp_time", $exp_t, PDO::PARAM_INT);
			$res->execute();

			$user_id = intval($res->fetch(PDO::FETCH_ASSOC)["user_id"]);
			if ($user_id > 0)
			{
				$_SESSION["restore_stage"] = 3;
				$_SESSION["waiting_id"]    = $user_id;
				
				$connection->prepare("UPDATE users.restore SET is_used = 1 WHERE user_id = ?;")->execute([strval($user_id)]);

				die(json_encode(array('stage' => 3)));
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
				$email = strval($_POST["email"]);
				if (is_empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 96)
				{
					die(json_encode(array(
						'error_code' => 2
					)));
				}

				$_SESSION["email_to_restore"] = $email;
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
				$last_name  = $_POST["last_name"];
				if (is_empty($last_name) || strlen($last_name) > 32 || preg_match("/[^a-zA-Zа-яА-ЯёЁ'-]/ui", $last_name))
					die(json_encode(array(
						'error_code' => 1
					)));

				$email = $_SESSION["email_to_restore"];
				$res = $connection->prepare("SELECT id, first_name FROM users.info WHERE email = ? AND last_name = ? LIMIT 1;");
				$res->execute(
					[$email, $last_name]
				);

				$user_data = $res->fetch(PDO::FETCH_ASSOC);
				$user_id = intval($user_data["id"]);
				if ($user_id <= 0)
				{
					die(json_encode(array(
						'error_code' => 1,
						'error_message' => $context->lang["i_not_found_user"]
					)));
				}

				$key = str_shuffle('abcdefghijklmnopqrstuvwxyz0123456789abcdefghijklmnopqrstuvwxyz0123456789abcdefghijklmnopqrstuvwxyz0123456789abcdefghijklmnopqrstuvwxyz');
				$link = 'https://yunnet.ru/restore?query='.$key;
				$text = str_replace('%ip%', $_SERVER['REMOTE_ADDR'], str_replace('%fn%', $user_data['first_name'], str_replace('%link%', $link, $context->lang['restore_text'])));
				$exp_time = time() + 86400;

				$res = $connection->prepare('INSERT INTO users.restore (user_id, query, expiration_time, is_used) VALUES (:user_id, :query, :exp_time, 0);');
				$res->bindParam(":user_id",  $user_id,  PDO::PARAM_INT);
				$res->bindParam(":query",    $key,      PDO::PARAM_STR);
				$res->bindParam(":exp_time", $exp_time, PDO::PARAM_INT);
				$res->execute();

				$_SESSION["restore_stage"] = 2;
				mail($email, $context->lang['restore_account'], $text);
				die(json_encode(array(
					"stage" => 2
				)));
			}

			/**
			 * Setting up the passwords
			*/
			if ($currentStage === 3)
			{
				$password        = strval($_POST["password"]);
				$repeat_password = strval($_POST["repeat_password"]);
				if (preg_match("/[^a-zа-яёЁбБвВгГдДжЖзЗиИйЙкКлЛмМнНоОпПРрсСтТуУфФхХцЦчЧшШщЩъЪыЫьЬэЭюЮяЯРА-ЯA-Z-'*@#$%_.\d!@#$%\^&*]/", $password)  || is_empty($password) || strlen($password) < 6)
					die(json_encode(array(
						'error_code' => 5
					)));

				if (preg_match("/[^a-zа-яёЁбБвВгГдДжЖзЗиИйЙкКлЛмМнНоОпПРрсСтТуУфФхХцЦчЧшШщЩъЪыЫьЬэЭюЮяЯРА-ЯA-Z-'*@#$%_.\d!@#$%\^&*]/", $repeat_password))
					die(json_encode(array(
						'error_code' => 6
					)));

				if ($password !== $repeat_password)
					die(json_encode(array(
						'error_code' => 6,
						'error_message' => str_replace('%first_name%', "???", $context->lang['found_bad_password'])
					)));

				$user_id = intval($_SESSION{"waiting_id"});
				if ($user_id > 0)
				{
					$password = password_hash($password, PASSWORD_DEFAULT);

					$res = $connection->prepare("UPDATE users.info SET password = ? WHERE id = ? LIMIT 1;");
					$res->execute(
						[$password, strval($user_id)]
					);
					$connection->prepare("DELETE FROM api.tokens WHERE owner_id = ?;")->execute([$user_id]);

					$_SESSION["restore_stage"] = 4;
					die(json_encode(array(
						"stage" => 4
					)));
				}
			}

			/**
			 * Logging into account
			*/
			if ($currentStage === 4)
			{
				$user_id = intval($_SESSION["waiting_id"]);

				Session::start($user_id)->setAsCurrent();

				$sessions = Session::getList();
				foreach ($sessions as $index => $session) {
					$session->end();
				}

				$_SESSION = [];

				Session::start($user_id)->setAsCurrent();

				die(json_encode(array(
					"stage" => 5
				)));
			}
		break;
		
		default:
		break;
	}

	die(json_encode(array('error' => 1)));
}

?>