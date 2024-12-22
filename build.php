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

        $this->server->loadConfig();



        LOG( "Build process ending for self-replicating repository", 0 );
    }
}

$build = new Build;
$build->letsGo();
