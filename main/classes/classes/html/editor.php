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
		
		$class_sfx	= '';
		if ($required ) {
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
		
		$alias		= preg_replace('/[^a-zA-Z0-9_]/', '', $name);
		
		$class_sfx	= '';
		if ($required ) {
			$class_sfx	= ' required';
		}
		
		$html	= "<textarea class=\"inputbox{$class_sfx}\" id=\"{$alias}\" name=\"{$name}\" rows=\"{$rows}\" cols=\"{$cols}\">{$value}</textarea>";
		
		ob_start();
?>
<script>
	CKEDITOR.replace('<?php echo $alias;?>');
</script>
<?php	
		// get html code
		$html	.= ob_get_clean();
		
		return $html;		
	}
	
	// tinymce
	function tinymce( $name, $value='', $required=false, $rows=10, $cols=50, $params=null )
	{
		$app	=& Factory::getApplication();
		$config	=& Factory::getConfig();
		
		$id		= preg_replace('/[^a-zA-Z0-9_]/', '', $name);
		
		// set script source path
		$baseURL	= $config->baseURL;
		$baseURL	= str_replace( @$config->admin_path, '', $baseURL );
		$app->set('js', $baseURL . 'assets/editors/tinymce/tiny_mce.js');
		
		$show_format_1	= isset($params['buttons1_format']) && $params['buttons1_format'] ? true : false;
		$show_buttons_2	= isset($params['buttons2']) && $params['buttons2'] ? true : false;
		
		$show_docbase	= true;
		if (isset($params['no_document_base_url']) && !$params['no_document_base_url']) {
			$show_docbase	= false;
		}
		$allow_script		= false;
		if (isset($params['allow_script']) && $params['allow_script']) {
			$allow_script	= true;
		}
		
		ob_start();
?>
<script>
tinyMCE.init({
	// General options
	mode : 'exact',
	elements : "<?php echo $id;?>",
	theme : 'advanced',
	skin : 'o2k7',
	skin_variant : 'silver',
	relative_urls : <?php echo (isset($params['relative_urls']) && $params['relative_urls'] ? 'true' : 'false');?>,
	remove_script_host : false,
	invalid_elements: '<?php echo ($allow_script?'':'script');?>',
	plugins : "safari,table,style,advimage,paste,advlink,inlinepopups,media,directionality",
	<?php if ($show_docbase):?>
	document_base_url : "<?php echo $baseURL;?>",
	<?php endif;?>
	
	// Theme options
	content_css : "<?php echo $baseURL;?>templates/<?php echo $config->__raw->template;?>/assets/css/editor.css",
	theme_advanced_buttons1 : "<?php echo ($show_format_1 ? 'formatselect,' : '');?>bold,italic,underline,|,justifyleft,justifycenter,justifyright,justifyfull,|,bullist,numlist,|,outdent,indent,|undo,redo,|,pastetext,pasteword,|,link,unlink,image,|,code",
	theme_advanced_buttons2 : "<?php echo ($show_buttons_2 ? 'tablecontrols,|,hr,removeformat' : '');?>",
	theme_advanced_buttons3 : "<?php echo ($show_buttons_2 ? 'formatselect,fontselect,fontsizeselect' : '');?>",
	theme_advanced_toolbar_location : 'top',
	theme_advanced_toolbar_align : 'left',
	theme_advanced_statusbar_location : 'none'
	<?php if (isset($params['directionality'])):?>
	,directionality: "<?php echo $params['directionality'];?>"
	<?php endif;?>
	<?php if (isset($params['width'])):?>
	,width: <?php echo $params['width'];?>
	<?php endif;?>
	<?php if (isset($params['height'])):?>
	,height: <?php echo $params['height'];?>
	<?php endif;?>
	
	// File manager (MFM)
	,file_browser_callback : _loadMFM
});
</script>
<?php
		// get JS code
		$js_script	= ob_get_clean();
		
		// build params for this plugin
		$fm_params	= array(
			'base_path' => BASE_PATH,
			'root_path' => $baseURL,
			'editor_path' => $baseURL . 'assets/editors/tinymce',
			'file_root' => 'uploads'
		);
		$fm_params	= base64_encode(serialize($fm_params));
		$app->set('js', array('
<script>
function _loadMFM(field_name, url, type, win) {
	tinyMCE.activeEditor.windowManager.open({
		file : "'.$baseURL.'assets/editors/editor_plugins/mfm.php?field=" + field_name + "&url=" + url + "&params='.$fm_params.'",
		title : \'File Manager\',
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
		', $js_script), false, true);

		$attributes	= array(
			'mce_editable="true"'
		);
		
		// attach script tag
		if (isset($params['attach_script']) && $params['attach_script'] ) {
			$script_tag	= str_replace( array('<script>','</script>'), array('',''), $js_script );
			$attributes[]	= '_script_tag="' . base64_encode($script_tag) . '"';
		}
		
		// load generic
		return HTMLHelper::_('editor.generic', $name, $value, $required, $rows, $cols, implode(' ', $attributes));
	}
}