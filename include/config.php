<?php

  $config = new DataTree();

  $site = $config->start( "site" );
    $site->store( "domain", "munchiemania.superplaygames.com" );
    $site->store( "path", "/munchie/api" );
  $site = NULL;

  $db = $config->start( "db" );
    $db->store( "host", "localhost" );
    $db->store( "port", "" );
    $db->store( "name", "nike" );
    $db->store( "user", "munchie" );
    $db->store( "pass", "munch13m4n14" );
  $db = NULL;

  $cookies = $config->start( "cookies" );
    $cookies->store( "domain", "munchiemania.superplaygames.com" );
    $cookies->store( "prefix", "mnch_" );
    $cookies->store( "expiration", ( 60 * 60 * 24 * 1 ) );
  $cookies = NULL;

?>