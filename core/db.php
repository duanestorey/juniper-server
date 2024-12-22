<?php

namespace Juniper\Server;

class DB {
    var $sql = null;

    public function __construct() {
        $this->sql = new \SQLite3( JUNIPER_SERVER_DIR . '/repo.db', SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE );
    }

    public function init() {
        $this->addSchema( JUNIPER_SERVER_DIR . '/schema/sites.sql' );
        $this->addSchema( JUNIPER_SERVER_DIR . '/schema/addons.sql' );
        $this->addSchema( JUNIPER_SERVER_DIR . '/schema/releases.sql' );
    }

    public function getLastInsertId() {
        return $this->sql->lastInsertRowID();
    }

    public function addSchema( $schemaFile ) {
        LOG( sprintf( "Adding scheme [%s]", $schemaFile ), 2 );

        $schemaContent = file_get_contents( $schemaFile );
        if ( $schemaContent ) {
            @$this->sql->query( $schemaContent );
        }
    }

    public function escapeWithTicks( $str ) {
        return "'" . $this->sql->escapeString( $str ) . "'";
    }

    public function prepare( $statement ) {
        return $this->sql->prepare( $statement );
    }

    public function query( $statement ) {
        return $this->sql->query( $statement );
    }

    public function shutdown() {
        $this->sql->close();
        $this->sql = null;
    }
}