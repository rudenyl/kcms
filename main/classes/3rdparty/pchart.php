<?php
defined('_PRIVATE') or die('Direct access not allowed');

class ThirdPartyClasspChart
{
	function render( $data, $graph_type='spline' )
	{
		$classPath	= dirname(__FILE__) .DS. 'pChart' .DS. 'class';
		$fontPath	= dirname(__FILE__) .DS. 'pChart' .DS. 'fonts';
	
		// include required files
		include( $classPath .DS. 'pData.class.php' );
		include( $classPath .DS. 'pDraw.class.php' );
		include( $classPath .DS. 'pImage.class.php' );

		// create datasets
		$graph_data	= new pData();   
		$graph_data->addPoints($data['values']['y'], 'Probe 1'); 
		$graph_data->setSerieWeight('Probe 1', 2); 
		$graph_data->setAxisName(0, @$data['ordinate_title']); 
		$graph_data->addPoints($data['values']['x'], 'Labels'); 
		$graph_data->setAbscissa('Labels'); 


		/* Create the pChart object */ 
		$graph_image = new pImage($data['dimension']['width'], $data['dimension']['height'], $graph_data); 

		// Turn anti-aliasing OFF
		$graph_image->Antialias = false; 

		// add border
		$graph_image->drawRectangle(0,0,$data['dimension']['width']-1,$data['dimension']['height']-1, array('R'=>200, 'G'=>200, 'B'=>200)); 

		// title
		if (isset($data['graph_title']) && !empty($data['graph_title'])) {
			$graph_image->setFontProperties(
			array(
				'R'=>98, 'G'=>98, 'B'=>98,
				'FontName' => $fontPath .DS. 'arial.ttf', 
				'FontSize' => 11
			)); 
			$graph_image->drawText($data['dimension']['width'] / 2,30, $data['graph_title'], array('FontSize'=>16, 'Align'=>TEXT_ALIGN_BOTTOMMIDDLE)); 
		}

		// set default font
		$graph_image->setFontProperties(
		array(
			'R'=>98, 'G'=>98, 'B'=>98,
			'FontName' => $fontPath .DS. 'arial.ttf',
			'FontSize' => 8
		)); 

		// set graph area
		$graph_image->setGraphArea(75, 40, $data['dimension']['width']-10, $data['dimension']['height']-30); 

		// draw the scale
		$scaleSettings = array('XMargin'=>20, 'YMargin'=>5, 'Floating'=>true, 'GridR'=>200, 'GridG'=>200, 'GridB'=>200, 'DrawSubTicks'=>true, 'CycleBackground'=>true);
		if (isset($data['scaleSettings']) && is_array($data['scaleSettings'])) {
			$scaleSettings	= array_merge($scaleSettings, $data['scaleSettings']);
		}
		$graph_image->drawScale($scaleSettings); 

		// Turn anti-aliasing ON
		$graph_image->Antialias	= true; 

		// draw graph
		$graph_type	= empty($graph_type) ? 'spline' : $graph_type;
		switch ($graph_type) {
			case 'line':
				$graph_image->drawLineChart();
				break;
				
			case 'spline':
			default: 
				$graph_image->drawSplineChart();
				break;
		}
		$graph_image->setFontProperties(
		array(
			'FontName' => $fontPath .DS. 'verdana.ttf', 
			'FontSize' => 6
		)); 
		$graph_image->drawPlotChart(array('DisplayValues' => true, 'PlotBorder' => true, 'BorderSize'=>1, 'Surrounding'=>-60, 'BorderAlpha'=>80));

		// render now!
		$graph_image->autoOutput('pictures/chart.png'); 
	}
}