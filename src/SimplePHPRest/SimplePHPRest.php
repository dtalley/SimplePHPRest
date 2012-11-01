<?php

  require_once "OrionarkPHP/misc/DataTree.php";
  require_once "SimplePHPRest/RestfulService.php";

  class SimplePHPRest {

    private $_service = NULL;
    private $_config = NULL;

    public function __construct() {
      $this->_service = new RestfulService();
    }

    public function init( $config ) {
      $this->_config = $config;
      $this->_service->init( $config );
    }

    public function addDatabase( $id, $db )
    {
      $this->_service->addDatabase( $id, $db );
    }

    public function allowOrigin( $origin ) 
    {
      $this->_service->allowOrigin( $origin );
    }

    public function register( $uri, $handler ) {
      $this->_service->register( $uri, $handler );
    }

    public function respond() {
      global $_SERVER, $_REQUEST;

      if( !DEFINED( "REST_DIR" ) ) {
        DEFINE( "REST_DIR", "include/rest" );
      }

      $request = $_SERVER['REQUEST_URI'];
      $split = explode( "?", $request );
      $uri = $split[0];
      $uri = str_replace( $this->_config->get( "site/path" ), "", $uri );
      $method = $_SERVER['REQUEST_METHOD'];
      $method_alt = isset( $_REQUEST['_method'] ) ? $_REQUEST['_method'] : false;
      if( $method_alt !== false ) {
        $method = $method_alt;
        $this->_service->enablePseudo();
      }
      $secure = isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] == "on" ? true : false;
      $this->_service->respond( $uri, $method, $secure, false );
      $this->_service->dump();
    }
  }

?>