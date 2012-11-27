<?php
/* 
* $Id: htmlinput.php
*/
class HelperClassHtmlInput
{
	function textfield( $name, $value='', $attr='', $required=false )
	{
		$alias		= preg_replace('/[^a-zA-Z0-9_]/', '', $name);
		
		$class_sfx	= 'inputbox';
		if($required) {
			$class_sfx	.= ' required';
		}
		
		$html	= "<input type=\"text\" id=\"{$alias}\" name=\"{$name}\" class=\"{$class_sfx}\" value=\"{$value}\" {$attr} />";
		
		return $html;
	}
	
	function select($options, $name, $value=null, $attr='', $required=false)
	{
		$alias		= preg_replace('/[^a-zA-Z0-9_]/', '', $name);
		if(!empty($attr)) {
			$attr	= ' '.$attr;
		}
		
		$class_sfx	= 'inputbox';
		if($required) {
			$class_sfx	.= ' required';
		}
		
		$html	= "<select class=\"{$class_sfx}\" id=\"{$alias}\" name=\"{$name}\"{$attr}>\n";
		if (!empty($options)) {
			foreach($options as $option) {
				$option_text	= isset($option->text) ? $option->text : $option;
				$option_value	= isset($option->value) ? $option->value : $option;
				$option_attr	= isset($option->attr) ? $option->attr : '';
				
				if( is_array($value) ) {
					$is_selected	= in_array($option_value, $value);
				}
				else {
					$is_selected	= ($option_value == $value);
				}
				
				$selected	= ($is_selected ? ' SELECTED="SELECTED"' : '');
				$attributes	= isset($option_attr) && !empty($option_attr) ? ' '.$option_attr : '';
				
				// set defaults if value = null
				if ($value === null) {
					if( strpos($option_text, '[c]') !== false ) {
						$option_text	= str_replace('[c]', '', $option_text);
						$selected		= ' SELECTED="SELECTED"';
					}
					else if( strpos($option_value, '[c]') !== false ) {
						$option_value	= str_replace('[c]', '', $option_value);
						$selected		= ' SELECTED="SELECTED"';
					}
				}
				
				$html		.= "\n<option value=\"{$option_value}\"{$selected}{$attributes}>{$option_text}</option>\n";
			}
		}
		$html	.= "</select>\n";
		
		return $html;
	}
	
	function checkbox($options, $name, $value=null, $attr='', $required=false, $wrapper_template='', $size=0)
	{
		$alias		= preg_replace('/[^a-zA-Z0-9_]/', '', $name);
		if(!empty($attr)) {
			$attr	= ' '.$attr;
		}
		
		$class_sfx	= 'inputbox';
		if($required) {
			$class_sfx	.= ' required';
		}
		
		$html	= '';
		if (!empty($options)) {
			if ($size) {
				$html	.= '<table><tr><td style="border:0">';
			}
		
			foreach ($options as $i=>$option) {
				if( is_array($value) ) {
					$is_selected	= in_array($option->value, $value);
				}
				else {
					$is_selected	= ($option->value == $value);
				}
				
				$selected	= ($is_selected ? ' CHECKED="CHECKED"' : '');
				$attributes	= isset($option->attr) && !empty($option->attr) ? ' '.$option->attr : '';
				
				// set defaults if value = null
				if ($value === null) {
					if( strpos($option->text, '[c]') !== false ) {
						$option->text	= str_replace('[c]', '', $option->text);
						$selected		= ' CHECKED="CHECKED"';
					}
					else if( strpos($option->value, '[c]') !== false ) {
						$option->value	= str_replace('[c]', '', $option->value);
						$selected		= ' CHECKED="CHECKED"';
					}
				}
				
				$id			= strtolower($option->value . $name);
				$alias		= preg_replace('/[^a-zA-Z0-9_]/', '', $id);
				
				$layout	= '';
				if( empty($wrapper_template) ) {
					$layout	= "\n<input type=\"checkbox\" name=\"{$name}\" id=\"{$alias}\" value=\"{$option->value}\"{$selected}{$attributes} />\n";
					$layout	.= "<label for=\"{$alias}\" style=\"display:inline-block !important;\">{$option->text}</label>";
				}
				else {
					// pattern should have {label}{input}
					$input	= "\n<input type=\"checkbox\" name=\"{$name}\" id=\"{$alias}\" value=\"{$option->value}\"{$selected}{$attributes} />";
					$label	= "<label for=\"{$alias}\" style=\"display:inline-block !important;\">{$option->text}</label>";
					
					$layout	.= str_replace(array('{label}','{input}'), array($label, $input), $wrapper_template);
				}
				
				if ($size) {
					if ($i % $size == 0) {
						$html	.= '</td></tr><tr><td style="border:0">';
					}
					
					$html	.= $layout;
				}
				else {
					$html	.= $layout;
				}
			}
			
			if ($size) {
				$html	.= '</td></tr></table>';
			}
		}
		
		return $html;
	}
	
