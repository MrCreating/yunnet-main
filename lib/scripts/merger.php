<?php

$db = mysqli_connect("127.0.0.1", "root", "iA22021981_");

$res = mysqli_fetch_all(mysqli_query($db, "select deleted_for, uid, local_chat_id from messages.chat_engine_1;"));

foreach ($res as $key => $value) 
{
	if ($res[$key][0])
	{
		$item = unserialize($res[$key][0]);
		$uid = intval($res[$key][1]);
		$local_id = intval($res[$key][2]);

		if ($item)
		{
			$done = "";

			foreach ($item as $user_id) {
				$done = $done.$user_id.",";
			}

			mysqli_query($db, "update messages.chat_engine_1 set deleted_for = '".$done."' where uid = ".$uid." and local_chat_id = ".$local_id.";");
		}
	}
}
?>