<?php
error_reporting(0);

$db = mysqli_connect("127.0.0.1", "root", "iA22021981_");
$res = mysqli_fetch_all(mysqli_query($db, "select uid, users from messages.members_engine_1;"));

foreach ($res as $key => $value) {
	$uid  = intval($value[0]);
	$chat = unserialize($value[1]);

	foreach ($chat as $i => $user) {
		$user_id     = intval($user["user_id"]);
		$join_time   = intval($user["join_time"]);
		$return_time = intval($user["flags"]["return_time"]);
		$leaved_time = intval($user["flags"]["leaved_time"]);
		$local_id    = intval($user["lid"]);
		$perm_level  = intval($user['permissions']["level"]);
		$invited_by  = intval($user["flags"]["invited_by"]);
		$is_kicked   = intval($user["flags"]["is_kicked"]);
		$is_leaved   = intval($user["flags"]["is_leaved"]);
		$is_muted    = intval($user["flags"]["is_muted"]);

		$user = mysqli_fetch_assoc(mysqli_query($db, 'SELECT * FROM messages.members_chat_list WHERE uid = '.$uid.' AND user_id = '.$user_id.';'));

		if (!$user) {
			mysqli_query($db, '
				INSERT INTO messages.members_chat_list 
					(
						uid, 
						user_id,
						return_time,
						join_time,
						leaved_time,
						lid,
						permissions_level,
						invited_by,
						is_kicked,
						is_leaved,
						is_muted
					) 
				VALUES 
					(
						'.$uid.', 
						'.$user_id.',
						'.$return_time.',
						'.$join_time.',
						'.$leaved_time.',
						'.$local_id.',
						'.$perm_level.',
						'.$invited_by.',
						'.$is_kicked.',
						'.$is_leaved.',
						'.$is_muted.'
					);
			');

			echo '[+] Adding user with id '.$user_id.' to uid '.$uid.PHP_EOL;
		}
	}
}

echo '[ok] All data merged'.PHP_EOL;
?>