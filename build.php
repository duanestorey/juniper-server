<?php

namespace Juniper\Server;

use \Wongyip\HTML\Beautify;
use ScssPhp\ScssPhp\Compiler;

define( 'SKIP_BUILD', 0 );

ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);

define( 'JUNIPER_SERVER_VER', '1.1.0' );
define( 'JUNIPER_SERVER_DIR', dirname( __FILE__ ) );

require_once( JUNIPER_SERVER_DIR . '/core/server.php' );

class Build {
    var $latte = null;
    var $server = null;

    public function __construct() {
        $this->server = new Server();
        

        @mkdir( JUNIPER_SERVER_DIR . '/cache', 0775 );
        @mkdir( JUNIPER_SERVER_DIR . '/_public', 0775 );
        @mkdir( JUNIPER_SERVER_DIR . '/_dist', 0775 );
    }

    public function beautify( $html ) {
        // return Beautify::html( $html )
        return $html;
    }

    public function getSiteData() {
        $siteData = new \stdClass;
        $siteData->bust = time();
        $siteData->totalDownloads = $this->server->getTotalDownloads();
        $siteData->totalPlugins = $this->server->getTotalPlugins();

        return $siteData;
    }

    public function compileAndCopyAssets() {
        $sassFile = JUNIPER_SERVER_DIR . '/src/juniper-server.scss';

        LOG( sprintf( "Compiling Sas file [%s]", $sassFile ), 1 );
        $sassContents = file_get_contents( $sassFile );
        if ( $sassContents ) {
            $compiler = new Compiler();
            $css = $compiler->compileString( $sassContents )->getCss();

            @mkdir( JUNIPER_SERVER_DIR . '/_public/dist/', 0755, true );
            file_put_contents( JUNIPER_SERVER_DIR . '/_public/dist/juniper-server.css', $css );
        }
    }

