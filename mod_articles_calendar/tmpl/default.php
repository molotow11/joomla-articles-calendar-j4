<?php
/**
 * @package     Joomcar Articles Calendar
 *
 * @copyright   Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
?>

<link type="text/css" href="https://code.jquery.com/ui/1.11.4/themes/<?php echo $params->get('uiTheme', 'ui-lightness'); ?>/jquery-ui.css" rel="stylesheet" />

<style>
	.articles-calendar * { border-radius: 0px !important; font-family: unset !important; }
	.articles-calendar .ui-datepicker, .articles-calendar .ui-datepicker-header { border-radius: 3px !important; }
	.articles-calendar .ui-datepicker-header { background-image: none !important; }
	.articles-calendar img.loading { width: 50px; display: block; margin: 0 auto; }
	.articles-calendar span.counter {
		display: block;
		width: 100%;
		margin: 0px !important;
		padding: 0px !important;
		text-align: center;
		font-size: 17px;
		font-weight: normal;
		float: none !important;
		border: none !important;
		background: #fff !important;
	}
	.articles-calendar a.active span.counter { outline: 1px solid #fad42e !important; background: #fbec88 !important; }
	.articles-calendar .ui-datepicker { width: 22em; max-width: 100%; }
	.articles-calendar a.ui-state-default, 
	.articles-calendar .ui-widget-content a.ui-state-default, 
	.articles-calendar .ui-widget-header a.ui-state-default { font-weight: normal !important; cursor: default; }
	.articles-calendar a.active,
	.articles-calendar .ui-datepicker-prev, .articles-calendar .ui-datepicker-next { cursor: pointer !important; }
	.articles-calendar .ui-datepicker-header .ui-state-hover { background: none !important; border: none !important; }
</style>

<div class="articles-calendar <?php echo $params->get("moduleclass_sfx"); ?>">
	<div id="datepicker<?php echo $module->id; ?>">
		<img class="loading" src="<?php echo JURI::root(true) ;?>/modules/mod_articles_calendar/tmpl/loading.png" />
	</div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/1.9.1/jquery.min.js" integrity="sha512-jGR1T3dQerLCSm/IGEGbndPwzszJBlKQ5Br9vuB0Pw2iyxOy+7AK+lJcCC8eaXyz/9du+bkCy4HXxByhxkHf+w==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.9.2/jquery-ui.min.js" integrity="sha512-cViKBZswH231Ui53apFnPzem4pvG8mlCDrSyZskDE9OK2gyUd/L08e1AC0wjJodXYZ1wmXEuNimN1d3MWG7jaQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.9.2/jquery.ui.datepicker.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.9.2/i18n/jquery.ui.datepicker-<?php echo $params->get("Language"); ?>.min.js"></script>
<script>
	jQuery(document).ready(function($) {
		<?php
			$date_initial = date("Y-n");
			$day = '';
			$date_default = '';
			for($i = 0; $i < count($_GET); $i++) {
				if(array_keys($_GET)[$i] == "calendar") {
					$day = $_GET[array_keys($_GET)[$i + 1]]; //next parameter after calendar
				}
			}

			if($day != '') {
				$date = DateTime::createFromFormat("Y-n", $day);
				$date_initial = $date->format("Y-n");
				$date_default = $day;
			}
		?>
		var year_month = "<?php echo $date_initial; ?>";
		var date_default = "<?php echo $date_default; ?>";
		var counters = [];
		getCalendarInitial(year_month);
		
		function getCalendarInitial(year_month) {
			jQuery.ajax({
				data: "calendar=1&date="+year_month+"&search_mode=dates&date_field=<?php echo $params->get("date_field", "created"); ?>&moduleId=<?php echo $module->id; ?>",
				type: "GET",
				dataType: 'json',
				url: window.location.origin + window.location.pathname,
				success: function(counters) {
					$("#datepicker<?php echo $module->id; ?>").datepicker({
						defaultDate: date_default,
						dateFormat: "yy-mm-dd",
						onChangeMonthYear: function(year, month, obj) {
							var year_month = year + "-" + month;
							$("#datepicker<?php echo $module->id; ?> img.loading").show();
							$("#datepicker<?php echo $module->id; ?> .ui-datepicker").hide();
							getNewMonthValues(year_month);
						}
					});
					$("#datepicker<?php echo $module->id; ?> .ui-datepicker").hide();
					setTimeout(function() {
						$("#datepicker<?php echo $module->id; ?> .ui-datepicker-calendar td").each(function() {
							var date = $(this).data("year") + "" + ($(this).data("month") + 1) + "" + $(this).find("a").text();
							if(date && typeof counters[date] !== 'undefined') {
								$(this).find("a").append("<span class='counter'>" + counters[date] + "</span>");
								if(counters[date] > 0) {
									$(this).find("a").addClass("active");
								}
							}
							$(this).unbind("click");
						});
						$("#datepicker<?php echo $module->id; ?> img.loading").hide();
						$("#datepicker<?php echo $module->id; ?> .ui-datepicker").show();
					}, 0);
				}
			});
		}
		
		function getNewMonthValues(year_month) {
			jQuery.ajax({
				data: "calendar=1&date="+year_month+"&search_mode=dates&date_field=<?php echo $params->get("date_field", "created"); ?>&moduleId=<?php echo $module->id; ?>",
				type: "GET",
				dataType: 'json',
				url: window.location.origin + window.location.pathname,
				success: function(counters) {
					$("#datepicker<?php echo $module->id; ?> img.loading").hide();
					$("#datepicker<?php echo $module->id; ?> .ui-datepicker").show();
					$("#datepicker<?php echo $module->id; ?> .ui-datepicker-calendar td").each(function() {
						var date = $(this).data("year") + "" + ($(this).data("month") + 1) + "" + $(this).find("a").text();
						if(date && typeof counters[date] !== 'undefined') {
							$(this).find("a").append("<span class='counter'>" + counters[date] + "</span>");
							if(counters[date] > 0) {
								$(this).find("a").addClass("active");
							}
						}
						$(this).unbind("click");
					});
				}
			});
		}
		
		$("body").on("click", "#datepicker<?php echo $module->id; ?> .ui-datepicker-calendar td", function(e) {
			var date = $(this).data("year") + "-" + ("0" + ($(this).data("month") + 1)).slice(-2) + "-" + ("0" + $(this).find("a").contents().get(0).nodeValue).slice(-2);
			if(date && parseInt($(this).find("span.counter").text()) > 0) {
				var url = window.location.origin + window.location.pathname + "?calendar=1&<?php echo $params->get("date_field", "created"); ?>=" + date + "&orderby=<?php echo $params->get("date_field", "created"); ?>&orderto=asc&moduleId=<?php echo $module->id; ?>&Itemid=<?php echo $params->get("Itemid", 123); ?>";
				window.location.href = url;
			}
			return false;
		});
	});
</script>