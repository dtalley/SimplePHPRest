<?php

  if( !defined( "INCLUDE_DIR" ) ) {
    define( "INCLUDE_DIR", "" );
  }
  require_once INCLUDE_DIR . "DataTree.php";
  require_once INCLUDE_DIR . "rest/RestfulHandler.php";

  class User extends RestfulHandler {
    
    public function respond( DataTree $response ) {
      $directories = explode( "/", $this->_uri );
      $root = array_shift( $directories );
      if( $root == "user" ) {
        $this->handleUser( $response, $directories );
      } else if( $root == "users" ) {
        $this->handleUsers( $response, $directories );
      } else {
        $this->_service->error( 10002, 1, "Invalid general user request" );
      }
    }

    private function handleUser( $response, $directories ) {
      $user = isset( $directories[0] ) ? $directories[0] : NULL;
      if( $this->_method == "head" ) {
        $this->checkUser();
      } else if( $this->_method == "post" ) {
        $this->createUser( $response );
      } else if( $this->_method == "put" ) {
        $this->updateUser( $response );
      } else if( $this->_method == "delete" ) {
        $this->deleteUser( $response );
      } else if( $this->_method == "get" ) {
        $this->getUser( $response );
      } else {
        $this->_service->error( 10002, 1, "Invalid user request" );
      }
    }

    private function handleUsers( $response, $directories ) {
      if( $this->_method == "get" ) {
        $this->listUsers( $response );
      } else {
        $this->_service->error( 10002, 1, "Invalid users request" );
      }
    }

    private function checkUser() {
      $type = $this->input( "type", false );

      /**
       * Check to make sure the provided type value
       * is either the name of a valid user type, or
       * the ID of a valid user type.
       */
      $query = new PGSQLQuery( $this->_db );
      $types = $query->open( TABLE_USER_TYPES );
      $types->select("utp_id");
      if( preg_match( "/[^0-9]/", $type ) ) {
        $types->where( "utp_name", $query->sanitize( $type ) );
      } else {
        $types->where( "utp_id", $type );
      }
      $typeData = $query->select();
      if( !$typeData ) {
        $this->setCode( 400 );
        $this->_service->error( 10003, 1, "Invalid user type provided" );
        return;
      }
      $key = $this->input( "key", false );
      if( $key === false ) {
        $this->setCode( 400 );
        $this->_service->error( 10003, 2, "No user key provided" );
        return;
      }

      /**
       * Check if the user key and type already exists in the
       * database, and if so, indicate as such.
       */
      $query = new PGSQLQuery( $this->_db );
      $users = $query->open( TABLE_USERS );
      $users->select("*");
      $users->where( "usr_key", $query->sanitize( $key ), "AND" );
      $users->where( "utp_id", $typeData['utp_id'] );
      if( $query->select() ) {
        $this->setCode( 200 );
      } else {
        $this->setCode( 404 );
      }
    }

    private function createUser( $response ) {
      if( !$this->_secure ) {
        $this->setCode( 400 );
        $this->_service->error( 10003, 1, "Secure connection must be used" );
        return;
      }
      $time = time();
      $type = $this->input( "type", false );
      /**
       * Check to make sure the provided type value
       * is either the name of a valid user type, or
       * the ID of a valid user type.
       */
      $query = new PGSQLQuery( $this->_db );
      $types = $query->open( TABLE_USER_TYPES );
      $types->select("*");
      if( preg_match( "/[^0-9]/", $type ) ) {
        $types->where( "utp_name", $query->sanitize( $type ) );
      } else {
        $types->where( "utp_id", $type );
      }
      $typeData = $query->select();
      if( !$typeData ) {
        $this->setCode( 400 );
        $this->_service->error( 10003, 1, "Invalid user type provided" );
        return;
      }
      $key = NULL;
      if( $typeData['utp_name'] != "default" ) {
        $key = $this->input( "key", false );
        if( $key === false ) {
          $this->setCode( 400 );
          $this->_service->error( 10003, 2, "No user key provided" );
          return;
        }
      }
      $password = NULL;
      if( $typeData['utp_name'] == "default" ) {
        $password = $this->input( "password", false );
        if( $password === false ) {
          $this->setCode( 400 );
          $this->_service->error( 10003, 3, "No password provided" );
          return;
        }
        $hasher = new PasswordHash();
        $password = $hasher->hash( $password, false );
      }

      /**
       * Check if the user key and type already exists in the
       * database, and if so, indicate as such.
       */
      $query = new PGSQLQuery( $this->_db );
      $users = $query->open( TABLE_USERS );
      $users->select("*");
      $users->where( "usr_key", $query->sanitize( $key ), "AND" );
      $users->where( "utp_id", $typeData['utp_id'] );
      if( $query->select() ) {
        $this->setCode( 409 );
        $this->_service->error( 10003, 4, "User already exists" );
        return;
      }

      $email = $this->input( "email", false );
      /**
       * An e-mail must be provided for this
       * to be a valid request
       */
      if( $email === false ) {
        $this->setCode( 400 );
        $this->_service->error( 10003, 5, "No e-mail provided" );
        return;
      }
      /**
       * The e-mail must be in the proper e-mail
       * format for this to be a valid request
       */
      if( !preg_match( "/^([^@\s]+)@([^@\s]+)\.([^@.\s]+)$/", $email ) ) {
        $this->setCode( 400 );
        $this->_service->error( 10003, 6, "Invalid e-mail provided" );
      }
      
      $query = new PGSQLQuery( $this->_db );

      $users = $query->open( TABLE_USERS );     
      if( $key ) { 
        $users->set( "usr_key", $query->sanitize( $key ) );
      }
      $users->set( "utp_id", $typeData['utp_id'] );
      $users->set( "usr_email", $query->sanitize( $email ) );
      $users->set( "usr_created", $time );
      $users->set( "usr_active", $time );
      if( $password !== NULL ) {
        $users->set( "usr_code", $password );
      }
      if( ( $id = $query->insert() ) === false ) {
        $this->setCode( 500 );
        $this->_service->error( 10004, 1, "Unable to add user" );
        return;
      }

      $response->store( "success", true );
      $response->store( "id", $id );
    }

    private function updateUser( $response ) {
      
    }

    private function deleteUser( $response ) {
      
    }

    private function getUser( $response ) {
      
    }

    private function listUsers( $response ) {
      
    }

  }

?>