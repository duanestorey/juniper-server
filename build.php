<?php

namespace Juniper\Server;

use \Wongyip\HTML\Beautify;
use ScssPhp\ScssPhp\Compiler;

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

        @mkdir( JUNIPER_SERVER_DIR . '/cache', 0775 );
        @mkdir( JUNIPER_SERVER_DIR . '/_public', 0775 );
        @mkdir( JUNIPER_SERVER_DIR . '/_dist', 0775 );
        $this->latte->setTempDirectory( JUNIPER_SERVER_DIR . '/cache' );
    }

    public function beautify( $html ) {
        // return Beautify::html( $html )
        return $html;
    }

    public function getSiteData() {
        $siteData = new \stdClass;
        $siteData->bust = time();
        return $siteData;
    }

    public function compileAndCopyAssets() {
        $sassFile = JUNIPER_SERVER_DIR . '/src/juniper-server.scss';

         LOG( sprintf( "Compiling Sas file [%s]", $sassFile ), 0 );
        $sassContents = file_get_contents( $sassFile );
        if ( $sassContents ) {
            $compiler = new Compiler();
            $css = $compiler->compileString( $sassContents )->getCss();

            @mkdir( JUNIPER_SERVER_DIR . '/_public/dist/', 0755, true );
            file_put_contents( JUNIPER_SERVER_DIR . '/_public/dist/juniper-server.css', $css );
        }
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
        $params = [ 'plugins' => $plugins, 'site' => $this->getSiteData() ];
        $output = $this->latte->renderToString( JUNIPER_SERVER_DIR . '/theme/plugins.latte', $params );

        @mkdir( JUNIPER_SERVER_DIR . '/_public/plugins/', 0755, true );
        file_put_contents( JUNIPER_SERVER_DIR . '/_public/plugins/index.html', $this->beautify( $output ) );
    }

    public function findReleaseWithTag( $releases, $tag ) {
        $latestRelease = false;

        foreach( $releases as $release ) {
           if ( $release['release_tag'] == trim( $tag ) ) {
                $latestRelease = $release;
                break;
            }
        }

        return $latestRelease;
    }

    public function writeSinglePluginPage( $plugin, $releases, $issues ) {
        $latestRelease = $this->findReleaseWithTag( $releases, $plugin['stable_version'] );
        if ( !$latestRelease ) {
            if ( count ( $releases ) ) {
                $latestRelease = $releases[ 0 ];
            }
        }

        $params = [ 'plugin' => $plugin, 'releases' => $releases, 'issues' => $issues, 'latestRelease' => $latestRelease, 'site' => $this->getSiteData() ];
        $output = $this->latte->renderToString( JUNIPER_SERVER_DIR . '/theme/plugin-single.latte', $params );

        @mkdir( JUNIPER_SERVER_DIR . '/_public/plugins/' . $plugin['slug'], 0755, true );
        file_put_contents( JUNIPER_SERVER_DIR . '/_public/plugins/' . $plugin['slug'] . '/index.html', $this->beautify( $output ) );
    }

    public function writeRankedXmlPage() {
        $addons = $this->server->getRankedPluginList();

        $params = [ 'addons' => $addons, 'site' => $this->getSiteData() ];
        $output = $this->latte->renderToString( JUNIPER_SERVER_DIR . '/theme/plugins-xml.latte', $params );

        @mkdir( JUNIPER_SERVER_DIR . '/_public/api/ranked/', 0755, true );
        file_put_contents( JUNIPER_SERVER_DIR . '/_public/api/ranked/index.xml', $output );
    }

    public function writeHomeLikePage( $template = 'home.latte', $destFile = 'index.html' ) {
        $newsSites = $this->server->getConfigSetting( 'repo.news' );
        $allNews = [];

        foreach( $newsSites as $site ) {
            $content = \Feed::loadRss( $site );

            foreach ( $content->item as $item ) {
                $newsItem = new \stdClass;

                $newsItem->title = $item->title;
                $newsItem->url = $item->link;
                $newsItem->timestamp = $item->timestamp;
                $newsItem->desc = $item->description;
                $newsItem->content = $item->{'content:encoded'};

                $allNews[ $newsItem->timestamp->__toString() ] = $newsItem;
            }
        }

        krsort( $allNews );
        $allNews = array_slice( $allNews, 0, 5 );

        $newPlugins = $this->server->getNewestAddons();

        $params = [ 'news' => $allNews, 'newPlugins' => $newPlugins, 'site' => $this->getSiteData() ];
        $output = $this->latte->renderToString( JUNIPER_SERVER_DIR . '/theme/' . $template, $params );

        file_put_contents( JUNIPER_SERVER_DIR . '/_public/' . $destFile, $this->beautify( $output ) );   
    }

    public function letsGo() {
        $this->branding();
        LOG( "Build process starting for self-replicating repository", 0 );

        $this->server->startDb();
        $this->server->loadConfig();
        $this->compileAndCopyAssets();
        
        $sites = $this->server->getSites();

        foreach( $sites as $site ) {     
            $site = rtrim( $site, '/' );
            $site = rtrim( $site, '/' );

            $siteId = $this->server->addSiteToDb( $site );

            LOG( sprintf( "Importing site [%s]", $site ), 1 );

            $contents = $this->server->curlGet( $site . '/wp-json/juniper/v1/plugins/?v=' . time() );
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

        $this->writeHomeLikePage();

        @mkdir( JUNIPER_SERVER_DIR . '/_public/submit', 0755, true );
        $this->writeHomeLikePage( 'submit.latte', 'submit/index.html' );

        $this->writeRankedXmlPage();

        $this->server->stopDb();

        LOG( "Build process ending for self-replicating repository", 0 );
        echo "\n";
    }
}

if (\PHP_SAPI === 'cli') {
    $build = new Build;
    $build->letsGo();
}
