<?php

namespace Juniper\Server;

ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);

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

        @mkdir( JUNIPER_SERVER_DIR . '/cache', true, 0775 );
        $this->latte->setTempDirectory( JUNIPER_SERVER_DIR . '/cache' );
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

    public function writeSinglePluginPage( $plugin, $releases, $issues ) {
        $params = [ 'plugin' => $plugin, 'releases' => $releases, 'issues' => $issues ];
        $output = $this->latte->renderToString( JUNIPER_SERVER_DIR . '/theme/plugin-single.latte', $params );

        @mkdir( JUNIPER_SERVER_DIR . '/_public/plugins/' . $plugin['slug'], 0755, true );
        file_put_contents( JUNIPER_SERVER_DIR . '/_public/plugins/' . $plugin['slug'] . '/index.html', $output );
    }

    public function writeHomePage() {
        $newsSites = $this->server->getConfigSetting( 'repo.news' );
        $allNews = [];

        foreach( $newsSites as $site ) {
            $content = \Feed::loadRss( $site );

            foreach ( $content->item as $item ) {
                $newsItem = new \stdClass;

                $newsItem->title = $item->title;
                $newsItem->url = $item->url;
                $newsItem->timestamp = $item->timestamp;
                $newsItem->desc = $item->description;
                $newsItem->content = $item->{'content:encoded'};

                $allNews[ $newsItem->timestamp->__toString() ] = $newsItem;
            }
        }

        krsort( $allNews );
        $allNews = array_slice( $allNews, 0, 5 );

        $newPlugins = $this->server->getNewestAddons();

        $params = [ 'news' => $allNews, 'newPlugins' => $newPlugins ];
        $output = $this->latte->renderToString( JUNIPER_SERVER_DIR . '/theme/home.latte', $params );

        file_put_contents( JUNIPER_SERVER_DIR . '/_public/index.html', $output );   
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

            $contents = $this->server->curlGet( $site . '/wp-json/juniper/v1/releases/' );
            if ( $contents ) {
                $decodedContents = json_decode( $contents );        
                if ( is_array( $decodedContents ) ) {
                    foreach( $decodedContents as $num => $addOn ) {
                        LOG( sprintf( "Adding new ADDON [%s] of TYPE [%s]", $addOn->info->pluginName, $addOn->info->type ), 1 );
                        $addOnId = $this->server->addAddonToDb( $siteId, $addOn );

                        foreach( $addOn->releases as $num => $release ) {
                            LOG( sprintf( "Adding release with TAG [%s]", $release->tag ), 2 );
                            $this->server->addReleaseToDb( $addOnId, $release );
                        }

                        if ( $addOn->issues ) {
                            foreach( $addOn->issues as $issue ) {
                                LOG( sprintf( "Adding issues with NAME [%s]", $issue->title ), 2 );
                                $this->server->addIssueToDb( $addOnId, $issue );
                            } 
                        }
                    }
                }
            }
        }

        // Build plugin pages
        $plugins = $this->server->getPluginList();
        $this->writePluginPage( $plugins );

        foreach( $plugins as $plugin ) {
            $releases = $this->server->getPluginReleases( $plugin['id']);
            $issues = $this->server->getPluginIssues( $plugin['id'] );
            $this->writeSinglePluginPage( $plugin, $releases, $issues );
        }

        $this->writeHomePage();

        $this->server->stopDb();

        LOG( "Build process ending for self-replicating repository", 0 );
        echo "\n";
    }
}

$build = new Build;
$build->letsGo();