	function radio($options, $name, $value=null, $attr='', $required=false, $wrapper_template='', $size=0)
	{
		$alias		= preg_replace('/[^a-zA-Z0-9_]/', '', $name);
		if(!empty($attr)) {
			$attr	= ' '.$attr;
		}
		
		$class_sfx	= 'inputbox';
		if($required) {
			$class_sfx	.= ' required';
		}
		
		$html	= '';
		if (!empty($options)) {
			if ($size) {
				$html	.= '<table><tr><td style="border:0">';
			}
		
			foreach($options as $i=>$option) {
				if( strpos($option->text, '|') !== false ) {
					@list($option->value, $option->text)	= explode('|', $option->text);
				}
			
				$selected	= ($option->value == $value && !empty($value) ? ' CHECKED="CHECKED"' : '');
				$attributes	= isset($option->attr) && !empty($option->attr) ? ' '.$option->attr : '';
				
				// set defaults if value = null
				if ($value === null) {
					if( strpos($option->text, '[c]') !== false ) {
						$option->text	= str_replace('[c]', '', $option->text);
						$selected		= ' CHECKED="CHECKED"';
					}
					else if( strpos($option->value, '[c]') !== false ) {
						$option->value	= str_replace('[c]', '', $option->value);
						$selected		= ' CHECKED="CHECKED"';
					}
				}
				
				$id			= strtolower($option->value . $name) .'-'. $i;
				$alias		= preg_replace('/[^a-zA-Z0-9_]/', '', $id);
				
				$layout		= '';
				if( empty($wrapper_template) ) {
					$layout		.= "\n<input type=\"radio\" name=\"{$name}\" id=\"{$alias}\" value=\"{$option->value}\"{$selected}{$attributes} />\n";
					$layout		.= "<label for=\"{$alias}\" style=\"display:inline-block !important;\">{$option->text}</label>";
				}
				else {
					// pattern should have {label}{input}
					$label	= "<label for=\"{$alias}\" style=\"display:inline-block !important;\">{$option->text}</label>";
					$input	= "\n<input type=\"radio\" name=\"{$name}\" id=\"{$alias}\" value=\"{$option->value}\"{$selected}{$attributes} />";
					
					$layout	.= str_replace(array('{label}','{input}'), array($label, $input), $wrapper_template);
				}
				
				if ($size) {
					if ($i % $size == 0) {
						$html	.= '</td></tr><tr><td style="border:0">';
					}
					
					$html	.= $layout;
				}
				else {
					$html	.= $layout;
				}
			}
			
			if ($size) {
				$html	.= '</td></tr></table>';
			}
		}
		
		return $html;
	}
	
	function range($name, $start, $end, $increment=1, $default_value=null, $attr='', $required=false, $index=null, $decimals=1)
	{
		$alias		= preg_replace('/[^a-zA-Z0-9_]/', '', $name);
		if ($index !== null) {
			$alias	= $alias . "{$index}";
		}
		
		if (!empty($attr)) {
			$attr	= ' '.$attr;
		}
		
		$class_sfx	= 'inputbox';
		if($required) {
			$class_sfx	.= ' required';
		}
				
		$html	= "<select class=\"{$class_sfx}\" id=\"{$alias}\" name=\"{$name}\"{$attr}>\n";
		for ($i=$start; $i<=$end; $i+=$increment) {
			$selected	= ($i == $default_value ? ' SELECTED="SELECTED"' : '');
			
			$text	= $i;
			$value	= $i;
			
			if (strpos($increment, '.') !== false) {
				$text	= number_format($value, $decimals);
			}
			
			$html		.= "\n<option value=\"{$value}\"{$selected}>{$text}</option>\n";
		}
		$html	.= "</select>\n";
		
		return $html;
	}
	
	function imageselect($path_to_images, $name, $value='', $attr='', $required=false)
	{
		$html	= '';
	
		// load directory files
		if( file_exists($path_to_images) && is_dir($path_to_images) ) {
			// image type
			$mime_image	= array('jpg', 'jpeg', 'png', 'bmp', 'gif');
			
			$options	= array();
			$options[]	= HTMLHelper::_('htmlinput.option', '--Empty--', '');			

			$res_dir = dir($path_to_images);
			while(false !== ($file = $res_dir->read())) {
				if( !in_array($file, array('.','..') ) ) {	// do not include top folders
					if( !is_dir($file) )	{	// folder?
						$dot = strrpos($file, '.') + 1;
						$ext = substr($file, $dot);

						// add to image list if type=image
						if( in_array($ext, $mime_image) ) {
							$obj		= new stdclass();
							$obj->text	= $file;
							$obj->value	= $file;
							
							array_push($options, $obj);
						}
					}
				}
			}
			$res_dir->close();
			
			if( !empty($options) ) {
				return self::select($options, $name, $value, $attr, $required);
			}
		}
		
		return $html;
	}
	
	function select_yesno($name, $value='', $attr='', $required=false)
	{
		$options	= array();
		$options[]	= HTMLHelper::_('htmlinput.option', 'No', 0);
		$options[]	= HTMLHelper::_('htmlinput.option', 'Yes', 1);
		
		return self::select($options, $name, $value, $attr, $required);
	}
	
	function option($text, $value='', $attributes='', $disabled=false)
	{
		$obj			= new stdclass();
		$obj->text		= $text;
		$obj->value		= $value;
		$obj->attr		= $attributes . ($disabled ? ' DISABLED="DISABLED"' : '');
		
		return $obj;
	}
}
