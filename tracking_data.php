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
          && !empty($_POST['pos_cur_gps_epoch'])
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
        $uav_id = intval(mysqli_real_escape_string($con, $_POST['uav_id']));
        $uav_auth_key = mysqli_real_escape_string($con, $_POST['uav_auth_key']);


        // Check authentication key match with the provided uav_id
        auth_req($uav_id, $uav_auth_key, $con);
        // Passed authentication

        // Save and sanitise values
        $uav_op_status = intval(mysqli_real_escape_string($con, $_POST['uav_op_status']));
        $uav_bat_soc = -1;
        if(!empty($_POST['uav_bat_soc'])){
          $uav_bat_soc = floatval(mysqli_real_escape_string($con, $_POST['uav_bat_soc']));
        }

        $pos_cur_lat_dd = floatval(mysqli_real_escape_string($con, $_POST['pos_cur_lat_dd']));
        $pos_cur_lng_dd = floatval(mysqli_real_escape_string($con, $_POST['pos_cur_lng_dd']));
        $pos_cur_alt_m = floatval(mysqli_real_escape_string($con, $_POST['pos_cur_alt_m']));
        $pos_cur_hdg_deg = floatval(mysqli_real_escape_string($con, $_POST['pos_cur_hdg_deg']));
        $pos_cur_vel_mps = floatval(mysqli_real_escape_string($con, $_POST['pos_cur_vel_mps']));
        $pos_cur_gps_epoch = intval(mysqli_real_escape_string($con, $_POST['pos_cur_gps_epoch']));

        $wp_next_lat_dd = floatval(mysqli_real_escape_string($con, $_POST['wp_next_lat_dd']));
        $wp_next_lng_dd = floatval(mysqli_real_escape_string($con, $_POST['wp_next_lng_dd']));
        $wp_next_alt_m = floatval(mysqli_real_escape_string($con, $_POST['wp_next_alt_m']));
        $wp_next_hdg_deg = floatval(mysqli_real_escape_string($con, $_POST['wp_next_hdg_deg']));
        $wp_next_vel_mps = floatval(mysqli_real_escape_string($con, $_POST['wp_next_vel_mps']));
        $wp_next_eta_epoch = floatval(mysqli_real_escape_string($con, $_POST['wp_next_eta_epoch']));

        // Insert the data in the DB

        $sql = "INSERT INTO `$tracking_data_db` (`int_id`, `uav_id`, `uav_op_status`, `uav_bat_soc`, `time_epoch`, `pos_cur_lat_dd`, `pos_cur_lng_dd`, `pos_cur_alt_m`, `pos_cur_hdg_deg`, `pos_cur_vel_mps`, `pos_cur_gps_timestamp`, `wp_next_lat_dd`, `wp_next_lng_dd`, `wp_next_alt_m`, `wp_next_hdg_deg`, `wp_next_vel_mps`, `wp_next_eta_epoch`) VALUES (NULL, '$uav_id', '$uav_op_status', '$uav_bat_soc', '$time_epoch', '$pos_cur_lat_dd', '$pos_cur_lng_dd', '$pos_cur_alt_m', '$pos_cur_hdg_deg', '$pos_cur_vel_mps', '$pos_cur_gps_epoch', '$wp_next_lat_dd', '$wp_next_lng_dd', '$wp_next_alt_m', '$wp_next_hdg_deg', '$wp_next_vel_mps', '$wp_next_eta_epoch');";

        $result = mysqli_query($con, $sql);          //query

        // Close DB connection
        mysqli_close($con);

        // Check result
        if($result !== false){
          // Set 'Accepted' response code
          http_response_code(202);
          // Send result to user
          echo json_encode(1);
          die();
        }
        // Close DB connection
        mysqli_close($connection);
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
        $time_delta_s = intval( mysqli_real_escape_string($con, $_GET['time_delta_s']) );
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

      // Close DB connection
      mysqli_close($con);

      //$out_arr = array_slice($out_arr, 1, 15);
      //var_dump($out_arr);

      //print_r($out_arr);

      //$ar = array('a'=>'apple', 'b'=>'banana', '42'=>'pear', 'd'=>'orange');
      //print_r(array_slice($ar, 0, 3));
      //print_r(array_slice($ar, 0, 4, true));

      echo json_encode($out_arr);

      die();
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
