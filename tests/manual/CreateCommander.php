<?php

  $name = isset( $_REQUEST['name'] ) ? $_REQUEST['name'] : NULL;
  $token = isset( $_REQUEST['token'] ) ? $_REQUEST['token'] : NULL;
  $submit = isset( $_REQUEST['submit'] ) ? $_REQUEST['submit'] : false;

  if( $submit !== false ) {

    $url = "https://boomtown.orionark.com/api/v1/commander";
    echo "Accessing API endpoint: " . $url . "<br /><br />\r\n\r\n";
    $curl = curl_init( $url );
    curl_setopt( $curl, CURLOPT_CUSTOMREQUEST, "POST" );
    curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
    curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, false );
    curl_setopt( $curl, CURLOPT_POSTFIELDS, array(
      "name" => $name,
      "token" => $token,
      "key" => "84jf88ritjdjfkr3"
    ));
    $result = curl_exec( $curl );
    echo "Result:<br /><br />\r\n\r\n";
    echo $result;
    exit;
  
  }

?>

<html>
  <head>
    <title>Create Commander Test</title>
  </head>
  <body>
    <form method="post" action="">
      Name:<br />
      <input type="text" size="24" name="name" /><br /><br />

      Token:<br />
      <input type="text" size="24" name="token" /><br /><br />

      <input type="submit" name="submit" value="Submit" />
    </form>
  </body>
</html>