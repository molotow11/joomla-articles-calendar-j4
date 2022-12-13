<?php
/**
 * @package     Articles Calendar
 *
 * @copyright   Copyright (C) 2017 Joomcar extensions. All rights reserved.
 * @license     GNU General Public License version 2 or later.
 */
 
// no direct access
defined('_JEXEC') or die('Restricted access');

class ArticlesModelCalendar extends JModelList {
	var $input;
	var $module_id;
	var $module_helper;
	var $module_params;
	var $limit;
	var $limitstart;
	var $total_items;
	var $search_query;
	var $query;
	
	function __construct() {
		$this->input = JFactory::getApplication()->input;
		require_once(JPATH_SITE . "/modules/mod_articles_calendar/helper.php");
		$this->module_id = $this->input->get("moduleId", "", "int");
		$this->module_helper = new modArticlesCalendarHelper;
		$this->module_params = $this->module_helper->getModuleParams($this->module_id);
		if($this->module_params->savesearch) {
			$this->saveSearchSession();
		}
		$this->search_query = $this->getSearchQuery();
		$this->total_items = $this->getItems(true);
	}
	
	function getItems($total = false) {
		$db = JFactory::getDBO();

		if($total) {
			$query = "SELECT COUNT(DISTINCT i.id)";
		}
		else {
			$query = "SELECT i.*, cat.title as category";
			//select field ordering value
			preg_match('/^field([0-9]+)$/', $_GET['orderby'], $matches);
			if(count($matches)) {
				$query .= ", fv2.value as {$matches[0]}";
			}
		}
		
		$query .= " FROM #__content as i";
		$query .= " LEFT JOIN #__categories AS cat ON cat.id = i.catid";
				
		//left join field ordering value
		preg_match('/^field([0-9]+)$/', $_GET['orderby'], $matches);
		if(count($matches)) {
			$query .= " LEFT JOIN #__fields_values AS fv2 ON fv2.item_id = i.id AND fv2.field_id = {$matches[1]}";
		}
		
		$query .= " WHERE i.state = 1";
		
		//publish up/down
		$timezone = new DateTimeZone(JFactory::getConfig()->get('offset'));
		$time = new DateTime(date("Y-m-d H:i:s"), $timezone);
		$time = $time->format('Y-m-d H:i:s');
		$query .= " AND i.publish_up <= '{$time}' AND (i.publish_down > '{$time}' OR i.publish_down = '0000-00-00 00:00:00' OR ISNULL(i.publish_down))";
		
		switch($this->module_params->include_featured) {
			case "First" :
				$this->module_params->ordering_default = 'featured';
			break;
			case "Only" : 
				$query .= " AND i.featured = 1";
			break;
			case "No" :
				$query .= " AND i.featured = 0";
			break;
		}

		//category restriction
		$module_params_native = $this->module_helper->getModuleParams($this->module_id, true);
		$category_restriction = explode("\n", $module_params_native->get("restcat", ""));
		if($category_restriction[0] != "") {
			$query .= " AND i.catid IN (".implode(",", $category_restriction).")";
		}
		
		//language filter
		$language = JFactory::getLanguage();
		$defaultLang = $language->getDefault();
		$currentLang = $language->getTag();
		$query .= " AND i.language IN ('*', '{$currentLang}')";

		//general search query build
		$query .= $this->search_query;

		if(!$total) {
			$query .= " GROUP BY i.id";
		
			$orderby = $this->input->getVar("orderby", $this->module_params->ordering_default);
			$orderto = $this->input->getVar("orderto", "desc");
			switch($orderby) {
				case "title" :
					if($this->input->getVar("orderto") == "") {
						$orderto = "ASC";
						$this->input->setVar("orderto", "asc");
					}
					$query .= " ORDER BY i.title {$orderto}";
				break;
				case "alias" :
					$query .= " ORDER BY i.alias {$orderto}";
				break;				
				case "date" :
				case "created" :
					$query .= " ORDER BY i.created {$orderto}";
				break;				
				case "date_publishing" :
				case "publish_up" :
					$query .= " ORDER BY i.publish_up {$orderto}";
				break;
				case "category" :
					$query .= " ORDER BY category {$orderto}";
				break;
				case "popular" :
					$query .= " ORDER BY i.hits {$orderto}";
				break;
				case "featured" :
					$query .= " ORDER BY i.featured {$orderto}, i.created {$orderto}";
				break;
				case "rand" :
					$currentSession = JFactory::getSession();    
					$sessionNum = substr(preg_replace('/[^0-9]/i','',$currentSession->getId()),2,3); 
					$query .= " ORDER BY RAND({$sessionNum})";
				break;
				case "id" :
				default :
					//order by field value
					preg_match('/^field([0-9]+)$/', $_GET['orderby'], $matches);
					if(count($matches)) {
						$query .= " ORDER BY fv2.value {$orderto}";
					}
					else {
						$query .= " ORDER BY i.id {$orderto}";
					}
			}
		}

		if(isset($_GET['debug'])) {
			echo "<br /><hr /><br />";
			var_dump($_REQUEST);
			echo "<br /><hr /><br />";
			echo $query;
			$db->setQuery($query, $this->limitstart, $this->limit);
			var_dump($db->loadObjectList());
			die;
		}

		if($total) {
			$db->setQuery($query);	
			return $db->loadResult();
		}
		else {
			$this->limitstart = $this->input->get("page-start", 0, "int");			
			$db->setQuery($query, $this->limitstart, $this->limit);	
			$this->query = $query;
			return $db->loadObjectList();
		}
	}
	
