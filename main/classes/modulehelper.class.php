<?php
/*
* $Id: modulehelper.class.php, version 0.1.15082012
* Module Helper base class
* @author: Dhens <rudenyl@gmail.com>
*/

defined('_PRIVATE') or die('Direct access not allowed');

final class ModuleHelper
{
	/**
	Initialization shorthand
		@param $load_type string
		@param $params mixed
		@public
	**/
	function _( $load_type, $params )
	{
		$func	= '_loadBy' . ucwords($load_type);
		
		// run
		return self::$func($params);
	}
	
	/**
	Render module
		@param $module object
		@public
	**/
	function render( $module )
	{
		$html	= '';
		
		if ($module->id) {
			$config	=& Factory::getConfig();
			
			// get layout from current template
			$tpl_path	= PATH_TEMPLATES .DS. $config->template .DS. 'module.php';
			if (is_file($tpl_path)) {
				// set params
				$params	= isset($module->params) ? $module->params : '';
				$params	= new Parameter($params);
				
				ob_start();
				require ($tpl_path);
				$html	= ob_get_clean();
			}
			else {
				// just return the data
				$html	= $module->data;
			}
		}
		
		return $html;
	}
	
	/**
	Get module count
		@param $position string
		@return int
		@public
	**/
	function getModuleCount( $position )
	{
		$db		=& Factory::getDBO();
		
		$sql	= "SELECT count(*)"
		."\n FROM {TABLE_PREFIX}_modules"
		."\n WHERE `position` = " . $db->Quote($position)
		."\n AND `published` = '1'"
		."\n ORDER BY `ordering`"
		;
		$db->query($sql);
		
		return (int)$db->result();
	}
	
	/**
	Load module by position
		@param $position string
		@private
	**/
	private function _loadByPosition( $position )
	{
		$db		=& Factory::getDBO();
		
		$sql	= "SELECT *"
		."\n FROM {TABLE_PREFIX}_modules"
		."\n WHERE `position` = " . $db->Quote($position)
		."\n AND `published` = '1'"
		."\n ORDER BY `ordering`"
		;
		$db->query($sql);
		
		return $db->fetch_object_list();
	}
	
	/**
	Load module by module id
		@param $id int
		@public
	**/
	private function _loadById( $id )
	{
		$db		=& Factory::getDBO();
		
		$sql	= "SELECT *"
		."\n FROM {TABLE_PREFIX}_modules"
		."\n WHERE `id` = " . $db->Quote($id)
		."\n AND `published` = '1'"
		."\n ORDER BY `ordering`"
		;
		$db->query($sql);
		
		return $db->fetch_object();
	}
}
