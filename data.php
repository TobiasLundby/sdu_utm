<?php
  if (!empty($_SERVER['HTTPS'])) { // TLS enabled
    if ($_SERVER['REQUEST_METHOD'] == 'GET') { // Check for GET request
      if ( isset($_GET['type']) ){
        // Require DB config
        require_once('config.php');

        $type = trim( strip_tags( addslashes($_GET['type']) ) );
        if ($type == 'static_no_fly') {
          $filename = $static_no_fly_zones_file;
          $mimetype = 'application/vnd.google-earth.kml+xml';//"mime/type";
          header("Content-Type: ".$mimetype );
          echo readfile($filename);
          die();
        }
      }
    }
    // Set 'Bad Request' response code and output 0
    http_response_code(400);
    echo 0;
    die();
  } else { // NO TLS
    // Set 'HTTP Version not supported' response code and output 0
    http_response_code(505);
    echo 0;
    die();
  }
?>
