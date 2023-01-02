<?php

namespace unt\parsers;

use unt\objects\Attachment;
use unt\objects\BaseObject;
use unt\objects\Photo;
use unt\objects\Poll;
use unt\objects\Post;
use unt\objects\Theme;

/**
 * Parse attachments from credentials
*/

class AttachmentsParser extends BaseObject
{
	public function parseCredentials (string $credentials): Credentials
	{
		$credentials_data = [];
		$type = '';

		if (substr(strtolower($credentials), 0, 5) === Photo::ATTACHMENT_TYPE)
		{
			$type = Photo::ATTACHMENT_TYPE;
			$credentials_data = explode('_', explode(Photo::ATTACHMENT_TYPE, $credentials)[1]);
		}

		if (substr(strtolower($credentials), 0, 5) === Theme::ATTACHMENT_TYPE)
		{
			$type = Theme::ATTACHMENT_TYPE;
			$credentials_data = explode('_', explode(Theme::ATTACHMENT_TYPE, $credentials)[1]);
		}

		if (substr(strtolower($credentials), 0, 4) === Poll::ATTACHMENT_TYPE)
		{
			$type = Poll::ATTACHMENT_TYPE;
			$credentials_data = explode('_', explode(Poll::ATTACHMENT_TYPE, $credentials)[1]);
		}

		if (substr(strtolower($credentials), 0, 4) === Post::ATTACHMENT_TYPE)
		{
			$type = Post::ATTACHMENT_TYPE;
			$credentials_data = explode('_', explode(Post::ATTACHMENT_TYPE, $credentials)[1]);
		}

		return new Credentials($type, intval($credentials_data[0]), intval($credentials_data[1]), strval($credentials_data[2]));
	}

	public function getObject (?string $credentials): ?Attachment
	{
		if ($credentials && !is_empty($credentials))
		{
			$resulted_data = $this->parseCredentials($credentials);

			if ($resulted_data->type === Photo::ATTACHMENT_TYPE)
			{
				$attachment_object = new Photo($resulted_data->owner_id, $resulted_data->id, $resulted_data->access_key);
				if ($attachment_object->valid())
					return $attachment_object;
			}

			if ($resulted_data->type === Poll::ATTACHMENT_TYPE)
			{
				$attachment_object = new Poll($resulted_data->owner_id, $resulted_data->id, $resulted_data->access_key);
				if ($attachment_object->valid())
					return $attachment_object;
			}

			if ($resulted_data->type === Post::ATTACHMENT_TYPE)
			{
				$attachment_object = new Post($resulted_data->owner_id, $resulted_data->id);
				if ($attachment_object->valid())
					return $attachment_object;
			}

			if ($resulted_data->type === Theme::ATTACHMENT_TYPE)
			{
				$attachment_object = new Theme($resulted_data->owner_id, $resulted_data->id);
				if ($attachment_object->valid())
					return $attachment_object;
			}
		}

		return NULL;
	}

    /**
     * Return attachments array
     * @param ?string $credentials - списки полей вложений
     * @return array<Attachment>
     */
	public function getObjects (?string $credentials): array
	{
		if (!$credentials || is_empty($credentials)) return [];

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

	public function resolveFromQuery (?string $query): ?Photo
	{
		if (!$query || is_empty($query)) return NULL;

		$query = explode('__', substr($query, 0, strlen($query)))[0];

		$res = \unt\platform\DataBaseManager::getConnection()->prepare('SELECT id, owner_id, access_key, type FROM attachments.d_1 WHERE query = ? LIMIT 1');
		if ($res->execute([$query]))
		{
			$result = $res->fetch(\PDO::FETCH_ASSOC);
			if ($result)
			{
				$object = NULL;

				if ($result['type'] === Photo::ATTACHMENT_TYPE)
					$object = new Photo(intval($result['owner_id']), intval($result['id']), strval($result['access_key']));

				if ($object && $object->valid())
					return $object;
			}
		}

		return NULL;
	}
}

?>