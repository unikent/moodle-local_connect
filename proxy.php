<?php

define('AJAX_SCRIPT', true);

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(__FILE__) . '/locallib.php');
require_once(dirname(__FILE__) . '/proxylib.php');

if (!\local_connect\utils::is_enabled()) {
  die(json_encode(array("error" => "Connect has been disabled")));
}

if (!\local_connect\course::has_access()) {
  die(json_encode(array("error" => "You do not have access to view this")));
}

/**
 * We now have two choices:
 *   1) We can use the fancy new stuff
 *   2) The fancy new stuff does not do what we want yet, so we use the old stuff.
 */

// 
// New stuff
// 

switch ($_SERVER['PATH_INFO']) {
  case '/courses':
  case '/courses/':
    $category_restrictions = isset($_GET['category_restrictions']) ? $_GET['category_restrictions'] : array();
    $courses = \local_connect\course::get_courses($category_restrictions);
    echo json_encode($courses);
    exit(0);
}


//
// Old Stuff
//

//make resource
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $CFG->kent_connect_url . $_SERVER['PATH_INFO'] . '?' . $_SERVER['QUERY_STRING']);
curl_setopt($ch, CURLOPT_HEADER, 1);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $_SERVER["REQUEST_METHOD"]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLINFO_HEADER_OUT, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents("php://input"));

// just force set the content type to json since all this stuff is json anyway
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

//get contents
$response = curl_exec( $ch );

if( !$response ) {
  header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
} else {

  $lines = explode("\r\n\r\n", $response);

  if (count($lines) > 2) {
    if (preg_match("/100 Continue/i", $lines[0])) {
      // we can throw this away, as we don't need it
      array_shift($lines);
    } else {
      // this is a problem and means this reverse proxy isn't
      // working properly which wouldn't be very surprising because
      // this is a horrible hack
      echo "REVERSE PROXY IS BROKEN";
      die(); 
    }
  }

  $response_headers = $lines[0];
  if ($lines[1]) {
    $response_body = $lines[1];
  } else {
    $response_body = '';
  }

  //send your header
  $ary_headers = explode("\n", $response_headers );
  
  foreach($ary_headers as $hdr) {
    if (!preg_match("/Transfer-Encoding/i", $hdr)) {
      header($hdr);
    }
  }

  echo $response_body;
}
