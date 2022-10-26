<?php

namespace Infrastructure\Util;

use JMS\Serializer\Naming\IdenticalPropertyNamingStrategy;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerBuilder;

/**
 * Class JsonHelper
 * @package RZ2\Commons\Infrastructure\Util
 */
class JMSHelper
{
    private $serializer;

    public function setSerializerConfiguration($metadataPath)
    {
        $this->serializer =  SerializerBuilder::create()
            ->addMetadataDir($metadataPath)
            ->setPropertyNamingStrategy(new IdenticalPropertyNamingStrategy())
            ->build();

        return $this;
    }

    public function toJson($entity, $exclude = false)
    {
        if ($exclude) {
            $exclude = ['Default'];
        } else {
            $exclude = ['Default', 'All'];
        }
        return $this->serializer->serialize($entity, 'json', SerializationContext::create()->setGroups($exclude)->enableMaxDepthChecks());
    }

    public function stockToJson($entity, $includes = [])
    {
        if (!$includes) {
            $includes = ['Default'];
        } else {
            $includes[] =  'Default';
        }
        return $this->serializer->serialize($entity, 'json', SerializationContext::create()->setGroups($includes)->enableMaxDepthChecks());
    }
}
