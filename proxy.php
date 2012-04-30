<?php
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php'); 
if( !$CFG->kent_connect ) die(0);

$sitecontext = get_context_instance(CONTEXT_SYSTEM);
$site = get_site();

$cats = $DB->get_records('course_categories');
$cat_permissions = array();

foreach($cats as $cat) {
  $context = get_context_instance(CONTEXT_COURSECAT, $cat->id);

  if(has_capability('moodle/category:manage', $context)) {
    array_push($cat_permissions, $cat->id);
  }
}

if(count($cat_permissions) == 0) {
  print_error('accessdenied', 'local_connect');
}

//make resource
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $CFG->kent_connect_url . $_SERVER['PATH_INFO'] . '?' . $_SERVER['QUERY_STRING']);
curl_setopt($ch, CURLOPT_HEADER, 1);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $_SERVER["REQUEST_METHOD"]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLINFO_HEADER_OUT, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents("php://input"));

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
    header($hdr);
  }
  echo $response_body;
}
exit(0);
