<?php

namespace Juniper\Server;

class Log {
    const DEBUG = 0;
    const INFO = 1;
    const WARNING = 2;
    const ERROR = 3;
    const FATAL = 10;

    private static $instance = null;
    var $listeners = [];

    protected function __construct() {
    }

    public function log( $message, $tabs = 0, $level = Log::INFO ) {
        if ( count( $this->listeners ) ) {
            foreach( $this->listeners as $listener ) {
                $listener->log( $message, $tabs, $level );
            }
        }
    }

    public function installListener( $listener ) {
        $this->listeners[] = $listener;
    }

    static function instance() {
        if ( self::$instance == null ) {
            self::$instance = new Log();
        }

        return self::$instance;
    }
}

function LOG( $message, $tabs = 0, $level = Log::INFO ) { Log::instance()->log( $message, $tabs, $level ); }