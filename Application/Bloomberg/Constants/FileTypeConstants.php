<?php

namespace Application\Bloomberg\Constants;

class FileTypeConstants
{
    //    const IMAGE_TYPE = 'I';
    const BANNER_TYPE = 'B';
    const WRITER_TYPE = 'W';
    //    const ADMIN_TYPE = 'A';
    const USER_TYPE = 'U';
    const LAYOUT_TYPE = 'L';
    //    const PDF_TYPE = 'P';

    /**
     * @return array
     * @throws \ReflectionException
     */
    public static function getImagesTypes()
    {
        $oClass = new \ReflectionClass(__CLASS__);
        return $oClass->getConstants();
    }
}
