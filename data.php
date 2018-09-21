<?php
  if (!empty($_SERVER['HTTPS'])) { // TLS enabled
    if ($_SERVER['REQUEST_METHOD'] == 'GET') { // Check for GET request
      if ( isset($_GET['data_type']) ){
        // Require DB config
        require_once('config.php');

        $data_type = trim( strip_tags( addslashes($_GET['data_type']) ) );
        if ($data_type == 'static_no_fly') {
          // *** Static NO-FLY Zones ***
          $filename = $static_no_fly_zones_file;
          $mimetype = 'application/vnd.google-earth.kml+xml';//"mime/type";
          header("Content-Type: " . $mimetype );
          echo readfile($filename);
          die();

        } elseif ($data_type == 'dynamic_no_fly') {
          // *** Dynamic NO-FLY Zones ***
          // Connect to DB
          $con = mysqli_connect($mysql_host, $mysql_user, $mysql_pw, $mysql_db);
          if (!$con) {
            die("Connection failed: " . mysqli_connect_error());
          }

          // Get the active dynamic no-fly zones from the DB
          $sql = "SELECT * FROM `$dynamic_no_fly_db` WHERE `valid_from_epoch` <= $time_epoch AND `valid_to_epoch` >= $time_epoch ORDER BY `valid_from_epoch` ASC";

          $out_arr = array();
          $result = mysqli_query($con, $sql);          //query

          // Add some randomness to the calc such that there is not just a specific amount active at all times
          $rand_zones_to_activate = rand(-$no_active_dynamic_no_fly_zones_max,0);
          //echo 'Rand: ' . $rand_zones_to_activate . '<br>';
          if (mysqli_num_rows($result) < $no_active_dynamic_no_fly_zones_max + $rand_zones_to_activate) {
            // No active zones, enable some of them
            $zones_to_activate = $no_active_dynamic_no_fly_zones_max + $rand_zones_to_activate - mysqli_num_rows($result);
            //echo 'Active zones: ' . mysqli_num_rows($result) . '<br>';
            //echo 'Required active zones: ' . $no_active_dynamic_no_fly_zones_max . '<br>';
            //echo 'Zones to activate: ' . $zones_to_activate . '<br>';

            while($zones_to_activate) {
              //Fetch the non-active zones

              $sql = "SELECT * FROM `$dynamic_no_fly_db` WHERE `valid_to_epoch` < $time_epoch ORDER BY `int_id` ASC";
              $result = mysqli_query($con, $sql);          //query

              // Check result
              if($result == false){
                // Close DB connection
                mysqli_close($con);
                // Set content type header to support data
                $mimetype = 'text/plain';//"mime/type";
                header("Content-Type: " . $mimetype );
                // Set 'Internal Server Error' response code and output 0
                http_response_code(500);
                echo 0;
                die(); // DIE
              }

              $no_non_active_zones = mysqli_num_rows($result);
              $zone_to_activate = rand(0,$no_non_active_zones-1);
              //echo 'Non-active zones: ' . $no_non_active_zones . '<br>';
              //echo 'Activating zone: ' . $zone_to_activate . '<br>';
              $active_time = rand($dynamic_no_fly_zone_active_time_min_s, $dynamic_no_fly_zone_active_time_max_s);
              //echo 'Activating for: ' . $active_time . 's<br>';
              $active_time_epoch_lower = $time_epoch;
              $active_time_epoch_upper = $time_epoch + $active_time;
              //echo 'Corresponding to: ' . $active_time_epoch_lower . ' and ' . $active_time_epoch_upper . '<br>';


              $tmp_arr = array();
              if (mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                  $tmp_arr[] = $row;
                }
              }
              $zone_to_activate_int_id = $tmp_arr[$zone_to_activate]['int_id'];
              //echo 'Internal ID to update: ' . $zone_to_activate_int_id . ' - ' . $tmp_arr[$zone_to_activate]['name'] . '<br>';


              $sql = "UPDATE `$dynamic_no_fly_db` SET `valid_from_epoch` = '$active_time_epoch_lower', `valid_to_epoch` = '$active_time_epoch_upper' WHERE `int_id` = '$zone_to_activate_int_id'";
              $result = mysqli_query($con, $sql);          //query

              if ($result !== false) {
                //echo 'Activated zone (int_id) ' .  $zone_to_activate_int_id . ', time: ' . $active_time_epoch_lower . '-' . $active_time_epoch_upper . '<br>';
                $zones_to_activate = $zones_to_activate -1;
              }
            }
            // Get the (new) active dynamic no-fly zones from the DB
            //mysqli_data_seek($result, 0); //Use if looping through the same data again
            $sql = "SELECT * FROM `$dynamic_no_fly_db` WHERE `valid_from_epoch` <= $time_epoch AND `valid_to_epoch` >= $time_epoch ORDER BY `valid_from_epoch` ASC";
            $result = mysqli_query($con, $sql);          //query

            // Check result
            if($result == false){
              // Close DB connection
              mysqli_close($con);
              // Set content type header to support data
              $mimetype = 'text/plain';//"mime/type";
              header("Content-Type: " . $mimetype );
              // Set 'Internal Server Error' response code and output 0
              http_response_code(500);
              echo 0;
              die(); // DIE
            }

            // Make array with zones for output
            if (mysqli_num_rows($result) > 0) {
              while ($row = mysqli_fetch_assoc($result)) {
                $out_arr[] = $row;
              }
            }
          } else {
            // Return zones if the active requirement is satisfied.
            if (mysqli_num_rows($result) > 0) {
              while ($row = mysqli_fetch_assoc($result)) {
                $out_arr[] = $row;
              }
            }
          }

          // Close DB connection
          mysqli_close($con);

          // Set content type header to support data
          $mimetype = 'application/json';//"mime/type";
          header("Content-Type: " . $mimetype );
          // Return the data as JSON
          echo json_encode($out_arr);
          die();

        } elseif ($data_type == 'rally_points') {
          // *** Rally Points ***
          // Connect to DB
          $con = mysqli_connect($mysql_host, $mysql_user, $mysql_pw, $mysql_db);
          if (!$con) {
            die("Connection failed: " . mysqli_connect_error());
          }

          // Get the rally point data out from the database
          $sql = "SELECT * FROM `$rally_points_db`";

          $result = mysqli_query($con, $sql);          //query

          // Close DB connection
          mysqli_close($con);

          // Check result
          if($result == false){
            // Set content type header to support data
            $mimetype = 'text/plain';//"mime/type";
            header("Content-Type: " . $mimetype );
            // Set 'Internal Server Error' response code and output 0
            http_response_code(500);
            echo 0;
            die(); // DIE
          }

          $out_arr = array();
          if (mysqli_num_rows($result) == 0) { // Check if no results
            // Set content type header to support data
            $mimetype = 'text/plain';//"mime/type";
            header("Content-Type: " . $mimetype );
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

          // Set content type header to support data
          $mimetype = 'application/json';//"mime/type";
          header("Content-Type: " . $mimetype );
          // Return the data as JSON
          echo json_encode($out_arr);
          die();

        } elseif ($data_type == 'adsb') {
          // *** ADS-B ***
          // Get data, remove whitespace and tags (HTML etc.)
          $csv = explode("\n", trim( strip_tags( file_get_contents($adsb_address) ) ) );

          // Make key array to combine with the data
          $keys = array(
            "0" => "timestamp",
            "1" => "timestamp_epoch",
            "2" => "icao",
            "3" => "flight",
            "4" => "lat_dd",
            "5" => "lng_dd",
            "6" => "alt_m",
            "7" => "hdg_deg",
            "8" => "vel_mps"
          );

          // Make array from data with keys
          $out_arr = array();
          foreach ($csv as $key => $line) {
            $line_csv = str_getcsv($line);
            $line_csv_keys = array_combine($keys, $line_csv);
            $line_csv_keys['timestamp_epoch'] = intval($line_csv_keys['timestamp_epoch']);
            $line_csv_keys['lat_dd'] = floatval($line_csv_keys['lat_dd']);
            $line_csv_keys['lng_dd'] = floatval($line_csv_keys['lng_dd']);
            $line_csv_keys['alt_m'] = intval($line_csv_keys['alt_m']);
            $line_csv_keys['hdg_deg'] = intval($line_csv_keys['hdg_deg']);
            $line_csv_keys['vel_mps'] = intval($line_csv_keys['vel_mps']);
          	$out_arr[] = $line_csv_keys ;
          }

          // Set content type header to support data
          $mimetype = 'application/json';//"mime/type";
          header("Content-Type: " . $mimetype );
          // Return the data as JSON
          echo json_encode($out_arr);

          die();

        } elseif ($data_type == 'server_time') {
          // Set content type header to support data
          $mimetype = 'text/plain';//"mime/type";
          header("Content-Type: " . $mimetype );
          echo $time_epoch;
          die();

        }
      }
    }
    // Set content type header to support data
    $mimetype = 'text/plain';//"mime/type";
    header("Content-Type: " . $mimetype );
    // Set 'Bad Request' response code and output 0
    http_response_code(400);
    echo 0;
    die();
  } else { // NO TLS
    // Set content type header to support data
    $mimetype = 'text/plain';//"mime/type";
    header("Content-Type: " . $mimetype );
    // Set 'HTTP Version not supported' response code and output 0
    http_response_code(505);
    echo 0;
    die();
  }
?>
