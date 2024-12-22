<?php

namespace Juniper\Server;

class LogListenerShell extends LogListener {
    protected $currentLevel = LOG::INFO;
    protected $startTime = 0;

    public function __construct() {
        $this->startTime = microtime( true );
    }

    public function setLevel( $level ) {
        $this->currentLevel = $level;
    }

    public function log( $message, $tabs, $level ) {
        if ( $level < $this->currentLevel ) {
            return;
        }

        $message = $this->getTabsAsSpaces( $tabs ) . $message;

        switch( $level ) {
            case Log::DEBUG:
                echo "\033[90;10m" . sprintf( "[DEBUG][%7.3f] %s", microtime( true ) - $this->startTime, $message ) . "\033[0m\n";
                break;
            case Log::INFO:
                echo "\033[92;10m" . sprintf( "[INFO ][%7.3f] %s", microtime( true ) - $this->startTime, $message ) . "\033[0m\n";
                break;
            case Log::WARNING:
                echo "\033[33;10m" . sprintf( "[WARN ][%7.3f] %s", microtime( true ) - $this->startTime, $message ) . "\033[0m\n"; 
                break;
            case Log::ERROR:
                echo "\033[91;10m" . sprintf( "[ERROR][%7.3f] %s", microtime( true ) - $this->startTime, $message ) . "\033[0m\n"; 
                break;    
        }
    }

    private function getTabsAsSpaces( $tabs ) {
        $spaces = '';
        for( $i = 0; $i < $tabs; $i++ ) {
            $spaces = $spaces . '  ';
        }

        return $spaces;
    }
}