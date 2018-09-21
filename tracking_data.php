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
            if ($result === FALSE) {
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

      // if (mysqli_num_rows($result) == 0) {
      //   // Set content type header to support data
      //   $mimetype = 'text/plain';//"mime/type";
      //   header("Content-Type: " . $mimetype );
      //   // Set 'Not Found' response code and output 0
      //   http_response_code(404);
      //   echo 0;
      //   die();
      // }
      //echo 'Entries: ' . mysqli_num_rows($result) . '<br>';
      $out_arr = array();
      if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
          //echo 'UAV ID: ' . $row['uav_id'] . ', int ID:' . $row['int_id'] . ', time EPOCH: ' . $row['time_epoch'] . '<br>';
          //print_r($row);
          //print_r(array_slice($row,1, 16, true));
          $out_arr[] = array_slice($row,1, 16, true);
          //$out_arr[] = $row;
        }
      }

      $csv = explode("\n", trim( strip_tags( file_get_contents('https://droneid.dk/tobias/droneid.php') ) ) );
      #var_dump($csv);

      echo '\n\n';

      // Make key array to combine with the data
      $keys_droneID = array(
        "0" => "timestamp",
        "1" => "timestamp_epoch",
        "2" => "uav_id",
        "3" => "uav_name",
        "4" => "pos_cur_lat_dd",
        "5" => "pos_cur_lng_dd",
        "6" => "pos_cur_alt_m",
        "7" => "acc_pct",
        "8" => "fix_type",
        "9" => "lnk_pct",
        "10" => "tracker_bat_soc_pct",
        "11" => "sim"
      );

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

      $entry_template = $out_arr[0];
      $entry_template['uav_op_status'] = -1;
      $entry_template['uav_bat_soc'] = -1;
      $entry_template['pos_cur_hdg_deg'] = -1;
      $entry_template['pos_cur_vel_mps'] = -1;
      $entry_template['pos_cur_gps_timestamp'] = -1;
      $entry_template['wp_next_lat_dd'] = -1;
      $entry_template['wp_next_lng_dd'] = -1;
      $entry_template['wp_next_alt_m'] = -1;
      $entry_template['wp_next_hdg_deg'] = -1;
      $entry_template['wp_next_vel_mps'] = -1;
      $entry_template['wp_next_eta_epoch'] = -1;
      print_r($entry_template);

      // Remember to handle if THERE IS NO DATA IN THE DB!! NOTE TODO

      // Make array from data with keys
      //$out_arr = array();
      foreach ($csv as $key => $line) {
        $line_csv = str_getcsv($line);
        echo '\n\n----';
        print_r($line_csv);
        $entry_template['uav_id'] = $line_csv[2];
        $entry_template['time_epoch'] = $line_csv[1];
        $entry_template['pos_cur_lat_dd'] = $line_csv[4];
        $entry_template['pos_cur_lng_dd'] = $line_csv[5];
        $entry_template['pos_cur_alt_m'] = $line_csv[6];

        // $line_csv_keys = array_combine($keys, $line_csv);
        // $line_csv_keys['timestamp_epoch'] = intval($line_csv_keys['timestamp_epoch']);
        // $line_csv_keys['lat_dd'] = floatval($line_csv_keys['lat_dd']);
        // $line_csv_keys['lng_dd'] = floatval($line_csv_keys['lng_dd']);
        // $line_csv_keys['alt_m'] = intval($line_csv_keys['alt_m']);
        // $line_csv_keys['hdg_deg'] = intval($line_csv_keys['hdg_deg']);
        // $line_csv_keys['vel_mps'] = intval($line_csv_keys['vel_mps']);
        $out_arr[] = $entry_template ;
      }
      echo '\n\n----';
      var_dump($out_arr);

      die();

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
