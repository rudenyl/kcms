<?php
defined('_PRIVATE') or die('Direct access not allowed');

class ThirdPartyClassPHP_WriteExcel
{
	function export( $data, $file_name='' )
	{
		// set timeout
		set_time_limit(10);
		// don't report errors
		error_reporting(0);

		require_once 'php_writeexcel' .DS. 'class.writeexcel_workbook.inc.php';
		require_once 'php_writeexcel' .DS. 'class.writeexcel_worksheet.inc.php';

		$_TMP_file	= tempnam('/tmp', time() . '.xls');
		$workbook	= new writeexcel_workbook($_TMP_file);
		$worksheet	= $workbook->addworksheet();

		# The general syntax is write($row, $column, $token). Note that row and
		# column are zero indexed

		foreach ($data as $i=>$item) {
			foreach ($item as $k=>$v) {
				$worksheet->write($i, $k, $v);
			}
		}

		// close write
		$workbook->close();
		
		// set filename
		$file_name	= $file_name ? $file_name : time();

		header( 'Content-Type: application/x-msexcel; name="example-simple.xls"' );
		header( 'Content-Disposition: inline; filename="' .$file_name. '.xls"' );

		$fp	= fopen($_TMP_file, "rb");
		fpassthru($fp);
		unlink($_TMP_file);
	}
}