<?php

/**
 * @package     Articles Calendar
 *
 * @copyright   Copyright (C) 2017 Joomcar extensions. All rights reserved.
 * @license     GNU General Public License version 2 or later.
 */

defined('_JEXEC') or die;

$model->module_params->show_info = 1;

$image_type = ''; //intro || text
$images = json_decode($item->images);			
$ImageIntro = strlen($images->image_intro) > 1 ? 1 : 0;
preg_match('/(<img[^>]+>)/i', $item->introtext, $matches);
$ImageInText = count($matches);

if (JPluginHelper::isEnabled('system', 'imagestab')) {
	$db = JFactory::getDBO();
	$db->setQuery("SELECT COUNT(*) FROM #__content_images_data WHERE article_id = {$item->id}");
	$res = $db->loadResult();
	$ImagesTab = (int)$res;
}

if ($image_type == "intro" || $ImagesTab) {
	$item->introtext = trim(strip_tags($item->introtext, '<h2><h3>'));
}
if($model->module_params->text_limit) {
	preg_match('/(<img[^>]+>)/i', $item->introtext, $images_text);	
	$item->introtext = trim(strip_tags($item->introtext, '<h2><h3>'));
	$item->introtext = mb_strimwidth($item->introtext, 0, $model->module_params->text_limit, '...', 'utf-8');
	if(count($images_text) && 
		($image_type == "text" || ($image_type == "" && !$ImageIntro))
	) {
		if(strpos($images_text[0], '://') === false) {
			$parts = explode('src="', $images_text[0]);
			$images_text[0] = $parts[0] . 'src="' . JURI::root() . $parts[1];
		}
		$item->introtext = $images_text[0] . $item->introtext;
	}
}
$model->execPlugins($item);

?>

<div class="item<?php echo $item->featured ? ' featured' : ''; ?> <?php if($columns > 1 && ($items_counter % $columns == 0)) { echo 'unmarged'; } ?> <?php if($columns > 1) { echo 'span' . 12 / $columns; } ?>" itemprop="blogPost" itemscope itemtype="https://schema.org/BlogPosting">
	<h3 itemprop="name" class="item-title">
		<a href="<?php echo JRoute::_(ContentHelperRoute::getArticleRoute($item->slug, $item->catid, $item->language)); ?>" itemprop="url">
			<?php echo $item->title; ?>
		</a>
	</h3>
	<?php echo $item->event->afterDisplayTitle; ?>
	<?php echo $item->event->beforeDisplayContent; ?>

	<?php if ($ImageIntro && !$ImagesTab && ($image_type == "intro" || $image_type == "")) { ?>
	<div class="item-image">
		<a href="<?php echo JRoute::_(ContentHelperRoute::getArticleRoute($item->slug, $item->catid, $item->language)); ?>">
			<img src="<?php echo JURI::root() . htmlspecialchars($images->image_intro, ENT_COMPAT, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($images->image_intro_alt, ENT_COMPAT, 'UTF-8'); ?>" itemprop="thumbnailUrl"/>
		</a>
	</div>
	<?php } ?>
	
	<?php 
	$image_empty = $model->module_params->image_empty;
	if(((!$ImageIntro && $image_type == "intro") || (!$ImageInText && $image_type == "text") || (!$ImageIntro && !$ImageInText && $image_type == "")) && $image_empty != "" && $image_empty != "-1" && !$ImagesTab) { ?>
	<div class="item-image image-empty">
		<a href="<?php echo JRoute::_(ContentHelperRoute::getArticleRoute($item->slug, $item->catid, $item->language)); ?>">
			<img src="<?php echo JURI::root(); ?>images/<?php echo $image_empty; ?>" itemprop="thumbnailUrl"/>
		</a>
	</div>
	<?php } ?>
	
	<?php if($model->module_params->show_introtext) { ?>
	<div class="item-body">
		<div class="introtext">
			<?php echo $item->introtext; ?>
		</div>
		<div style="clear: both;"></div>
	</div>
	<?php } ?>
	
	<?php if($model->module_params->show_readmore) { ?>
	<div class="item-readmore">
		<a class="btn btn-secondary" href="<?php echo JRoute::_(ContentHelperRoute::getArticleRoute($item->slug, $item->catid, $item->language)); ?>"><?php echo JText::_('MOD_AGS_ITEM_READMORE'); ?></a>
	</div>
	<?php } ?>
	
	<?php if($model->module_params->show_info) { ?>
	<div class="item-info">
		<ul>
			<li class="category-name hasTooltip" title="" data-original-title="Category">
				<i class="icon icon-folder"></i>
				<a href="<?php echo JRoute::_(ContentHelperRoute::getCategoryRoute($item->catid, $item->language)); ?>"><span itemprop="genre"><?php echo $model->getCategoryById($item->catid)->title; ?></span></a>				
			</li>
			<?php
			if($item->tags != "") {
				$item->tags = JFactory::getDBO()->setQuery("SELECT * FROM #__tags WHERE id IN ({$item->tags})")->loadObjectList();
				$item->tagLayout = new JLayoutFile('joomla.content.tags');
				$articleTags = $item->tagLayout->render($item->tags);
			?>
			<li class="tags hasTooltip" title="" data-original-title="Tags">
				<i class="icon icon-tags"></i>
				<div style="display: inline-block;">
				<?php echo $articleTags; ?>
				</div>
			</li>
			<?php } ?>
			<li class="created">
				<i class="icon icon-clock"></i>
				<time datetime="<?php echo $item->created; ?>" itemprop="dateCreated">
					<?php 
						$day = DateTime::createFromFormat("Y-m-d H:i:s", $item->created);
						$locale = JFactory::getLanguage()->getLocale();
						if(count($locale) > 1) {
							$locale = $locale[0];
						}
						$formatter = new IntlDateFormatter($locale, IntlDateFormatter::SHORT, IntlDateFormatter::SHORT);
						$formatter->setPattern('d MMMM yyyy');
						$day = $formatter->format($day);					
						echo $day;
					?>
				</time>
			</li>
		</ul>
	</div>
	<?php } ?>
	
	<?php echo $item->event->afterDisplayContent; ?>
	<div style="clear: both;"></div>
</div>
<?php if(($items_counter + 1) % $columns == 0) { ?>
<div style="clear: both;"></div>
<?php } ?>