<?php
//David Lippman 2014 for Lumen Learning
//Takes a directory of HTML files from an OpenStax epub file,
//pulls out the multiple choice questions at the end and converts them into
//QTI files.

// GPL License

require("phpQuery-onefile.php");

$dir = 'bio';
$filelevel = "section";  //or "chapter" or "question"
$keywords = "OpenStax Concepts of Biology";

function startqti($ident,$keywd) {
	$c = '<?xml version="1.0" encoding="UTF-8"?>
<questestinterop xmlns="http://www.imsglobal.org/xsd/ims_qtiasiv1p2" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.imsglobal.org/xsd/ims_qtiasiv1p2 http://www.imsglobal.org/xsd/ims_qtiasiv1p2p1.xsd">
  <assessment ident="'.$ident.'" title="'.$keywd.'">
    <qtimetadata>
      <qtimetadatafield>
        <fieldlabel>cc_maxattempts</fieldlabel>
        <fieldentry>1</fieldentry>
      </qtimetadatafield>
      <qtimetadatafield>
        <fieldlabel>keyword</fieldlabel>
        <fieldentry>'.$keywd.'</fieldentry>
      </qtimetadatafield>
    </qtimetadata>
    <section ident="root_section">';
    return $c;
}

function endqti() {
	$c =  '</section>
  </assessment>
</questestinterop>';
	return $c;
}

$files = glob($dir."/*.html");

$lets = array('A','B','C','D','E','F','G');
$zip = new ZipArchive;
$zip->open($dir.'/'.$dir.".zip",ZipArchive::OVERWRITE);
$n = 0;
foreach ($files as $file) {
	$chp = explode('.',basename($file))[0];
	if ($filelevel == "chapter") {
		$out = startqti($chp,$keywords.' Chapter '.substr($chp,2));
	}

	$html = file_get_contents($file);
	phpQuery::newDocumentHTML($html);
	//.multiple-choice is used in bio, .section-quiz in soc
	$quiz = pq(".cnx-eoc.multiple-choice, .cnx-eoc.section-quiz");
	$secs = pq($quiz)->find(".section");
	foreach ($secs as $s=>$sec) {
		$secn = 0;
		$secnum = "sec".$s;
		$sectitle = pq($sec)->find(".cnx-gentext-section.cnx-gentext-n")->text();
		
		if ($filelevel == "section") {
			$out = startqti("Section".$sectitle,$keywords.' Section '.$sectitle);
		}
		
		//it appears we don't need to be this specific
		//$mcs = pq($sec)->find(".exercise.section-quiz");
		$mcs = pq($sec)->find(".exercise");
		foreach ($mcs as $c=>$mc) {
			$title = $sectitle.' '. pq($mc)->find(".title:first")->text();
			if ($filelevel == "question") {
				$out = startqti("Section".$sec.'#'.$c,$keywords.' Section '.$title);
			}
			
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
			
			$out .= '<item ident="'.$chp.$secnum.'q'.$c.'" title="'.($title).'">
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
			    $out .= '<response_label ident="'.$chp.$secnum.'q'.$c.'o'.$k.'">
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
			    $out .= '<varequal respident="response1">'.$chp.$secnum.'q'.$c.'o'.$corrects[0].'</varequal>';
		    } else {
			    $out .= '<and>';
			    foreach ($solntext as $k=>$it) {
				    if (in_array($k,$corrects)) {
					    $out .= '<varequal respident="response1">'.$chp.$secnum.'q'.$c.'o'.$k.'</varequal>';
				    } else {
					    $out .= '<not><varequal respident="response1">'.$chp.$secnum.'q'.$c.'o'.$k.'</varequal></not>';
				    }
			    }
			    $out .= '</and>';
		    }
		    
		    
		     $out .= '</conditionvar>
		    <setvar action="Set" varname="SCORE">100</setvar>
		  </respcondition>
		</resprocessing>
	      </item>';
	      		if ($filelevel == "question") {
	      			$out .= endqti();
	      			$zip->addFromString($sectitle.'.'.$c.".xml", $out);
	      		}
		}
		$n+=count($mcs);
		if ($filelevel == "section" && count($mcs)>0) {
			$out .= endqti();
			$zip->addFromString($sectitle.".xml", $out);
		}
	}
		
	if ($filelevel == "chapter") {
		$out .= endqti();
		$zip->addFromString($chp.".xml", $out);
	}
}
$zip->close();
echo $n;
?>
