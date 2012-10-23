<?php

  if( !DEFINED( "REST_DIR" ) ) {
    DEFINE( "REST_DIR", "include/rest" );
  }
  
  require_once "SimplePHPRest/RestfulHandler.php";

  class RestfulService {
    
    private $_config = NULL;
    private $_dbs = array();

    private $_pseudo = false;

    private $_register = NULL;
    private $_data = NULL;

    private $_code = 200;
	
    private $_method = NULL;

    private $_handler = NULL;

    private $_errorCodes = array(
      10001 => "Database connection error",
      10002 => "Unsupported URI requested",
      10003 => "Client error",
      10004 => "System error",
      10005 => "Required database not found"
    );

    private $_codes = array(
      200 => 'OK',
      201 => 'Created',
      202 => 'Accepted',
      204 => 'No Content',
      301 => 'Moved Permanently',
      304 => 'Not Modified',
      307 => 'Temporary Redirect',
      400 => 'Bad Request',
      401 => 'Unauthorized',
      402 => 'Payment Required',
      403 => 'Forbidden',
      404 => 'Not Found',
      405 => 'Method Not Allowed',
      406 => 'Not Acceptable',
      408 => 'Request Timeout',
      409 => 'Conflict',
      410 => 'Gone',
      411 => 'Length Required',
      412 => 'Precondition Failed',
      413 => 'Request Entity Too Large',
      415 => 'Unsupported Media Type',
      500 => 'Internal Server Error',
      501 => 'Not Implemented',
      503 => 'Service Unavailable'
    );

    public function __construct() {
      $this->_register = new DataTree();
      $this->_data = new DataTree();
    }

    public function enablePseudo() {
      $this->_pseudo = true;
    }

    public function init( $config ) {
      $this->_config = $config;
    }

    public function addDatabase( $id, $db )
    {
      $this->_dbs[$id] = $db;
    }

    public function respond( 
      $uri, 
      $method, 
      $secure, 
      $internal,
      DataTree &$bank = NULL,
      array &$input = NULL
    ) {
      if( substr( $uri, 0, 1 ) == "/" ) {
        $uri = substr( $uri, 1 );
      }
      if( substr( $uri, -1, 1 ) == "/" ) {
        $uri = substr( $uri, 0, strlen( $uri ) - 1 );
      }
      $tree = $this->_register;
      $directories = explode( "/", $uri );
      foreach( $directories as $directory ) {
        if( $tree->get( $directory ) ) {
          $tree = $tree->get( $directory );
        } else if( 
          preg_match( "/[^0-9.\-]/", $directory ) &&
          $tree->get( "_string" ) 
        ) {
          $tree = $tree->get( "_string" );    
        } else if( $tree->get( "_int" ) ) {
          $tree = $tree->get( "_int" );
        }
      }
      if ( ( $class = $tree-> get( "class" ) ) !== NULL ) {
	    $this->_method = $method;
        require_once REST_DIR . "/" . $class . ".php";
        $this->_handler = new $class();
        $this->_handler->process( 
          $this, 
          $uri, 
          $method, 
          $secure, 
          $internal,
          $this->_config, 
          $this->_dbs,
          $input
        );
        if( $bank !== NULL ) {
          $use_bank = $bank;
        } else {
          $use_bank = new DataTree();
        }
        $override = $this->_handler->respond( 
          $use_bank
        );
        if( 
          $bank === NULL &&
          !$use_bank->isEmpty()
        ) {
          if( $override === true ) {
            $this->_data = $use_bank;
          } else {
            $this->_data->store( "response", $use_bank );
          }
        }
        if( $this->_handler->getCode() > $this->_code ) {
          $this->_code = $this->_handler->getCode();
        }
        if( $bank !== NULL ) {
          return $bank;
        }
      } else {
        $this->error( 10002 );
      }
      return $this->_code;
    }

    public function register( $base, $class ) {
      $paths = explode( ",", $base );
      foreach( $paths as $path ) {
        $directories = explode( "/", $path );
        $tree = $this->_register;
        foreach( $directories as $directory ) {
          if( $directory == "*" ) {
            $tree->store( "class", $class );
          } else if( $directory == "[int]" ) {
            $tree = $tree->start( "_int" );
          } else if( $directory == "[string]" ) {
            $tree = $tree->start( "_string" );
          } else {
            $tree = $tree->start( $directory );
          }
        }
      }
    }

    public function error( 
      $code, $level = 0, $message = "" 
    ) {
      $error = $this->_data->start( "errors", true );
      $error->store( "code", $code );
      $error->store( 
        "description", $this->_errorCodes[$code] 
      );
      if( $level ) {
        $error->store( "level", $level );
      }
      if( $message ) {
        $error->store( "message", $message );
      }
      if( $code == 10001 || $code == 10004 ) {
        $this->_code = 500;
      } else if( $code == 10002 ) {
        $this->_code = 404;
      }
    }

    public function dump() {
      if( $this->_pseudo ) {
        $this->_data->store( "code", $this->_code );
        $this->_code = 200;
      } else if( $this->_method == "head" ) {
        header( "HTTP/1.1 " . $this->_code . " " . $name );
        return;
      }
      $name = $this->_codes[$this->_code];
      header( "HTTP/1.1 " . $this->_code . " " . $name );
      header( "Content-type: application/json" );
      print $this->_data->save( "json" );
    }

  }

?>