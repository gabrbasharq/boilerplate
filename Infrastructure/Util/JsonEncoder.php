<?php

namespace Infrastructure\Util;

class JsonEncoder
{
    public static function encode($arr)
    {
        return json_encode($arr, JSON_UNESCAPED_UNICODE);
    }

    public static function decode($json)
    {
        return json_decode($json, true);
    }
}
