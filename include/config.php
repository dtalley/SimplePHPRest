<?php

  $config = new DataTree();

  $site = $config->start( "site" );
    $site->store( "domain", "boomtown.orionark.com" );
    $site->store( "path", "/api/v1" );
  $site = NULL;

  $db = $config->start( "db" );
    $db->store( "host", "localhost" );
    $db->store( "port", "" );
    $db->store( "name", "boomtown01" );
    $db->store( "user", "boomtown" );
    $db->store( "pass", "b0om3rv1ll3" );
  $db = NULL;

  $cookies = $config->start( "cookies" );
    $cookies->store( "domain", "boomtown.orionark.com" ):
    $cookies->store( "prefix", "bmtn_" );
    $cookies->store( "expiration", ( 60 * 60 * 24 * 1 ) );
  $cookies = NULL;

?>