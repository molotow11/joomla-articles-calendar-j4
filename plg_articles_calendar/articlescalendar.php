<?php

/**
 * @package     Articles Calendar
 *
 * @copyright   Copyright (C) 2017 Joomcar extensions. All rights reserved.
 * @license     GNU General Public License version 2 or later.
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Plugin\CMSPlugin;

class plgSystemArticlesCalendar extends CMSPlugin {
	
	function onAfterDispatch() {
		$app = JFactory::getApplication();
		if($app->isClient('admin')) return;
		
		if(isset($_REQUEST['K2ContentBuilder'])) return;

		$init_parameter = JFactory::getApplication()->input->getInt('calendar');
		if($init_parameter) {
			error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_STRICT);
			
			$doc = JFactory::getDocument();
			
			$search_type = "com_content";
			$format = JFactory::getApplication()->input->getWord("search_mode", "html");
			switch($search_type) {
				case "com_content" :
					require_once(dirname(__FILE__)."/view/com_content/view.{$format}.php");
					$view = new ArticlesViewCalendar;
					$template = $view->display($search_type);
				break;
			}		
			$doc->setBuffer($template, "component");
		}
	}	
	
	function onAfterRoute() {
		//$init_parameter = JRequest::getVar('calendar');
		//if($init_parameter) {
		//	JRequest::setVar("option", "com_content");
		//	JRequest::setVar("view", "featured");
			
			//can be enabled for increase a speed
			//JRequest::setVar("option", "com_contact"); //false code for disable standard component output
		//}
	}
}