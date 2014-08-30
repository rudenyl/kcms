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
	static function _( $load_type, $params )
	{
		$func	= '_loadBy' . ucwords($load_type);
		
		// run
		return self::$func($params);
	}
	
	/**
	Get module metadata
		@param $module object
		@public
	**/
	static function getMetaData( $module, $load_params=true, $node_name='module' )
	{
		if (is_file($module)) {
			$manifest_file	= $module;
		}
		else {
			$manifest_file	= BASE_PATH .DS. 'modules' .DS. $module .DS. 'metadata.xml';
		}
		
		if (!is_file($manifest_file)) {
			return null;
		}
		
		// set node name
		if (empty($node_name)) {
			$node_name	= 'module';
		}
		
		// constants
		$context_required	= array(
			'module' => array(
				'description'
			),
			'field' => array(
				'meta' => array(
					'description'
				)
			)
		);
		
		// initialize xml object
		$xml	= new DOMDocument;
		$xml->load($manifest_file);
		
		// get root
		$root	= $xml->getElementsByTagName($node_name);
		if (empty($root->length)) {
			return null;
		}
		
		// get the first item
		$parent_node	= $root->item(0);
		
		// verify
		if (@$parent_node->nodeName <> $node_name) {
			return null;
		}
		
		// get meta
		$module_id		= $parent_node->getAttribute('name');
		if (empty($module_id)) {
			return null;
		}
		
		// build manifest data
		$manifest	= new stdclass;
		$manifest->name			= $module_id;
		$manifest->title		= $parent_node->getAttribute('title');
		$manifest->description	= $parent_node->getElementsByTagName('description');
		// run through
		foreach ($parent_node->childNodes as $cnode) {
			if (in_array($cnode->nodeName, $context_required['module'])) {
				$manifest->{$cnode->nodeName}	= $cnode->nodeValue;
			}
		}
		
		// get instance
		$instance				= $parent_node->getAttribute('instance');
		if (!empty($instance) && $instance === 'single') {
			$manifest->single_instance	= true;
		}
		
		// params
		if ($load_params) {
			self::__getManifestParamList($parent_node, $manifest->params);
			
			// get tabs
			$manifest->tabs		= array();
			$tabs				= $parent_node->getElementsByTagName('tab');
			foreach ($tabs as $tnode) {
				// init tab
				$tab		= new stdclass;
				$tab->id	= $tnode->getAttribute('id');
				$tab->title	= $tnode->getAttribute('title');
			
				// get tab contents
				self::__getManifestParamList($tnode, $tab);
				
				// add to list
				$manifest->tabs[]	= $tab;
			}
		}
		
		return $manifest;
	}
	
	/**
	Render module
		@param $module object
		@public
	**/
	static function render( $module, $custom=true )
	{
		$auth	=& Factory::getAuth();
		$config	=& Factory::getConfig();
		
		$html	= '';
		if (isset($module->access) && ($module->access && !$auth->loggedIn())) {
			// no access
			return $html;
		}
		
		if ($custom) {
			if ($module->id) {
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
					// translate
					self::__translate($module);
					
					// just return the data
					$html	= $module->data;
					
					// convert to SEF
					self::__getSEFURL($html);
				}
			}
		}
		else {
			$module_path	= BASE_PATH .DS. 'templates' .DS. $config->template .DS. 'modules' .DS. @$module->name .DS. @$module->name . '.php';
			
			// check admin
			$base_path	= str_replace(DS.'applications', '', PATH_APPLICATIONS);
			$base_path	= str_replace(BASE_PATH, '', $base_path);
			$base_path	= str_replace(DS, '/', $base_path);
			if ($base_path == $config->admin_path) {
				$module_path	= null;
			}
			
			if (!is_file($module_path)) {
				$module_path	= PATH_MODULES .DS. $module->name .DS. $module->name .'.php';
			}
			
			if (is_file($module_path)) {
				ob_start();
				// load module
				include( $module_path );
				$html	= ob_get_clean();
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
	static function getModuleCount( $position )
	{
		$db		= Factory::getDBO();
		
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
		@private
	**/
	static private function _loadByPosition( $position )
	{
		$db		= Factory::getDBO();
		
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
		@private
	**/
	static private function _loadById( $id )
	{
		$db		= Factory::getDBO();
		
		$sql	= "SELECT *"
		."\n FROM {TABLE_PREFIX}_modules"
		."\n WHERE `id` = " . $db->Quote($id)
		."\n AND `published` = '1'"
		."\n ORDER BY `ordering`"
		;
		$db->query($sql);
		
		return $db->fetch_object();
	}
	
	/**
	Convert URL fragments to SEF
		@private
	**/
	private function __getSEFURL( &$buffer )
	{
		$config	=& Factory::getConfig();
		$basepath	= ($config->baseURL);
		$basepath	= str_replace('/', '\/', $basepath);
		
		$regex	= "/\<a.+?href=[\"|']$basepath(.+?)[\"|']/i";
		
		$callback_func	= '
			if (!empty($matches[1])) {
				return \'<a href="\'. URL::_($matches[1]).\'"\';
			}
			
			return $matches[1];
		';
		$buffer	= preg_replace_callback($regex, create_function('$matches', $callback_func), 
			$buffer);
	}
	
	/** 
	Parse param list from manifest file
		@private
	**/
	private function __getManifestParamList( $topNode, &$param=null )
	{
		$node_meta	= array(
			'id',
			'title',
			'instance',
			'description'
		);
		
		// build param object
		if (!is_object($param)) {
			$param	= new stdclass;
		}
		$param->fields	= array();
		
		// get node meta
		foreach ($topNode->childNodes as $cnode) {
			$child_tag	= $cnode->nodeName;
			
			if (in_array($child_tag, $node_meta)) {
				$param->{$child_tag}	= (string)$cnode->nodeValue;
			}
		}
		
		// get params node
		$pnode	= $topNode->getElementsByTagName('params')->item(0);
		foreach ($pnode->childNodes as $cnode) {
			$child_tag	= $cnode->nodeName;
			
			if ('param' <> $child_tag) {
				continue;
			}
			
			$field			= array();
			$helper_data	= array();
			
			foreach ($cnode->attributes as $i=>$attrib) {
				if (is_null($attrib)) {
					continue;
				}
			
				if (strpos($attrib->name, 'helper') !== false) {
					$helper_data[$attrib->name]	= $attrib->value;
				}
				else {
					$field[$attrib->name]		= $attrib->value;
				}
				
				// get options if exists
				$options		= $cnode->getElementsByTagName('option');
				$option_data	= array();
				
				foreach ($options as $option) {
					$option_data[]	= $option->getAttribute('value') .'|'. $option->nodeValue;
				}
				
				if (!empty($option_data)) {
					$field['items']		= implode(',', $option_data);
				}
			}
			
			// add helper data
			if ($helper_data) {
				$field['helper']		= $helper_data;
			}
		
			// add to list
			$param->fields[]	= $field;
		}
	
		return true;
	}
	
	/**
	Get custom (html/text) module translations
		@private
	**/
	static private function __translate( &$item, $section='custom', $skip_enable_checking=false )
	{
		if (empty($item)) {
			return false;
		}
		
		$params 	= json_decode($item->params);
		$serialized	= ($params !== null);
		if (!$serialized) {
			$params	= new Parameter(@$item->params);
		}

		// get active lang
		$active_lang	= I18N::getCurrentLanguage();

		if (!$skip_enable_checking) {
			$translation_opt	= $serialized ? @$params->translation : @json_decode($params->get('translation'));

			// check enabled
			$lang_enabled		= isset($translation_opt->{"$active_lang"}) ? $translation_opt->{$active_lang}->enabled : false;
			if ($lang_enabled && isset($translation_opt->{$active_lang})) {
				foreach (get_object_vars($translation_opt->{$active_lang}) as $k=>$v) {
					if (strpos($k, 'params__') !== false && isset($item->params)) {
						$k	= str_replace('params__', '', $k);
						if ($serialized) {
							$params->{$k}	= $v;
						}
						else {
							$params->set($k, $v);
						}
					}
					else {
						$item->{$k}	= $v;
					}
				}

				/**/
				$item->params	= $serialized ? json_encode($params) : $params->toString();
			}
		}
		else {
			$lang_enabled	= true;
		}
		
		if ($lang_enabled) {
			$lang_map		= array();
			$active_lang	= empty($active_lang) ? I18N::getDefaultLanguage() : $active_lang;

			// get translation map
			$map_data	= I18N::getTranslationMapKeyValuePair(array(
					'section' => 'default.modules.' . $section,
					'id' => $item->id
				)
				, 'lang_code'
			);
			
			if (isset($map_data[$active_lang])) {
				$lang_map	= $map_data[$active_lang];
			}

			if ($lang_map) {
				// translate
				foreach ($lang_map as $k=>$v) {
					if (isset($item->{$k}) && !empty($v)) {
						$item->{$k}	= $v;
					}
				}
			}
		}
	}
}
