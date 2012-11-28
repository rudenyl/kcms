<?php
/* 
 * $Id: mime.php
 * HTMLHelper plugin class
 * @author: Dhens <rudenyl@gmail.com>
*/
class HelperClassMime
{
	function xml( $data )
	{
		$xml = '<?xml version="1.0" encoding="UTF-8"?>';
		$xml .= "\n<root>";
		$xml .= "\n</root>";
		
		return $xml;
	}
	
	function excel( $data )
	{
		ob_start();
		echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		echo '<?mso-application progid="Excel.Sheet"?>' . "\n";
	?>
		<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:html="http://www.w3.org/TR/REC-html40" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet" xmlns:x="urn:schemas-microsoft-com:office:excel">
			<Worksheet ss:Name="Sheet1">
				<Table x:FullColumns="1" x:FullRows="1">
					<?php foreach($data as $item):?>
					<Row>
						<?php foreach($item as $v):?>
						<Cell>
							<Data ss:Type="String"><?php echo $v;?></Data>
						</Cell>
						<?php endforeach;?>
					</Row>
					<?php endforeach;?>
				</Table>
				<WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel">
					<Print xmlns="urn:schemas-microsoft-com:office:excel">
						<ValidPrinterInfo xmlns="urn:schemas-microsoft-com:office:excel" />
						<HorizontalResolution xmlns="urn:schemas-microsoft-com:office:excel">1200</HorizontalResolution>
						<VerticalResolution xmlns="urn:schemas-microsoft-com:office:excel">1200</VerticalResolution>
					</Print>
					<ProtectObjects xmlns="urn:schemas-microsoft-com:office:excel">False</ProtectObjects>
					<ProtectScenarios xmlns="urn:schemas-microsoft-com:office:excel">False</ProtectScenarios>
				</WorksheetOptions>
			</Worksheet>
		</Workbook>
	<?php
		$xml = ob_get_contents();
		ob_end_clean();
		
		return $xml;
	}
}