    public function getDefaultImage() {
        return 'https://images.unsplash.com/photo-1465146344425-f00d5f5c8f07?q=80&w=2952&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D';
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
        LOG( "Writing plugin index file", 1 );

        $params = [ 
            'plugins' => $plugins, 
            'site' => $this->getSiteData(), 
            'title' => 'List of plugins for Wordpress - ' . $this->server->config[ 'repo.name' ],
            'desc' => 'The main plugin listings for self-hosted Github plugins for WordPress',
            'image' => $this->getDefaultImage()
        ];

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

    public function cleanupReadme( $plugin ) {
        $result = preg_match_all( '!<a\s+(?:[^>]*?\s+)?href=(["\'])(.*?)\1!', $plugin[ 'readme' ], $matches );
        if ( $result ) {
            $changed = false;
            $newReadme = $plugin[ 'readme' ];
            foreach( $matches[2] as $num => $link ) {
                $originalLink = $link;

                if ( strpos( $originalLink, 'http://' ) === false && strpos( $originalLink, 'https://' ) === false ) {
                    // this is not an external link
                    $newLink = 'https://github.com/' . $plugin[ 'slug' ] . '/' . $originalLink;
                    $newReadme = str_replace( $originalLink, $newLink, $newReadme );

                    $changed = true;
                }
            }

            if ( $changed ) {
                $plugin[ 'readme' ] = $newReadme;
            }
        }

        return $plugin;

    }

    public function writeSinglePluginPage( $plugin, $releases, $issues ) {
        LOG( sprintf( "Writing individual plugin [%s]", $plugin[ 'slug' ] ), 1 );

        $latestRelease = $this->findReleaseWithTag( $releases, $plugin['stable_version'] );
        if ( !$latestRelease ) {
            if ( count ( $releases ) ) {
                $latestRelease = $releases[ 0 ];
            }
        }

        $newPlugin = $this->cleanUpReadme( $plugin );

        $params = [ 
            'plugin' => $newPlugin, 
            'releases' => $releases, 
            'issues' => $issues, 
            'latestRelease' => $latestRelease, 
            'site' => $this->getSiteData(),
            'title' => $plugin['name' ] . ' by ' . $plugin['author_name'],
            'desc' => $plugin['description'],
            'image' => ( $plugin['banner_image_url'] ? $plugin['banner_image_url'] : $this->getDefaultImage() )
        ];
        $output = $this->latte->renderToString( JUNIPER_SERVER_DIR . '/theme/plugin-single.latte', $params );

        @mkdir( JUNIPER_SERVER_DIR . '/_public/plugins/' . $plugin['slug'], 0755, true );
        file_put_contents( JUNIPER_SERVER_DIR . '/_public/plugins/' . $plugin['slug'] . '/index.html', $this->beautify( $output ) );
    }

    public function writeHomeLikePage( $template = 'home.latte', $destFile = 'index.html', $title = '', $desc = '' ) {
        LOG( sprintf( "Writing page [%s]", $destFile ), 1 );
    
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

        $params = [ 
            'news' => $allNews, 
            'newPlugins' => $newPlugins, 
            'site' => $this->getSiteData(), 
            'title' => $title,
            'desc' => $desc,
            'image' => $this->getDefaultImage()
        ];
        $output = $this->latte->renderToString( JUNIPER_SERVER_DIR . '/theme/' . $template, $params );

        file_put_contents( JUNIPER_SERVER_DIR . '/_public/' . $destFile, $this->beautify( $output ) );   
    }

    public function letsGo() {
        $this->branding();

        if ( !file_exists( 'vendor' ) ) {
            LOG( 'You need to run [composer install] before building', 1, LOG::ERROR );
            LOG( "Build process ended prematuredly for self-replicating repository", 0 );
            die;
        }

        if ( !file_exists( dirname( __FILE__ ) . '/site.yaml' ) ) {
            LOG( "Missing site.yaml configuration file - copy the site.yaml from the config directory and modify it", 0, LOG::ERROR );
            die;
        }

        require_once( 'vendor/autoload.php' );

        $this->latte = new \Latte\Engine;
        $this->latte->setTempDirectory( JUNIPER_SERVER_DIR . '/cache' );
        
        $this->server->loadConfig();
        if ( $this->server->config[ 'repo.role.producer' ] == 0 ) {
            // we are a consumer
            LOG( "Consumer mode - grabbing Sqlite database from producer", 0 );
            $dbLocation = $this->server->config[ 'repo.role.consumer_source' ] . '/repo.db';

            @unlink( '_public/repo.db' );
            @copy( $dbLocation, '_public/repo.db' );

            if ( file_exists( '_public/repo.db' ) ) {
                 LOG( "File successfully downloaded", 1 );
            } else {
                 LOG( sprintf( "Error downloading database file from [%s]", $this->server->config[ 'repo.role.consumer_source' ] ), 1, LOG::ERROR );
                 LOG( "Build process ended prematuredly for self-replicating repository", 0 );
                 die;
            }
        } else {
            if ( !SKIP_BUILD ) {    
            
                LOG( "Build process starting for self-replicating repository", 0 );

                $this->server->destroyAll();

                $sites = $this->server->getSites();

                foreach( $sites as $site ) {     
                    $site = rtrim( $site, '/' );
                    $site = rtrim( $site, '/' );

                    $siteId = $this->server->addSiteToDb( $site );

                    LOG( sprintf( "Importing site [%s]", $site ), 1 );

                    $contents = $this->server->curlGet( $site . '/wp-json/juniper/v1/releases/?v=' . time() );
                    if ( $contents ) {
                        $decodedContents = json_decode( $contents );   

                        // to handle our new versioning
                        if ( isset( $decodedContents->client_version ) ) {
                        
                            $decodedContents = $decodedContents->releases;
                        }

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
            }
        }

        $this->server->startDb();
        $this->compileAndCopyAssets();

        // Build plugin pages
        $plugins = $this->server->getPluginList();
        $this->writePluginPage( $plugins );

        foreach( $plugins as $plugin ) {
            $releases = $this->server->getPluginReleases( $plugin['id']);
            $issues = $this->server->getPluginIssues( $plugin['id'] );
            $this->writeSinglePluginPage( $plugin, $releases, $issues );
        }

        $this->writeHomeLikePage( 'home.latte', 'index.html', $this->server->config[ 'repo.name' ], 'The NotWP Repositority of self-hosted Github plugins and themes for WordPress'  );

        @mkdir( JUNIPER_SERVER_DIR . '/_public/submit', 0755, true );
        $this->writeHomeLikePage( 'submit.latte', 'submit/index.html', 'Submit new plugin or theme - ' . $this->server->config[ 'repo.name' ], "Submit a new plugin to the Not WP Repository for WordPress" );

        $this->server->stopDb();

        LOG( "Build process ending for self-replicating repository", 0 );
        echo "\n";
    }
}

if (\PHP_SAPI === 'cli') {
    $build = new Build;
    $build->letsGo();
}
