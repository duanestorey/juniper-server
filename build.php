<?php

namespace Juniper\Server;

define( 'JUNIPER_SERVER_VER', '1.0.0' );
define( 'JUNIPER_SERVER_DIR', dirname( __FILE__ ) );

require_once( JUNIPER_SERVER_DIR . '/vendor/autoload.php' );
require_once( JUNIPER_SERVER_DIR . '/core/server.php' );

class Build {
    var $latte = null;
    var $server = null;

    public function __construct() {
        $this->server = new Server();
        $this->latte = new \Latte\Engine;
        $this->latte->setTempDirectory( sys_get_temp_dir() );
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

    public function writePluginPage( $plugins ) {
        $params = [ 'plugins' => $plugins ];
        $output = $this->latte->renderToString( JUNIPER_SERVER_DIR . '/theme/plugins.latte', $params );

        @mkdir( JUNIPER_SERVER_DIR . '/_public/plugins/', 0755, true );
        file_put_contents( JUNIPER_SERVER_DIR . '/_public/plugins/index.html', $output );
    }

    public function writeSinglePluginPage( $plugin ) {
        $params = [ 'plugin' => $plugin ];
        $output = $this->latte->renderToString( JUNIPER_SERVER_DIR . '/theme/plugin-single.latte', $params );

        @mkdir( JUNIPER_SERVER_DIR . '/_public/plugins/' . $plugin['slug'], 0755, true );
        file_put_contents( JUNIPER_SERVER_DIR . '/_public/plugins/' . $plugin['slug'] . '/index.html', $output );
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

        // Build plugin pages
        $plugins = $this->server->getPluginList();
        $this->writePluginPage( $plugins );

        foreach( $plugins as $plugin ) {
            $this->writeSinglePluginPage( $plugin );
        }

        $this->server->stopDb();

        LOG( "Build process ending for self-replicating repository", 0 );
        echo "\n";
    }
}

$build = new Build;
$build->letsGo();
