<?php
  if (!empty($_SERVER['HTTPS'])) { // TLS enabled
    // Check for POST request
    if($_SERVER['REQUEST_METHOD'] == 'POST'){
      // Check if everything has been POSTed
      if ( !empty($_POST['uav_name'])
          && !empty($_POST['uav_weight_kg'])
          && !empty($_POST['uav_max_vel_mps'])
          && !empty($_POST['uav_max_endurance_s'])
          && !empty($_POST['gdpr_compliance']) ) { //check if post using isset
        //  PASSED CHECKS
        // Require DB config
        require_once('config.php');
        // Require DB functions
        require_once('db_functions.php');
        // Require crypt functions
        require_once('crypt.php');

        // Connect to DB
        $con = mysqli_connect($mysql_host, $mysql_user, $mysql_pw, $mysql_db);
        if (!$con) {
          die("Connection failed: " . mysqli_connect_error());
        }

        // Save post values and SQL sanitise
        $uav_name = mysqli_real_escape_string($con, trim( strip_tags( addslashes($_POST['uav_name']) ) ) );
        // GDPR - START
        $operator_name = 'Empty due to GDPR';
        $operator_phone = 'Empty due to GDPR';
        $operator_drone_cert = 'Empty due to GDPR';
        $gdpr = mysqli_real_escape_string($con, trim( strip_tags( addslashes($_POST['gdpr_compliance']) ) ) );
        if($gdpr == 'yes' || $gdpr == 'y' || $gdpr == 'YES' || $gdpr == 'Y' || $gdpr == 1){
          if(!empty($_POST['operator_name'])){
            $operator_name = mysqli_real_escape_string($con, trim( strip_tags( addslashes($_POST['operator_name']) ) ) );
            $operator_name = encrypt($operator_name);
          } else {
            $operator_name = 'GDPR accept but no input';
          }
          if(!empty($_POST['operator_phone'])){
            $operator_phone = mysqli_real_escape_string($con, trim( strip_tags( addslashes($_POST['operator_phone']) ) ) );
            $operator_phone = encrypt($operator_phone);
          } else {
            $operator_phone = 'GDPR accept but no input';
          }
          if(!empty($_POST['operator_drone_cert'])){
            $operator_drone_cert = mysqli_real_escape_string($con, trim( strip_tags( addslashes($_POST['operator_drone_cert']) ) ) );
            $operator_drone_cert = encrypt($operator_drone_cert);
          } else {
            $operator_drone_cert = 'GDPR accept but no input';
          }
        }
        // GDPR - END

        $uav_weight_kg = floatval( mysqli_real_escape_string($con, trim( strip_tags( addslashes($_POST['uav_weight_kg']) ) ) ) );
        $uav_max_vel_mps = floatval( mysqli_real_escape_string($con, trim( strip_tags( addslashes($_POST['uav_max_vel_mps']) ) ) ) );
        $uav_max_endurance_s = intval( mysqli_real_escape_string($con, trim( strip_tags( addslashes($_POST['uav_max_endurance_s']) ) ) ) );
        $ip_addr = $_SERVER['REMOTE_ADDR'];
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        $protocol = $_SERVER['SERVER_PROTOCOL'];

        // Generate authentication key
        $unhash_string = $operator_name . $operator_drone_cert . $ip_addr . $user_agent . $time_epoch . $salt;
        $hash_string   = hash('sha512', $unhash_string);
        $unhash_string = NULL;

        // DB code here
        $uav_id = get_next_uav_id_and_incr($con);

        $sql = "INSERT INTO `$uav_data_db` (`int_id`, `uav_id`, `uav_name`, `operator_name`, `operator_phone`, `operator_drone_cert`, `uav_weight_kg`, `uav_max_vel_mps`, `uav_max_endurance_s`, `uav_auth_key`, `reg_time`, `reg_ip`, `req_user_agent`) VALUES (NULL, '$uav_id', '$uav_name', '$operator_name', '$operator_phone', '$operator_drone_cert', '$uav_weight_kg', '$uav_max_vel_mps', '$uav_max_endurance_s', '$hash_string', '$time_epoch', '$ip_addr', '$user_agent');";

        $result = mysqli_query($con, $sql);          //query

        // Close DB connection
        mysqli_close($con);

        // Check result
        if($result !== false){
          // Set 'Created' response code
          http_response_code(201);
          // Send generated data to user
          $array_out = array(
            "uav_id" => $uav_id,
            "uav_auth_key" => $hash_string,
          );
          echo json_encode($array_out);
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
      if ( !isset($_GET['uav_id']) ){
        // Set 'Bad Request' response code and output 0
        http_response_code(400);
        echo 0;
        die();
      }

      // Require DB config
      require_once('config.php');

      // Connect to DB
      $con = mysqli_connect($mysql_host, $mysql_user, $mysql_pw, $mysql_db);
      if (!$con) {
        die("Connection failed: " . mysqli_connect_error());
      }

      $uav_id = intval( mysqli_real_escape_string($con, trim( strip_tags( addslashes($_GET['uav_id']) ) ) ) );
      //print $uav_id . '<br>';

      $sql = "SELECT * FROM `$uav_data_db` WHERE `uav_id` = '$uav_id' LIMIT 1";

      $result = mysqli_query($con, $sql);          //query

      //echo 'Entries: ' . mysqli_num_rows($result) . '<br>';
      $out_arr = array();
      if (mysqli_num_rows($result) == 0) {
        // Set 'Not Found' response code and output 0
        http_response_code(404);
        echo 0;
        die();
      }
      if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
          //echo 'UAV ID: ' . $row['uav_id'] . ', int ID:' . $row['int_id'] . ', time EPOCH: ' . $row['time_epoch'] . '<br>';
          //print_r($row);
          //print_r(array_slice($row,1, 16, true));
          $row_1st_data = array_slice($row,1, 2, true);
          $row_2nd_data = array_slice($row,6, 3, true);
          $row_combined = array_merge($row_1st_data, $row_2nd_data);
          //$out_arr[] = array_slice($row,1, 8, true);
          $out_arr[] = $row_combined;
          //$out_arr[] = $row;
        }
      }

      //print_r($out_arr);
      echo json_encode($out_arr);
      die();
    }
    // DID NOT PASS CHECKS
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
