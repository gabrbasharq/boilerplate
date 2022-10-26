<?php

namespace Application\Bloomberg\Constants;

/**
 * Class PathConstant
 *
 * @package Alalamelyoum\Client\Constants
 * @author  Faris Ahmed <faris@ana.ae>
 */
class PathConstant
{
    // PATHS
    const UPLOADS_PATH = __DIR__ . '/../public/uploads/';

    const BANNERS_IMAGES_PATH = self::UPLOADS_PATH . 'banners/';
    const GENERAL_IMAGES_PATH = self::UPLOADS_PATH . 'general_images/';
    const USERS_IMAGES_PATH = self::UPLOADS_PATH . 'users/';
    const WRITERS_IMAGES_PATH = self::UPLOADS_PATH . 'writers/';
    const LAYOUTS_IMAGES_PATH = self::UPLOADS_PATH . 'layouts/';
    const WEATHER_ICONS_PATH = self::UPLOADS_PATH . 'weather/';

    public static function TMP_VIDEOS_PATH()
    {
        return storage_path('tmp_videos') . '/';
    }
}
