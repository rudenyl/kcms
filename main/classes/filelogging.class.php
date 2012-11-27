<?php
/*
* $Id: filelogging.class.php
*
* Logging using filesystem
*/

defined('_PRIVATE') or die('Direct access not allowed');

class FileLogging extends Logging_Abstract
{
	protected $_logfile		= '';
	protected $_unique_id	= '';
	protected $__logFormat	= "{date}: {log_message}\n";
	
	function __construct( $unique_id=null )
	{
		if ($unique_id) {
			$this->_unique_id	= $unique_id;
		}
	}
	
	function store( $msg, $date=null )
	{
		// open in read/write mode
		$logfile	= $this->_getLogFile();
		if (empty($logfile)) {
			return false;
		}

		if (!is_file($logfile)) {
			// create it
			touch($logfile);
			chmod($logfile, 0775);
		}
		
		if ( !($fp = fopen($logfile, "a")) ) {
			throw new Exception('Could not write to cache.');
		}
		else {
			// set writable
			@chmod($logfile, 0775);
		}
		
		flock($fp, LOCK_EX);	// set exclusive lock
		
		// build data
		if (strtotime($date) === false) {
			$date	= date('Y-m-d H:i:s');
		}
		$format_replacements	= array(
			'{date}' => $date,
			'{log_message}' => $msg
		);
		$data	= str_replace(array_keys($format_replacements), array_values($format_replacements), $this->__logFormat);
		if (fwrite($fp, $data) === false) {
			throw new Exception('Could not write to cache.');
		}

		fclose($fp);
		
		return true;
	}
	
	function getLogFile()
	{
		return $this->_getLogFile();
	}
	
	function removeLogFile() 
	{
		$logfile = $this->_getLogFile();
		
		return @unlink($logfile);
	}
 
	/**
	* Helper functions
	*/
	private function _getLogFile() 
	{
		if (empty($this->_logfile)) {
			$base_paths	= array(
				BASE_PATH .DS. '_cache' .DS. 'logs',
				BASE_PATH .DS. 'logs',
				BASE_PATH
			);
			
			foreach ($base_paths as $base_path) {
				// create log path
				$log_filename	= date('Ymd') .'.txt';
				$folder_name	= ($this->_unique_id ? '$'.md5($this->_unique_id) : false);
				$file_path		= $base_path .DS. ($folder_name ? $folder_name .DS : '') . $log_filename;
				
				if (is_file($file_path)) {
					return $file_path;
				}
				
				if (is_dir($base_path) && is_writable($base_path)) {
					if ($folder_name) {
						if (@mkdir($base_path .DS. $folder_name)) {
							$base_path	= $base_path .DS. $folder_name;
						}
					}
					
					return $base_path .DS. $log_filename;
				}
			} // foreach
		}
		
		return $this->_logfile;
	}
}
