<?php

/*
  A general purpose, web-accessed search/replace/append tool 
  using Canvas LMS API library
  
  uses canvassearch.html and canvassearch.js
  
  Copyright 2014 David Lippman, Lumen Learning
  GPL License
*/

@set_time_limit(0); //make sure we don't timeout; this might take a while
ini_set("max_input_time", "2400");
ini_set("max_execution_time", "2400");

require("canvaslib.php");

$domain = $_POST['domain'];
$token = $_POST['token'];
$cid = $_POST['cid'];

$api = new CanvasLMS($token,$domain);

if ($_POST['do']=='getlist') {
	$itemlist = $api->getItemList($cid, $_POST['type']);
	
	if (count($itemlist)==0) {
		echo '<li>No items, or an error occurred.</li>';
	} else {
		foreach ($itemlist as $k=>$v) {
			echo '<li><input type="checkbox" name="items[]" class="itemsel" value="'.$k.'" checked="checked"/> '.$v.'</li>';	
		}
	}
} else if ($_POST['do']=='test') {
	$page = $api->getItemData($cid, $_POST['type'], $_POST['item']);
	
	$attr = $_POST['attr'];
        $val =  $page->$attr;
        if ($_POST['txttype']=='searchreplace') {
        	$val = str_replace($_POST['src'],$_POST['rep'],$val);
        } else if ($_POST['txttype']=='replace') {
        	$val = $_POST['rep'];
        } else if ($_POST['txttype']=='append') {
        	$val .= $_POST['app'];
        } else if ($_POST['txttype']=='regex') {
        	$val = preg_replace('/'.$_POST['src'].'/',$_POST['rep'], $val);
        }
        echo htmlentities($val);
	
} else if ($_POST['do']=='execute') {
	$attr = $_POST['attr'];
	$items = explode(':::',$_POST['items']);
	foreach ($items as $item) {
		if (isset($_POST['txttype']) && $_POST['txttype']!='replace') {
			$page = $api->getItemData($cid,$_POST['type'], $item);
			$val =  $page->$attr;
			if ($_POST['txttype']=='searchreplace') {
				$val = str_replace($_POST['src'],$_POST['rep'],$val);
			} else if ($_POST['txttype']=='append') {
				$val .= $_POST['app'];
			} else if ($_POST['txttype']=='regex') {
				$val = preg_replace('/'.$_POST['src'].'/',$_POST['rep'], $val);
			}
		} else if (isset($_POST['txttype']) && $_POST['txttype']=='replace') {
			$val = $_POST['rep'];
		} else {
			$val = $_POST['val'];
		}
		if ($val===null) {
			$val = '';
		}
		
		$response = $api->updateItem($cid, $_POST['type'], $item, array($attr=>$val));
		
	        if ($response===false) {
		   echo "fail on $item";
	       }
		
	}
	echo "Done";
	
} else {
	echo "Invalid";
}
?>
