<?php
// Copyright 2014 David Lippman for Lumen Learning
// GPL Licensed
// 
// Beware: many parts of this script have to be customized to the content
// being processed.

@set_time_limit(0);
ini_set("max_input_time", "600");
ini_set("max_execution_time", "600");

// imagine the content you want to import has been stored already in the form
//  $content[module number][item number] = html content for the page (inside body)
//  $title[module number][item number] = content title
//  $indent[module number][item number] = indent amount for the item
//  $modtitle[module number] = module title

//delete this - this is for testing
$modtitle[0] = "Whee sample mod";
$content[0][0] = "This is text 1";
$content[0][1] = "This is text 2";
$title[0][0] = "title 1.1";
$title[0][1] = "title 1-2";
$indent[0][0] = 0;
$indent[0][1] = 1;
$modtitle[1] = "Whee sample mod 2";
$content[1][0] = "This is text 3";
$content[1][1] = "This is text 4";
$title[1][0] = "title 2.1";
$title[1][1] = "title 2-2";
$indent[1][0] = 0;
$indent[1][1] = 0;

copy("./canvasbase2.zip",'./canvasout2.zip');
$zip = new ZipArchive;
$zip->open('./canvasout2.zip');

$imsmodxml = '';
$modxml = '';
$res = '';

$newpagecnt = 0;
for ($mn = 0; $mn < count($title); $mn++) {
	//set up module in imsmanifest
	$imsmodxml .= '<item identifier="NewMod'.$mn.'">';
	$imsmodxml .= '<title>'.htmlentities(str_replace("\n",' ',$modtitle[$mn]), ENT_XML1).'</title>';
	
	//set up module in module_meta
	$modxml .= '<module identifier="NewMod'.$mn.'">';
	$modxml .= '<title>'.htmlentities(str_replace("\n",' ',$modtitle[$mn]), ENT_XML1).'</title>';
	$modxml .= '<workflow_state>active</workflow_state>';
	$modxml .= '<position>'.($mn+1).'</position>';
	$modxml .= '<require_sequential_progress>false</require_sequential_progress>';
	$modxml .= '<items>';
	
	for ($in = 0; $in < count($title[$mn]); $in++) {
		//add wiki page to cartridge
		$filename = str_replace(array(" ","."),"-", $title[$mn][$in]).'.html';;
		//add necessary header/wrapper to page content
		$html = '<html>
			<head>
			<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
			<title>'.htmlentities(str_replace("\n",' ',$title[$mn][$in])).'</title>
			<meta name="identifier" content="PAGE'.$mn.'-'.$in.'"/>
			<meta name="editing_roles" content="teachers"/>
			<meta name="workflow_state" content="active"/>
			</head>
			<body>';
		$html .= $content[$mn][$in];
		$html .= '</body></html>'; 
		$zip->addFromString('wiki_content/'.$filename, $html);
		
		//Add to imsmanifest resources
		$res .= '<resource identifier="PAGE'.$mn.'-'.$in.'" type="webcontent" ';
		$res .= 'href="wiki_content/'.$filename.'">';
		$res .= '<file href="wiki_content/'.$filename.'"/></resource>';
		
		//Add to imsmanifest items
		$imsmodxml .= '<item identifier="PAGEitem'.$mn.'-'.$in.'" identiferref="PAGE'.$mn.'-'.$in.'">';
		$imsmodxml .= '<title>'.htmlentities(str_replace("\n",' ',$title[$mn][$in]), ENT_XML1).'</title>';
		$imsmodxml .= '</item>';
		
		//Add to module_meta items
		$modxml .= '<item identifier="PAGEitem'.$mn.'-'.$in.'">';
		$modxml .= '<content_type>WikiPage</content_type>';
		$modxml .= '<workflow_state>active</workflow_state>';
		$modxml .= '<title>'.htmlentities(str_replace("\n",' ',$title[$mn][$in]), ENT_XML1).'</title>';
		$modxml .= '<identiferref>PAGE'.$mn.'-'.$in.'</identiferref>';
		$modxml .= '<position>'.($in+1).'</position>';
		$modxml .= '<new_tab/>';
		$modxml .= '<indent>'.$indent[$mn][$in].'</indent>';
		$modxml .= '</item>';
	}
	
	$imsmodxml .= '</item>';
	$modxml .= '</items></module>';
}

//put changes into manifest
$manifest = $zip->getFromName("imsmanifest.xml");
$manifest = str_replace('##RESOURCES_REPLACEME##', $res, $manifest);
$manifest = str_replace('##ITEMS_REPLACEME##', $imsmodxml, $manifest);	
$zip->addFromString('imsmanifest.xml', $manifest);

//put changes into modules_meta
$modmeta = $zip->getFromName("course_settings/module_meta.xml");
$modmeta  = str_replace('##MODULES_REPLACEME##', $modxml, $modmeta );
$zip->addFromString("course_settings/module_meta.xml", $modmeta );

?>
Done
