<?php
/*
* $Id: languagehelper.class.php, version 1.0,02062013
* Language Pack Helper base class
* @author: Dhens <rudenyl@gmail.com>
*/

defined('_PRIVATE') or die('Direct access not allowed');

final class I18N
{
	/**
	Get current language settings
		@param $use_session boolean
		@return array
		@public
	**/
	function getCurrentLanguage( $use_session=true )
	{
		return Request::getVar('language', false, $use_session ? 'SESSION' : null);
	}
	
	/**
	Get internationalization list
		@param $all boolean
		@return array
		@public
	**/
	function getList( $all=false )
	{
		static $list;
		
		if (!is_object($list)) {
			$db		=& Factory::getDBO();
			
			$sql	= "SELECT *"
			."\n FROM {TABLE_PREFIX}_languages"
			.($all ? '' : "\n WHERE `published` = '1'")
			;
			$db->query($sql);
			
			$packs	= $db->fetch_object_list();
			$list	= array();
			if ($packs) {
				foreach ($packs as $pack) {
					$list[$pack->lang_code]	= $pack;
				}
			}
		}
		
		return $list;
	}
	
	/**
	Text translation
		@param $text string
		@return array
		@public
	**/
	function translate( $text )
	{
		$translations	= self::_loadTranslation();
		
		if (in_array($text, array_keys($translations), true)) {
			$text	= @$translations[$text];
			
			// get args
			$args	= func_get_args();
			
			if ($args) {
				array_shift($args[1]);
				$text	= vsprintf($text, $args[1]);
			}
		}
		
		return $text;
	}
	
	##
	## Helper functions
	##
	/**
	Load language translations
		@return array
		@private
	**/
	private function _loadTranslation()
	{
		static $translations;
		
		if (!is_array($translations)) {
			$translations	= array();
			
			// get current lang code from current session
			$sess_lang_code	= self::getCurrentLanguage(true);
			
			if (empty($sess_lang_code)) {
				// get from storage
				$default_lang_o	= self::_getDefault();
				if ($default_lang_o) {
					$sess_lang_code	= $default_lang_o->lang_code;
				}
				else {
					$sess_lang_code	= 'en';
				}
			}
			
			// check if active. if not, use default
			$is_active	= self::_isActive($sess_lang_code);
			if (!$is_active) {
				$sess_lang_code	= 'en';
			}
			
			// get translation files
			$files	= Files::getFolderFiles(PATH_LANGUAGES .DS. $sess_lang_code, 'ini');
			if ($files && count($files)) {
				foreach ($files as $file) {
					$lang_file_path	= PATH_LANGUAGES .DS. $sess_lang_code .DS. $file;
					
					// load file
					$data	= Utility::parse_ini_file($lang_file_path, true);
					if (isset($data['translations'])) {
						$translation	= $data['translations'];
						if (isset($translation['node'])) {
							// get nodes
							$items	= array();
							foreach ($translation['node'] as $node) {
								$node_data	= $data[$node];
								
								// remove title
								unset($node_data['title']);
								
								$items	= array_merge($items, $node_data);
							}
							
							$translation	= $items;
						}
						
						// add to list
						$translations	= array_merge($translations, $translation);
					}
				}
			}
		}
		
		return $translations;
	}
	
	/**
	Get default stored language
		@return object
		@private
	**/
	private function _getDefault()
	{
		$db		=& Factory::getDBO();
		
		$sql	= "SELECT *"
		."\n FROM {TABLE_PREFIX}_languages"
		."\n WHERE `is_default` = '1'"
		;
		$db->query($sql);
		
		return $db->fetch_object();
	}
	
	/**
	Check if selected language is active
		@return boolean
		@private
	**/
	private function _isActive( $lang_id )
	{
		$db		=& Factory::getDBO();
		
		$sql	= "SELECT `published`"
		."\n FROM {TABLE_PREFIX}_languages"
		."\n WHERE `lang_code` = " . $db->Quote($lang_id)
		;
		$db->query($sql);
		
		return (int)$db->result();
	}
}
