<?php
	
  require_once "OrionarkPHP/misc/DataTree.php";

  $config = new DataTree();

  $site = $config->start( "site" );
    $site->store( "domain", "www.yourdomain.com" );
    $site->store( "path", "/path/to/api" );
  $site = NULL;

  $db = $config->start( "db" );
    $db->store( "type", "MySQLConnection" );
    $db->store( "host", "localhost" );
    $db->store( "port", "" );
    $db->store( "name", "dbname" );
    $db->store( "user", "dbusername" );
    $db->store( "pass", "dbpassword" );
  $db = NULL;

  $cookies = $config->start( "cookies" );
    $cookies->store( "domain", "www.yourdomain.com" );
    $cookies->store( "prefix", "prfx_" );
    $cookies->store( "expiration", ( 60 * 60 * 24 * 1 ) );
  $cookies = NULL;

?>