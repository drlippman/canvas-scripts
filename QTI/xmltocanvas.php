<?php
//Copyright 2014 David Lippman, Lumen Learning
// GPL License

/*
look for <assessment>
	look for <title>  Pull contents for assessment title
	
look for <multiple_choice>
	pull <body> as question text
	look for <input> item, pull shuffle attribute
		look for <choice> items
			pull value attribute as identifier
			pull content as question text
	look for <part> item
		look for <response> items
			pull match attribute as identifier
			pull score attribute (=0 wrong, >0 right)
			pull <feedback> item as feedback text
*/


@set_time_limit(0);
ini_set("max_execution_time", "20000");
ini_set("memory_limit", "104857600");
$curdir = rtrim(dirname(__FILE__), '/\\');

error_reporting(E_ALL);


function DOMinnerHTML($element) { 
    $innerHTML = ""; 
    $children = $element->childNodes; 
    if (!$element->hasChildNodes()) {
    	    return $element->textContent;
    }
    	    
    foreach ($children as $child) 
    { 
        $tmp_dom = new DOMDocument(); 
        $tmp_dom->appendChild($tmp_dom->importNode($child, true)); 
        $innerHTML.=trim($tmp_dom->saveHTML()); 
    } 
    $innerHTML = preg_replace('/\s+/',' ',$innerHTML);
    return $innerHTML; 
} 

function stripsmartquotes($text) {
	$text = str_replace(
		array("\xe2\x80\x98", "\xe2\x80\x99", "\xe2\x80\x9c", "\xe2\x80\x9d", "\xe2\x80\x93", "\xe2\x80\x94", "\xe2\x80\xa6"),
		array("'", "'", '"', '"', '-', '--', '...'),
		$text);
	// Next, replace their Windows-1252 equivalents.
	$text = str_replace(
		array(chr(145), chr(146), chr(147), chr(148), chr(150), chr(151), chr(133)),
		array("'", "'", '"', '"', '-', '--', '...'),
		$text);
	return $text;
}


$handle = @opendir("$curdir/xmlfiles"); 
$arr = array();
$k = 0;
while (false !== ($file=readdir($handle))) {
	if ($file != "." && $file != ".." && !is_dir($file)) {
		if (strtolower(strrchr($file,"."))=='.xml') {
			$arr[] = $file;
	
		}
	}
}
closedir($handle);



foreach ($arr as $file) {

$dom = new DOMDocument();
$str = @file_get_contents("$curdir/xmlfiles/$file");

//$str = stripsmartquotes($str);

$dom->loadXML($str);

$chpid = str_replace('.xml','',$file);
$chp = DOMinnerHTML($dom->getElementsByTagName("title")->item(0));

$out = '<?xml version="1.0" encoding="UTF-8"?>
<questestinterop xmlns="http://www.imsglobal.org/xsd/ims_qtiasiv1p2" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.imsglobal.org/xsd/ims_qtiasiv1p2 http://www.imsglobal.org/xsd/ims_qtiasiv1p2p1.xsd">
  <assessment ident="'.$chpid.'" title="'.$chp.'">
    <qtimetadata>
      <qtimetadatafield>
        <fieldlabel>cc_maxattempts</fieldlabel>
        <fieldentry>1</fieldentry>
      </qtimetadatafield>
    </qtimetadata>
    <section ident="root_section">';


$mcs = $dom->getElementsByTagName("multiple_choice");
    
$j = 0;
foreach ($mcs as $q) {
    $out .= '<item ident="'.$chpid.'_Question'.$j.'" title="Question">
        <itemmetadata>
          <qtimetadata>
            <qtimetadatafield>
              <fieldlabel>question_type</fieldlabel>
              <fieldentry>multiple_choice_question</fieldentry>
            </qtimetadatafield>
            <qtimetadatafield>
              <fieldlabel>points_possible</fieldlabel>
              <fieldentry>1</fieldentry>
            </qtimetadatafield>
           ';
           
      $prompt = DOMinnerHTML($q->getElementsByTagName("body")->item(0));
      $shuffle = $q->getElementsByTagName("input")->item(0)->getAttribute("shuffle");
      if ($shuffle == 'Yes' || $shuffle == 'true') { $doshuffle = true;} else {$doshuffle = false;}
      
      $out.= '</qtimetadata>
        </itemmetadata>
        <presentation>
          <material>
            <mattext texttype="text/html">'.htmlspecialchars($prompt, ENT_XML1).'</mattext>
          </material>
          <response_lid ident="'.$chpid.'_response'.$j.'" rcardinality="Single">
          ';
          //NOTE:  Need to manually deal with images later
          if ($doshuffle) {
          	  $out .= '<render_choice shuffle="Yes">';
          } else {
          	  $out .= '<render_choice>';
          }
          
          $choices = $q->getElementsByTagName("choice");
         
          foreach ($choices as $choice) {
          	  $it = DOMinnerHTML($choice);
          	  $v = $choice->getAttribute("value");
            	    $out .= '<response_label ident="'.$chpid.'_item'.$j.'r'.$v.'">
                <material>
                  <mattext texttype="text/plain">'.htmlspecialchars($it, ENT_XML1).'</mattext>
                </material>
              </response_label>';
           }
           $out .= '
            </render_choice>
          </response_lid>
        </presentation>
        <resprocessing>
          <outcomes>
            <decvar maxvalue="100" minvalue="0" varname="SCORE" vartype="Decimal"/>
          </outcomes>
          ';
          
          $anss = $q->getElementsByTagName("response");
          $fbs = array();
          foreach ($anss as $ans) {
          	  $v = $ans->getAttribute("match");
          	  $sc = $ans->getAttribute("score");
          	  $txt = DOMinnerHTML($ans->getElementsByTagName("feedback")->item(0));
          	  $fbs[$v] = htmlspecialchars($txt, ENT_XML1);
          	  if ($sc==0) {
 
          	  } else {
          	  	  $correctv = $v;
          	  }
          }
          foreach ($fbs as $v=>$txt) {
          	  $out .= '<respcondition continue="Yes">
            <conditionvar>
              <varequal respident="'.$chpid.'_response'.$j.'">'.$chpid.'_item'.$j.'r'.$v.'</varequal>
            </conditionvar>
            <displayfeedback feedbacktype="Response" linkrefid="'.$chpid.'_item'.$j.'r'.$v.'_fb"/>
          </respcondition>
          	  ';
          }
          $out .= '<respcondition continue="No">
            <conditionvar>
              <varequal respident="'.$chpid.'_response'.$j.'">'.$chpid.'_item'.$j.'r'.$correctv.'</varequal>
            </conditionvar>
            <setvar action="Set" varname="SCORE">100</setvar>
          </respcondition>
        </resprocessing>';
        foreach ($fbs as $v=>$txt) {
        	 $out .= '<itemfeedback ident="'.$chpid.'_item'.$j.'r'.$v.'_fb">
          <flow_mat>
            <material>
              <mattext texttype="text/plain">'.$txt.'</mattext>
            </material>
          </flow_mat>
        </itemfeedback>';
        }
        $out .= '</item>';
        $j++;
}
$out .= '</section>
  </assessment>
</questestinterop>';	
file_put_contents($curdir.'/out/'.$chpid.'.xml',$out);
}

?>
