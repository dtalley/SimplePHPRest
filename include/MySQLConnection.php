<?php

  if( !defined( "INCLUDE_DIR" ) ) {
    define( "INCLUDE_DIR", "" );
  }
  require_once INCLUDE_DIR . "MySQLQuery.php";

  /**
   * Class to allow an easy, elegant way to 
   * connect to a PostgreSQL database.
   */

  class MySQLConnection {
    
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
	  $host = $this->_host;
	  if ( $this->_port )
	  {
	    $host .= ":" . $this->_port;
	  }
      $this->_connection = mysqli_connect($host, $this->_user, $this->_pass, $this->_name);
      return $this->_connection;
    }

    //Return a new PGSQLQuery
    public function start() {
      return new MySQLQuery( $this );
    }

    //Submit a string of SQL to the server
    public function query( $sql ) {
      if( $this->_connection === NULL ) {
        $this->connect();
      }
      if ( $this->_connection !== NULL ) {
        return mysqli_query( $this->_connection, $sql );
      }
      return mysqli_query( $sql );
    }

    //Retrieve an associative array from a resource
    public function assoc( $resource ) {
      return mysqli_fetch_assoc( $resource );
    }

    //Free the memory a resource occupies
    public function free( $resource ) {
      return mysqli_free_result( $resource );
    }

    //Get the total number of rows from a resource
    public function total( $resource ) {
      return mysqli_num_rows( $resource );
    }

    //Return the last database error
    public function error() {
      return mysqli_error();
    }

    //Get the last val from a sequence update
    public function last() {
      $result = mysqli_insert_id();
      if( $result === false ) {
        return false;
      }
      return $result;
    }

    //Get the actual connection object
    public function getConnection() {
      return $this->_connection;
    }

    //Check if the connection is still active
    public function isConnected() {
      if( 
        $this->_connection !== NULL &&
        mysqli_ping($this->_connection)
      ) {
        return true;
      }
      return false;
    }

  }

?>