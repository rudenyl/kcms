<?php
/*
* $Id: files.class.php, version 0.1.020711
* File utility base class
* @author: Dhens <rudenyl@gmail.com>
*/

defined('_PRIVATE') or die('Direct access not allowed');

final class Files
{
	/**
	Class constructor
		@public
	**/
	function __construct()
	{
	}
	
	/**
	Get directory folders
		@param $path string
		@public
	**/
	static public function getDirectoryFolders( $path='' )
	{
		if (!is_dir($path)) {
			return false;
		}
		
		$dir 	= dir($path);
		
		$folders	= array();
		while (false !== ($file = $dir->read())) {
			if (is_dir($path .DS. $file)) {
				if (($file == '.') || ($file == '..')) {
					continue;
				}
				
				$folders[]	= $file;
			}
		}
		
		$dir->close();
		
		return $folders;
	}
	
	/**
	Get directory files
		@param $path string
		@param $filter string
		@public
	**/
	static public function getFolderFiles( $path='', $filter='.', $recursive=false )
	{
		if (!is_dir($path)) {
			return false;
		}
		
		// get filters
		$filters	= explode(',', $filter);
		
		// open directory path
		$dir 		= dir($path);
		
		$files		= array();
		while (false !== ($file = $dir->read())) {
			if (is_file($path .DS. $file)) {
				// get file info
				$path_info	= pathinfo($path .DS. $file);
				if (($filter != '.') && !in_array($path_info['extension'], $filters)) {
					continue;
				}
				
				// add to list
				$files[]	= $file;
			}
			else if (is_dir($path .DS. $file) && $recursive) {
				if (($file == '.') || ($file == '..')) {
					continue;
				}
				
				$sub_files	= self::getFolderFiles($path .DS. $file, $filter, $recursive);
				if ($sub_files) {
					foreach ($sub_files as $sub_file) {
						$files[]	= $file .DS. $sub_file;
					}
				}
			}
		}
		
		// close directory
		$dir->close();
		
		return $files;
	}
	
	/** Get file mime type
		@ref: http://stackoverflow.com/questions/1232769/how-to-get-the-content-type-of-a-file-in-php (modified)
		@param $filename string
		@public
	**/
	function getFileMimeType( $filename, $use_extension=false ) 
	{
		if ($use_extension) {
			// get file info
			$finfo			= pathinfo($filename);
			$type			= $finfo['extension'];

			// get media types
			$media_types	= self::getMediaTypes();
			$type			= isset($media_types['.' . $type]) ? $media_types['.' . $type] : $type;
		}
		else {
			// get media types
			$media_types	= self::getMediaTypes('type');

			$type			= null;
			if (function_exists('finfo_file')) {
				$finfo	= finfo_open(FILEINFO_MIME_TYPE);
				$type	= @finfo_file($finfo, $filename);
				finfo_close($finfo);
			} 
			else if (function_exists('mime_content_type')) {
				$type	= mime_content_type($filename);
			}

			$type	= isset($media_types[$type]) ? $media_types[$type] : $type;
		}

		return $type;
	}	
	
	/** Get list of media types
		@list-ref: http://www.freeformatter.com/mime-types-list.html
		@param $key string
		@param $display string
		@public

		// display option
		name - type name
		type - media type (ie. text/html)
		ext - extension
		detail - more details
	**/
	function getMediaTypes( $key='ext', $display=null ) 
	{
		static $list;

		if (!$list) {
			$list	= array();
			
			$mime_type_fp	= PATH_CLASSES .DS. '3rdparty' .DS. 'data.mime_types.php';
			if (is_file($mime_type_fp)) {
				$display_options	= array('name','type','ext','detail');
				if (!in_array($key, $display_options)) {
					$key	= 'ext';
				}
				$display	= in_array($display, $display_options) ? $display : null;

				// load to array
				$fp_lines	= file($mime_type_fp);

				if ($fp_lines) {
					foreach ($fp_lines as $line) {
						@list($name, $type, $ext, $detail)	= explode('***', $line);

						$fkey		= "${$key}";
						
						if ($display) {
							$fdisplay		= "${$display}";
							$list[$fkey]	= $fdisplay;
						}
						else {
							// create data ref
							$list[$fkey]	= array(
								'type' => $type,
								'ext' => $ext,
								'name' => $name,
								'detail' => $detail
							);
						}
					} // foreach
				}
			}
		}

		return $list;
	}	
	
	/**
	Delete a folder an all existing files under
		@param $path string
		@public
	**/
	static public function deleteFolder( $path ) 
	{
		if (!is_dir($path)) {
			return;
		}
		
		if (substr($path, strlen($path) - 1, 1) != '/') {
			$path	.= '/';
		}
		
		$files	= glob($path . '*', GLOB_MARK);
		foreach ($files as $file) {
			if (is_dir($file)) {
				self::deleteFolder($file);
			} 
			else {
				@unlink($file);
			}
		}
		
		@rmdir($path);
	}
	
	/**
	Download a file
		@param $filename string
		@param $is_link boolean
		@param $inline boolean
		@public
	**/
	function download( $filename, $is_link=false, $inline=false )
	{
		// clear current buffered output
		while (@ob_end_clean());
		
		if (!is_file($filename)) {
			return false;
		}

		$base_name	= basename($filename);
		if ($is_link) {
			header('Location: '. substr($base_name, 6));
			return;
		}

		$file_size		= @filesize($filename);
		$date_modified	= date('r', filemtime($filename));
		$content_disp	= $inline ? 'inline' : 'attachment';
		$mime_type		= self::getFileMimeType($filename);

		// required for IE, otherwise Content-disposition is ignored
		if(ini_get('zlib.output_compression')) {
			ini_set('zlib.output_compression', 'Off');
		}
		// Fix IE7/8
		if(function_exists('apache_setenv')) {
			apache_setenv('no-gzip', '1');
		}

		header('Pragma: public');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Expires: 0');
		header('Content-Transfer-Encoding: binary');
		// for RFC2183 compliance
		header('Content-Disposition:' . $content_disp .';'
			.' filename="' . str_replace('"', '\"', $base_name) . '";'
			.' modification-date="' .$date_modified. '";'
			.' size=' .$file_size. ';'
		);
		header('Content-Type: ' . $mime_type);
		header('Content-Length: ' . $file_size);

		// no time limit (if not on safe mode)
		if (!ini_get('safe_mode')) {
			@set_time_limit(0);
		}

		self::readFileChunks($filename);
	}
	
	/**
	Read file in chunks
		@param $path string
		@param $numbytes int
		@private
	**/
    private function readFileChunks( $filename, &$numbytes=0 )
    {
		$chunk_size	= 1 * (1024*1024); // bytes per chunk
		$buffer		= '';

		$handle		= fopen($filename, 'rb');
		if ($handle === false) {
			return false;
		}
		
		while (!feof($handle)) {
			$buffer	= fread($handle, $chunk_size);
			echo $buffer;
			@ob_flush();
			flush();
			
			$numbytes	+= strlen($buffer);
		}
		
		$status	= fclose($handle);
		
		return $status;
	}
}