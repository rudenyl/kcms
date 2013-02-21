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
		if( !is_dir($path) ) return false;
		
		$dir 	= dir($path);
		
		$folders	= array();
		while(false !== ($file = $dir->read())) {
			if( is_dir($path .DS. $file) ) {
				if( ($file == '.') || ($file == '..') ) continue;
				
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
	static public function getFolderFiles( $path='', $filter='.' )
	{
		if (!is_dir($path)) return false;
		
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
	function getFileMimeType( $filename ) 
	{
		if (function_exists('finfo_file')) {
			$finfo	= finfo_open(FILEINFO_MIME_TYPE);
			$type	= @finfo_file($finfo, $filename);
			finfo_close($finfo);
		} 
		else {
			//require_once 'upgradephp/ext/mime.php';
			//$type	= mime_content_type($filename);
			return false;
		}

		if (!$type || in_array($type, array('application/octet-stream', 'text/plain'))) {
			$secondOpinion	= exec('file -b --mime-type ' . escapeshellarg($filename), $foo, $returnCode);	// Yeah, truly second opinion (use with caution)
			
			if ($returnCode === 0 && $secondOpinion) {
				$type	= $secondOpinion;
			}
		}

		if (!$type || in_array($type, array('application/octet-stream', 'text/plain'))) {
			//require_once 'upgradephp/ext/mime.php';
			//$exifImageType	= exif_imagetype($filename);
			//if ($exifImageType !== false) {
			//	$type	= image_type_to_mime_type($exifImageType);
			//}
			return false;
		}

		return $type;
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