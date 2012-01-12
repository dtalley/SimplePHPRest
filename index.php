<?php

  DEFINE( "INCLUDE_DIR", "include/" );

  require_once INCLUDE_DIR . "tables.php";
  require_once INCLUDE_DIR . "DataTree.php";
  require_once INCLUDE_DIR . "config.php";
  require_once INCLUDE_DIR . "PGSQLConnection.php";
  require_once INCLUDE_DIR . "PGSQLQuery.php";
  require_once INCLUDE_DIR . "RestfulService.php";

  $service = new RestfulService();

  $db = new PGSQLConnection();
  $db->setHost( $config->get( "db/host" ) );
  $db->setPort( $config->get( "db/port" ) );
  $db->setName( $config->get( "db/name" ) );
  $db->setUser( $config->get( "db/user" ) );
  $db->setPass( $config->get( "db/pass" ) );
  if( !$db->connect() ) {
    $service->error( 10001 );
    $service->dump();
  }
  $service->init( $config, $db );
  $db = NULL;

  $service->register( "user/*,users/*", "User" );
  $service->register( "commander/*,commanders/*", "Commander" );
  $service->register( "soldier/*,soldiers/*", "Unit" );
  $service->register( "battle/*,battles/*", "Battle" );
  $service->register( "squad/*,squads/*", "Squad" );

  $authorization = $_SERVER['HTTP_AUTHORIZATION'];
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
  $secure = isset( $_SERVER['HTTPS'] ) ? true : false;
  $config = NULL;
  $service->respond( $uri, $method, $secure, false );
  $service->dump();

?>