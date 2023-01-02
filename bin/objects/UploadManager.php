<?php

namespace unt\objects;

use unt\platform\Data;
use unt\platform\DataBaseManager;

/**
 * File uploader checker and upload session creator
*/
class UploadManager extends BaseObject
{
    /**
     * Массив полученных вложений после загрузки
     * @var array<Attachment> $handledData
     */
    protected array $handledData = [];

    /**
     * @throws \Exception
     */
    public function __construct (string $query)
	{
        parent::__construct();

        // parsing query.
        $data = explode('|', openssl_decrypt(strval(str_replace(' ', '+', explode('__', $query)[0])), 'AES-256-OFB', self::SERVER_KEY, 0, self::SERVER_IV, intval(explode('__', $query)[1])));

        // if not enough data.
        if (count($data) < 2) return false;

        $path   = $data[0];
        $type_c = $data[1];

        $key_of_attachment = explode('_', $path)[count(explode('_', $path)) - 1];
        $attachment_id     = intval(explode('_', $path)[count(explode('_', $path)) - 2]) + 1;

        foreach ($_FILES as $index => $currentFileInfo) {
            // временно
            if ($index >= 1) break;

            switch ($type_c) {
                case Theme::ATTACHMENT_TYPE:
                    $fileNameAndExt = explode('.', $currentFileInfo['name']);
                    $extension = $fileNameAndExt[count($fileNameAndExt) - 1];

                    $themeInfoString = file_get_contents($currentFileInfo['tmp_name']);
                    if ($themeInfoString)
                    {
                        $themeData = unserialize(unserialize(json_decode($themeInfoString)));

                        if ($themeData)
                        {
                            $themeTitle       = $themeData['title'];
                            $themeDescription = $themeData['description'];

                            $oldCSSCode       = $themeData['data']['css'];
                            $oldJSCode        = $themeData['data']['js'];

                            if (!$themeTitle) break;
                            if (!$themeDescription) break;
                            if ($oldCSSCode === NULL) break;
                            if ($oldJSCode === NULL) break;

                            $theme = Theme::create($themeTitle, $themeDescription, true);

                            if (!$theme)
                                break;

                            $css_result = $theme->setCSSCode($oldCSSCode);
                            $js_result  = $theme->setJSCode($oldJSCode);

                            if ($css_result !== true && $css_result !== false)
                                throw new \Exception($css_result, -1);
                            if ($js_result !== true && $js_result !== false)
                                throw new \Exception($js_result, -2);

                            $this->handledData[] = $theme;
                        }
                    }

                    break;
                case Photo::ATTACHMENT_TYPE:
                    $info = getimagesize($currentFileInfo['tmp_name']);

                    // доступные расширения для загрузки картинок
                    $whitelist = [
                        ".jpg",
                        ".jpeg",
                        ".gif",
                        ".png",
                        ".svg",
                        ".bmp"
                    ];

                    $size = $info['size'];
                    if ($size > 200000000)
                        break;

                    // getting extension
                    $extension = image_type_to_extension($info[2]);

                    // extension check.
                    if (!in_array($extension, $whitelist, true))
                        break;

                    try {
                        $img = new \Imagick($currentFileInfo['tmp_name']);

                        $width = intval($info[0]);
                        $height = intval($info[1]);

                        // sizes check
                        if ($width < 25 || $height < 25) break;
                        if ($width > 7000 || $height > 7000) break;


                        $done_path = $path.$extension;
                        if (!file_exists(__DIR__ . '/../../attachments/d-1/' . $_SESSION['user_id'])) {
                            if (!mkdir(__DIR__ . '/../../attachments/d-1/' . $_SESSION['user_id'])) break;
                        }
                        if (!file_exists(__DIR__ . '/../../attachments/d-1/' . $_SESSION['user_id'] . '/images')) {
                            if (!mkdir(__DIR__ . '/../../attachments/d-1/' . $_SESSION['user_id'] . '/images')) break;
                            if (!mkdir(__DIR__ . '/../../attachments/d-1/' . $_SESSION['user_id'] . '/documents')) break;
                            if (!mkdir(__DIR__ . '/../../attachments/d-1/' . $_SESSION['user_id'] . '/audios')) break;
                        }

                        if (move_uploaded_file($currentFileInfo['tmp_name'], __DIR__ . $done_path))
                        {
                            if ($info['mime'] !== "image/gif")
                            {
                                $expLevel = self::getImageExpressionLevel($img);

                                $img->setCompression(\Imagick::COMPRESSION_JPEG);
                                $icc_profile = $img->getImageProfiles('icc', true);

                                $img->resizeImage(intval($width / $expLevel), intval($height / $expLevel), \Imagick::FILTER_LANCZOS, 1);
                                $img->setCompressionQuality(20);

                                if(!empty($profiles))
                                {
                                    $img->profileImage('icc', $icc_profile['icc']);
                                }

                                $img->writeImages(__DIR__ . $done_path, true);
                            }

                            $iv    = rand(1, 9999999);
                            $rv    = $iv;
                            $new_q = openssl_encrypt($done_path.'|'.$type_c, 'AES-256-OFB', self::SERVER_KEY, 0, self::SERVER_IV, $iv);
                            $user_id = intval($_SESSION['user_id']);

                            $res = DataBaseManager::getConnection()->prepare('
							    INSERT INTO attachments.d_1 (path, query, owner_id, access_key, id, width, height, type) VALUES (:path, :query, :owner_id, :access_key, :id, :width, :height, "photo");
						    ');

                            //tmp
                            $done_path = substr($done_path, 7);
                            $res->bindParam(":path",       $done_path, \PDO::PARAM_STR);
                            $res->bindParam(":query",      $new_q,     \PDO::PARAM_STR);
                            $res->bindParam(":owner_id",   $user_id,   \PDO::PARAM_INT);
                            $res->bindParam(":access_key", $key_of_attachment,    \PDO::PARAM_STR);
                            $res->bindParam(":id",         $attachment_id,     \PDO::PARAM_INT);
                            $res->bindParam(":width",      $width,     \PDO::PARAM_INT);
                            $res->bindParam(":height",     $height,    \PDO::PARAM_INT);
                            if ($res->execute())
                            {
                                $result = new Photo($user_id, $attachment_id, $key_of_attachment);
                                if (!$result->valid())
                                    break;

                                $this->handledData[] = $result;
                            }
                        }
                    } catch (\Exception $e) {
                    }

                    break;
                default:
                    break;
            }
        }
	}

    public function getUploadedAttachments (): array
    {
        return $this->handledData;
    }

    ///////////////////////////////////
    const SERVER_KEY = 'hblgbeulniudnkvjneiudelkkeluhlifneoindlkmd';
    const SERVER_IV  = '984859739795879033';

    public static function getImageExpressionLevel (\Imagick $img): float
    {
        $expressionCoefficient = 1;

        try {
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
        } catch (\Exception $e) {
        }

        return $expressionCoefficient;
    }

    // link for uploading attachment
    public static function getLink (string $type = Photo::ATTACHMENT_TYPE): ?Data
    {
        $res = DataBaseManager::getConnection()->prepare('SELECT id FROM attachments.d_1 ORDER BY id DESC LIMIT 1;');
        if ($res->execute())
        {
            // getting last attachment id.
            $last_attachment_id = intval($res->fetch(\PDO::FETCH_ASSOC)["id"]);

            $iv = rand(1, 9999999);
            $resulted_iv = $iv;

            switch ($type) {
                case Theme::ATTACHMENT_TYPE:
                    $themeInfo = 'owner_id=' . $_SESSION['user_id'] . '|' . $type;

                    $done        = openssl_encrypt($themeInfo, 'AES-256-OFB', self::SERVER_KEY, 0, self::SERVER_IV, $iv);
                    $q_done      = $done . '__' . $resulted_iv;

                    return new Data([
                        'owner_id' => $_SESSION['user_id'],
                        'url'      => Project::getDefaultDomain() . "/upload?action=upload&query=" . $q_done,
                        'query'    => $q_done
                    ]);

                case Photo::ATTACHMENT_TYPE:
                    // create upload query.
                    // query - it is encrypted save path + attachment type
                    $key     = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyz'), 0, 10);
                    $attachment_data = $_SESSION['user_id'] . '_' . $last_attachment_id . '_' . $key;
                    $path    = '/../../attachments/d-1/' . $_SESSION['user_id'] .'/images/im'. $attachment_data . '|' . $type;

                    $done        = openssl_encrypt($path, 'AES-256-OFB', self::SERVER_KEY, 0, self::SERVER_IV, $iv);
                    $q_done      = $done . '__' . $resulted_iv;

                    return new Data([
                        'owner_id' => $_SESSION['user_id'],
                        'url'      => Project::getDefaultDomain() . "/upload?action=upload&query=" . $q_done,
                        'query'    => $q_done
                    ]);

                default:
                    break;
            }
        }

        return NULL;
    }
}

?>