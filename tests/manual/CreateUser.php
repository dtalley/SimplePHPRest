<?php

  $key = isset( $_REQUEST['key'] ) ? $_REQUEST['key'] : NULL;
  $type = isset( $_REQUEST['type'] ) ? $_REQUEST['type'] : NULL;
  $email = isset( $_REQUEST['email'] ) ? $_REQUEST['email'] : NULL;
  $submit = isset( $_REQUEST['submit'] ) ? $_REQUEST['submit'] : false;

  if( $submit !== false ) {

    $url = "http://boomtown.orionark.com/api/v1/user";
    echo "Accessing API endpoint: " . $url . "<br /><br />\r\n\r\n";
    $curl = curl_init( $url );
    curl_setopt( $curl, CURLOPT_CUSTOMREQUEST, "POST" );
    curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
    curl_setopt( $curl, CURLOPT_POSTFIELDS, array(
      "key" => $key,
      "type" => $type,
      "email" => $email
    ));
    $result = curl_exec( $curl );
    echo $result;
    exit;
  
  }

?>

<html>
  <head>
    <title>Add User Test</title>
  </head>
  <body>
    <form method="post" action="AddUser.php">
      Key:<br />
      <input type="text" size="24" name="key" /><br /><br />

      Type:<br />
      <select name="type">
        <option value="1">Facebook</option>
        <option value="2">Kongregate</option>
        <option value="3">Google Plus</option>
        <option value="4">Default</option>
      </select><br /><br />

      E-mail:<br />
      <input type="text" size="50" name="email" /><br /><br />

      Password (if applicable):<br />
      <input type="text" size="24" name="password" /><br /><br />

      <input type="submit" name="submit" value="Submit" />
    </form>
  </body>
</html>