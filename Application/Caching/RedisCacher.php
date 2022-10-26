<?php

namespace Application\Caching;

use Predis\Client;
use Predis\Connection\ConnectionException;

class RedisCacher
{
    const DEFAULT_CACHE_MINUTES = 1;

    protected $predis;

    public function __construct()
    {
        $this->predis = new Client();
    }

    private function __entityObjToString($entity, $include = [])
    {
        $json = help()->jms
            ->setSerializerConfiguration(base_path('../../Infrastructure/Persistence/Doctrine/Serializations'))
            ->stockToJson($entity, $include);
        return $json;
    }

    public function exists($key)
    {
        try {
            return $this->predis->exists(env('APP_SERVER_NAME') . '_' . $key);
        } catch (ConnectionException $e) {
            return false;
        }
    }

    public function get($key)
    {
        try {
            $redisValue = $this->predis->get(env('APP_SERVER_NAME') . '_' . $key);
            return json_decode($redisValue, true);
        } catch (ConnectionException $e) {
            return false;
        }
    }

    public function put($key, $value, $includes = [], $expiryPerMinutes = null)
    {
        try {
            if (is_object($value) || is_array($value)) {
                $value = $this->__entityObjToString($value, $includes);
            }

            if ($expiryPerMinutes) {
                $this->predis->setex(env('APP_SERVER_NAME') . '_' . $key, $expiryPerMinutes * 60, $value);
            } else {
                $this->predis->set(env('APP_SERVER_NAME') . '_' . $key, $value);
            }
        } catch (ConnectionException $e) {
            return false;
        }
    }

    public function del($key)
    {
        try {
            $this->predis->del(env('APP_SERVER_NAME') . '_' . $key);
        } catch (ConnectionException $e) {
            return false;
        }
    }
}
