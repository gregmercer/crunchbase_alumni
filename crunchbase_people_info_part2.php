<?php

define("INPUT_FILE", "people_info_part1.csv");

define("DB_HOST", "127.0.0.1:33066");
define("DB_USER", "root");
define("DB_PASS", "root");
define("DB_DATABASE", "crunchbase");

/**
 * main()
**/

$hostname = DB_HOST;
$username = DB_USER;
$password = DB_PASS;
$dbname = DB_DATABASE;

$all_people = array();

$file = fopen(INPUT_FILE,"r");

$headers = fgetcsv($file);

while(!feof($file)) {
  $row = fgetcsv($file);
  if (!$row) {
    break;
  }
  $all_people[] = array_combine($headers, $row);
}
fclose($file);

// connection to the database
$dbhandle = mysql_connect($hostname, $username, $password)
  or die("Unable to connect to MySQL");

echo "Connected to MySQL\r\n";

// select a database to work with
$selected = mysql_select_db($dbname, $dbhandle)
  or die("Could not select db " . $dbname);

echo "Connected to db = $dbname\r\n";

foreach ($all_people as &$value) {

  if (empty($value["path"])) {
    break;
  }

  $name = $value["name"];
  $path = $value["path"];

  $name = str_replace('"', '\"', $name);
  $path = str_replace('"', '\"', $path);

  $sql_statement = 'INSERT INTO people_info_part1 ' .
    '(Name,Path,UpdateDate) ' . 'VALUES("' . $name . '","' . $path . '","' . '")';

  //echo $sql_statement;

  $rec_insert = mysql_query( $sql_statement);
  if (!$rec_insert) {
    die('Could not enter data: ' . mysql_error());
  }
  echo "Entered data successfully $name $path\r\n";

}

// close the connection
mysql_close($dbhandle);

/**
 * Functions
**/

?>