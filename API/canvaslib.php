<?php
/*
  Simple Canvas LMS API library
  
  Note: This library does NOT validate inputs to ensure they comply with
    the Canvas API.  
  
  Copyright 2014 David Lippman, Lumen Learning
  
    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

class CanvasLMS {
	private $token;
	private $domain;
	public function CanvasLMS($t, $d) {
		$this->token = $t;
		$this->domain = $d;
	}

	//These functions return a list of items as an associative array: id=>name
	public function getCourseList($max=-1) {
		return $this->getlist("/api/v1/courses", 'id', 'name', $max);
	}
	
	public function getAssignmentList($courseid,$max=-1) {
		return $this->getlist("/api/v1/courses/$courseid/assignments", 'id', 'name', $max);
	}
	
	public function getFileList($courseid,$max=-1) {
		return $this->getlist("/api/v1/courses/$courseid/files", 'id', 'display_name', $max);
	}
	
	public function getQuizList($courseid,$max=-1) {
		return $this->getlist("/api/v1/courses/$courseid/quizzes", 'id', 'title', $max);
	}
	
	public function getPageList($courseid,$max=-1) {
		return $this->getlist("/api/v1/courses/$courseid/pages", 'url', 'title', $max);
	}
	
	public function getDiscussionTopicList($courseid,$max=-1) {
		return $this->getlist("/api/v1/courses/$courseid/discussion_topics", 'id', 'title', $max);
	}
	
	public function getItemList($courseid, $type, $max=-1) {
		switch($type) {
			case 'assignments':
				return $this->getAssignmentList($courseid, $max);
				break;		
			case 'files':
				return $this->getFileList($courseid, $max);
				break;
			case 'quizzes':
				return $this->getQuizList($courseid, $max);
				break;
			case 'pages':
				return $this->getPageList($courseid, $max);
				break;
			case 'discuss':
				return $this->getDiscussionTopicList($courseid, $max);
				break;
			default:
				echo 'error in item type';
				exit;
		}
	}
	
	//These functions return the full list results of the API list call
	// as an associative array:  id=>dataObject
	public function getFullCourseList($max=-1) {
		return $this->getlist("/api/v1/courses", 'id', '', $max);
	}
	
	public function getFullAssignmentList($courseid,$max=-1) {
		return $this->getlist("/api/v1/courses/$courseid/assignments", 'id', '', $max);
	}
	
	public function getFullFileList($courseid,$max=-1) {
		return $this->getlist("/api/v1/courses/$courseid/files", 'id', '', $max);
	}
	
	public function getFullQuizList($courseid,$max=-1) {
		return $this->getlist("/api/v1/courses/$courseid/quizzes", 'id', '', $max);
	}
	
	public function getFullPageList($courseid,$max=-1) {
		return $this->getlist("/api/v1/courses/$courseid/pages", 'url', '', $max);
	}
	
	public function getFullDiscussionTopicList($courseid,$max=-1) {
		return $this->getlist("/api/v1/courses/$courseid/discussion_topics", 'id', '', $max);
	}
	
	//These functions return the detailed data on one specific item
	public function getCourseData($courseid, $item='') {
		return $this->getdata("/api/v1/courses/$courseid", $item);	
	}
	
	public function getAssignmentData($courseid, $assignmentid, $item='') {
		return $this->getdata("/api/v1/courses/$courseid/assignments/$assignmentid", $item);	
	}
	
	public function getFileData($fileid, $item='') {
		return $this->getdata("/api/v1/files/$fileid", $item);	
	}
	
	public function getQuizData($courseid, $quizid, $item='') {
		return $this->getdata("/api/v1/courses/$courseid/quizzes/$quizid", $item);	
	}
	
	public function getPageData($courseid, $pageid, $item='') {
		return $this->getdata("/api/v1/courses/$courseid/pages/".urlencode($pageid), $item);	
	}
	
	public function getDiscussionTopicData($courseid, $discid, $item='') {
		return $this->getdata("/api/v1/courses/$courseid/discussion_topics/$discid", $item);	
	}
	
	public function getItemData($courseid, $type, $typeid, $item='') {
		switch($type) {
			case 'assignments':
				return $this->getAssignmentData($courseid, $typeid, $item='');
				break;		
			case 'files':
				return $this->getFileData($courseid, $typeid, $item='');
				break;
			case 'quizzes':
				return $this->getQuizData($courseid, $typeid, $item='');
				break;
			case 'pages':
				return $this->getPageData($courseid, $typeid, $item='');
				break;
			case 'discuss':
				return $this->getDiscussionTopicData($courseid, $typeid, $item='');
				break;
			default:
				echo 'error in item type';
				exit;
		}
	}
	
	//These functions update an item.  The val array should be an associative
	// array of the form key=>value.  Consult the Canvas API for valid keys.
	// For items like Wiki Pages, use the keys that would be reported in the
	// item details, not the update parameters.  For example, use "body" for
	// the key, not "wiki_page[body]".
	public function updateAssignment($courseid,$assignmentid,$valarray) {
		$paramarray = array();
		foreach ($valarray as $p=>$v) {
			$paramarray[] = "assignment[$p]=".urlencode($v);
		}
		return $this->update("/api/v1/courses/$courseid/assignments/$assignmentid", implode('&', $paramarray));
	}
	
	public function updateFile($courseid,$fileid,$valarray) {
		$paramarray = array();
		foreach ($valarray as $p=>$v) {
			$paramarray[] = "$p=".urlencode($v);
		}
		return $this->update("/api/v1/files/$fileid", implode('&', $paramarray));
	}
	
	public function updateQuiz($courseid,$quizid,$valarray) {
		$paramarray = array();
		foreach ($valarray as $p=>$v) {
			$paramarray[] = "quiz[$p]=".urlencode($v);
		}
		
		return $this->update("/api/v1/courses/$courseid/quizzes/$quizid", implode('&', $paramarray));
	}
	
	public function updatePage($courseid,$pageid,$valarray) {
		$paramarray = array();
		foreach ($valarray as $p=>$v) {
			$paramarray[] = "wiki_page[$p]=".urlencode($v);
		}
		return $this->update("/api/v1/courses/$courseid/pages/".urlencode($pageid), implode('&', $paramarray));
	}
	
	public function updateDiscussionTopic($courseid,$discid,$valarray) {
		$paramarray = array();
		foreach ($valarray as $p=>$v) {
			$paramarray[] = "$p=".urlencode($v);
		}
		return $this->update("/api/v1/courses/$courseid/discussion_topics/$discid", implode('&', $paramarray));
	}
	
	public function updateItem($courseid, $type, $typeid, $valarray) {
		switch($type) {
			case 'assignments':
				return $this->updateAssignment($courseid, $typeid,$valarray);
				break;		
			case 'files':
				return $this->updateFile($courseid, $typeid, $valarray);
				break;
			case 'quizzes':
				return $this->updateQuiz($courseid, $typeid, $valarray);
				break;
			case 'pages':
				return $this->updatePage($courseid, $typeid, $valarray);
				break;
			case 'discuss':
				return $this->updateDiscussionTopic($courseid, $typeid, $valarray);
				break;
			default:
				echo 'error in item type';
				exit;
		}
	}
	
	
	
	
	
	//These are the internal functions that do the calls.
	private function getlist($base,$itemident,$nameident,$max=-1) {
		$pagecnt = 1;
		$itemcnt = 0;
		$itemassoc = array();
		while(1) {
			$f = @file_get_contents('https://'.$this->domain.$base.'?per_page=50&page='.$pagecnt.'&access_token='.$this->token);
			$pagecnt++;
			if (trim($f)=='[]' || $pagecnt>30 || $f===false) {
				break; //stop if we run out, or if something went wrong and $pagecnt is over 30
			} else {
				$itemlist = json_decode($f);
				for ($i=0;$i<count($itemlist);$i++) {
					if ($nameident != '') {
						$itemassoc[$itemlist[$i]->$itemident] = $itemlist[$i]->$nameident;
					} else {
						$itemassoc[$itemlist[$i]->$itemident] = $itemlist[$i];
					}
					$itemcnt++;
					if ($max!=-1 && $itemcnt>=$max) {
						break;
					}
				}
				if (count($itemlist)<50) { //not going to be another page
					break;
				}
			}
		}
		return $itemassoc;
	}
	
	private function getdata($base,$item='') {
		$page= json_decode(file_get_contents('https://'.$this->domain.$base.'?access_token='.$this->token));
		if ($item=='') {
			return $page;
		} else {
			return $page->$item;
		}
	}
	
	private function update($item,$vals) {
		$ch = curl_init('https://'.$this->domain.$item.'?access_token='.$this->token);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
		curl_setopt($ch, CURLOPT_POSTFIELDS, $vals);
		$response = curl_exec($ch);
		return ($response!==false);
	}
}
