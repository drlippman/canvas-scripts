<?php
/*  David Lippman for Lumen Learning
*   List students with primary email addresses
*/

$courseid = '';
$token = '';
$domain = '';

//get list of students.  Might be multiple pages long
$pagecnt = 1;
$stus = array();
while(1) {
	$f = @file_get_contents('https://'.$domain.'/api/v1/courses/'.$courseid.'/users?enrollment_type=student&include[]=email&per_page=50&page='.$pagecnt.'&access_token='.$token);
	$pagecnt++;
	if (trim($f)=='[]' || $pagecnt>30 || $f===false) {
		break; //stop if we run out, or if something went wrong and $pagecnt is over 30
	} else {
		$list = json_decode($f);
		for ($i=0;$i<count($list);$i++) {
			$stus[] = array("name"=>$list[$i]->name, "email"=>$list[$i]->email);
		}
		if (count($list)<50) { //not going to be another page
			break;
		}
	}
}
foreach ($stus as $stu) {
	echo $stu['name'].': '.$stu['email'].'<br/>';
}
?>
