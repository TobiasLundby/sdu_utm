<?php
  if (!empty($_SERVER['HTTPS'])) { // TLS enabled
    if($_SERVER['REQUEST_METHOD'] == 'POST'){ // Check for POST request
      // Check if everything has been POSTed
      if ( !empty($_POST['uav_id'])
          && !empty($_POST['uav_auth_key'])
          && isset($_POST['uav_op_status'])
          && isset($_POST['pos_cur_lat_dd'])
          && isset($_POST['pos_cur_lng_dd'])
          && isset($_POST['pos_cur_alt_m'])
          && isset($_POST['pos_cur_hdg_deg'])
          && isset($_POST['pos_cur_vel_mps'])
          && !empty($_POST['pos_cur_gps_timestamp'])
          && isset($_POST['wp_next_lat_dd'])
          && isset($_POST['wp_next_lng_dd'])
          && isset($_POST['wp_next_alt_m'])
          && isset($_POST['wp_next_hdg_deg'])
          && isset($_POST['wp_next_vel_mps'])
          && !empty($_POST['wp_next_eta_epoch']) ) { //check if post using isset
        //  PASSED CHECKS
        // Require DB config
        require_once('config.php');
        // Require DB functions
        require_once('db_functions.php');

        // Connect to DB
        $con = mysqli_connect($mysql_host, $mysql_user, $mysql_pw, $mysql_db);
        if (!$con) {
          die("Connection failed: " . mysqli_connect_error());
        }

        // Save and sanitise first values to authenticate
        $uav_id = intval(mysqli_real_escape_string($con, trim( strip_tags( addslashes($_POST['uav_id']) ) ) ) );
        $uav_auth_key = mysqli_real_escape_string($con, trim( strip_tags( addslashes($_POST['uav_auth_key']) ) ) );


        // Check authentication key match with the provided uav_id
        auth_req($uav_id, $uav_auth_key, $con);
        // Passed authentication

        // Save and sanitise values
        $uav_op_status = intval( mysqli_real_escape_string($con, trim( strip_tags( addslashes($_POST['uav_op_status']) ) ) ) );
        $uav_bat_soc = -1;
        if(!empty($_POST['uav_bat_soc'])){
          $uav_bat_soc = floatval( mysqli_real_escape_string($con, trim( strip_tags( addslashes($_POST['uav_bat_soc']) ) ) ) );
        }

        $pos_cur_lat_dd = floatval( mysqli_real_escape_string($con, trim( strip_tags( addslashes($_POST['pos_cur_lat_dd']) ) ) ) );
        $pos_cur_lng_dd = floatval( mysqli_real_escape_string($con, trim( strip_tags( addslashes($_POST['pos_cur_lng_dd']) ) ) ) );
        $pos_cur_alt_m = floatval( mysqli_real_escape_string($con, trim( strip_tags( addslashes($_POST['pos_cur_alt_m']) ) ) ) );
        $pos_cur_hdg_deg = floatval( mysqli_real_escape_string($con, trim( strip_tags( addslashes($_POST['pos_cur_hdg_deg']) ) ) ) );
        $pos_cur_vel_mps = floatval( mysqli_real_escape_string($con, trim( strip_tags( addslashes($_POST['pos_cur_vel_mps']) ) ) ) );
        $pos_cur_gps_timestamp = intval( mysqli_real_escape_string($con, trim( strip_tags( addslashes($_POST['pos_cur_gps_timestamp']) ) ) ) );

        $wp_next_lat_dd = floatval( mysqli_real_escape_string($con, trim( strip_tags( addslashes($_POST['wp_next_lat_dd']) ) ) ) );
        $wp_next_lng_dd = floatval( mysqli_real_escape_string($con, trim( strip_tags( addslashes($_POST['wp_next_lng_dd']) ) ) ) );
        $wp_next_alt_m = floatval( mysqli_real_escape_string($con, trim( strip_tags( addslashes($_POST['wp_next_alt_m']) ) ) ) );
        $wp_next_hdg_deg = floatval( mysqli_real_escape_string($con, trim( strip_tags( addslashes($_POST['wp_next_hdg_deg']) ) ) ) );
        $wp_next_vel_mps = floatval( mysqli_real_escape_string($con, trim( strip_tags( addslashes($_POST['wp_next_vel_mps']) ) ) ) );
        $wp_next_eta_epoch = floatval( mysqli_real_escape_string($con, trim( strip_tags( addslashes($_POST['wp_next_eta_epoch']) ) ) ) );

        // Insert the data in the DB

        $sql = "INSERT INTO `$tracking_data_db` (`int_id`, `uav_id`, `uav_op_status`, `uav_bat_soc`, `time_epoch`, `pos_cur_lat_dd`, `pos_cur_lng_dd`, `pos_cur_alt_m`, `pos_cur_hdg_deg`, `pos_cur_vel_mps`, `pos_cur_gps_timestamp`, `wp_next_lat_dd`, `wp_next_lng_dd`, `wp_next_alt_m`, `wp_next_hdg_deg`, `wp_next_vel_mps`, `wp_next_eta_epoch`) VALUES (NULL, '$uav_id', '$uav_op_status', '$uav_bat_soc', '$time_epoch', '$pos_cur_lat_dd', '$pos_cur_lng_dd', '$pos_cur_alt_m', '$pos_cur_hdg_deg', '$pos_cur_vel_mps', '$pos_cur_gps_timestamp', '$wp_next_lat_dd', '$wp_next_lng_dd', '$wp_next_alt_m', '$wp_next_hdg_deg', '$wp_next_vel_mps', '$wp_next_eta_epoch');";

        $result = mysqli_query($con, $sql);          //query

        // Close DB connection
        mysqli_close($con);

        // Check result
        if($result !== false){
          if ($drone_id_forward) {
            $data = array('aid' => $uav_id, 'lat' => $pos_cur_lat_dd, 'lon' => $pos_cur_lng_dd, 'alt' => $pos_cur_alt_m);

            // use key 'http' even if you send the request to https://...
            $options = array(
                'http' => array(
                    'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                    'method'  => 'POST',
                    'content' => http_build_query($data)
                )
            );
            $context  = stream_context_create($options);
            $result = file_get_contents($drone_id_post_url, false, $context);

            // Check the result (the stripped result should be 'OK')
            if ($result === FALSE || trim( strip_tags($result) ) !== 'OK') {
              // Set content type header to support data
              $mimetype = 'text/plain';//"mime/type";
              header("Content-Type: " . $mimetype );
              // Set 'Internal Server Error' response code and output 0
              http_response_code(500);
              echo 0;
              die(); // DIE - Authentication failed - not correct format
            }
          }
          // Set content type header to support data
          $mimetype = 'text/plain';//"mime/type";
          header("Content-Type: " . $mimetype );
          // Set 'Accepted' response code
          http_response_code(202);
          // Send result to user
          echo json_encode(1);
          die();
        }
        // }else{
        //   echo("Error description: " . mysqli_error($con));
        // }

        // Set content type header to support data
        $mimetype = 'text/plain';//"mime/type";
        header("Content-Type: " . $mimetype );
        // Set 'Internal Server Error' response code and output 0
        http_response_code(500);
        echo 0;
        die(); // DIE - Authentication failed - not correct format
      }
    } elseif ($_SERVER['REQUEST_METHOD'] == 'GET') { // Check for GET request
      // Require DB config
      require_once('config.php');
      // Require DB functions
      require_once('db_functions.php');

      // Connect to DB
      $con = mysqli_connect($mysql_host, $mysql_user, $mysql_pw, $mysql_db);
      if (!$con) {
        die("Connection failed: " . mysqli_connect_error());
      }

      // Get the time variable
      $time_delta_s = $time_delta_default_s; // 2 mins
      if ( isset($_GET['time_delta_s']) ){
        $time_delta_s = intval( mysqli_real_escape_string($con, trim( strip_tags( addslashes($_GET['time_delta_s']) ) ) ) );
        if($time_delta_s > $time_delta_max_s){$time_delta_s = $time_delta_max_s;}
      }

      //echo $_GET['time_delta_s'] . '<br>';
      $time_interval_high = $time_epoch;
      $time_interval_low = $time_epoch - $time_delta_s;

      $sql = "SELECT * FROM `$tracking_data_db` WHERE `time_epoch` BETWEEN $time_interval_low AND $time_interval_high ORDER BY `int_id` DESC";
      if ( isset($_GET['uav_id']) ){
        $uav_id = intval( mysqli_real_escape_string($con, $_GET['uav_id']) );
        $sql = "SELECT * FROM `$tracking_data_db` WHERE `time_epoch` BETWEEN $time_interval_low AND $time_interval_high AND `uav_id` = '$uav_id' ORDER BY `int_id` DESC";
      }
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

      //echo 'Entries: ' . mysqli_num_rows($result) . '<br>';
      $out_arr = array();
      if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
          //echo 'UAV ID: ' . $row['uav_id'] . ', int ID:' . $row['int_id'] . ', time EPOCH: ' . $row['time_epoch'] . '<br>';
          $row['int_id'] = intval($row['int_id']);
          $row['uav_id'] = intval($row['uav_id']);
          $row['uav_op_status'] = intval($row['uav_op_status']);
          $row['uav_bat_soc'] = floatval($row['uav_bat_soc']);
          $row['time_epoch'] = intval($row['time_epoch']);
          $row['pos_cur_lat_dd'] = floatval($row['pos_cur_lat_dd']);
          $row['pos_cur_lng_dd'] = floatval($row['pos_cur_lng_dd']);
          $row['pos_cur_alt_m'] = floatval($row['pos_cur_alt_m']);
          $row['pos_cur_hdg_deg'] = floatval($row['pos_cur_hdg_deg']);
          $row['pos_cur_vel_mps'] = floatval($row['pos_cur_vel_mps']);
          $row['pos_cur_gps_timestamp'] = intval($row['pos_cur_gps_timestamp']);
          $row['wp_next_lat_dd'] = floatval($row['wp_next_lat_dd']);
          $row['wp_next_lng_dd'] = floatval($row['wp_next_lng_dd']);
          $row['wp_next_alt_m'] = floatval($row['wp_next_alt_m']);
          $row['wp_next_hdg_deg'] = floatval($row['wp_next_hdg_deg']);
          $row['wp_next_vel_mps'] = floatval($row['wp_next_vel_mps']);
          $row['wp_next_eta_epoch'] = intval($row['wp_next_eta_epoch']);

          $out_arr[] = array_slice($row,1, 16, true);
        }
      }

      // Download data from DroneID
      $csv = explode("\n", trim( strip_tags( file_get_contents($drone_id_get_url) ) ) );

      if ($csv[0] != "") { // Check if there is any data from DroneID
        if (count($out_arr) > 0) {
          // There is data in the DB so use that as a template for DroneID data parsing
          $entry_template_w_keys = $out_arr[0];
          $entry_template_w_keys['uav_op_status'] = -1;
          $entry_template_w_keys['uav_bat_soc'] = -1;
          $entry_template_w_keys['pos_cur_hdg_deg'] = -1;
          $entry_template_w_keys['pos_cur_vel_mps'] = -1;
          $entry_template_w_keys['pos_cur_gps_timestamp'] = -1;
          $entry_template_w_keys['wp_next_lat_dd'] = -1;
          $entry_template_w_keys['wp_next_lng_dd'] = -1;
          $entry_template_w_keys['wp_next_alt_m'] = -1;
          $entry_template_w_keys['wp_next_hdg_deg'] = -1;
          $entry_template_w_keys['wp_next_vel_mps'] = -1;
          $entry_template_w_keys['wp_next_eta_epoch'] = -1;

          foreach ($csv as $key => $line) { // Loop through all of the DroneID entries
            $line_csv = str_getcsv($line);
            $entry_template_w_keys['uav_id'] = intval($line_csv[2]);
            $entry_template_w_keys['time_epoch'] = intval($line_csv[1]);
            $entry_template_w_keys['pos_cur_lat_dd'] = floatval($line_csv[4]);
            $entry_template_w_keys['pos_cur_lng_dd'] = floatval($line_csv[5]);
            $entry_template_w_keys['pos_cur_alt_m'] = floatval($line_csv[6]);

            if ($entry_template_w_keys['time_epoch'] >= $time_interval_low) {
              // Check if data is already present in the output array made from DB data
              $add_entry = True;
              foreach ($out_arr as &$value) {
                if ($value['uav_id'] == $entry_template_w_keys['uav_id']) {
                  $add_entry = False;
                }
              }
              if ($add_entry) { // Add the DroneID entry if not already in the output array made from DB data
                $out_arr[] = $entry_template_w_keys ;
              }
            }
          }
        } else {
          // There is no data in the DB so make array format from scratch
          $keys_db = array(
            "0" => "uav_id",
            "1" => "uav_op_status",
            "2" => "uav_bat_soc",
            "3" => "time_epoch",
            "4" => "pos_cur_lat_dd",
            "5" => "pos_cur_lng_dd",
            "6" => "pos_cur_alt_m",
            "7" => "pos_cur_hdg_deg",
            "8" => "pos_cur_vel_mps",
            "9" => "pos_cur_gps_timestamp",
            "10" => "wp_next_lat_dd",
            "11" => "wp_next_lng_dd",
            "12" => "wp_next_alt_m",
            "13" => "wp_next_hdg_deg",
            "14" => "wp_next_vel_mps",
            "15" => "wp_next_eta_epoch"
          );
          $entry_template = array(
            "0" => 0,
            "1" => -1,
            "2" => -1,
            "3" => 0,
            "4" => 0,
            "5" => 0,
            "6" => 0,
            "7" => -1,
            "8" => -1,
            "9" => -1,
            "10" => -1,
            "11" => -1,
            "12" => -1,
            "13" => -1,
            "14" => -1,
            "15" => -1
          );
          $entry_template_w_keys = array_combine($keys_db, $entry_template); // Combine the arrays

          foreach ($csv as $key => $line) { // Loop through all of the DroneID entries
            $line_csv = str_getcsv($line);
            $entry_template_w_keys['uav_id'] = intval($line_csv[2]);
            $entry_template_w_keys['time_epoch'] = intval($line_csv[1]);
            $entry_template_w_keys['pos_cur_lat_dd'] = floatval($line_csv[4]);
            $entry_template_w_keys['pos_cur_lng_dd'] = floatval($line_csv[5]);
            $entry_template_w_keys['pos_cur_alt_m'] = floatval($line_csv[6]);

            if ($entry_template_w_keys['time_epoch'] >= $time_interval_low) {
              $out_arr[] = $entry_template_w_keys;
            }
          }
        }
      }
      // echo '\n--------final\n';
      // var_dump($out_arr);
      //
      // die();

      if (count($out_arr) == 0) {
        // Set content type header to support data
        $mimetype = 'text/plain';//"mime/type";
        header("Content-Type: " . $mimetype );
        // Set 'Not Found' response code and output 0
        http_response_code(404);
        echo 0;
        die();
      }


      // Set content type header to support data
      $mimetype = 'application/json';//"mime/type";
      header("Content-Type: " . $mimetype );

      echo json_encode($out_arr);

      die();
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
