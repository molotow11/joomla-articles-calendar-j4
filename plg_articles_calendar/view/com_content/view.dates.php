<?php

/**
 * @package     Articles Calendar
 *
 * @copyright   Copyright (C) 2017 Joomcar extensions. All rights reserved.
 * @license     GNU General Public License version 2 or later.
 */

defined('_JEXEC') or die;

class ArticlesViewCalendar extends JViewCategory {
	function display($search_type = "com_content") {	
		require_once(JPATH_SITE . "/plugins/system/articlescalendar/models/com_content/model.php");
		
		list($year, $month) = explode("-", JFactory::getApplication()->input->getVar('date'));
		$date_field = JFactory::getApplication()->input->getVar('date_field');
		$date = $year . "-" . $month;
		
		$res = array();
		for($i = 1; $i < 32; $i++) {
			$day = $date . "-" . substr("0" . $i, -2);
			$_REQUEST[$date_field] = $day;
			$model = new ArticlesModelCalendar;
			
			$kday = $year . $month . $i;
			$res[$kday] = $model->total_items;
		}
		echo json_encode($res);
		
		unset($_REQUEST[$date_field]);
		die;
	}
}

?>