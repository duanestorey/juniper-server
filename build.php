<?php

namespace Juniper\Server;

define( 'JUNIPER_SERVER_VER', '1.0.0' );
define( 'JUNIPER_SERVER_DIR', dirname( __FILE__ ) );

require_once( JUNIPER_SERVER_DIR . '/vendor/autoload.php' );
require_once( JUNIPER_SERVER_DIR . '/core/server.php' );

class Build {
    var $server = null;

    public function __construct() {
        $this->server = new Server();
    }

    public function branding() {
        $brandText = sprintf( "| Juniper/Server version %s |", JUNIPER_SERVER_VER );
        $lineText = '';
        for ( $i = 0 ; $i < strlen( $brandText ); $i++ ) {
            $lineText = $lineText . '-';
        }

        LOG( $lineText );
        LOG( $brandText );
        LOG( $lineText );
    }

    public function letsGo() {
        $this->branding();
        LOG( "Build process starting for self-replicating repository", 0 );

        $this->server->startDb();
        $this->server->loadConfig();
        $sites = $this->server->getSites();

        foreach( $sites as $site ) {     
            $siteId = $this->server->addSiteToDb( $site );

            LOG( sprintf( "Importing site [%s]", $site ), 1 );

            $contents = file_get_contents( $site . '/wp-json/juniper/v1/releases/' );
            if ( $contents ) {
                $decodedContents = json_decode( $contents );        
                if ( is_array( $decodedContents ) ) {
                    foreach( $decodedContents as $num => $addOn ) {
                        LOG( sprintf( "Adding new ADDON [%s] of TYPE [%s]", $addOn->info->pluginName, $addOn->info->type ), 1 );
                        $addOnId = $this->server->addAddonToDb( $siteId, $addOn );

                        foreach( $addOn->releases as $num => $release ) {
                            LOG( sprintf( "Adding release with TAG [%s]", $release->tagName ), 2 );
                            $this->server->addReleaseToDb( $addOnId, $release );
                        }
                    }
                }
            }
        }
     

        $this->server->stopDb();

        LOG( "Build process ending for self-replicating repository", 0 );
        echo "\n";
    }
}

$build = new Build;
$build->letsGo();
