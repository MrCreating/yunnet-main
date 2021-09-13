<?php

/**
 * This file contains all functions for 
 * working with the filsystem (upload etc)
*/

// secret data.
define('SERVER_KEY', 'hblgbeulniudnkvjneiudelkkeluhlifneoindlkmd');
define('SERVER_IV', 984859739795879033);

/**
 * Receives a dynamical expression coefficient
 * @return int - coefficient of expression
 *
 * Parameters:
 * @param $img - ImageMagick instance of image
*/
function getExpessionLevel ($img)
{
	$expressionCoefficient = 1;

	$width  = $img->getImageWidth();
	$height = $img->getImageHeight();
	$size   = $img->getImageLength();

	if ($width >= 1920 || $height >= 1200 || $size >= 1048576)
	{
		if (($width >= 1520 || $height >= 800) && ($width <= 1800 || $height <= 1200)) $expressionCoefficient = 1.3;
		if (($width >= 1800 || $height >= 1200) && ($width <= 3048 || $height <= 2920)) $expressionCoefficient = 1.5;
		if (($width >= 3048 || $height >= 2920) && ($width <= 4096 || $height <= 3048)) $expressionCoefficient = 1.9;
		if (($width >= 4096 || $height >= 3048) && ($width <= 5620 || $height <= 4096)) $expressionCoefficient = 2.1;
		if (($width >= 5620 || $height >= 4096) && ($width <= 6280 || $height <= 5620)) $expressionCoefficient = 2.6;
		if (($width >= 6280 || $height >= 5620) && ($width <= 7028 || $height <= 6300)) $expressionCoefficient = 2.5;
		if (($width >= 7028 || $height >= 6300) && ($width <= 8192 || $height <= 7028)) $expressionCoefficient = 3.2;

		if (($width >= 8192 || $height >= 7028)) $expressionCoefficient = 4;
	}

	return $expressionCoefficient;
}

