<?php

  if( !defined( "INCLUDE_DIR" ) ) {
    define( "INCLUDE_DIR", "" );
  }
  require_once INCLUDE_DIR . "DataTree.php";
  require_once INCLUDE_DIR . "rest/RestfulHandler.php";
  require_once INCLUDE_DIR . "PasswordHash.php";

  class Authentication extends RestfulHandler {

    private $_user = NULL;
    private $_verified = false;

    private $_pgsql = NULL;
    
    public function respond( DataTree $response ) {
      if( ( $this->_pgsql = $this->getDatabase( "PGSQLConnection" ) ) === false ) {
        $this->_service->error( 10005, 1, "A PGSQLConnection object must be present" );
      }
      $directories = explode( "/", $this->_uri );
      $root = array_shift( $directories );
      if( $root == "auth" ) {
        $this->handleAuth( $response, $directories );
      } else {
        $this->_service->error( 10002, 1, "Invalid general auth request" );
      }
    }

    private function handleAuth( $response, $directories ) {
      $method = isset( $directories[0] ) ? $directories[0] : NULL;
      if( 
        ( $this->_method == "post" || 
        $this->_method == "get" ) &&
        $method
      ) {
        if( $method == "grant" ) {
          $this->handleGrant( $response );
        } else if( $method == "verify" ) {
          $this->handleVerify( $response );
        }
      } else {
        $this->_service->error( 10002 );
      }
    }

    private function handleGrant( $response ) {
      /**
       * We support several methods of granting an access
       * token, namely through the facebook, kongregate, and
       * google+ APIs, but also through a default user/pass
       * scheme.  If no service is provided, this is an
       * invalid request.
       */
      $service = $this->input( "service", false );
      if( $service === false ) {
        $this->setCode( 400 );
        $this->_service->error( 10003, 1, "No target service provided" );
        return;
      }
      $query = $this->_pgsql->start();
      $types = $query->open( TABLE_USER_TYPES );
      $types->select( "*" );
      //The service can be a name or an ID
      if( preg_match( "/[^0-9]/", $service ) ) {
        $types->where( "utp_name", $query->sanitize( $service ) );
      } else {
        $types->where( "utp_id", $service );
      }
      $typeData = $query->select();
      if( !$typeData ) {
        $this->setCode( 400 );
        $this->_service->error( 10003, 2, "Invalid service provided" );
        return;
      }
      if( $typeData['utp_name'] == "facebook" ) {
        $this->grantFacebook( $response, $typeData['utp_id'] );
      } else if( $typeData['utp_name'] == "kongregate" ) {
        $this->grantKongregate( $response, $typeData['utp_id'] );
      } else if( $typeData['utp_name'] == "googleplus" ) {
        $this->grantGoogle( $response, $typeData['utp_id'] );
      } else if( $typeData['utp_name'] == "default" ) {
        $this->grantDefault( $response, $typeData['utp_id'] );
      }
    }

    private function grantFacebook( $response, $type ) {
      
    }

    private function grantKongregate( $response, $type ) {
      
    }

    private function grantGoogle( $response, $type ) {
      
    }

    /**
     * This is the default authentication scheme.  Tokens
     * are created when the proper username/password
     * combination are provided over a secure connection,
     * after which a cookie is set to make it easier
     * to retrieve the valid token later.
     * @param $response The target DataTree to fill
     * with return data
     * @param $type The user type ID
     */
    private function grantDefault( $response, $type ) {
      //Must use SSL to get tokens
      if( !$this->_secure ) {
        $this->setCode( 400 );
        $this->_service->error( 10003, 1, "Secure connection must be used" );
        return;
      }
      $time = time();
      //An API key must be provided
      $key = $this->input( "key", false );
      if( $key === false ) {
        $this->setCode( 400 );
        $this->_service->error( 10003, 2, "No API key provided" );
        return;
      }
      //First check if the user has our data cookie populated
      $data = $this->cookie( "data", false );
      $user = NULL;
      if( $data ) {
        $userCookie = unserialize( $data );
        $id = $userCookie['id'];
        $hash = $userCookie['hash'];
        $userData = $this->getAuthData( $id, $key );
        /**
         * Here we make sure the user exists, their
         * token isn't expired, and the secret generated
         * hash that should have been stored in the user's
         * cookie is the same as the one that's actually
         * stored there.
         */
        if( $userData ) {
          if( $userData['tkn_expires'] >= $time ) {
            $currentHash = md5( $userData['usr_active'] . $userData['tkn_expires'] );
            if( $currentHash == $hash ) {
              $response->store( "token", $userData['tkn_id'] );
              $response->store( "id", $id );
              $this->updateUserActivity( $id, $time, $userData['tkn_expires'] );
              $user = $userCookie;
              return;
            }
          }
        }
      }
      
      /**
       * If we made it to this point, the user 
       * doesn't have a cookie or the cookie's
       * hash was invalid.
       */
      //The API key must be in the database and activated
      $query = $this->_pgsql->start();
      $keys = $query->open( TABLE_KEYS );
      $keys->select( "key_id" );
      $keys->where( "key_id", $query->sanitize( $key ) );
      if( !$query->select() ) {
        $this->setCode( 400 );
        $this->_service->error( 10003, 3, "Invalid API key provided" );
        return;
      }
      //An e-mail must be provided to look up the user password
      $email = $this->input( "email", false );
      if( $email === false ) {
        $this->setCode( 400 );
        $this->_service->error( 10003, 4, "No e-mail provided" );
        return;
      }
      //Check if that e-mail is associated with a user
      $query = $this->_pgsql->start();
      $users = $query->open( TABLE_USERS );
      $users->select( "usr_id, usr_code" );
      $users->where( "usr_email", $query->sanitize( $email ), "AND" );
      $users->where( "utp_id", $type );
      $user = $query->select();
      if( !$user ) {
        $this->setCode( 400 );
        $this->_service->error( 10003, 5, "Invalid e-mail provided" );
        return;
      }
      //A password for the user must be provided
      $password = $this->input( "password", false );
      if( $password === false ) {
        $this->setCode( 400 );
        $this->_service->error( 10003, 6, "No password provided" );
        return;
      }
      /**
       * Here we use phpass password hashing to check if
       * the provided password hashes to a comparable value
       * of the one stored in the database.
       */
      $hasher = new PasswordHash();
      if( $hasher->CheckPassword( $password, $user['usr_code'] ) ) {
        $this->setCode( 400 );
        $this->_service->error( 10003, 7, "Invalid password provided" );
        return;
      }
      //Now we check to see if a token already exists for this user
      $token = NULL;
      $query = $this->_pgsql->start();
      $tokens = $query->open( TABLE_TOKENS );
      $tokens->select( "tkn_id, tkn_expires" );
      $tokens->where( "usr_id", $user['usr_id'], "AND" );
      $tokens->where( "key_id", $query->sanitize( $key ) );
      $tokenData = $query->select();
      //If the token is expired, delete it
      if( $tokenData && $tokenData['tkn_expires'] < $time ) {
        $query = $this->_pgsql->start();
        $tokens = $query->open( TABLE_TOKENS );
        $tokens->where( "tkn_id", $query->sanitize( $tokenData['tkn_id'] ) );
        if( !$query->delete() ) {
          $this->setCode( 500 );
          $this->_service->error( 10004, 1, "Unable to delete expired token" );
          return;
        }
      } else if( $tokenData ) {
        $token = $tokenData['tkn_id'];
      }
      //If no valid token is found, create one
      if( !$token ) {
        $tokenData = $this->createToken( $user['usr_id'], $key );
        if( !$tokenData ) {
          return;
        }
        $token = $tokenData['tkn_id'];
      }
      if( $token ) {
        $response->store( "token", $token );
        $response->store( "id", $user['usr_id'] );
        //Update the user's activity
        $this->updateUserActivity( $user['usr_id'], $time, $tokenData['tkn_expires'] );
      } else {
        $this->setCode( 500 );
        $this->_service->error( 10004, 2, "An unknown error occurred" );
        return;
      }
    }

    /**
     * Create a valid 32 character access token
     * and assign it to the provided user under the
     * provided API key.
     * @param $user The user's ID
     * @param $key The API key
     * @return An array filled with the token data
     */
    private function createToken( $user, $key ) {
      $time = time();
      /**
       * Here we generate a random string of characters,
       * and then check to see if an access token with an
       * ID matching those characters is already in
       * the database.  Once we have a unique ID, we can
       * continue.
       */
      $chars = "";
      $chars .= "abcdefghijklmnopqrstuvwxyz";
      $chars .= "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
      $chars .= "0123456789";
      $total = strlen( $chars );
      $found = false;
      while( !$found ) {
        $token = "";
        while( strlen( $token ) < 32 ) {
          $token .= $chars[rand(0,$total-1)];
        }
        $query = $this->_pgsql->start();
        $tokens = $query->open( TABLE_TOKENS );
        $tokens->select( "tkn_id" );
        $tokens->where( "tkn_id", $query->sanitize( $token ) );
        if( !$query->select() ) {
          $found = true;
        }
      }
      //Insert the generated token into the database
      $query = $this->_pgsql->start();
      $tokens = $query->open( TABLE_TOKENS );
      $tokens->set( "tkn_id", $query->sanitize( $token ) );
      $tokens->set( "usr_id", $user );
      $tokens->set( "key_id", $query->sanitize( $key ) );
      $tokens->set( "tkn_created", $time );
      $tokens->set( "tkn_expires", $time + ( 60 * 60 * 24 ) );
      if( !$query->insert( false ) ) {
        $this->setCode( 500 );
        $this->_service->error( 10004, 1, "Unable to create token" );
        return;
      }
      return array(
        "tkn_id" => $token,
        "usr_id" => $user,
        "key_id" => $key,
        "tkn_created" => $time,
        "tkn_expires" => $time + ( 60 * 60 * 24 )
      );
    }

    /**
     * Return the full spectrum of authentication
     * related data attached to a user, including
     * user info, token info, and API key info
     * @param $user The user ID
     * @param $key The API key
     * @return An array filled with the auth data
     */
    private function getAuthData( $user, $key ) {
      $query = $this->_pgsql->start();
      $users = $query->open( TABLE_USERS, "u" );
      $users->select("{*}");
      $users->where( "{usr_id}", $query->sanitize( $user ) );
      $tokens = $users->join( TABLE_TOKENS, "t" );
      $tokens->select("{*}");
      $tokens->on( "{usr_id}", $users->column( "usr_id" ) );
      $keys = $tokens->join( TABLE_KEYS, "k" );
      $keys->select("{*}");
      $keys->on( "{key_id}", $tokens->column( "key_id" ) );
      $keys->where( "{key_id}", $query->sanitize( $key ) );
      $data = $query->select();
      return $data;
    }

    /**
     * Update a user's recent activity timestamp
     * in the database, and if we're on a secure
     * connection, update their stored authentication
     * cookie with the provided arguments.
     * @param $user The user ID
     * @param $time The current timestamp
     * @param $expires The timestamp for when the user's
     * current auth token expires
     * @return A boolean indicating success or failure
     */
    private function updateUserActivity( $user, $time, $expires ) {
      if( $this->_secure ) {
        $user = array();
        $user['id'] = $user;
        $user['hash'] = md5( $time . $expires );
        $this->setCookie( "data", serialize( $user ), true );
      }
      $query = $this->_pgsql->start();
      $users = $query->open( TABLE_USERS );
      $users->set( "usr_active", $time );
      $users->where( "usr_id", $user );
      if( !$query->update() ) {
        $this->setCode( 500 );
        $this->_service->error( 10004, 1, "Could not update user activity" );
        return false;
      }
      return true;
    }

    /**
     * Check for a provided authentication token, and
     * if present, verify it and return an
     * indication of whether the user was authenticated,
     * and if so, provide their user ID
     * @param $response The DataTree object to add the
     * returned data into
     */
    private function handleVerify( $response ) {
      $auth = $response->start( "auth" );
      /**
       * If we're already verified, no need to go
       * through the motions, just return the
       * previously returned value.
       */
      if( $this->_verified ) {
        if( $this->_user === NULL ) {
          $auth->store( "authenticated", false );
        } else {
          $auth->store( "authenticated", true );
          $auth->store( "id", $this->_user['usr_id'] );
        }
        return;
      }
      $time = time();
      //An API key must be provided for this request to be valid
      $key = $this->input( "key", false );
      if( $key === false ) {
        $this->setCode( 400 );
        $this->_service->error( 10003, 1, "No API key provided" );
        return;
      }
      //Verify that the provided API key is real
      $query = $this->_pgsql->start();
      $keys = $query->open( TABLE_KEYS );
      $keys->select( "key_id" );
      $keys->where( "key_id", $query->sanitize( $key ) );
      if( !$query->select() ) {
        $this->setCode( 400 );
        $this->_service->error( 10003, 2, "Invalid API key provided" );
        return;
      }
      //Store the valid, used key
      $response->store( "key", $key );
      //An auth token must be provided
      $token = $this->input( "token", false );
      if( $token === false ) {
        $this->setCode( 400 );
        $this->_service->error( 10003, 3, "No token provided" );
        return;
      }
      //Check to make sure the provided auth token exists
      $query = $this->_pgsql->start();
      $tokens = $query->open( TABLE_TOKENS );
      $tokens->select( "*" );
      $tokens->where( "tkn_id", $query->sanitize( $token ), "AND" );
      $tokens->where( "key_id", $query->sanitize( $key ) );
      $tokenData = $query->select();
      if( !$tokenData ) {
        $this->setCode( 400 );
        $this->_service->error( 10003, 4, "Invalid token provided" );
        return;
      }
      //Make sure the provided token isn't expired
      if( $tokenData['tkn_expires'] < $time ) {
        $this->setCode( 400 );
        $this->_service->error( 10003, 5, "Expired token provided" );
        return;
      }
      //Now pull the user's information
      $query = $this->_pgsql->start();
      $users = $query->open( TABLE_USERS );
      $users->select( "usr_id" );
      $users->where( "usr_id", $tokenData['usr_id'] );
      $user = $query->select();
      if( $user ) {
        $this->_user = $user;
      }
      $this->_verified = true;
      if( $this->_user !== NULL ) {
        $auth->store( "authenticated", true );
        $auth->store( "id", $this->_user['usr_id'] );
      } else {
        $auth->store( "authenticated", false );
      }
    }

  }

?>