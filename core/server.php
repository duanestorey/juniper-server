<?php


namespace Juniper\Server;

require_once( JUNIPER_SERVER_DIR . '/core/config.php' );
require_once( JUNIPER_SERVER_DIR . '/core/log-listener.php' );
require_once( JUNIPER_SERVER_DIR . '/core/log-listener-shell.php' );
require_once( JUNIPER_SERVER_DIR . '/core/log.php' );

class Server {
    var $config = null;

    public function __construct() {
        Log::instance()->installListener( new LogListenerShell() );
    }

    public function loadConfig() {
        $this->config = Config::load( JUNIPER_SERVER_DIR . '/config/site.yaml' );
    }
}