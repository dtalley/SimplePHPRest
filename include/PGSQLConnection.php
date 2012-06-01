<?php

  if( !defined( "INCLUDE_DIR" ) ) {
    define( "INCLUDE_DIR", "" );
  }
  require_once INCLUDE_DIR . "PGSQLQuery.php";

  /**
   * Class to allow an easy, elegant way to 
   * connect to a PostgreSQL database.
   */

  class PGSQLConnection {
    
    private $_host = "";
    private $_port = "";
    private $_name = "";
    private $_user = "";
    private $_pass = "";

    private $_connection = NULL;

    //Set the database server host name
    public function setHost( $val ) {
      $this->_host = $val;
    }

    //Set the database server port
    public function setPort( $val ) {
      $this->_port = $val;
    }

    //Set the database name to use
    public function setName( $val ) {
      $this->_name = $val;
    }

    //Set the user name to log in with
    public function setUser( $val ) {
      $this->_user = $val;
    }

    //Set the password to log in with
    public function setPass( $val ) {
      $this->_pass = $val;
    }

    //Connect to the database with stored info
    public function connect() {
      $cstr = "";
      $this->add( 
        $cstr, "host", $this->_host 
      );
      $this->add( 
        $cstr, "port", $this->_port 
      );
      $this->add( 
        $cstr, "dbname", $this->_name 
      );
      $this->add( 
        $cstr, "user", $this->_user 
      );
      $this->add( 
        $cstr, "password", $this->_pass 
      );

      $this->_connection = pg_connect( $cstr );
      return $this->_connection;
    }

    //Return a new PGSQLQuery
    public function start() {
      return new PGSQLQuery( $this );
    }

    //Submit a string of SQL to the server
    public function query( $sql ) {
      if( $this->_connection === NULL ) {
        $this->connect();
      }
      if( $this->_connection !== NULL ) {
        return pg_query( $this->_connection, $sql );
      }
      return pg_query( $sql );
    }

    //Retrieve an associative array from a resource
    public function assoc( $resource ) {
      return pg_fetch_assoc( $resource );
    }

    //Free the memory a resource occupies
    public function free( $resource ) {
      return pg_free_result( $resource );
    }

    //Get the total number of rows from a resource
    public function total( $resource ) {
      return pg_num_rows( $resource );
    }

    //Return the last database error
    public function error() {
      return pg_last_error();
    }

    //Get the last val from a sequence update
    public function last() {
      $result = pg_query( 
        "SELECT lastval() AS last_val" 
      );
      if( $result === false ) {
        return false;
      }
      $last = pg_fetch_assoc( $result );
      pg_free_result( $result );
      if( isset( $last['last_val'] ) ) {
        return $last['last_val'];
      }
      return false;
    }

    //Connection string helper function
    private function add( 
      &$cstr, $key, $val 
    ) {
      if( $key && $val ) {
        if( $cstr ) {
          $cstr .= " ";
        }
        $cstr .= $key . "=" . $val;
      }
    }

    //Get the actual connection object
    public function getConnection() {
      return $this->_connection;
    }

    //Check if the connection is still active
    public function isConnected() {
      if( 
        $this->_connection !== NULL &&
        pg_connection_status( 
          $this->_connection 
        ) == PGSQL_CONNECTION_OK
      ) {
        return true;
      }
      return false;
    }

  }

?>