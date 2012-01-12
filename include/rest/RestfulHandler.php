<?php

  abstract class RestfulHandler {
    
    protected $_service;
    protected $_method;
    protected $_uri;
    protected $_secure;
    protected $_internal;
    protected $_config;
    protected $_db;

    private $_code = 200;

    private $_inputSource;

    public function process( 
      RestfulService &$service, 
      $uri, 
      $method, 
      $secure, 
      $internal,
      DataTree &$config, 
      PGSQLConnection &$db,
      array &$input = NULL
    ) {
      global $_GET, $_POST, $_REQUEST;

      $this->_service = $service;
      if( substr( $uri, 0, 1 ) == "/" ) {
        $uri = substr( $uri, 1 );
      }
      if( substr( $uri, -1, 1 ) == "/" ) {
        $uri = substr( $uri, 0, strlen( $uri ) - 1 );
      }
      $this->_uri = $uri;
      $this->_method = trim( strtolower( $method ) );
      $this->_secure = $secure;
      $this->_internal = $internal;
      $this->_config = $config;
      $this->_db = $db;

      if( $input !== NULL ) {
        $this->_inputSource = $input;
      } else if( $this->_method == "post" ) {
        $this->_inputSource = $_POST;
      } else if( $this->_method == "get" ) {
        $this->_inputSource = $_GET;
      } else if( 
        $this->_method == "put" || 
        $this->_method == "delete" ||
        $this->_method == "head"
      ) {
        $params = file_get_contents( "php://input" );
        if( $params ) {
          $this->_inputSource = parse_str( $params );
        } else {
          $this->_inputSource = $_REQUEST;
        }
      }
    }

    abstract public function respond( DataTree $response );

    public function getCode() {
      return $this->_code;
    }

    protected function setCode( $code ) {
      $this->_code = $code;
    }

    protected function input( $id, $default ) {
      if( isset( $this->_inputSource[$id] ) ) {
        return $this->_inputSource[$id];
      }
      return $default;
    }

    protected function cookie( $id, $default ) {
      global $_COOKIE;
      if( isset( $_COOKIE[$id] ) ) {
        return $_COOKIE[$id];
      }
      return $default;
    }

    protected function setCookie( $id, $value ) {
      $key = $this->_config->get( "cookies/prefix" ) . $id;
      $domain = $this->_config->get( "cookies/domain" );
      $path = $this->_config->get( "site/path" );
      $expiration = time() + $this->_config->get( "cookies/expiration" );
      setcookie( $key, $value, $expiration, $path, $domain, false );
    }

    protected function refreshCookie( $id ) {
      $value = $this->cookie( $id, false );
      if( $value !== false ) {
        $this->setCookie( $id, $value );
      }
    }

    protected function clearCookie( $id ) {
      $key = $this->_config->get( "cookies/prefix" ) . $id;
      $domain = $this->_config->get( "cookies/domain" );
      $path = $this->_config->get( "site/path" );
      setcookie( $key, "", 0, $path, $domain, false );
    }

    protected function file( $id ) {
      global $_FILES;
      if( isset( $_FILES[$id] ) ) {
        return $_FILES[$id];
      }
      return false;
    }

  }

?>