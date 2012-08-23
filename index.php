<?php

  DEFINE( "INCLUDE_DIR", "include/" );

  require_once INCLUDE_DIR . "tables.php";
  require_once INCLUDE_DIR . "DataTree.php";
  require_once INCLUDE_DIR . "config.php";
  require_once INCLUDE_DIR . "MySQLConnection.php";
  require_once INCLUDE_DIR . "RestfulService.php";

  $service = new RestfulService();

  $db = new MySQLConnection();
  $db->setHost( $config->get( "db/host" ) );
  $db->setPort( $config->get( "db/port" ) );
  $db->setName( $config->get( "db/name" ) );
  $db->setUser( $config->get( "db/user" ) );
  $db->setPass( $config->get( "db/pass" ) );
  $service->init( $config, $db );
  $db = NULL;

  $service->register( "score/*,scores/*", "Score" );

  //$authorization = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : NULL;
  $request = $_SERVER['REQUEST_URI'];
  $split = explode( "?", $request );
  $uri = $split[0];
  $uri = str_replace( $config->get( "site/path" ), "", $uri );
  $method = $_SERVER['REQUEST_METHOD'];
  $method_alt = isset( $_REQUEST['_method'] ) ? $_REQUEST['_method'] : false;
  if( $method_alt !== false ) {
    $method = $method_alt;
    $service->enablePseudo();
  }
  $secure = isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] == "on" ? true : false;
  $config = NULL;
  $service->respond( $uri, $method, $secure, false );
  $service->dump();

?>