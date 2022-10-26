<?php

namespace Application\Bloomberg\Http\Controllers;

use Application\Bloomberg\Http\Response\JsonResponseDefault;
use Laravel\Lumen\Routing\Controller as BaseController;

class Controller extends BaseController
{
    protected function sendResponse($data, $message = 'success')
    {
        $data = $this->serialize($data);
        return JsonResponseDefault::create(true, $data, $message, 200);
    }

    protected function sendResponseWithCode($data, $code, $message = 'Error')
    {
        $data = $this->serialize($data);
        return JsonResponseDefault::create(true, $data, $message, $code);
    }

    public function serialize($entity, $exclude = false)
    {
        $json = help()->jms
            ->setSerializerConfiguration(base_path('../../../Infrastructure/Persistence/Doctrine/Serializations'))
            ->toJson($entity, $exclude);

        $array = json_decode($json, true);

        return $array;
    }
}
