<?php


namespace Juniper\Server;

require_once( JUNIPER_SERVER_DIR . '/core/config.php' );
require_once( JUNIPER_SERVER_DIR . '/core/db.php' );
require_once( JUNIPER_SERVER_DIR . '/core/log-listener.php' );
require_once( JUNIPER_SERVER_DIR . '/core/log-listener-shell.php' );
require_once( JUNIPER_SERVER_DIR . '/core/log.php' );

class Server {
    var $config = null;
    var $db = null;

    public function __construct() {
        Log::instance()->installListener( new LogListenerShell() );

        $this->db = new DB();
    }

    public function loadConfig() {
        $this->config = Config::load( JUNIPER_SERVER_DIR . '/config/site.yaml' );
        $this->config = Config::flatten( $this->config );
    }

    public function startDb() {
        LOG( "Opening Sqlite database", 1 );

        $this->db->init();
    }

    public function addSiteToDb( $siteUrl ) {
        LOG( sprintf( "Adding site to DB [%s]", $siteUrl ), 2 );
        $queryString = sprintf( 
            "INSERT INTO sites (url,num_failures,updated_at,first_added) VALUES (%s,0,%u,%u)",
            $this->db->escapeWithTicks( $siteUrl ),
            time(),
            time()
        );

        $this->db->query( $queryString );

        return $this->db->getLastInsertId();
    }

    public function addAddonToDb( $siteId, $addOnData ) {
        LOG( sprintf( "Adding add-on to DB [%s]", $addOnData->slug ), 2 );

        $queryString = sprintf( 
            "INSERT INTO addons (site_id,type,name,slug,description,stable_version,banner_image_url,requires_php,requires_at_least,tested_up_to,updated_at,created_at) " . 
            "VALUES (%u,%s,%s,%s,%s,%s,%s,%s,%s,%s,%u,%u)",
            $siteId,
            $this->db->escapeWithTicks( 'plugin' ),
            $this->db->escapeWithTicks( $addOnData->info->pluginName ),
            $this->db->escapeWithTicks( $addOnData->slug ),
            $this->db->escapeWithTicks( $addOnData->info->description ),
            $this->db->escapeWithTicks( $addOnData->info->stable ),
            '\'\'',
            $this->db->escapeWithTicks( $addOnData->info->requiresPHP ),
            $this->db->escapeWithTicks( $addOnData->info->requiresAtLeast ),
            $this->db->escapeWithTicks( $addOnData->info->testedUpTo ),
            time(),
            time()
        );

        $this->db->query( $queryString );
        return $this->db->getLastInsertId();
    }

    public function addReleaseToDb( $addOnId, $releaseData ) {
        LOG( sprintf( "Adding release to DB [%s]", $releaseData->tagName ), 2 );

        $queryString = sprintf( 
            "INSERT INTO releases (addon_id,release_tag,name,description,download_url,signed,release_date) " . 
            "VALUES (%u,%s,%s,%s,%s,%d,%u)",
            $addOnId,
            $this->db->escapeWithTicks( $releaseData->tagName ),
            $this->db->escapeWithTicks( $releaseData->name ),
            $this->db->escapeWithTicks( $releaseData->name ),
            $this->db->escapeWithTicks( $releaseData->package_url ),
            $releaseData->signed ? 1 : 0,
            $releaseData->publishedDate
        );

        $this->db->query( $queryString );
    }

    public function stopDb() {
        LOG( "Closing Sqlite database", 1 );
        $this->db->shutdown();
    }

    public function getSites() {
       return $this->config[ 'repo.sites' ];
    }
}