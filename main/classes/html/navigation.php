<?php
/* 
 * $Id: navigation.php
*/
 
class HelperClassNavigation 
{
	function init($page, $totalElement, $maxElement = 0)
	{
		$args			= array();
		$total_pages	= 0;
		
		if ($maxElement > 0) {
			$total = $totalElement;
			
			$html	= '<ul>';
			
			// create navigation	
			if ( $total > $maxElement ) {
				$total_pages = ceil( $total / $maxElement );
				
				if ( 1 < $page ) {
					$args['page'] = ( 1 == $page - 1 ) ? false : $page - 1;
					$previous = $args['page'];
					if (false == $args['page']) {
						$previous = 1; 
					}
					$prev	= '?p=' . $previous;
					$html	.=  '<li><a class="icons prev" href="' .$prev. '">&laquo;</a></li>';
				}
				
				$total_pages = ceil( $total / $maxElement );
				
				if ( $total_pages > 1 ) {
					for ( $page_num = 1; $page_num <= $total_pages; $page_num++ ) {
						if ( $page == $page_num ) {
							$html .=  '<li><span id="current">' . $page_num . '</span></li>';
						} else {
							$p = false;
							if ( $page_num < 3 || ( $page_num >= $page - 3 && $page_num <= $page + 3 ) || $page_num > $total_pages - 3 ) {
								$args['page']	= ( 1 == $page_num ) ? false : $page_num;
								$link	= '?p=' .$page_num;
								$html 	.= '<li><a class="page-numbers" href="' .$link. '">' .$page_num. '</a></li>';
								$in		= true;
							} elseif ( $in === true ) {
								$html	.= '<li>...</li>';
								$in		= false;
							}
						}
					}
				}
				
				if ( ( $page ) * $maxElement < $total || -1 == $total ) {
					$args['page'] = $page + 1;
					$next	= '?p=' . $args['page'];
					$html .=  '<li><a class="icons next" href="' .$next. '">&raquo;</a></li>';
				}
			}
			
			$html	.= '</ul>';
		}
		
		// build object
		$obj = new stdclass();
		
		$obj->start			= ($page > 1) ? ($page * $maxElement) - $maxElement : 0;
		$obj->limit			= $maxElement;
		$obj->total			= $totalElement;
		$obj->pages			= $total_pages;
		$obj->counter		= $html;
		$obj->single_page	= $total_pages < 1;
		
		if( $obj->single_page )
			$obj->display	= $totalElement . ' items';
		else {
			$obj->display	= 'page '. $page  .' of '. $total_pages;
			
			if( $page > $total_pages ) {
				$obj->error	= 'Page out of range.';
			}
		}
		
		return $obj;
	}
}