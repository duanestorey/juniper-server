<?php


namespace Juniper\Server;

require_once( JUNIPER_SERVER_DIR . '/core/config.php' );

class Server {
    var $config = null;

    public function __construct() {
        $this->config = new Config;
    }
}