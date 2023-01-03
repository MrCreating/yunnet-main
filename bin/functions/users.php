<?php

// connecting modules.
use unt\objects\Bot;
use unt\objects\User;
use unt\platform\DataBaseManager;

/** 
 * search users by query
 * returns array of User and Bot objects
 * or empty array
 */
function search_users ($connection, $query, $additional_params = [
	"search_bots" => false,
	"offset"      => 0,
	"count"       => 50
]): array
{
	$result = [];

	$query = explode(' ', capitalize(trim($query)));
	if (count($query) > 20 || count($query) < 1)
		return $result;

	$query_call = "SELECT DISTINCT id FROM users.info WHERE ";
	if ($additional_params['only_bots'])
		$query_call = "SELECT DISTINCT id FROM bots.info WHERE ";

	foreach ($query as $index => $word) {
		if (is_empty($word))
			continue;

		if (!$additional_params['only_bots'])
		{
			$only_online = '';
			if ($additional_params['online_only'])
				$only_online = ' AND is_online >= '.(time() - 30);

			if ($index === 0)
				$query_call .= '((id LIKE :id_'.$index.' OR first_name LIKE CONCAT("%", :first_name_'.$index.', "%") OR last_name LIKE CONCAT("%", :last_name_'.$index.', "%")) AND is_banned = 0 AND is_deleted = 0'.$only_online.')';
			else
				$query_call .= ' OR ((id LIKE :id_'.$index.' OR first_name LIKE CONCAT("%", :first_name_'.$index.', "%") OR last_name LIKE CONCAT("%", :last_name_'.$index.', "%")) AND is_banned = 0 AND is_deleted = 0'.$only_online.')';
		} else
		{
			if ($index === 0)
				$query_call .= '((id LIKE :id_'.$index.' OR name LIKE CONCAT("%", :name_'.$index.', "%")) AND is_banned = 0 AND is_deleted = 0)';
			else
				$query_call .= ' OR ((id LIKE :id_'.$index.' OR name LIKE CONCAT("%", :name_'.$index.', "%")) AND is_banned = 0 AND is_deleted = 0)';
		}
	}

	$query_call .= " LIMIT ".intval($additional_params['offset']).",".intval($additional_params["count"]).";";

	// preparing requests
	$res = DataBaseManager::getConnection()->prepare($query_call);
	foreach ($query as $index => $word) {
		if (is_empty($word))
			continue;

		if (!$additional_params['only_bots'])
		{
			$res->bindParam(":id_".$index,         $word, PDO::PARAM_INT);
			$res->bindParam(":first_name_".$index, $word, PDO::PARAM_STR);
			$res->bindParam(":last_name_".$index,  $word, PDO::PARAM_STR);
		} else
		{
			$res->bindParam(":id_".$index,         $word, PDO::PARAM_INT);
			$res->bindParam(":name_".$index, $word, PDO::PARAM_STR);
		}
		
	}
	
	if ($res->execute())
	{
		$data = $res->fetchAll(PDO::FETCH_ASSOC);
		$temp = [];

		foreach ($data as $user_id) {
			$user_id = $user_id["id"];

			if (!in_array(intval($user_id), $temp))
			{
				$temp[] = intval($user_id);

				$object = new User(intval($user_id));
				if (!$object->valid() || $additional_params['only_bots'])
				{
					$object = new Bot(intval($user_id));
				}

				if (!$object->valid()) continue;

				$result[] = $object;
			}
		}
	}

	return $result;
}

?>