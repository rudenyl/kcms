<?php
/* 
 * $Id: editor.php
 * HTMLHelper plugin class
 * @author: Dhens <rudenyl@gmail.com>
*/
class HelperClassEditor
{
	// default
	function generic( $name, $value='', $required=false, $rows=5, $cols=60, $attributes='' )
	{
		$alias		= preg_replace('/[^a-zA-Z0-9_]/', '', $name);
		if(!empty($attr)) {
			$attr	= ' '.$attr;
		}
		
		$class_sfx	= '';
		if( $required ) {
			$class_sfx	= ' required';
		}
		
		// generic
		$html	= "<textarea class=\"inputbox{$class_sfx}\" id=\"{$alias}\" name=\"{$name}\" rows=\"{$rows}\" cols=\"{$cols}\" {$attributes}>{$value}</textarea>";
		
		return $html;		
	}
	
	// ckeditor
	function ckeditor( $name, $value='', $required=false, $rows=10, $cols=50, $params=null )
	{
		$app	=& Factory::getApplication();
		$config	=& Factory::getConfig();
		
		// set script source path
		$baseURL	= $config->baseURL;
		$baseURL	= str_replace( @$config->admin_path, '', $baseURL );
		$app->set('js', $baseURL . 'assets/editors/ckeditor/ckeditor.js');
	
		$basePath	= BASE_PATH .DS. 'assets' .DS. 'editors' .DS. 'ckeditor';
		
		$admin_path	= str_replace('/', '', @$config->admin_path);
		$basePath	= str_replace($admin_path, '', $basePath);
		
		include_once( $basePath .DS. 'ckeditor.php' );
		
		$CKEditor	= new CKEditor();
		$config		= array();
		$config['toolbar']	= array(
			array( 'Source', '-', 'Bold', 'Italic', 'Underline', 'Strike' ),
				array( 'Image', 'Link', 'Unlink', 'Anchor' )
		);
		$config['width']	= $width ? $width : 500;
		$config['height']	= $height ? $height : 400;
		$events['instanceReady'] = 'function(ev) {
		}';
		if( $required ) {
			$CKEditor->textareaAttributes = array("class" => "required");
		}
		
		ob_start();
		$CKEditor->editor($name, $value, $config, $events);
		$html	= ob_get_clean();
		
		return $html;		
	}
	
	// tinymce
	function tinymce( $name, $value='', $required=false, $rows=10, $cols=50, $params=null )
	{
		$app	=& Factory::getApplication();
		$config	=& Factory::getConfig();
		
		// set script source path
		$baseURL	= $config->baseURL;
		$baseURL	= str_replace( @$config->admin_path, '', $baseURL );
		$app->set('js', $baseURL . 'assets/editors/tinymce/tiny_mce.js');
		
		$show_format_1	= isset($params['buttons1_format']) && $params['buttons1_format'] ? true : false;
		$show_buttons_2	= isset($params['buttons2']) && $params['buttons2'] ? true : false;
		
		ob_start();
?>
<script>
tinyMCE.init({
	// General options
	mode : "exact",
	elements : "<?php echo $name;?>",
	theme : "advanced",
	skin : "o2k7",
	skin_variant : "silver",
	relative_urls : false,
	remove_script_host : false,
	invalid_elements: "script",
	plugins : "safari,table,style,advimage,paste,advlink,inlinepopups,media,directionality",

	// Theme options
	content_css : "<?php echo $baseURL;?>templates/<?php echo $config->__raw->template;?>/assets/css/editor.css",
	theme_advanced_buttons1 : "<?php echo ($show_format_1 ? 'formatselect,' : '');?>bold,italic,underline,|,justifyleft,justifycenter,justifyright,justifyfull,|,bullist,numlist,|,outdent,indent,|undo,redo,|,pastetext,pasteword,|,link,unlink,image,|,code",
	theme_advanced_buttons2 : "<?php echo ($show_buttons_2 ? 'tablecontrols,|,hr,removeformat' : '');?>",
	theme_advanced_buttons3 : "<?php echo ($show_buttons_2 ? 'formatselect,fontselect,fontsizeselect' : '');?>",
	theme_advanced_toolbar_location : "top",
	theme_advanced_toolbar_align : "left",
	theme_advanced_statusbar_location : "none"
	<?php if( isset($params['width']) ):?>
	,width: <?php echo $params['width'];?>
	<?php endif;?>
	<?php if( isset($params['height']) ):?>
	,height: <?php echo $params['height'];?>
	<?php endif;?>
	
	// File manager (MFM)
	,file_browser_callback : _loadMFM
});
function _loadMFM(field_name, url, type, win) {
	tinyMCE.activeEditor.windowManager.open({
		<?php
		// build params for this plugin
		$fm_params	= array(
			'base_path' => BASE_PATH,
			'root_path' => $baseURL,
			'editor_path' => $baseURL . 'assets/editors/tinymce',
			'file_root' => 'uploads'
		);
		$fm_params	= base64_encode(serialize($fm_params));
		?>
		file : "<?php echo $baseURL;?>assets/editors/editor_plugins/mfm.php?field=" + field_name + "&url=" + url + "&params=<?php echo $fm_params;?>",
		title : 'File Manager',
		width : 640,
		height : 450,
		resizable : "no",
		inline : "yes",
		close_previous : "no"
	}, 
	{
		window : win,
		input : field_name
	});
	
	return false;
}
</script>
<?php
		$js_script	= ob_get_clean();
		
		$app->set('js', $js_script, false, true);
		
		$attributes	= array(
			'mce_editable="true"'
		);
		
		// attach script tag
		if( isset($params['attach_script']) && $params['attach_script'] ) {
			$script_tag	= str_replace( array('<script>','</script>'), array('',''), $js_script );
			$attributes[]	= '_script_tag="' . base64_encode($script_tag) . '"';
		}
		
		// load generic
		return HTMLHelper::_('editor.generic', $name, $value, $required, $rows, $cols, implode(' ', $attributes));
	}
}