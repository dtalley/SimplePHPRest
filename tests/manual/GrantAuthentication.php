<?php

  $code = isset( $_REQUEST['code'] ) ? $_REQUEST['code'] : NULL;
  $type = isset( $_REQUEST['type'] ) ? $_REQUEST['type'] : NULL;
  $email = isset( $_REQUEST['email'] ) ? $_REQUEST['email'] : NULL;
  $submit = isset( $_REQUEST['submit'] ) ? $_REQUEST['submit'] : false;

  if( $submit !== false ) {

    $url = "https://boomtown.orionark.com/api/v1/auth/grant?service=" . $type;
    $url .= "&key=84jf88ritjdjfkr3";
    if( $type == 4 ) {
      $url .= "&email=" . $email . "&password=" . $code;
    } else {
      $url .= "&email=" . $email . "&code=" . $code;
    }
    echo "Accessing API endpoint: " . $url . "<br /><br />\r\n\r\n";
    $curl = curl_init( $url );
    curl_setopt( $curl, CURLOPT_CUSTOMREQUEST, "GET" );
    curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
    curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, false );
    /*curl_setopt( $curl, CURLOPT_POSTFIELDS, array(
      "key" => $key,
      "type" => $type,
      "email" => $email
    ));*/
    $result = curl_exec( $curl );
    echo $result;
    exit;
  
  }

?>

<html>
  <head>
    <title>Grant Authentication Test</title>
  </head>
  <body>
    <form method="post" action="">
      Code:<br />
      <input type="text" size="24" name="code" /><br /><br />

      Type:<br />
      <select name="type">
        <option value="1">Facebook</option>
        <option value="2">Kongregate</option>
        <option value="3">Google Plus</option>
        <option value="4">Default</option>
      </select><br /><br />

      E-mail:<br />
      <input type="text" size="50" name="email" /><br /><br />

      <input type="submit" name="submit" value="Submit" />
    </form>
  </body>
</html>