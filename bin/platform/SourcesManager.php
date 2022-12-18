<?php

namespace unt\platform;

use MatthiasMullie\Minify\CSS;
use MatthiasMullie\Minify\JS;
use unt\objects\Project;
use unt\platform\Cache;

class SourcesManager extends \unt\objects\BaseObject
{
    public function __construct()
    {
        parent::__construct();
    }

    //////////////////////////////////////////
    public static function load (string $path): array
    {
        $full_path = PROJECT_ROOT . '/' . ltrim($path, '/');
        $extension = pathinfo($full_path)['extension'];

        $cache = Cache::getCacheServer();

        $file_data = '';
        if (Project::isProduction())
        {
            $file_data = $cache->get($full_path);

            if (!$file_data && file_exists($full_path))
            {
                $file_data = file_get_contents($full_path);

                if ($extension === 'js') {
                    $file_data = (new JS())->add($file_data)->minify();
                } else if ($extension === 'css') {
                    $file_data = (new CSS())->add($file_data)->minify();
                } else {
                    $img = imagecreatefromstring($file_data);

                    switch ($extension) {
                        case 'png':
                            $file_data = imagepng($img); break;
                        case 'gif':
                            $file_data = imagegif($img); break;
                        case 'jpeg':
                        case 'jpg':
                            $file_data = imagejpeg($img); break;
                    }
                }

                $cache->set($full_path, $file_data);
            }
        } else
        {
            if (file_exists($full_path))
                $file_data = file_get_contents($full_path);

            if ($extension !== 'js' && $extension !== 'css')
            {
                $img = imagecreatefromstring($file_data);

                switch ($extension) {
                    case 'png':
                        $file_data = imagepng($img); break;
                    case 'gif':
                        $file_data = imagegif($img); break;
                    case 'jpeg':
                    case 'jpg':
                        $file_data = imagejpeg($img); break;
                }
            }
        }

        return [
            'extension' => $file_data != '' ? $extension : NULL,
            'data' => $file_data
        ];
    }
}

?>