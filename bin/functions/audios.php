<?php

namespace unt\functions\audios;

require_once __DIR__.'/../../lib/vk_audio/autoloader.php';

use Vodka2\VKAudioToken\SupportedClients;

/**
 * Perspectiva audios platform
*/

/**
 * Getting the audios from accounts or unt
 * @param $user_id - current user id
 * @param int $account_type - account type
 * @param int $offset - audios offset (default: 0)
 * @param int $count - audios count (default: 30)
*@return Array with audios info
 *
 * Parameters
 */
function get_audio ($connection, $user_id, int $account_type = 1, int $offset = 0, int $count = 30)
{
	// perspective unt audios
	if ($account_type === 0)
	{
		return [];
	}

	// vk audios
	if ($account_type === 1)
	{
		if (!function_exists('get_accounts'))
			require __DIR__ . '/accounts.php';

		$token = get_accounts($connection, $user_id, true)[0];
		if (!$token) return false;

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_HTTPHEADER, array('User-Agent: '.SupportedClients::Kate()->getUserAgent()));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL, "https://api.vk.com/method/audio.get?access_token=".$token."&count=".intval($count)."&offset=".intval($offset)."&v=5.95");

		$audios = json_decode(curl_exec($ch), true)['response']['items'];
		curl_close($ch);

		if (!$audios) return [];

		$result = [];
		foreach ($audios as $index => $audio)
		{
			$audioObject = [
				'owner_id'   => null,
				'id'         => null,
				'access_key' => null,
				'url'        => $audio['url'],
				'title'      => $audio['title'],
				'artist'     => $audio['artist'],
				'lyrics'     => '',
				'duration'   => $audio['duration'],
				'service'    => [
					'internal_credentials' => 'audio' . $audio['owner_id'] . '_' . $audio['id'] . '_' . $audio['access_key']
				]
			];

			$result[] = $audioObject;
		}

		return $result;
	}

	return false;
}

?>