<?php

define("CRUNCHBASE_USER_KEY", "<your user key goes here>");
define("CRUNCHBASE_PEOPLE_URL", "http://api.crunchbase.com/v/2/people");

define("OUTPUT_FILE", "people_info_part1.csv");

define("SLEEP_TIME_60_SECONDS", "60 * 1000000");

/**
 * main()
**/

$all_people = array();

// there are currently 333 pages of people
$max_pages = 333;

// we'll go thru 40 pages at a time
$increment = 40;

// get a set of people at a time
for ($index = 0; $index <= ($max_pages/$increment); $index++) {
  // get a set
  getPeopleSet($all_people, $index, $max_pages, $increment);
  // wait for 60 seconds
  usleep(SLEEP_TIME_60_SECONDS);
}

write_out_people($all_people);

/**
 * Functions
**/

function getPeopleSet(&$all_people, $setNumber, $max_pages, $increment) {

  $start_page = ($setNumber * $increment) + 1;
  $end_page = ($setNumber + 1) * $increment;
  if ($end_page > $max_pages) {
    $end_page = $max_pages;
  }

  for ($index = $start_page; $index <= $end_page; $index++) {
    $people = getPeople($index);
    $all_people = array_merge($all_people, $people);
  }

}

function getPeople($pageNumber) {

  $url = CRUNCHBASE_PEOPLE_URL . "?user_key=" . CRUNCHBASE_USER_KEY . "&page=" .
    $pageNumber .
    "&order=created_at+ASC";

  $data = file_get_contents($url);
  $data = json_decode($data);

  $items = array();
  foreach ($data->data->items as $item) {
    //echo var_dump($item);
    $items[] = array(
      'name' => $item->name,
      'path' => $item->path
    );
  }

  return $items;
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

function write_out_people($people) {

  $flat = array();
  foreach ($people as $row) {
    $flat[] = flatten_array($row);
  }

  // open the file
  $file = fopen(OUTPUT_FILE,"w");

  // write out the column header
  fputcsv($file, array_keys($flat[0]));

  // write out the rows
  foreach ($flat as $row) {
    fputcsv($file,$row);
  }

  // close the file
  fclose($file);

}

?>