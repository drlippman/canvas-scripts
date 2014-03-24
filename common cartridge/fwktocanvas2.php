<?php
// Copyright 2014 David Lippman for Lumen Learning
// GPL Licensed
// 
// Beware: many parts of this script have to be customized to the content
// being processed.

@set_time_limit(0);
ini_set("max_input_time", "600");
ini_set("max_execution_time", "600");

$folder = "econ";
$webroot = 'http://www.savingstudentsmoney.org/FWK/econ/';

if (!is_writable(__DIR__)) { die('directory not writable'); }

$curdir = rtrim(dirname(__FILE__), '/\\');

// $image is $_FILES[ <image name> ]
// $imageId is the id used in a database or wherever for this image
// $thumbWidth and $thumbHeight are desired dimensions for the thumbnail

$imgcnt = 0;
function processImage( $image, $thumbWidth, $thumbHeight )
{
    global $imgcnt,$folder;
    $imgcnt++;
    $curdir = rtrim(dirname(__FILE__), '/\\');
    $galleryPath = "$curdir/$folder/images/";
   
    $im = imagecreatefromjpeg($image);
    $size = getimagesize($image);
    $w = $size[ 0 ];
    $h = $size[ 1 ];
   
    // create thumbnail
    $tw = $thumbWidth;
    $th = $thumbHeight;
    
   
    if ( $w/$h > $tw/$th )
    { // wider
	$tmph = $h*($tw/$w);
	$imT = imagecreatetruecolor( $tw, $tmph );
	imagecopyresampled( $imT, $im, 0, 0, 0, 0, $tw, $tmph, $w, $h ); // resize to width
    }else
    { // taller
        /* crops
	$imT = imagecreatetruecolor( $tw, $th );
	$tmph = $h*($tw/$w );
        $temp = imagecreatetruecolor( $tw, $tmph );
        imagecopyresampled( $temp, $im, 0, 0, 0, 0, $tw, $tmph, $w, $h ); // resize to height
        imagecopyresampled( $imT, $temp, 0, 0, 0, $tmph/2-$th/2, $tw, $th, $tw, $th ); // crop
	imagedestroy( $temp );
	*/
	//nocrop version
	$tmpw = $w*($th/$h);
	$imT = imagecreatetruecolor( $tmpw, $th );
	imagecopyresampled( $imT, $im, 0, 0, 0, 0, $tmpw, $th, $w, $h ); // resize to width
    }
   
    // save the image
   imagejpeg( $imT, $galleryPath . 'image'.$imgcnt . '.jpg', 71 );
   return 'images/'.'image'.$imgcnt . '.jpg';
}


function fileize($str,$i,$j,$title) {
	global $webroot;
	$attr = '<hr />
<div style="font-size: x-small;">This page is licensed under a <a href="http://creativecommons.org/licenses/by-nc-sa/3.0" rel="license">Creative Commons Attribution Non-Commercial Share-Alike License</a> and contains content from a variety of sources published under a variety of open licenses, including:
<ul>
<li><a href="'.$webroot.'">Content</a> created by Anonymous under a <a href="http://creativecommons.org/licenses/by-nc-sa/3.0" rel="license">Creative Commons Attribution Non-Commercial Share-Alike License</a></li>
<li>Original content contributed by Lumen Learning</li>
</ul>
<p>If you believe that a portion of this Open Course Framework infringes another\'s copyright, <a href="http://lumenlearning.com/copyright">contact us</a>.</p>
</div>';
	$str = preg_replace('/<a\s+class="glossterm">(.*?)<\/a>/sm','$1',$str);
	
	$str = preg_replace('/<a\s+class="footnote"[^>]*#(.*?)".*<\/a>(.*?)<\/sup>/sm','<a class="footnote" href="#$1">$2</sup></a>',$str);
	$str = preg_replace('/<a[^>]+name="ftn\.(.*?)".*?<\/a>/sm','<a name="ftn.$1"></a>',$str);
	$str = preg_replace('/<a[^>]*catalog\.flatworldknowledge[^>]*>(.*?)<\/a>/sm',' $1 ',$str);
	$str = preg_replace('/<p[^>]*>/sm','<p>',$str);
	
	$out = '<html>
	<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<title>'.$title.'</title>
	<meta name="identifier" content="PAGEREF'.$i.'s'.$j.'"/>
	<meta name="editing_roles" content="teachers"/>
	<meta name="workflow_state" content="active"/>
	</head>
	<body>';
	$out .= $str;
	$out .= $attr.'</body></html>';
	return $out;	
}

