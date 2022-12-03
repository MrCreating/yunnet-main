<?php

require_once __DIR__ . '/Credentials.php';
require_once __DIR__ . '/../objects/Photo.php';
require_once __DIR__ . '/../objects/Theme.php';
require_once __DIR__ . '/../objects/Poll.php';
require_once __DIR__ . '/../objects/Post.php';

/**
 * Parse attachments from credentials
*/

class AttachmentsParser 
{
	public function parseCredentials (string $credentials): Credentials
	{
		$credentials_data = [];
		$type = '';

		if (substr(strtolower($credentials), 0, 5) === 'photo')
		{
			$type = 'photo';
			$credentials_data = explode('_', explode('photo', $credentials)[1]);
		}

		if (substr(strtolower($credentials), 0, 5) === 'theme')
		{
			$type = 'theme';
			$credentials_data = explode('_', explode('theme', $credentials)[1]);
		}

		if (substr(strtolower($credentials), 0, 4) === 'poll')
		{
			$type = 'poll';
			$credentials_data = explode('_', explode('poll', $credentials)[1]);
		}

		if (substr(strtolower($credentials), 0, 4) === 'wall')
		{
			$type = 'wall';
			$credentials_data = explode('_', explode('wall', $credentials)[1]);
		}

		return new Credentials($type, intval($credentials_data[0]), intval($credentials_data[1]), strval($credentials_data[2]));
	}

	public function getObject (?string $credentials): ?Attachment
	{
		if ($credentials && !unt\functions\is_empty($credentials))
		{
			$resulted_data = $this->parseCredentials($credentials);

			if ($resulted_data->type === "photo")
			{
				$attachment_object = new Photo($resulted_data->owner_id, $resulted_data->id, $resulted_data->access_key);
				if ($attachment_object->valid())
					return $attachment_object;
			}

			if ($resulted_data->type === "poll")
			{
				$attachment_object = new Poll($resulted_data->owner_id, $resulted_data->id, $resulted_data->access_key);
				if ($attachment_object->valid())
					return $attachment_object;
			}

			if ($resulted_data->type === "wall")
			{
				$attachment_object = new Post($resulted_data->owner_id, $resulted_data->id);
				if ($attachment_object->valid())
					return $attachment_object;
			}

			if ($resulted_data->type === 'theme')
			{
				$attachment_object = new Theme($resulted_data->owner_id, $resulted_data->id);
				if ($attachment_object->valid())
					return $attachment_object;
			}
		}

		return NULL;
	}

	public function getObjects (?string $credentials): array
	{
		if (!$credentials || unt\functions\is_empty($credentials)) return [];

		$attachments_list = explode(',', trim($credentials));
		$objects_list     = [];

		$attachments_list_parsed = [];
		foreach ($attachments_list as $index => $item) {
			if (unt\functions\is_empty($item)) continue;

			$attachments_list_parsed[] = $item;
		}

		foreach ($attachments_list_parsed as $index => $credential)
		{
			if ($index >= 10) break;

			$attachment_object = $this->getObject($credential);
			if ($attachment_object)
				$objects_list[] = $attachment_object;
		}

		return $objects_list;
	}

	public function resolveFromQuery (?string $query): ?Attachment
	{
		if (!$query || unt\functions\is_empty($query)) return NULL;

		$query = explode('__', substr($query, 0, strlen($query)))[0];

		$res = DataBaseManager::getConnection()->prepare('SELECT id, owner_id, access_key, type FROM attachments.d_1 WHERE query = ? LIMIT 1');
		if ($res->execute([$query]))
		{
			$result = $res->fetch(PDO::FETCH_ASSOC);
			if ($result)
			{
				$object = NULL;

				if ($result['type'] === "photo")
					$object = new Photo(intval($result['owner_id']), intval($result['id']), strval($result['access_key']));

				if ($object && $object->valid())
					return $object;
			}
		}

		return NULL;
	}
}

?>