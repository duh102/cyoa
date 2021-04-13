<?php
require 'db/util.php';


try {
  $conn = get_conn();
  echo "Connected successfully";
} catch(PDOException $e) {
  echo "Connection failed: " . $e->getMessage();
}

?>
