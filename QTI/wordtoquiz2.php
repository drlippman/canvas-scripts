<?php
//Copyright 2014 David Lippman, Lumen Learning
// GPL License

if (!isset($_POST['text'])) {
	echo '<html><head><script src="https://ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js" type="text/javascript"></script>';
	echo '<script type="text/javascript" src="../javascript/general.js"></script>';
	echo '<script type="text/javascript" src="../editor/tiny_mce.js?v=111612"></script>';
	echo "\n";
	echo '<script type="text/javascript">';
	echo 'var coursetheme = "modern.css"; var fileBrowserCallBackFunc = null; imasroot = "/myopenmath"; initeditor("exact","text");';
	echo '</script>';
	echo '</head><body>';
	echo '<form method="post" class="nolimit">';
	echo '<p>Copy-paste in the quiz below.  To be processed correctly:</p><ul><li>Do not use auto-numbering</li><li>Number the questions in the form 1. question</li><li>Letter the options in form A) choice<li>Mark the correct answer with a * at the end</li></ul>';
	echo '<p>Title: <input type="text" size="40" name="title"/></p>';
	$ex = htmlentities('<p>1. An example question</p>
<p>A) A first option</p>
<p>B) The correct answer*</p>
<p>C) Another answer</p>');
	echo '<div class="editor"><textarea rows="30" cols="80" id="text" name="text">'.$ex.'</textarea></div>';
	echo '<input type="submit" value="Generate QTI"/>';
	echo '</form>';
	echo '</body></html>';
	exit;
}

function RandomString()
{
    $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randstring = '';
    for ($i = 0; $i < 10; $i++) {
        $randstring .= $characters[rand(0, strlen($characters))];
    }
    return $randstring;
}

$title = htmlentities($_POST['title'], ENT_XML1);
$html = $_POST['text'];

$chp = RandomString();

$out = '<?xml version="1.0" encoding="UTF-8"?>
<questestinterop xmlns="http://www.imsglobal.org/xsd/ims_qtiasiv1p2" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.imsglobal.org/xsd/ims_qtiasiv1p2 http://www.imsglobal.org/xsd/ims_qtiasiv1p2p1.xsd">
  <assessment ident="'.$chp.'" title="'.$title.'">
    <qtimetadata>
      <qtimetadatafield>
        <fieldlabel>cc_maxattempts</fieldlabel>
        <fieldentry>1</fieldentry>
      </qtimetadatafield>
    </qtimetadata>
    <section ident="root_section">';

//$html = str_replace("\r\n","\n",$html);
//$html = str_replace("\r","\n",$html);
$html = str_replace('&nbsp;','',$html);
$html = preg_replace('/<p[^>]*>\s*<\/p>/','',$html);
$html = preg_replace('/<p[^>]*>(.*?)<\/p>/','$1',$html);
$html = preg_Replace('/<br\s+\/>/','',$html);
$html = preg_replace('/<strong>\w\.\s*<\/strong>/','',$html);
$html = preg_replace('/^\d+\.\s*/m', ':Q:', $html);
$html = preg_replace('/^[A-G]+\)\s*/m', ':o:', $html);

$pts = explode(':Q:',$html);
array_shift($pts);

foreach ($pts as $c=>$pt) {
	if (trim($pt)=='') {continue;}
	$pp = explode(':o:',$pt);
	if (count($pp)<3) {continue;}
	$prompt = preg_replace('/^\d+(\.|\))\s+/','',trim($pp[0]));
	$prompt = htmlentities($prompt);
	$items = array();
	$corrects = array();
	for ($i=1;$i<count($pp);$i++) {
		$item = trim($pp[$i]);
		if ($item{strlen($item)-1}=='*') {
			$corrects[] = $i-1;
			$item = substr($item,0,-1);
		}
		$items[$i-1] = htmlentities($item);
	}
	$out .= '<item ident="'.$chp.'q'.$c.'" title="Question '.($c+1).'">
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
           /*
           <qtimetadatafield>
              <fieldlabel>assessment_question_identifierref</fieldlabel>
              <fieldentry>i749ccb344a90b1893fb7cef1aaad8133</fieldentry>
            </qtimetadatafield>
            */
        $out.= '</qtimetadata>
        </itemmetadata>
        <presentation>
          <material>
            <mattext texttype="text/html">'.$prompt.'</mattext>
          </material>
          <response_lid ident="response1" rcardinality="Single">
            <render_choice>';
            foreach ($items as $k=>$it) {
            	    $out .= '<response_label ident="'.$chp.'q'.$c.'o'.$k.'">
                <material>
                  <mattext texttype="text/html">'.$it.'</mattext>
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
            	    foreach ($items as $k=>$it) {
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


$zip = new ZipArchive;
$zip->open("../admin/import/".preg_replace('/[^\w]/','',$title).".zip",ZipArchive::OVERWRITE);
$zip->addFromString("quiz.xml", $out);
$zip->close();
header('Content-Type: application/zip');
header('Content-disposition: attachment; filename='.preg_replace('/[^\w]/','',$title).'.zip');
readfile("../admin/import/".preg_replace('/[^\w]/','',$title).".zip");
?>
