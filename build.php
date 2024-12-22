<?php

namespace Juniper\Server;

define( 'JUNIPER_SERVER_DIR', dirname( __FILE__ ) );

require_once( JUNIPER_SERVER_DIR . '/vendor/autoload.php' );
require_once( JUNIPER_SERVER_DIR . '/core/server.php' );

class Build {
    var $server = null;

    public function __construct() {
        $this->server = new Server();
    }

    public function letsGo() {

    }
}

$build = new Build;
$build->letsGo();
