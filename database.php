<?php

/*

This program retrevies JSON data from the given API, connects to a given SQL database,
and creates the table and columns, and then inserts the data into the given database

*/

// Get data from API and decode
$buildings = json_decode(file_get_contents('https://mobile.mtsu.edu/request/mobileapi_json/buildings'));
if ($buildings->success == true) {
  echo "Data retreived\n";
}
else {
  echo "Unable to retreve data\n";
  return;
}

// Define variables to establish connection
$servername = "localhost:3306";
$username = "root";
$password = "rootroot";
$dbname = "MTSU_buildings";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}
else {
  echo "Connected succesfully\n";
}

// Create database (most likely not needed; database should already be created)
// $sql = "CREATE DATABASE MTSU_buildings";
// if ($conn->query($sql) === TRUE) {
//   echo "Database created successfully \n";
// }
// else {
//   echo "Error creating database: " . $conn->error . "\n";
// }

// Create table named 'building' with one column ('key_id' the primany key)
$sql = "USE MTSU_buildings";
$sql = "CREATE TABLE building (key_id INT(6) NOT NULL PRIMARY KEY)";

if ($conn->query($sql) === TRUE) {
  echo "Table building created successfully\n";
} 
else {
  echo "Error creating table: " . $conn->error;
}

// Create other columns as dictated by the data
foreach($buildings->data[0] as $key => $object){
  // Define type of each column (strings have 1500 char because the description for buildings for this data is long-- can be changed in the future)
  $type = gettype($object);
  if (strcmp($type,"integer") == 0){
    $type = "INT(6)";
  }
  else if (strcmp($type,"string") == 0){
    $type = "VARCHAR(1500)";
  }
  else if (strcmp($type,"double") == 0){
    $type = "FLOAT(6)";
  }

  $sql = "ALTER TABLE building ADD $key $type";

  if ($conn->query($sql) === TRUE) {
    echo "Table building altered successfully, column " . $key . " with type " . $type . " created\n";
  } else {
    echo "Error altering table, column " . $key . " with type " . $type . " was not created: " . $conn->error . "\n";
  }
}

// Insert data
// First create data for key_id for each building
$i = 0;
foreach($buildings->data as $info){
  ++$i;
  $sql = "INSERT INTO building (key_id) VALUES ($i)";
  if ($conn->query($sql) === TRUE) {
    echo "New building created successfully with key_id = ". $i . "\n";
  } else {
    echo "Error building with key_id = ". $i . " was unable to be created: " . $sql . $conn->error . "\n";
  }
}
// Updates other columns of each bulding created with a key_id
for($a = 1; $a <= $i; ++$a) {
  foreach($buildings->data[$a - 1] as $key => $object){
    if ($object == 0 and (strcmp(gettype($object),"integer") == 0)){
      $sql = "UPDATE building SET $key = 0 WHERE key_id = $a";
    }
    else if ($object == null){
      $sql = "UPDATE building SET $key = ' ' WHERE key_id = $a";
    }
    else if(strcmp(gettype($object),"string") == 0) {
      $newobject = addslashes($object);
      $sql = "UPDATE building SET $key = '$newobject' WHERE key_id = $a"; 
    }
    else {
      $sql = "UPDATE building SET $key = $object WHERE key_id = $a";
    }
    if ($conn->query($sql) === TRUE) {
      echo "New data inserted successfully in column " . $key . " where key_id = " . $a . "\n";
    } else {
      echo "Error data " . $object . " was unable to be inserted in column " . $key . "where key_id = " . $a . " : " . $sql . $conn->error . "\n";
    }
  }
}

// Close connection
$conn->close();
?>