/**
 * Handle uploaded files
 * @return Attachment class if ok or false if error
 *
 * Parameters:
 * @param $query - requested query from link.
 * @param $user_id - who uploads the files
*/
function fetch_upload ($connection, $query, $user_id)
{
	// connecting modules
	if (!class_exists('Photo'))
		require __DIR__ . "/../objects/photo.php";

	// parsing query.
	$data = explode('|', openssl_decrypt(strval(str_replace(' ', '+', explode('__', $query)[0])), 'AES-256-OFB', SERVER_KEY, 0, SERVER_IV, intval(explode('__', $query)[1])));

	// if not enough data.
	if (count($data) < 2) return false;

	$path   = $data[0];
	$type_c = $data[1];
	$keyatt = explode('_', $path)[count(explode('_', $path))-1];
	$attid  = intval(explode('_', $path)[count(explode('_', $path))-2])+1;
	$result = false;

	switch ($type_c) {
		case 'theme':
			$theme = false;

			foreach ($_FILES as $index => $currentFileInfo) {
				$fileNameAndExt = explode('.', $currentFileInfo['name']);
				$extension = $fileNameAndExt[count($fileNameAndExt) - 1];
				if ($extension !== "uth") return false;

				$themeInfoString = file_get_contents($currentFileInfo['tmp_name']);
				if (!$themeInfoString) return false;

				$themeData = json_decode(unserialize(unserialize($themeInfoString)), true);
				if (!$themeData) return false;

				$themeTitle       = $themeData['title'];
				$themeDescription = $themeData['description'];
				$isPrivate        = true;

				$oldCSSCode       = $themeData['data']['css'];
				$oldJSCode        = $themeData['data']['js'];

				if (!$themeTitle) return false;
				if (!$themeDescription) return false;
				if (!$oldCSSCode) return false;
				if (!$oldJSCode) return false;

				if (!function_exists('create_theme'))
					require __DIR__ . '/theming.php';

				$theme = create_theme($connection, $user_id, $themeTitle, $themeDescription, intval($isPrivate));

				update_theme_code($theme, $user_id, 'css', $oldCSSCode);
				update_theme_code($theme, $user_id, 'js', $oldJSCode);
				break;
			}

			return $theme;
		break;
		case 'image':
			foreach ($_FILES as $index => $item) {
				// size check.
				$info = getimagesize($item['tmp_name']);

				// checking file size
				$size = $item['size'];
				if ($size > 200000000) return false;

				// allowed extensions
				$whitelist = array(".jpg",".jpeg",".gif",".png", ".svg", ".bmp");

				// getting extension
				$extension = image_type_to_extension($info[2]);

				// extension check.
				if (!in_array($extension, $whitelist, true)) return false;

				try {
					$img = new Imagick($item['tmp_name']);
				} catch (Exception $e) {
					return false;
				}

				// if not valid image - false!
				if (!$img) return false;

				$width = intval($info[0]);
				$height = intval($info[1]);

				// sizes check
				if ($width < 25 || $height < 25) return false;
				if ($width > 7000 || $height > 7000) return false;

				// all data checked. Save the file.
				$done_path = $path.$extension;
				if (!file_exists(__DIR__ . '/../../attachments/d-1/'.$user_id)) {
					mkdir(__DIR__ . '/../../attachments/d-1/'.strval($user_id));
				};
				if (!file_exists(__DIR__ . '/../../attachments/d-1/'.$user_id.'/images')) {
					mkdir(__DIR__ . '/../../attachments/d-1/'.strval($user_id).'/images');
					mkdir(__DIR__ . '/../../attachments/d-1/'.strval($user_id).'/documents');
					mkdir(__DIR__ . '/../../attachments/d-1/'.strval($user_id).'/audios');
				};

				// compress image and save.
				try {
					if (move_uploaded_file($item['tmp_name'], __DIR__.$done_path))
					{
						if ($info['mime'] !== "image/gif")
						{
							$expLevel = getExpessionLevel($img);

							$img->setCompression(Imagick::COMPRESSION_JPEG);
							$icc_profile = $img->getImageProfiles('icc', true);

							$img->resizeImage(intval($width / $expLevel), intval($height / $expLevel), Imagick::FILTER_LANCZOS, 1);
							$img->setCompressionQuality(20);

							if(!empty($profiles)) 
							{
						    	$img->profileImage('icc', $icc_profile['icc']);
						    }

						    $img->writeImages(__DIR__.$done_path, true);
						}

						$iv    = rand(1, 9999999);
						$rv    = $iv;
						$new_q = openssl_encrypt($done_path.'|'.$type_c, 'AES-256-OFB', SERVER_KEY, 0, SERVER_IV, $iv);

						$res = $connection->prepare('
							INSERT INTO attachments.d_1 (path, query, owner_id, access_key, id, width, height, type) VALUES (:path, :query, :owner_id, :access_key, :id, :width, :height, "photo");
						');

						//tmp
						$done_path = substr($done_path, 7);
						$res->bindParam(":path",       $done_path, PDO::PARAM_STR);
						$res->bindParam(":query",      $new_q,     PDO::PARAM_STR);
						$res->bindParam(":owner_id",   $user_id,   PDO::PARAM_INT);
						$res->bindParam(":access_key", $keyatt,    PDO::PARAM_STR);
						$res->bindParam(":id",         $attid,     PDO::PARAM_INT);
						$res->bindParam(":width",      $width,     PDO::PARAM_INT);
						$res->bindParam(":height",     $height,    PDO::PARAM_INT);
						$res->execute();
						
						$result = new Photo($user_id, $attid, $keyatt);
						if (!$result->valid())
							$result = false;
					}
				} catch (Exception $e) {
					$result = false;
				}
			}
		break;
		default:
		break;
	}

	return $result;
}

/**
 * Creates upload link.
*/
function get_upload_link ($connection, $user_id, $origin = 'https://yunnet.ru', $type = '')
{
	$res = $connection->prepare('SELECT id FROM attachments.d_1 ORDER BY id DESC LIMIT 1;');
	$res->execute();

	// getting last attachment id.
	$last_attachment_id = intval($res->fetch(PDO::FETCH_ASSOC)["id"]);
	if (!file_exists(__DIR__ . '/../../attachments/d-1/'.strval($user_id))) {
		mkdir(__DIR__ . '/../attachments/d-1/'.strval($user_id));
	};
	switch ($type) {
		case 'theme':
			$themeInfo = 'owner_id='.$user_id.'|'.strval($type);
			$iv      = rand(1, 9999999);

			$resulted_iv = $iv;
			$done        = openssl_encrypt($themeInfo, 'AES-256-OFB', SERVER_KEY, 0, SERVER_IV, $iv);
			$q_done      = strval($done).'__'.strval($resulted_iv);

			return [
				'owner_id' => $user_id,
				'url'      => $origin	."/upload?action=upload&query=".$q_done,
				'query'    => $q_done
			];
		break;
		case 'image':

			// create upload query.
			// query - it is encrypted save path + attachment type 
			$key     = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyz'), 0, 10);
			$attdata = strval($user_id).'_'.strval($last_attachment_id).'_'.strval($key);
			$path    = '/../../attachments/d-1/'.strval($user_id).'/images/im'.strval($attdata).'|'.strval($type);
			$iv      = rand(1, 9999999);

			$resulted_iv = $iv;
			$done        = openssl_encrypt($path, 'AES-256-OFB', SERVER_KEY, 0, SERVER_IV, $iv);
			$q_done      = strval($done).'__'.strval($resulted_iv);

			return [
				'owner_id' => $user_id,
				'url'      => $origin	."/upload?action=upload&query=".$q_done,
				'query'    => $q_done
			];
		break;
		default:
			return false;
		break;
	}

	return false;
}