<?php
/*  David Lippman for Lumen Learning
*
*   Add outcomes to an outcome group  
*/

$token = '';
$domain = 'school.instructure.com';

//Do you want to these at the global, account, or course level?
// 0 = global,  1 = account,  2 = course
$addto = 2;

//IF you're adding at the account level, what's the account ID?
$accountid = ''

//IF you're adding at the course level, what's the course ID?
$courseid = '';

//You need to precreate an outcome group in Canvas.  Enter the 
//outcome group ID here.  This is REQUIRED for a global outcome.  For
//course and account items, if you leave this blank, the first outcome group
//in your account/course will be used.
$outcomegroup = '';

//Load the outcomes from a CSV.   
//Column 1 is outcome title, column 2 is outcome description
$handle = fopen("outcomes.csv", "r");


//you shouldn't need to change anything after this

if ($addto==0) {
	//  GLOBAL LEVEL
	$endpoint = "/api/v1/global/outcome_groups/$outcomegroup/outcomes";
} else if ($addto==1) {
	//  ACCOUNT LEVEL

	if ($outcomegroup=='') {
		//fetch first outcome group in account
		$groupsendpoint = "/api/v1/accounts/$accountid/outcome_groups";
		$page= json_decode(file_get_contents('https://'.$domain.$groupsendpoint.'?access_token='.$token));
		$outcomegroup = $page[0]->id;
	}
	$endpoint = "api/v1/accounts/$accountid/outcome_groups/$outcomegroup/outcomes";
} else {
	//  COURSE LEVEL
	if ($outcomegroup=='') {  
		//fetch first outcome group in course
		$groupsendpoint = "/api/v1/courses/$courseid/outcome_groups";
		$page= json_decode(file_get_contents('https://'.$domain.$groupsendpoint.'?access_token='.$token));
		$outcomegroup = $page[0]->id;
	}
	$endpoint = "/api/v1/courses/$courseid/outcome_groups/$outcomegroup/outcomes";
}

//now read the CSV and add the outcomes
while (($data = fgetcsv($handle))!==false) {
	$parts = explode(",",$line);
	$vals = "title=".urlencode($parts[0]).'&description='.urlencode($parts[1]);
	$ch = curl_init('https://'.$domain.$endpoint.'?access_token='.$token);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
	curl_setopt($ch, CURLOPT_POSTFIELDS, $vals);
	$response = curl_exec($ch);
}

fclose($handle);
