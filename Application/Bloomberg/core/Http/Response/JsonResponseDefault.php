<?php

namespace Application\Bloomberg\Http\Response;

/**
 * Class JsonResponseDefault
 * @package Application\Bloomberg\Http\Response
 */
class JsonResponseDefault
{

    /**
     * @param $success
     * @param $data
     * @param $message
     * @param $code
     * @return mixed
     */
    public static function create(string $success, array $data, string $message = 'Success', int $code = 200)
    {
        $response = [
            'success' => $success,
            'data'  => $data,
            'message' => $message,
            'code'    => $code
        ];

        $header = [$response['code'] => $response['message']];

        return response($response, $response['code'], $header);
    }
}
