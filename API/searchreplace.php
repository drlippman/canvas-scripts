<?php

/*
  Basic Wiki Page Search-and-Replace 
  using Canvas LMS API library
  
  Copyright 2014 David Lippman, Lumen Learning
  GPL License
*/

//make sure we don't timeout; this will take a while
@set_time_limit(0); 
ini_set("max_input_time", "2400");
ini_set("max_execution_time", "2400");

require("canvaslib.php");

//Provide the API access info and course ID
$courseid = 1;
$token = 'API access token';
$domain = 'your canvas domain, like school.instructure.com';

$api = new CanvasLMS($token,$domain);

$cnt = 0;

//grab the list of pages. 
$pages = $api->getPageList($courseid);

//iterate through the page list
foreach ($pages as $id=>$name) {
	//grab the body of each page
	$body = $api->getPageData($courseid, $id, 'body');
	
	//do the search-and-replace logic here
	$newbody = str_replace("search","replace", $body);

	//update the page with the new body
	if ($api->updatePage($courseid, $id, array("body"=>$newbody))) {
		$cnt++;
	}
}

echo "$cnt pages updated";
?>
