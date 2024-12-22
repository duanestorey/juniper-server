<?php

namespace Juniper\Server;

use Symfony\Component\Yaml\Yaml;

class Config {
    static function load( $yamlFile ) {
        return Yaml::parseFile( $yamlFile );
    }
}