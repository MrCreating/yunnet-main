<?php

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

function call (API $api, array $params)
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
		$result = array_filter(array_map(function ($user_id) {
			$entity = Entity::findById(intval($user_id));

			return $entity ? $entity->toArray($params['fields']) : null;
		}, array_slice(array_unique(explode(',', $params['user_ids'])), 0, 100)), function ($entity) {
			return $entity != null;
		});
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