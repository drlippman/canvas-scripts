<?php
// Copyright 2014 David Lippman for Lumen Learning
// GPL Licensed
//
// Script for doing modifications to a common cartridge file

$file = "filename.zip";

require("phpQuery-onefile.php");

$zip = new ZipArchive;
$zip->open($file);

//read in manifest file
phpQuery::newDocumentXML($zip->getFromName("imsmanifest.xml"));

//read resource list so we know what file to look in for things
$ref = pq("resource");
$reflist = array();
$reftype = array();
foreach ($ref as $r) {
	$reflist[pq($r)->attr("identifier")] = pq($r)->attr("href");
	$reftype[pq($r)->attr("identifier")] = pq($r)->attr("type");
}
$cnt = 0;
//this accesses all the learning module items
//so this script will only modify items actually listed in the modules
$base = pq("item[identifier='LearningModules'] > item");
foreach ($base as $item) {
	$moduletitle = pq($item)->children("title")->html();
	$pgs = pq($item)->children("item");
	foreach ($pgs as $pg) {
		$title = addslashes(pq($pg)->children("title")->html());
		$iref = pq($pg)->attr("identifierref");
		if ($reftype[$iref]=="webcontent") { //a wiki page
			$filename = $reflist[$iref];
			//something weird with dashes in wiki names
			$filename = preg_replace('/(\d+)s(\d+)/','$1-$2',$filename);
			$html = $zip->getFromName($filename);
			
			//do search and replace logic here
			$html = str_replace('</body>','<hr/>This page was altered</body>', $html);
			
			//save back in zip
			$zip->addFromString($filename, $html);
			$cnt++;
		}
	}
}
$zip->close();
echo "$cnt items modified";
?>