	function getSearchQuery() {
		$timezone = new DateTimeZone(JFactory::getConfig()->get('offset'));
		$query = "";

		//keyword
		if($this->input->getWord("keyword")) {
			$keyword = $this->input->getVar("keyword");
			if($_GET['match'] == 'any') {
				$query .= " AND (";
				foreach(explode(" ", $keyword) as $k=>$word) {
					$query .= $k > 0 ? " OR " : "";
					$query .= "i.title LIKE '%{$word}%'";
				}
				$query .= ")";
			}
			else {
				$query .= " AND (i.title LIKE '%{$keyword}%'";
					$query .= "  OR i.introtext LIKE '%{$keyword}%'";
					//commented for prevent slow loading with big databases
					//$query .= "OR GROUP_CONCAT(fv.value SEPARATOR ', ') LIKE '%{$keyword}%'";
				$query .= ")";
			}
		}		
		
		//category
		if($this->input->getVar("category")) {
			$categories = $this->input->getVar("category");
			if($categories[0] != "") {
				if($this->module_params->restsub) {
					foreach($categories as $category) {
						$subs = (array)$this->module_helper->getSubCategories($category);
						$categories = array_merge($categories, $subs);
					}
				}
				$query .= " AND i.catid IN (".implode(",", $categories).")";
			}
		}

		//author
		if($this->input->getVar("author")) {
			$query .= " AND i.created_by IN (".implode(",", $this->input->getVar("author")).")";
		}
		
		//date
		if($this->input->getVar("date-from")) {
			$query .= " AND i.created >= '" . $this->input->getVar("date-from") . " 00:00:00'";
		}
		if($this->input->getVar("date-to")) {
			$query .= " AND i.created <= '" . $this->input->getVar("date-to") . " 23:59:59'";
		}

		if($_REQUEST['created']
			AND explode("-", $_REQUEST['created']) != ""
		) {
			$date_search = new DateTime($_REQUEST['created'], $timezone);
			$query_params = $date_search->format('Y-m-d');
			$query .= " AND i.created LIKE '%{$query_params}%'";
		}
		
		if($this->input->getVar("publish_up")) {
			$date_search = new DateTime($this->input->getVar("publish_up"), $timezone);
			$query_params = $date_search->format('Y-m-d');
			$query .= " AND i.created LIKE '%{$query_params}%'";
		}
		
		//fields search
		require_once(JPATH_BASE . '/modules/mod_articles_calendar/helper.php');
		$module_helper = new modArticlesCalendarHelper;
		foreach($_REQUEST as $param=>$value) {
			preg_match('/^field([0-9]+)$/', $param, $matches);
			$field_id = $matches[1];
			$query_params = $_REQUEST["field{$field_id}"];
			
			$sub_query = "SELECT DISTINCT item_id FROM #__fields_values WHERE 1";
			
			//text / date
			if(!is_array($query_params) && $query_params != "") {
				$sub_query .= " AND field_id = {$field_id}";
				$field_params = $module_helper->getCustomField($field_id);
				if($field_params->type == "calendar") {
					$query_params .= " 00:00:00";
					$date_search = new DateTime($query_params, $timezone);
					$query_params = date('Y-m-d', strtotime($date_search->format('Y-m-d H:i:s')) - $date_search->format('Z')); //substract timezone
					$sub_query .= " AND value LIKE '%{$query_params}%'";
				}
				else {
					$sub_query .= " AND value LIKE '%{$query_params}%'";
				}
			}
			if($query_params != "" && $query_params[0] != "") {
				$ids = JFactory::getDBO()->setQuery($sub_query)->loadColumn();
				if(count($ids)) {
					$query .= " AND i.id IN(" . implode(",", $ids) . ")";
				}
				else {
					$query .= " AND i.id = 0";
				}
			}
		}

		return $query;
	}
	
