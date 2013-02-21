<?php
/* 
 * $Id: time.php
 * HTMLHelper plugin class
 * @author: Dhens <rudenyl@gmail.com>
*/
 
class HelperClassTime
{
	static function getElapse($date, $offset=0)
	{
		// exit on invalid date
		if( empty($date) ) {
			return false;
		}
		if( ($tdate = strtotime($date)) == -1 ) {
			return false;
		}
		
		$diff			= array();
		
	    $uts['start']	= $tdate;
	    $uts['end']		= time();
	    if( $uts['start']!==-1 && $uts['end']!==-1 ) {
	        if( $uts['end'] >= $uts['start'] ) {
	            $diff		= $uts['end'] - $uts['start'];
	            if( $days = intval((floor($diff/86400))) ) {
	                $diff	= $diff % 86400;
				}
	            if( $hours = intval((floor($diff/3600))) ) {
	                $diff	= $diff % 3600;
				}
	            if( $minutes = intval((floor($diff/60))) ) {
	                $diff	= $diff % 60;
				}
	            $diff		= intval( $diff );            
				
	            $diff = array('days'=>$days, 'hours'=>$hours, 'minutes'=>$minutes, 'seconds'=>$diff);
	        }
	    }
		
		$html = '';
		if( !empty($diff['days']) ) {
			if($diff['days'] == 1)
				$html .= strftime('Yesterday at %I:%M %p', $tdate);
			else {
				$days = $diff['days'];
				
				if($days <= 7) 
					$html .= strftime('%A at %I:%M %p', $tdate);
				else {
					$now		= getdate();
					$end_date	= getdate($tdate);
					
					if($now['year'] == $end_date['year'])
						$html .= strftime('%b %d at %I:%M %p', $tdate);
					else {
						//$html .= strftime('%b %d, %Y at %I:%M %p', $tdate);
						$html .= strftime('%b %d, %Y', $tdate);
					}
				}
			}
		} else {
			if( !empty($diff['hours']) || !empty($diff['minutes']) ) {
				if( !empty($diff['hours']) ) {
					$t		= $diff['hours'];
					$html	.= $t . ' hour' .($t>1?'s':'');
				}
				
				if( !empty($diff['minutes']) ) {
					$t		= $diff['minutes'];
					if( !empty($html) ) {
						$html	.= ', ';
					}
					$html	.= $t . ' minute' .($t>1?'s':'');
				}
				
				$html		.= ' ago';
			}
		}
		
		if( empty($html) ){
			$html .= 'Less than a minute ago';
		}
		
		return $html;
	}
	
	static function format($date, $format='%b %d, %Y at %I:%M %p')
	{
		$date	= trim($date);
		
		// exit on invalid date
		if( empty($date) ) {
			return false;
		}
		if( substr($date,0,10) == '0000-00-00' ) { 
			return false;
		} 		
		if( ($ndate = strtotime($date)) === false ) {
			return false;
		}
		
		if( empty($format) ) {
			$format	= '%b %d, %Y at %I:%M %p';
		}
		
		$html	= strftime($format, $ndate);
		
		return $html;
	}
	
	static function sec_to_time_format( $secs, $full_format=false )
	{
		if( $hours = intval((floor($secs/3600))) ) {
			$secs	= $secs % 3600;
		}
		if( $minutes = intval((floor($secs/60))) ) {
			$secs	= $secs % 60;
		}
		
		$t	= array();
		if ($full_format) {
			$t	= array(
				$hours,
				sprintf('%02d', $minutes),
				sprintf('%02d', $secs)
			);
		}
		else {
			if ($hours ) {
				$t[]	= $hours;
			}
			if( $minutes ) {
				$t[]	= sprintf('%02d', $minutes);
			}
			if( $secs ) {
				$t[]	= sprintf('%02d', $secs);
			}
		}
		
		return implode(':', $t);
	}
}