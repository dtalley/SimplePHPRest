<?php

  $key = isset( $_REQUEST['key'] ) ? $_REQUEST['key'] : NULL;
  $email = isset( $_REQUEST['email'] ) ? $_REQUEST['email'] : NULL;
  $type = isset( $_REQUEST['type'] ) ? $_REQUEST['type'] : NULL;
  $submit = isset( $_REQUEST['submit'] ) ? $_REQUEST['submit'] : false;

  if( $submit !== false ) {

    $url = "http://boomtown.orionark.com/api/v1/user?type=" . $type;
    if( $key ) {
      $url .= "&key=" . $key;
    }
    if( $email ) {
      $url .= "&email=" . $email;
    }
    echo "Accessing API endpoint: " . $url . "<br /><br />\r\n\r\n";
    $curl = curl_init( $url );
    curl_setopt( $curl, CURLOPT_CUSTOMREQUEST, "HEAD" );
    curl_setopt( $curl, CURLOPT_NOBODY, true );
    curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
    curl_setopt( $curl, CURLOPT_HEADER, true );
    $result = curl_exec( $curl );
    echo $result;
    exit;
  
  }

?>

<html>
  <head>
    <title>Check User Test</title>
  </head>
  <body>
    <form method="post" action="">
      Key:<br />
      <input type="text" size="24" name="key" /><br /><br />

      E-mail:<br />
      <input type="text" size="24" name="email" /><br /><br />

      Type:<br />
      <select name="type">
        <option value="1">Facebook</option>
        <option value="2">Kongregate</option>
        <option value="3">Google Plus</option>
      </select><br /><br />

      <input type="submit" name="submit" value="Submit" />
    </form>
  </body>
</html>