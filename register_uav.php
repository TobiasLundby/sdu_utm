<?php
  // Check for POST request
  if($_SERVER['REQUEST_METHOD'] == 'POST'){
    // Check if everything has been POSTed
    if (!empty($_POST['uav_name'])
        && !empty($_POST['operator_name'])
        && !empty($_POST['operator_phone'])
        && !empty($_POST['operator_drone_cert'])
        && !empty($_POST['uav_weight_kg'])
        && !empty($_POST['uav_max_vel_mps'])
        && !empty($_POST['uav_max_endurance_s'])) { //check if post using isset
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

      // Save post values and SQL sanitise
      $uav_name = mysqli_real_escape_string($con, $_POST['uav_name']);
      $operator_name = mysqli_real_escape_string($con, $_POST['operator_name']);
      $operator_phone = mysqli_real_escape_string($con, $_POST['operator_phone']);
      $operator_drone_cert = mysqli_real_escape_string($con, $_POST['operator_drone_cert']);
      $uav_weight_kg = floatval(mysqli_real_escape_string($con, $_POST['uav_weight_kg']));
      $uav_max_vel_mps = floatval(mysqli_real_escape_string($con, $_POST['uav_max_vel_mps']));
      $uav_max_endurance_s = intval(mysqli_real_escape_string($con, $_POST['uav_max_endurance_s']));
      $ip_addr = $_SERVER['REMOTE_ADDR'];
      $user_agent = $_SERVER['HTTP_USER_AGENT'];
      $protocol = $_SERVER['SERVER_PROTOCOL'];

      // Generate authentication key
      $unhash_string = $operator_name . $operator_drone_cert . $ip_addr . $user_agent . $time_epoch . $salt;
      $hash_string   = hash('sha512', $unhash_string);
      $unhash_string = NULL;

      // DB code here
      $uav_id = get_next_uav_id_and_incr($con);

      $sql = "INSERT INTO `overview` (`int_id`, `uav_id`, `uav_name`, `operator_name`, `operator_phone`, `operator_drone_cert`, `uav_weight_kg`, `uav_max_vel_mps`, `uav_max_endurance_s`, `uav_auth_key`, `reg_time`, `reg_ip`, `req_user_agent`) VALUES (NULL, '$uav_id', '$uav_name', '$operator_name', '$operator_phone', '$operator_drone_cert', '$uav_weight_kg', '$uav_max_vel_mps', '$uav_max_endurance_s', '$hash_string', '$time_epoch', '$ip_addr', '$user_agent');";

      $result = mysqli_query($con, $sql);          //query

      // Close DB connection
      mysqli_close($con);

      // Check result
      if($result !== false){
        // Send generated data to user
        $array_out = array(
          "uav_id" => $uav_id,
          "uav_auth_key" => $hash_string,
        );
        echo json_encode($array_out);
      } else {
        // Something went wrong
        echo 0;
      }
    } else {
      // DID NOT PASS CHECKS
      echo 0;
    }
  } else {
    // DID NOT PASS CHECKS
    echo 0;
  }
?>