	function getPagination() {
		jimport('joomla.html.pagination');
		$pagination = new JPagination($this->total_items, $this->limitstart, $this->limit);
		foreach($_GET as $param=>$value) {
			if(in_array($param, Array("id", "start", "option", "view", "task"))) continue;
			if(is_array($value)) {
				foreach($value as $k=>$val) {
					$pagination->setAdditionalUrlParam($param . "[{$k}]", $val);
				}
			}
			else {
				$pagination->setAdditionalUrlParam($param, $value);
			}
		}
		return $pagination;
	}
	
	function execPlugins(&$item) {
		$app = JFactory::getApplication('site');
		$params = $app->getParams();
		$item->event   = new stdClass;

		// Old plugins: Ensure that text property is available
		$item->text = $item->introtext;
		
		JPluginHelper::importPlugin('content');
		\Joomla\CMS\Factory::getApplication()->triggerEvent('onContentPrepare', array ('com_content.category', &$item, &$item->params, 0));

		// Old plugins: Use processed text as introtext
		$item->introtext = $item->text;
		
		$item->params = new JRegistry($item->attribs);
		
		$results = \Joomla\CMS\Factory::getApplication()->triggerEvent('onContentBeforeDisplay', array('com_content.category', &$item, &$item->params, 0));
		$item->event->beforeDisplayContent = trim(implode("\n", $results));
		
		$results = \Joomla\CMS\Factory::getApplication()->triggerEvent('onContentAfterTitle', array('com_content.category', &$item, &$item->params, 0));
		$item->event->afterDisplayTitle = trim(implode("\n", $results));

		$results = \Joomla\CMS\Factory::getApplication()->triggerEvent('onContentAfterDisplay', array('com_content.category', &$item, &$item->params, 0));
		$item->event->afterDisplayContent = trim(implode("\n", $results));
	}
	
	function getAuthorById($id) {
		$db = JFactory::getDBO();
		$query = "SELECT * FROM #__users WHERE id = {$id}";
		$db->setQuery($query);
		return $db->loadObject();
	}
	
	function getCategoryById($id) {
		$db = JFactory::getDBO();
		$query = "SELECT * FROM #__categories WHERE id = {$id}";
		$db->setQuery($query);
		return $db->loadObject();
	}
	
	function saveSearchSession() {
		if(!$_GET['gsearch']) return;
		JFactory::getSession()->set("SaveSearchValues", $_GET);
	}	
}

?>