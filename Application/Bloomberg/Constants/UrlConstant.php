<?php

namespace Application\Bloomberg\Constants;

use Illuminate\Support\Facades\URL;

/**
 * Class UrlConstant
 *
 * @package Alalamelyoum\Client\Constants
 * @author  Faris Ahmed <faris@ana.ae>
 */
class UrlConstant
{
    public static function publicFolder()
    {
        return URL::to('/') . '/';
    }

    /**
     * Returns Uploads Url Based On ENV APP_URL
     *
     * @return string
     */
    public static function uploads()
    {
        return env('APP_URL') . '/uploads/';
    }

    public static function resizeImage($imageName, $w, $h)
    {
        return env('CDN_URL') . "/RAC.php?src={$imageName}&w={$w}&h={$h}";
    }

    /**
     * Returns Images Url
     *
     * @param $folderName
     * @param $imageName
     * @return string
     */
    public static function generalImages($folderName, $imageName)
    {
        return env('CDN_URL') . 'general_images/' . $folderName . '/' . $imageName;
    }

    /**
     * @param $imageName
     * @return string
     */
    public static function lowOriginal($imageName)
    {
        return env('CDN_URL') . '/uploads/general_images/low_original/' . $imageName;
    }

    public static function original($imageName)
    {
        return env('CDN_URL') . '/uploads/general_images/original/' . $imageName;
    }

    public static function inner($imageName)
    {
        return env('CDN_URL') . '/uploads/general_images/inner/' . $imageName;
    }

    /**
     * Returns Writers Images Url
     *
     * @param $imageName
     * @return string
     */
    public static function writersImages($imageName)
    {
        return self::uploads() . 'writers/' . $imageName;
    }

    /**
     * Returns Users Images Url
     *
     * @param $imageName
     * @return string
     */
    public static function usersImages($imageName)
    {
        return self::uploads() . 'users/' . $imageName;
    }

    /**
     * @param $imageName
     * @return string
     */
    public static function layouts($imageName)
    {
        return self::uploads() . 'layouts/' . $imageName;
    }

    public static function weather($imageName)
    {
        return self::uploads() . 'weather/' . $imageName;
    }
}
