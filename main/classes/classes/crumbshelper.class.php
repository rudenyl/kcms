<?php
/*
* $Id: crumbs.class.php
* Crumb display base class
* @author: Dhens <rudenyl@gmail.com>
*/

defined('_PRIVATE') or die('Direct access not allowed');

final class CrumbsHelper
{
	/**
	Class constructor
		@public
	**/
	function __construct()
	{
	}
	
	/**
	Get crumb tree
		@public
	**/
	static public function getCrumbs()
	{
		$app	=& Factory::getApplication();
		
		$items	= $app->get('crumbs');
		
		$crumbs	= array();
		if( $items ) {
			foreach($items as $item) {
				if( is_array($item) ) {
					$html	= "<a href=\"{link}\">{text}</a>\n";
					
					if( isset($item['link']) ) {
						$html	= str_replace('{link}', empty($item['link']) ? '#' : $item['link'], $html);
					}
					if( isset($item['text']) ) {
						$html	= str_replace('{text}', empty($item['text']) ? '?' : $item['text'], $html);
					}
				}
				else {
					$html	= "<span>{$item}</span>\n";
				}
				
				$crumbs[]	= $html;
			}
		}
		
		return $crumbs;
	}
}
