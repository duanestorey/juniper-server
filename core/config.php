<?php

namespace Juniper\Server;

use Symfony\Component\Yaml\Yaml;

class Config {
    static function load( $yamlFile ) {
        return Yaml::parseFile( $yamlFile );
    }

    static function flatten( $yaml_data, $base_string = '' ) {
        $flattened = [];

        if ( count( $yaml_data ) ) {
            foreach( $yaml_data as $key => $data ) {
                if ( $base_string ) {
                    $new_string = $base_string . '.' . $key;
                } else {
                    $new_string = $key;
                }
                
                if ( is_array( $data ) ) {
                    $flattened[ $new_string ] = $data;
                    $flattened = array_merge( $flattened, Config::flatten( $data, $new_string ) ); 
                } else {
                    $flattened[ $new_string ] = $data;
                }
            }
        }

        ksort( $flattened );
        return $flattened;
    }
}