copy("./canvasbase.zip",'./canvasout.zip');
$zip = new ZipArchive;
if ($zip->open('./canvasout.zip', ZipArchive::CREATE)===true) {
	//echo "opened zip";
}
//3~sFRGaRwnRO2kPq8AeMMW8FIQbqF40n9RjgjQHlXRfK2eJbWStDqu6TGAiROG4fso
$chapters = array();
$sections = array();
$secind = array();
$images = array();
for ($k=4;$k<=25;$k++) {
	if ($k<10) {
		$source = $source = 'section_0'.$k;
	} else {
		$source = $source = 'section_'.$k;
	}

	
	$c = file_get_contents($folder .'/'.$source.'.html');
	$c = preg_replace('|</div>\s*<div\sid=navbar-bottom.*$|sm','',$c);
	
	//$c = mb_convert_encoding($c,'windows-1252','UTF-8');
	
	//remove copyrighted images
	$c = preg_replace('/<img[^>]*><div\sclass="copyright".*?<\/div>/sm','',$c); 
	
	
	$c = str_replace('<div class="figure','<div style="width:500px;margin:auto;" class="figure',$c);
	
	preg_match_all('/<img[^>]*src="(.*?)"[^>]*>/',$c,$matches,PREG_SET_ORDER);
	$sl = strlen($source);
	foreach ($matches as $m) {
		if (substr($m[1],0,$sl)==$source) {
			$newpath = processImage('./'.$folder.'/'.$m[1], 500, 400);
			$images[] = $newpath;
			$c = str_replace($m[0],'<a target="_blank" href="'.$webroot.$m[1].'"><img src="%24IMS_CC_FILEBASE%24/'.$newpath.'"/></a>',$c);
		}
	}
	
	preg_match('/<div\s+class="chapter.*?>([^<>]+)<\/h1>/sm',$c,$matches);
	//for ($i=1;$i<count($parts);$i+=2) {
		$chp = $k;
		$chapters[$chp]	= htmlentities(str_replace("\n",' ',$matches[1]), ENT_XML1);
		$sections[$chp] = array();
		$secind[$chp] = array();
		$secparts = preg_split('/<div\s+class="section.*?id="([^"]*)".*?>([^<>]+)<\/h2>/sm',$c,-1,PREG_SPLIT_DELIM_CAPTURE);
		for ($j=1;$j<count($secparts);$j+=3) {
			$sec = ($j+2)/3;
			$sections[$chp][$sec] = htmlentities(str_replace("\n",' ',$secparts[$j+1]), ENT_XML1);
			$secind[$chp][$sec] = substr_count($secparts[$j],'_')-1; 
			$zip->addFromString('wiki_content/Section'.$chp.'-'.$sec.'.html', fileize('<div class="section"><h2>'.$secparts[$j+1].'</h2>'.$secparts[$j+2],$chp,$sec,$sections[$chp][$sec]));
			//$sectioncontent[$chp][$sec] = $secparts[$j+1];
		}
	//}
}


$item = '';
$res = array();

$modout = '<?xml version="1.0" encoding="UTF-8"?>
<modules xmlns="http://canvas.instructure.com/xsd/cccv1p0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://canvas.instructure.com/xsd/cccv1p0 http://canvas.instructure.com/xsd/cccv1p0.xsd">';

foreach ($chapters as $i=>$chp) {
	$item .= '<item identifier="MOD'.$i.'">
		<title>'.$chp.'</title>';
	$modout .= '<module identifier="MOD'.$i.'">
    <title>'.$chp.'</title>
    <workflow_state>active</workflow_state>
    <position>'.($i+1).'</position>
    <require_sequential_progress>false</require_sequential_progress>
    <items>';
    foreach ($sections[$i] as $j=>$sec) {
    	$modout .= '      <item identifier="PAGEITEM'.$i.'s'.$j.'">
        <content_type>WikiPage</content_type>
        <workflow_state>active</workflow_state>
        <title>'.$sec.'</title>
        <identifierref>PAGEREF'.$i.'s'.$j.'</identifierref>
        <position>'.($j+1).'</position>
        <new_tab></new_tab>
        <indent>'.$secind[$i][$j].'</indent>
      </item>'; 
      	$item .= '   <item identifier="PAGEITEM'.$i.'s'.$j.'" identifierref="PAGEREF'.$i.'s'.$j.'">
      		<title>'.$sec.'</title>
      		</item>';
      	$res[] = '<resource identifier="PAGEREF'.$i.'s'.$j.'" href="wiki_content/Section'.$i.'s'.$j.'.html" type="webcontent">
      			<file href="wiki_content/Section'.$i.'s'.$j.'.html"/>
      		</resource>';
    }
    $modout .= '   </items></module>';
    $item .= '</item>';
}
$modout .= '</modules>';
//file_put_contents('course_settings/module_meta.xml',$modout);
$zip->addFromString('course_settings/module_meta.xml',$modout);
/*
foreach ($chapters as $i=>$chp) {
	foreach ($sections[$i] as $j=>$sec) {
		$out = '<html>
	<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<title>Test Page</title>
	<meta name="identifier" content="PAGEREF'.$i.'s'.$j.'"/>
	<meta name="editing_roles" content="teachers"/>
	<meta name="workflow_state" content="active"/>
	</head>
	<body>';
		$out .= $sectioncontent[$i][$j];
		$out .= '</body></html>';
		file_put_contents('wiki_content/Section'.$i.'s'.$j.'.html', $out);
	}
}
*/
foreach ($images as $i=>$img) {
	$res[] = '<resource identifier="IMG'.$i.'" href="web_resources/'.$img.'" type="webcontent">
      			<file href="web_resources/'.$img.'"/>
      		</resource>';
      	$zip->addFile('./'.$folder.'/'.$img,'web_resources/'.$img);
}

