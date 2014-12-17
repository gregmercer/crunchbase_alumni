<?php

define("CRUNCHBASE_USER_KEY", "<your user key goes here>");
define("CRUNCHBASE_API_URL", "http://api.crunchbase.com/v/2/");
define("CRUNCHBASE_PEOPLE_URL", "http://api.crunchbase.com/v/2/people");

define("INPUT_FILE", "people_info_part1.csv");
define("OUTPUT_FILE", "people_info_part2.csv");

define("DB_HOST", "127.0.0.1:33066");
define("DB_USER", "root");
define("DB_PASS", "root");
define("DB_DATABASE", "crunchbase");

// Because the Crunchbase API has request limits,
// we will stop after we have done this number of
// updates.
define("UPDATE_MAX", "10");

define("SLEEP_TIME_60_SECONDS", "60 * 1000000");

/**
 * main()
**/

$all_people = read_in_people();
//var_dump($all_people);

$all_people = update_people($all_people);

//var_dump($all_people);
write_out_people($all_people);

/**
 * Functions
**/

function update_people($people) {

  $index = 1;
  $increment = 40;
  $update_max = UPDATE_MAX;
  $update_count = 0;
  foreach ($people as &$value) {

    if (empty($value["path"])) {
      continue;
    }

    // check if we should update this person
    if (!do_update_people_info($value["path"])) {
      continue;
    }

    // get the person and degrees info
    $data = getPerson($value["path"]);
    $degrees = getDegrees($data);
    $value['degrees'] = $degrees;

    // mark the person as flagged
    // and bump up the update count
    update_people_info_flag($value["path"]);
    $update_count++;
    if ($update_count >= $update_max) {
      break;
    }

    if ($index % $increment == 0) {
      // wait for 60 seconds
      usleep(SLEEP_TIME_60_SECONDS);
    }

    $index++;
  }

  return $people;
}

function getPerson($person_path) {

  $url = CRUNCHBASE_API_URL .
    $person_path .
    "?user_key=" . CRUNCHBASE_USER_KEY;

  $data = file_get_contents($url);
  $data = json_decode($data);

  return $data;
}

function getDegrees($data) {

  $degrees = array();

  if (isset($data->data->relationships) && isset($data->data->relationships->degrees)) {
    if (isset($data->data->relationships->degrees)) {
      foreach ($data->data->relationships->degrees->items as $item) {
        $organization_name = $item->organization_name;
        $organization_path = $item->organization_path;
        $degrees[] = array(
          'organization_name' => $organization_name,
          'organization_path' => $organization_path
        );
      }
    }
  }

  return $degrees;
}

function flatten_array($array, $prefix = '') {
  $result = array();
  foreach($array as $key=>$value) {
    if(is_array($value)) {
      $result = $result + flatten_array($value, $prefix . $key . '.');
    }
    else {
      $result[$prefix . $key] = $value;
    }
  }
  return $result;
}

function update_people_info_flag($path) {

  $today = date("m/d/y");

  $hostname = DB_HOST;
  $username = DB_USER;
  $password = DB_PASS;
  $dbname = DB_DATABASE;

  // connection to the database
  $dbhandle = mysql_connect($hostname, $username, $password)
    or die("Unable to connect to MySQL");

  echo "Connected to MySQL\r\n";

  // select a database to work with
  $selected = mysql_select_db($dbname, $dbhandle)
    or die("Could not select db " . $dbname);

  echo "Connected to db = $dbname\r\n";

  $sql_statement = 'UPDATE people_info_part1 ' .
    'SET UpdateDate = "' . $today . '" ' .
    'WHERE Path = "' . $path . '"';

  $rec_update = mysql_query( $sql_statement);
  if (!$rec_update) {
    die('Could not enter data: ' . mysql_error());
  }
  echo "Entered data successfully $path\r\n";

  // close the connection
  mysql_close($dbhandle);

  echo "close to db = $dbname\r\n";

}

function do_update_people_info($path) {

  $do_update = TRUE;

  $today = date("m/d/y");

  $hostname = DB_HOST;
  $username = DB_USER;
  $password = DB_PASS;
  $dbname = DB_DATABASE;

  // connection to the database
  $dbhandle = mysql_connect($hostname, $username, $password)
    or die("Unable to connect to MySQL");

  echo "Connected to MySQL\r\n";

  // select a database to work with
  $selected = mysql_select_db($dbname, $dbhandle)
    or die("Could not select db " . $dbname);

  echo "Connected to db = $dbname\r\n";

  $result = mysql_query("SELECT * FROM people_info_part1");

  // fetch tha data from the database
  while ($row = mysql_fetch_array($result)) {
    if ($row{'Path'} == $path) {
      if ($row{'UpdateDate'} != '') {
        $do_update = FALSE;
        break;
      }
    }
  }

  // close the connection
  mysql_close($dbhandle);

  echo "close to db = $dbname\r\n";

  if ($do_update) {
    echo 'do_update TRUE for path = ' . $path;
  }
  else {
    echo 'do_update FALSE for path = ' . $path;
  }
  echo "\r\n";

  return $do_update;
}

function read_in_people() {

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

  return $all_people;
}

function write_out_people($people) {

  $flat = array();
  foreach ($people as $row) {
    $flat[] = flatten_array($row);
  }

  // open the file
  $file = fopen(OUTPUT_FILE,"w");

  // find the longest row
  $max_len = 0;
  $max_row = 0;
  $row_index = 0;
  foreach ($flat as $row) {
    $num_keys = count(array_keys($row));
    if ($num_keys > $max_len) {
      $max_row = $row_index;
      $max_len = $num_keys;
    }
    $row_index++;
  }

  // write out the column header
  fputcsv($file, array_keys($flat[$max_row]));

  // write out the list of people
  foreach ($flat as $row) {
    fputcsv($file,$row);
  }

  // close the file
  fclose($file);

}

?>