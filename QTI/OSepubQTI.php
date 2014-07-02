<?php
//David Lippman 2014 for Lumen Learning
//Takes a directory of HTML files from an OpenStax epub file,
//pulls out the multiple choice questions at the end and converts them into
//QTI files.

// GPL License

require("phpQuery-onefile.php");
$meta = array();

//Change this section
$dir = 'bio';
$filelevel = "section";  //or "chapter" or "question"

$meta['book'] = 'Concepts of Biology';
$meta['org'] = 'OpenStax';
$meta['license'] = 'Creative Commons Attribution 3.0 Unported License';
$meta['licenseurl'] = 'http://creativecommons.org/licenses/by/3.0/';
$titleprefix = 'OpenStax Concepts of Biology';


//end changes
$assessuniq = uniqid();

function startqti() {
	global $meta,$assessuniq;
	$c = '<?xml version="1.0" encoding="UTF-8"?>
<questestinterop xmlns="http://www.imsglobal.org/xsd/ims_qtiasiv1p2" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.imsglobal.org/xsd/ims_qtiasiv1p2 http://www.imsglobal.org/xsd/ims_qtiasiv1p2p1.xsd">
  <assessment ident="'.$assessuniq.'" title="'.$meta['title'].'">
    <qtimetadata>
      <qtimetadatafield>
        <fieldlabel>cc_maxattempts</fieldlabel>
        <fieldentry>1</fieldentry>
      </qtimetadatafield>';
    if (isset($meta['book'])) {
    	    $c .= ' <qtimetadatafield>
        <fieldlabel>qmd_publication</fieldlabel>
        <fieldentry>'.$meta['book'].'</fieldentry>
      </qtimetadatafield>';
     }
     if (isset($meta['org'])) {
    	    $c .= ' <qtimetadatafield>
        <fieldlabel>qmd_organization</fieldlabel>
        <fieldentry>'.$meta['org'].'</fieldentry>
      </qtimetadatafield>';
     } 
     if (isset($meta['author'])) {
    	    $c .= ' <qtimetadatafield>
        <fieldlabel>qmd_author</fieldlabel>
        <fieldentry>'.$meta['author'].'</fieldentry>
      </qtimetadatafield>';
     }
     if (isset($meta['chapter'])) {
    	    $c .= ' <qtimetadatafield>
        <fieldlabel>qmd_chapter</fieldlabel>
        <fieldentry>'.$meta['chapter'].'</fieldentry>
      </qtimetadatafield>';
     }
     if (isset($meta['chpn'])) {
    	    $c .= ' <qtimetadatafield>
        <fieldlabel>qmd_chapter_number</fieldlabel>
        <fieldentry>'.$meta['chpn'].'</fieldentry>
      </qtimetadatafield>';
     }
     if (isset($meta['license'])) {
    	    $c .= ' <qtimetadatafield>
        <fieldlabel>qmd_license</fieldlabel>
        <fieldentry>'.$meta['license'].'</fieldentry>
      </qtimetadatafield>';
     }
     if (isset($meta['licenseurl'])) {
    	    $c .= ' <qtimetadatafield>
        <fieldlabel>qmd_license_id</fieldlabel>
        <fieldentry>'.$meta['licenseurl'].'</fieldentry>
      </qtimetadatafield>';
     }
     
    $c .=' </qtimetadata>
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
	
	$html = file_get_contents($file);
	phpQuery::newDocumentHTML($html);
	$meta['chapter'] = pq("h1.title span.cnx-gentext-t")->text();
	$meta['chpn'] = pq("h1.title span.cnx-gentext-n")->text();
	
	if ($filelevel == "chapter") {
		$meta['title'] = $titleprefix." Chapter ".$meta['chpn'];
		$out = startqti();
	}
	
	//.multiple-choice is used in bio, .section-quiz in soc
	$quiz = pq(".cnx-eoc.multiple-choice, .cnx-eoc.section-quiz");
	$secs = pq($quiz)->find(".section");
	foreach ($secs as $s=>$sec) {
		$secn = 0;
		$secnum = "sec".$s;
		$sectitle = pq($sec)->find(".cnx-gentext-section.cnx-gentext-n")->text();
		
		if ($filelevel == "section") {
			$meta['chapter'] = rtrim(pq($sec)->find(".cnx-gentext-section.cnx-gentext-t")->text(), " *");
			$meta['chpn'] = $sectitle;
			$meta['title'] = $titleprefix." Section ".$sectitle;
			$out = startqti();
		}
		
		//it appears we don't need to be this specific
		//$mcs = pq($sec)->find(".exercise.section-quiz");
		$mcs = pq($sec)->find(".exercise");
		foreach ($mcs as $c=>$mc) {
			$title = $sectitle.' '. pq($mc)->find(".title:first")->text();
			if ($filelevel == "question") {
				$meta['title'] = $titleprefix." Section ".$sectitle.'#'.$c;
				$out = startqti();
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
			
			$out .= '<item ident="'.$assessuniq.'q'.$n.'" title="'.($title).'">
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
		    <mattext texttype="text/html">'.htmlentities(trim($prompt),ENT_XML1).'</mattext>
		  </material>
		  <response_lid ident="response1" rcardinality="Single">
		    <render_choice>';
		    foreach ($solntext as $k=>$it) {
			    $out .= '<response_label ident="'.$assessuniq.'q'.$n.'o'.$k.'">
			<material>
			  <mattext texttype="text/html">'.htmlentities(trim($it),ENT_XML1).'</mattext>
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
			    $out .= '<varequal respident="response1">'.$assessuniq.'q'.$n.'o'.$corrects[0].'</varequal>';
		    } else {
			    $out .= '<and>';
			    foreach ($solntext as $k=>$it) {
				    if (in_array($k,$corrects)) {
					    $out .= '<varequal respident="response1">'.$assessuniq.'q'.$n.'o'.$k.'</varequal>';
				    } else {
					    $out .= '<not><varequal respident="response1">'.$assessuniq.'q'.$n.'o'.$k.'</varequal></not>';
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
	      		$n++;
		}
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