$manout = '<?xml version="1.0" encoding="UTF-8"?>
<manifest identifier="if7dca0913700911c301385921dfc23f6" xmlns="http://www.imsglobal.org/xsd/imsccv1p1/imscp_v1p1" xmlns:lom="http://ltsc.ieee.org/xsd/imsccv1p1/LOM/resource" xmlns:lomimscc="http://ltsc.ieee.org/xsd/imsccv1p1/LOM/manifest" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.imsglobal.org/xsd/imsccv1p1/imscp_v1p1 http://www.imsglobal.org/profile/cc/ccv1p1/ccv1p1_imscp_v1p2_v1p0.xsd http://ltsc.ieee.org/xsd/imsccv1p1/LOM/resource http://www.imsglobal.org/profile/cc/ccv1p1/LOM/ccv1p1_lomresource_v1p0.xsd http://ltsc.ieee.org/xsd/imsccv1p1/LOM/manifest http://www.imsglobal.org/profile/cc/ccv1p1/LOM/ccv1p1_lommanifest_v1p0.xsd">
  <metadata>
    <schema>IMS Common Cartridge</schema>
    <schemaversion>1.1.0</schemaversion>
    <lomimscc:lom>
      <lomimscc:general>
        <lomimscc:title>
          <lomimscc:string>test2</lomimscc:string>
        </lomimscc:title>
      </lomimscc:general>
      <lomimscc:lifeCycle>
        <lomimscc:contribute>
          <lomimscc:date>
            <lomimscc:dateTime>2013-06-25</lomimscc:dateTime>
          </lomimscc:date>
        </lomimscc:contribute>
      </lomimscc:lifeCycle>
      <lomimscc:rights>
        <lomimscc:copyrightAndOtherRestrictions>
          <lomimscc:value>yes</lomimscc:value>
        </lomimscc:copyrightAndOtherRestrictions>
        <lomimscc:description>
          <lomimscc:string>CC Attribution - http://creativecommons.org/licenses/by/3.0</lomimscc:string>
        </lomimscc:description>
      </lomimscc:rights>
    </lomimscc:lom>
  </metadata>
  <organizations>
    <organization identifier="org_1" structure="rooted-hierarchy">
      <item identifier="LearningModules">'.$item.'</item>
    </organization>
  </organizations>
  <resources>
    <resource identifier="ifce787660e5ece73f5fd9fbbfde33fab_syllabus" type="associatedcontent/imscc_xmlv1p1/learning-application-resource" href="course_settings/syllabus.html" intendeduse="syllabus">
      <file href="course_settings/syllabus.html"/>
    </resource>
    <resource identifier="ifce787660e5ece73f5fd9fbbfde33fab" type="associatedcontent/imscc_xmlv1p1/learning-application-resource" href="course_settings/canvas_export.txt">
      <file href="course_settings/course_settings.xml"/>
      <file href="course_settings/module_meta.xml"/>
      <file href="course_settings/files_meta.xml"/>
      <file href="course_settings/canvas_export.txt"/>
    </resource>';
foreach ($res as $r) {
	$manout .= $r;	
}
$manout .= '</resources>
</manifest>';
//file_put_contents('imsmanifest.xml',$modout);
$zip->addFromString('imsmanifest.xml',$manout);
if ($zip->close()===false) {
	echo "fail on close";
}
?>
Done
