<?php
  if (!empty($_SERVER['HTTPS'])) { // TLS enabled
    if ($_SERVER['REQUEST_METHOD'] == 'GET') { // Check for GET request
      if ( isset($_GET['data_type']) ){
        // Require DB config
        require_once('config.php');

        $data_type = trim( strip_tags( addslashes($_GET['data_type']) ) );
        if ($data_type == 'static_no_fly') {
          $filename = $static_no_fly_zones_file;
          $mimetype = 'application/vnd.google-earth.kml+xml';//"mime/type";
          header("Content-Type: " . $mimetype );
          echo readfile($filename);
          die();
        } elseif ($data_type == 'dynamic_no_fly') {
          echo 'not yet implemented!';
          die();
        } elseif ($data_type == 'rally_points') {
          // Connect to DB
          $con = mysqli_connect($mysql_host, $mysql_user, $mysql_pw, $mysql_db);
          if (!$con) {
            die("Connection failed: " . mysqli_connect_error());
          }

          // Get the rally point data out from the database
          $sql = "SELECT * FROM `$rally_points_db`";

          $result = mysqli_query($con, $sql);          //query

          $out_arr = array();
          if (mysqli_num_rows($result) == 0) {
            // Set 'Not Found' response code and output 0
            http_response_code(404);
            echo 0;
            die();
          }
          if (mysqli_num_rows($result) > 0) { // format data
            while ($row = mysqli_fetch_assoc($result)) {
              $out_arr[] = $row;
            }
          }

          // Close DB connection
          mysqli_close($con);

          // Return the data as JSON
          echo json_encode($out_arr);
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
