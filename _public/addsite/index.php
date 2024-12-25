<?php

namespace Juniper\Server;

use Symfony\Component\Yaml\Yaml;

require_once( '../../build.php' );
require_once( '../../vendor/autoload.php' );

function handle_add_site() {
    $response = new \stdClass;

    header( 'Content-type: text/json' );

    if ( isset( $_GET[ 'url' ] ) ) {
        $url = $_GET[ 'url' ];

        $cleanUrl = rtrim( $url, '/' );
        $url = rtrim( $cleanUrl, '/' ) . '/wp-json/juniper/v1/releases/';
        
        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL, $url );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
       // curl_setopt( $ch, CURLOPT_CAINFO, JUNIPER_SERVER_DIR . '/certs/curl-ca-bundle.pem' );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );

        $curlResponse = \curl_exec( $ch );
        if ( $curlResponse ) {
            $decodedResponse = json_decode( $curlResponse );
            if ( $decodedResponse ) {
                // this is valid site
                $sitesYaml = JUNIPER_SERVER_DIR . '/config/sites-valid.yaml';
                if ( file_exists( $sitesYaml ) ) {
                    $allSites = Yaml::parse( file_get_contents( $sitesYaml ) );
                } else {
                    $allSites = [];
                }
                
                if ( !isset( $allSites[ 'sites' ]) ) {
                    $allSites[ 'sites' ] = [];
                    $allSites[ 'sites' ][] = $cleanUrl;
                } else {
                    if ( !in_array( $cleanUrl, $allSites[ 'sites' ] ) ) {
                        $allSites[ 'sites' ][] = $cleanUrl;
                    }
                }

                file_put_contents( $sitesYaml, Yaml::dump( $allSites ) );

                $response->code = 200;
                $response->message = 'Site added successfully';

                header( 'HTTP/1.1 200 Ok' );
            } else {
                $sitesYaml = JUNIPER_SERVER_DIR . '/config/sites-invalid.yaml';
                if ( file_exists( $sitesYaml ) ) {
                    $allSites = Yaml::parse( file_get_contents( $sitesYaml ) );
                } else {
                    $allSites = [];
                }
                
                $allSites = Yaml::parse( file_get_contents( $sitesYaml ) );
                if ( !isset( $allSites[ 'sites' ] ) ) {
                    $allSites[ 'sites' ] = [];
                    $allSites[ 'sites' ][] = $cleanUrl;
                } else {
                    if ( !in_array( $cleanUrl, $allSites[ 'sites' ] ) ) {
                        $allSites[ 'sites' ][] = $cleanUrl;
                    }
                }

                file_put_contents( $sitesYaml, Yaml::dump( $allSites ) );

                $response->code = 406;
                $response->message = 'Incorrect content received';

                header( 'HTTP/1.1 406 Not Acceptable' );
            }
        } else {
            $sitesYaml = JUNIPER_SERVER_DIR . '/config/sites-invalid.yaml';
            if ( file_exists( $sitesYaml ) ) {
                $allSites = Yaml::parse( file_get_contents( $sitesYaml ) );
            } else {
                $allSites = [];
            }
            
            $allSites = Yaml::parse( file_get_contents( $sitesYaml ) );
            if ( !isset( $allSites[ 'sites' ] ) ) {
                $allSites[ 'sites' ] = [];
                $allSites[ 'sites' ][] = $cleanUrl;
            } else {
                if ( !in_array( $cleanUrl, $allSites[ 'sites' ] ) ) {
                    $allSites[ 'sites' ][] = $cleanUrl;
                }
            }

            file_put_contents( $sitesYaml, Yaml::dump( $allSites ) );

            $response->code = 404;
            $response->message = 'Site does not appear to be running Juniper/Author';

            header( 'HTTP/1.1 404 Not Found' );
        }
    } else {
        $response->code = 400;
        $response->message = 'You need to provide the URL of a Juniper/Author site to be added to the repository';

        header( 'HTTP/1.1 400 Bad Request' );
    }

    echo json_encode( $response );
}

handle_add_site();
