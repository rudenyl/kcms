<?php
defined('_PRIVATE') or die('Direct access not allowed');

class ThirdPartyClassPHPExcel
{
	function export( $data, $file_name='', $tmp_path='/tmp' )
	{
		// set timeout
		set_time_limit(10);
		// don't report errors
		error_reporting(0);

		require_once 'PHPExcel' .DS. 'PHPExcel.php';

		// Create new PHPExcel object
		$objPHPExcel	= new PHPExcel();

		$cols	= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';	// assumes 26-col
		foreach ($data as $i=>$item) {
			foreach ($item as $k=>$v) {
				$cell	= $cols{$k} . ($i + 1);
				
				$objPHPExcel->setActiveSheetIndex(0)->setCellValue($cell, $v);
			}
		}
		
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename="' .$file_name. '.xls"');
		header('Cache-Control: max-age=0');

		$objWriter	= PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
		$objWriter->save('php://output');
		exit;
	}
}