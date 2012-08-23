<?php

  if( !defined( "INCLUDE_DIR" ) ) {
    define( "INCLUDE_DIR", "" );
  }
  require_once INCLUDE_DIR . "DataTree.php";
  require_once INCLUDE_DIR . "rest/RestfulHandler.php";
  require_once INCLUDE_DIR . "PasswordHash.php";

  class Score extends RestfulHandler {

    private $_mysql = NULL;
    
    public function respond( DataTree $response ) {
      if( ( $this->_mysql = $this->getDatabase( "MySQLConnection" ) ) === false ) {
        $this->_service->error( 10005, 1, "A MySQLConnection object must be present" );
      }
      $directories = explode( "/", $this->_uri );
      $root = array_shift( $directories );
      if( $root == "score" ) {
        $this->handleScore( $response, $directories );
      } else if( $root == "scores" ) {
        $this->handleScores( $response, $directories );
      } else {
        $this->_service->error( 10002, 1, "Invalid general score request" );
      }
    }

    private function handleScore( $response, $directories ) {
      $user = isset( $directories[0] ) ? $directories[0] : NULL;
      if( $this->_method == "head" ) {
        //$this->checkUser();
      } else if( $this->_method == "post" ) {
        $this->setScore( $response );
      } else if( $this->_method == "put" ) {
        $this->setScore( $response );
      } else if( $this->_method == "delete" ) {
        $this->deleteScore( $response );
      } else if( $this->_method == "get" ) {
        $this->getScore( $response );
      } else {
        $this->_service->error( 10002, 1, "Invalid score request" );
      }
    }

    private function handleScores( $response, $directories ) {
      if( $this->_method == "get" ) {
        $this->listScores( $response );
      } else {
        $this->_service->error( 10002, 1, "Invalid scores request" );
      }
    }

    private function setScore( $response ) {
      $time = time();
      
	  $uid = $this->assert("uid", false, 400, 10003, 1, "No user ID provided");
	  $score = $this->assert("score", false, 400, 10003, 1, "No score provided");
      
	  $query = $this->_mysql->start();
	  $scores = $query->open( TABLE_HIGHSCORES );
	  $scores->where("facebookID", $query->sanitize( $uid ));
	  $data = $query->select();
	  
	  $query = $this->_mysql->start();
	  $scores = $query->open( TABLE_HIGHSCORES );
	  $scores->set("highscore", $query->sanitize( $score ));
	  if ( !$data )
	  {
		$scores->set("facebookID", $query->sanitize( $uid ));
		$result = $query->insert();
	  }
	  else
	  {
		$scores->where("facebookID", $query->sanitize( $uid ));
		$result = $query->update();
	  }
	  if ( $result === false )
	  {
		$this->setCode( 503 );
        $this->_service->error( 10000, 1, "An error occured adding the score" );
	  }

      $response->store( "success", true );
      $response->store( "uid", $uid );
	  $response->store( "score", $score );
    }
	
    private function deleteScore( $response ) {
      
    }

    private function getScore( $response ) {
      $uid = $this->input( "uid", false );
      if( $uid === false ) {
        $this->setCode( 400 );
        $this->_service->error( 10003, 1, "No ID provided" );
        return;
      }
	  
      $query = $this->_mysql->start();
      $scores = $query->open( TABLE_HIGHSCORES );
	  $scores->select("*");
      $scores->where( "facebookID", $query->sanitize( $uid ) );
      $data = $query->select();
      if( !$data ) {
        $this->setCode( 404 );
        $this->_service->error( 10003, 2, "User ID not found" );
        return;
      }

      $response->store( "uid", $data['facebookID'] );
      $response->store( "score", $data['highscore'] );
    }

    private function listScores( $response ) {
	  $uids = $this->assert( "uids", false, 400, 10003, 1, "No IDs provided" );
	  
	  $query = $this->_mysql->start();
	  
	  $split = explode(",", $uids);
	  $suids = "";
	  foreach( $split as $uid )
	  {
		if ( strlen( $suids ) > 0 )
		{
		  $suids .= ",";
		}
		$suids .= $query->sanitize($uid);
	  }
	  
      $scores = $query->open( TABLE_HIGHSCORES );
	  $scores->select("*");
      $scores->where( "facebookID", "IN", "(" . $suids . ")" );
      while( $data = $query->select() ) {
        $score = $response->start( "scores", true );
		$score->store( "uid", $data['facebookID'] );
		$score-> store( "score", $data['highscore'] );
      }
    }

  }

?>