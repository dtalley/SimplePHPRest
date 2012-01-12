<?php

  if( !defined( "USER_TABLES" ) ) {
    define( "USER_TABLES", true );
    define( "TABLE_USERS", "users" );
    define( "TABLE_USER_TYPES", "user_types" );
  }

  if( !defined( "COMMANDER_TABLES" ) ) {
    define( "COMMANDER_TABLES", true );
    define( "TABLE_COMMANDERS", "commanders" );
  }

  if( !defined( "AUTHENTICATION_TABLES" ) ) {
    define( "AUTHENTICATION_TABLES", true );
    define( "TABLE_TOKENS", "tokens" );
    define( "TABLE_KEYS", "keys" );
  }

?>