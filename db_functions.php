<?php
  function get_data_from_db_single($var_name, $connection){
    // Description: returns a single data entry from the DB for the variable name provided
    // Input: varible name, a mysqli connection
    // Output: data from DB (0 if no data)
    $sql = "SELECT * FROM `data` WHERE `var` = '$var_name'";
    $result = mysqli_query($connection, $sql);          //query
    if (mysqli_num_rows($result) > 0) {
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
    if (mysqli_num_rows($result) > 0) {
      $combined_data = array();
      while ($row = mysqli_fetch_assoc($result)) {
          $combined_data[] = $row['data'];
      }
      return $combined_data;
    }
    return 0;
  }

  function get_next_uav_id_and_incr($connection){
    $var_name = 'drone_id_next';
    $uav_id_next = get_data_from_db_single($var_name, $connection);
    $uav_id_2next = $uav_id_next + 1;
    $sql = "UPDATE `data` SET `data` = '$uav_id_2next' WHERE  `var` = '$var_name'";
    $result = mysqli_query($connection, $sql);          //query
    return intval($uav_id_next);
  }
?>
