<?php

  if( !defined( "INCLUDE_DIR" ) ) {
    define( "INCLUDE_DIR", "" );
  }
  require_once INCLUDE_DIR . "DataTree.php";
  require_once INCLUDE_DIR . "rest/RestfulHandler.php";

  class Commander extends RestfulHandler {
    
    public function respond( DataTree $response ) {
      $directories = explode( $this->_uri );
      $root = array_shift( $directories );
      if( $root == "commander" ) {
        $this->commander( $response, $directories );
      } else if( $root == "commanders" ) {
        $this->commanders( $response, $directories );
      } else {
        $this->_service->error( 10002 );
      }
    }

    private function commander( DataTree $response, array $directories ) {
      $commander = isset( $directories[0] ) ? $directories[0] : NULL;
      if( $this->_method == "head" ) {
        $this->checkCommander( $response, $commander );
      } else if( $this->_method == "post" ) {
        $this->createCommander( $response );
      } else if( $this->_method == "put" ) {
        $this->updateCommander( $response, $commander );
      } else if( $this->_method == "delete" ) {
        $this->deleteCommander( $response, $commander );
      } else if( $this->_method == "get" ) {
        $this->getCommander( $response, $commander );
      } else {
        $this->_service->error( 10002 );
      }
    }

    private function commanders( DataTree $response, array $directories ) {
      if( $this->_method == "get" ) {
        $this->listCommanders( $response );
      } else {
        $this->_service->error( 10002 );
      }
    }

    private function checkAuthentication() {
      $authData = new DataTree();
      $this->_service->respond( 
        "auth/verify", $this->_method, true, true, $authData 
      );
      if( $authData->get( "authorized" ) ) {
        return $authData->get( "id" );
      }
      return 0;
    }

    private function checkCommander( DataTree $response, $name ) {
      if( !$name ) {
        $name = $this->input( "name", false );
      }
      if( !$name ) {
        $this->setCode( 400 );
        $this->_service->error( 10003, 1, "No name provided" );
        return;
      }
      $query = $this->_db->start();
      $commanders = $query->open( TABLE_COMMANDERS );
      $commanders->where( "cdr_name", $query->sanitize( $name ) );
      if( $query->select() ) {
        $this->setCode( 200 );
      } else {
        $this->setCode( 404 );
      }
    }

    private function createCommander( DataTree $response ) {
      $user = $this->checkAuthentication();
      if( !$user ) {
        $this->setCode( 400 );
        $this->_service->error( 10003, 2, "No authenticated user found" );
        return;
      }
      $name = $this->input( "name", false );
      if( $name === false ) {
        $this->setCode( 400 );
        $this->_service->error( 10003, 3, "No commander name provided" );
        return;
      }
      if( preg_match( "/[^A-Za-z0-9\-_]/", $name ) ) {
        $this->setCode( 400 );
        $this->_service->error( 10003, 4, "Invalid commander name provided" );
        return;
      }
      $query = new PGSQLQuery( $this->_db );
      $commanders->open( TABLE_COMMANDERS, "c" );
      $commanders->where( "name", $query->sanitize( $name ) );
      if( $query->select() ) {
        $this->setCode( 400 );
        $this->_service->error( 10003, 5, "Commander name already exists" );
        return;
      }
      $userData = new DataTree();
      $this->_service->respond(
        "user", "get", true, true, $userData, array( "id" => $user )
      );
      if( $userData->get( "user" ) === NULL ) {
        $this->setCode( 400 );
        $this->_service->error( 10003, 6, "Invalid user provided" );
        return;
      }

      $time = time();
      $query = new PGSQLQuery( $this->_db );
      
      $commanders = $query->open( TABLE_COMMANDERS );      
      $commanders->set( "cdr_name", $query->sanitize( $name ) );
      $commanders->set( "cdr_created", $time );
      $commanders->set( "cdr_active", $time );
      $commanders->set( "usr_id", $user );
      if( ( $id = $query->insert() ) === false ) {
        $this->setCode( 500 );
        $this->_service->error( 10004, 1, "Could not add commander to database" );
        return;
      }

      $response->store( "success", true );
      $response->store( "id", $id );
    }

    private function updateCommander( DataTree $response, $name = false ) {
      if( $name === false ) {
        $name = $this->input( "name", false );
      }
      $query = $this->_db->start();
      $commanders = $query->open( TABLE_COMMANDERS );
      $commanders->where( "cdr_name", $query->sanitize( $name ) );
      $commanderData = $query->select();
      if( !$commanderData ) {
        $this->createCommander( $response );
        return;
      }
      $user = $this->checkAuthentication();
      if( !$user ) {
        $this->setCode( 400 );
        $this->_service->error( 10003, 1, "No authenticated user found" );
        return;
      }
      if( $commanderData['usr_id'] != $user ) {
        $this->setCode( 400 );
        $this->_service->error( 10003, 2, "Permission denied to update commander" );
        return;
      }

      $response->store( "success", true );
    }

    private function deleteCommander( DataTree $response, $name ) {
      $user = $this->checkAuthentication();
      if( !$user ) {
        $this->setCode( 400 );
        $this->_service->error( 10003, 1, "No authenticated user found" );
        return;
      }
      if( !$name ) {
        $name = $this->input( "name", false );
      }
      $id = $this->input( "id", false );
      $query = $this->_db->start();
      $commanders = $query->open( TABLE_COMMANDERS );
      if( $name ) {
        $commanders->where( "cdr_name", $query->sanitize( $name ) );
      }
      if( $id ) {
        $commanders->where( "cdr_id", $query->sanitize( $id ) );
      }
      if( !$name && !$id ) {
        $this->setCode( 400 );
        $this->_service->error( 10003, 2, "No name or ID provided" );
        return;
      }
      $commanderData = $query->select();
      if( !$commanderData ) {
        $this->setCode( 400 );
        $this->_service->error( 10003, 3, "Invalid name or id provided" );
        return;
      }
      if( $commanderData['usr_id'] != $user ) {
        $this->setCode( 400 );
        $this->_service->error( 10003, 4, "Permission denied to delete commander" );
        return;
      }
      $query = $this->_db->start();
      $commanders = $query->open( TABLE_COMMANDERS );
      $commanders->where( "cdr_id", $commanderData['cdr_id'] );
      if( !$query->delete() ) {
        $this->setCode( 500 );
        $this->_service->error( 10004, 1, "Unable to delete commander" );
        return;
      }

      $response->store( "success", true );
    }

    private function getCommander( DataTree $response, $name ) {
      $user = $this->checkAuthentication();
      if( !$name ) {
        $name = $this->input( "name", false );
      }
      $id = $this->input( "id", false );
      if( !$name && !$id ) {
        $this->setCode( 400 );
        $this->_service->error( 10003, 1, "No name or ID provided" );
        return;
      }
      $query = $this->_db->start();
      $commanders = $query->open( TABLE_COMMANDERS );
      if( $name ) {
        $commanders->where( "cdr_name", $query->sanitize( $name ) );
      }
      if( $id ) {
        $commanders->where( "cdr_id", $query->sanitize( $id ) );
      }
      $commanderData = $query->select();
      if( !$commanderData ) {
        $this->setCode( 404 );
        $this->_service->error( 10003, 2, "Commander not found" );
        return;
      }

      $response->store( "id", $commanderData['cdr_id'] );
      $response->store( "name", $commanderData['cdr_name'] );
      $response->store( "created", $commanderData['cdr_created'] );
    }

    private function listCommanders( DataTree $response ) {
      $user = $this->checkAuthentication();
    }

  }

?>