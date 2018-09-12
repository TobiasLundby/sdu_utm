<?php
  function get_data_from_db_single($var_name, $connection){
    // Description: returns a single data entry from the DB for the variable name provided
    // Input: varible name, a mysqli connection
    // Output: data from DB (0 if no data)
    $sql = "SELECT `data` FROM `data` WHERE `var` = '$var_name' LIMIT 1";
    $result = mysqli_query($connection, $sql);          //query
    if ($result !== false && mysqli_num_rows($result) > 0) {
      $row = mysqli_fetch_assoc($result);
      return $row['data'];
    }
    return 0;
  }

  function get_data_from_db_multiple($var_name, $connection){
    // Description: returns data entries from the DB for the variable name provided
    // Input: varible name, a mysqli connection
    // Output: data from DB (0 if no data)
    $sql = "SELECT * FROM `data` WHERE `var` = '$var_name'";
    $result = mysqli_query($connection, $sql);          //query
    if ($result !== false && mysqli_num_rows($result) > 0) {
      $combined_data = array();
      while ($row = mysqli_fetch_assoc($result)) {
          $combined_data[] = $row['data'];
      }
      return $combined_data;
    }
    return 0;
  }

  function get_next_uav_id_and_incr($connection){
    // Description: gets the next UAV ID / DroneID and increment the DB variable
    // Input: mysqli connection
    // Output: next UAV ID or -1 if did not work
    $var_name = 'drone_id_next';
    $uav_id_next = get_data_from_db_single($var_name, $connection);
    $uav_id_2next = $uav_id_next + 1;
    $sql = "UPDATE `data` SET `data` = '$uav_id_2next' WHERE  `var` = '$var_name'";
    $result = mysqli_query($connection, $sql);          //query
    if ($result !== false) {
      return intval($uav_id_next);
    }
    // Close DB connection
    mysqli_close($connection);
    // Set 'Internal Server Error' response code and output 0
    http_response_code(500);
    echo 0;
    die(); // DIE - Authentication failed - not correct format
  }

  function auth_req($uav_id, $uav_auth_key, $connection){
    // Description: compares the stored authentication key with the provided authentication key and UAV ID
    // Input: UAV ID, UAV authentication key, and a mysqli connection
    // Output: 1 = True and it terminates the script if the info does not match
    if(strlen($uav_auth_key) === 128){
      $sql = "SELECT `uav_auth_key` FROM `overview` WHERE `uav_id` = '$uav_id' LIMIT 1";
      $result = mysqli_query($connection, $sql);          //query
      if ($result !== false && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $db_uav_auth_key = $row['uav_auth_key'];
        if($db_uav_auth_key === $uav_auth_key){
          return 1;
        }
      }
      // Authentication failed - UAV does not exist - will die on next couple of lines
    }
    // Close DB connection
    mysqli_close($connection);
    // Set 'Precondition failed' response code and output 0
    http_response_code(412);
    echo 0;
    die(); // DIE - Authentication failed - not correct format
  }
?>
