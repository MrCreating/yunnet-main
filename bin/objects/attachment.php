<?php

if (!class_exists('Poll'))
	require __DIR__ . '/poll.php';
if (!class_exists('Photo'))
	require __DIR__ . '/photo.php';
if (!class_exists('Post'))
	require __DIR__ . '/post.php';
if (!class_exists('Theme'))
	require __DIR__ . '/theme.php';

abstract class Attachment
{
	protected $isValid = false;

	public function valid (): bool
	{
		return boolval($this->isValid);
	}

	abstract public function toArray(): array;
}

class Credentials
{
	public $type       = '';
	public $owner_id   = 0;
	public $id         = 0;
	public $access_key = '';

	function __construct ($type = '', $owner_id = 0, $id = 0, $access_key = '')
	{
		$this->type       = strval($type);
		$this->owner_id   = intval($owner_id);
		$this->id         = intval($id);
		$this->access_key = strval($access_key);
	}
}

// declares an a attachment parser class
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

	public function getObject ($credentials)
	{
		if (!is_empty($credentials))
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

	public function getObjects ($credentials): array
	{
		$attachments_list = explode(',', trim($credentials));
		$objects_list     = [];

		$attachments_list_parsed = [];
		foreach ($attachments_list as $index => $item) {
			if (is_empty($item)) continue;

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

	public function resolveFromQuery ($query)
	{
		$query = explode('__', substr($query, 0, strlen($query)))[0];

		$connection = new DataBaseConnection();

		$result = $connection->execute('SELECT id, owner_id, access_key, type FROM attachments.d_1 WHERE query = :query LIMIT 1;', new DataBaseParams(
			[new DBRequestParam(":query", $query, PDO::PARAM_STR)]
		));

		if (!$result) return NULL;

		$dataInfo = $result->{"0"};

		if ($dataInfo)
		{
			$object = NULL;

			if ($dataInfo->type === "photo")
				$object = new Photo($dataInfo->owner_id, $dataInfo->id, $dataInfo->access_key);

			if ($object && $object->valid())
				return $object;
		}

		return NULL;
	}
}

?>