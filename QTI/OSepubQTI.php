<?php
//David Lippman 2014 for Lumen Learning
//Takes a directory of HTML files from an OpenStax epub file,
//pulls out the multiple choice questions at the end and converts them into
//QTI files.

// GPL License

require("phpQuery-onefile.php");

$dir = 'soc';

$files = glob($dir."/*.html");

$lets = array('A','B','C','D','E','F','G');
$zip = new ZipArchive;
$zip->open($dir.'/'.$dir.".zip",ZipArchive::OVERWRITE);
foreach ($files as $file) {
	$chp = explode('.',basename($file))[0];
	
	$out = '<?xml version="1.0" encoding="UTF-8"?>
<questestinterop xmlns="http://www.imsglobal.org/xsd/ims_qtiasiv1p2" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.imsglobal.org/xsd/ims_qtiasiv1p2 http://www.imsglobal.org/xsd/ims_qtiasiv1p2p1.xsd">
  <assessment ident="'.$chp.'" title="'.$chp.'">
    <qtimetadata>
      <qtimetadatafield>
        <fieldlabel>cc_maxattempts</fieldlabel>
        <fieldentry>1</fieldentry>
      </qtimetadatafield>
    </qtimetadata>
    <section ident="root_section">';
	
	
	$html = file_get_contents($file);
	phpQuery::newDocumentHTML($html);
	$mcs = pq(".exercise.section-quiz");
	foreach ($mcs as $c=>$mc) {
		$title = $chp .' '. pq($mc)->find(".title:first")->text();
		$prob = pq($mc)->find(".problem");
		$spans = pq($prob)->find("span");
		foreach ($spans as $span) {
			if (trim(pq($span)->html())=='') {
				pq($span)->remove();
			}
		}
		$li = pq($prob)->find(".listitem");
		$solntext = array();
		foreach ($li as $l) {
			$solntext[] = pq($l)->html();
		}
		pq($prob)->find(".orderedlist")->remove();
		$prompt = pq($prob)->html();
		
		$solnval = trim(pq($mc)->find(".solution")->find(".body")->text());
		$soln = array_search($solnval, $lets);
		$corrects = array($soln);
		
		$out .= '<item ident="'.$chp.'q'.$c.'" title="'.($title).'">
        <itemmetadata>
          <qtimetadata>
            <qtimetadatafield>
              <fieldlabel>question_type</fieldlabel>
              <fieldentry>'.((count($corrects)>1)?'multiple_answers_question':'multiple_choice_question').'</fieldentry>
            </qtimetadatafield>
            <qtimetadatafield>
              <fieldlabel>points_possible</fieldlabel>
              <fieldentry>1</fieldentry>
            </qtimetadatafield>
           ';
            $out.= '</qtimetadata>
        </itemmetadata>
        <presentation>
          <material>
            <mattext texttype="text/html">'.trim($prompt).'</mattext>
          </material>
          <response_lid ident="response1" rcardinality="Single">
            <render_choice>';
            foreach ($solntext as $k=>$it) {
            	    $out .= '<response_label ident="'.$chp.'q'.$c.'o'.$k.'">
                <material>
                  <mattext texttype="text/html">'.trim($it).'</mattext>
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
          <respcondition continue="No">
            <conditionvar>
            ';
            if (count($corrects)==1) {
            	    $out .= '<varequal respident="response1">'.$chp.'q'.$c.'o'.$corrects[0].'</varequal>';
            } else {
            	    $out .= '<and>';
            	    foreach ($solntext as $k=>$it) {
            	    	    if (in_array($k,$corrects)) {
            	    	    	    $out .= '<varequal respident="response1">'.$chp.'q'.$c.'o'.$k.'</varequal>';
            	    	    } else {
            	    	    	    $out .= '<not><varequal respident="response1">'.$chp.'q'.$c.'o'.$k.'</varequal></not>';
            	    	    }
            	    }
            	    $out .= '</and>';
            }
            
            
             $out .= '</conditionvar>
            <setvar action="Set" varname="SCORE">100</setvar>
          </respcondition>
        </resprocessing>
      </item>';
	}
	$out .= '</section>
  </assessment>
</questestinterop>';	
	
	$zip->addFromString($chp.".xml", $out);
}
$zip->close();
?>
