<?php

use unt\objects\API;
use unt\objects\Entity;

$method_permissions_group = 0;

$method_params = [
	'user_id' => [
		'required' => 0,
		'type'     => 'integer'
	],
	'user_ids' => [
		'required' => 0,
		'type'     => 'string'
	],
	'fields' => [
		'required' => 0,
		'type'     => 'string'
	]
];

function call (API $api, array $params): APIResponse
{
	$result = [];

	if (isset($params['user_id']))
	{
		$entity = Entity::findById($params['user_id']);
		if ($entity)
		{
			$result[] = $entity->toArray(strval($params['fields']));
		}
	} else 
	if (isset($params['user_ids']))
	{
		$user_ids = array_map(function ($user_id) { return intval($user_id); }, explode(',', $params['user_ids']));
		$result_user_ids = [];

		foreach ($user_ids as $index => $user_id) 
		{
			if ($index > 100) break;

			if (!in_array($user_id, $result_user_ids))
			{
				$result_user_ids[] = $user_id;

				$entity = Entity::findById($user_id);
				if (!$entity)
				{
					$result[] = $entity->toArray(strval($params['fields']));
				}
			}
		}
	} else
	{
		$entity = Entity::findById($_SESSION['user_id']);
		if ($entity)
		{
			$result[] = $entity->toArray(strval($params['fields']));
		}
	}

	return new APIResponse(['items' => $result]);
}

?>
