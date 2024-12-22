<?php

namespace Juniper\Server;

abstract class LogListener {
    abstract function setLevel( $level );
    abstract function log( $message, $tabs, $level );
}