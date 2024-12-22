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
        LOG( sprintf( "Adding add-on to DB [%s]", $addOnData->info->slug ), 2 );

        $queryString = sprintf( 
            "INSERT INTO addons (site_id,type,name,slug,author_name,signing_authority,author_url,avatar_url,description,readme,stable_version,repo_version,banner_image_url,requires_php,requires_at_least,tested_up_to,open_issues_count,stars_count,watchers_count,subscribers_count,updated_at,created_at) " . 
            "VALUES (%u,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%u,%u,%u,%u,%u,%u)",
            $siteId,
            $this->db->escapeWithTicks( 'plugin' ),
            $this->db->escapeWithTicks( $addOnData->info->pluginName ),
            $this->db->escapeWithTicks( $addOnData->info->slug ),
            $this->db->escapeWithTicks( $addOnData->info->author ),
            $this->db->escapeWithTicks( $addOnData->info->signingAuthority ),
            $this->db->escapeWithTicks( $addOnData->info->authorUrl ),
            $this->db->escapeWithTicks( $addOnData->info->repoInfo->owner->avatar_url ),
            $this->db->escapeWithTicks( $addOnData->info->description ),
            $this->db->escapeWithTicks( $addOnData->info->readmeHtml ),
            $this->db->escapeWithTicks( $addOnData->info->stableVersion ),
            $this->db->escapeWithTicks( $addOnData->info->version ),
            $this->db->escapeWithTicks( $addOnData->info->bannerImage ),
            $this->db->escapeWithTicks( $addOnData->info->requiresPHP ),
            $this->db->escapeWithTicks( $addOnData->info->requiresAtLeast ),
            $this->db->escapeWithTicks( $addOnData->info->testedUpTo ),
            $addOnData->info->repoInfo->open_issues_count, 
            $addOnData->info->repoInfo->stargazers_count, 
            $addOnData->info->repoInfo->watchers_count, 
            $addOnData->info->repoInfo->subscribers_count, 
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
            $this->db->escapeWithTicks( $releaseData->description ),
            $this->db->escapeWithTicks( $releaseData->package_url ),
            $releaseData->signed ? 1 : 0,
            $releaseData->publishedDate
        );

        $this->db->query( $queryString );
    }

    public function getPluginList() {
        $queryString = sprintf( "SELECT * FROM addons WHERE type=%s ORDER BY name", $this->db->escapeWithTicks( 'plugin' ) );
        $result = $this->db->query( $queryString );

        $plugins = [];
        while ( $row = $result->fetchArray( SQLITE3_ASSOC ) ) {
            $plugins[] = $row;
        }

        return $plugins;
    }

    public function stopDb() {
        LOG( "Closing Sqlite database", 1 );
        $this->db->shutdown();
    }

    public function getSites() {
       return $this->config[ 'repo.sites' ];
    }
}