<?php
/*
* $Id: pagination.class.php, version 0.1.172011
* Page navigation base class
* @author: Dhens <rudenyl@gmail.com>
*/

defined('_PRIVATE') or die('Direct access not allowed');

final class Pagination
{
	var $_curpage			= 0;
	var $_limit				= 0;
	var $_limitstart		= 0;
	var $_total				= 0;
	var $_num_results		= 0;
	var $_num_pages			= 0;
	var $_single_page		= false;
	var $_baseURL			= '';
	var $_automakeSEF		= false;
	var $_detect_formatting	= true;
	
	protected $__component_format	= '';
	
	/**
	Class constructor
		@public
	**/
	function __construct( $page, $num_results, $total_items, $limit=0, $baseURL=null, $automakeSEF=false, $detect_formatting=true )
	{
		// bind
		$this->_total		= $total_items;
		$this->_num_results	= $num_results;
		
		$this->_limit		= $limit;
		$this->_limitstart	= ($this->_curpage > 1) ? ($this->_curpage * $limit) - $limit : 0;
		
		if ($this->_limit) {
			$this->_num_pages	= ($this->_total > $this->_limit) ? ceil($this->_total / $this->_limit) : 0;
		}
		$this->_single_page	= $this->_num_pages < 1;
		
		// check out-of-range page index
		$this->_curpage		= ($page > $this->_num_pages) ? 1 : $page;
		
		// set base URL if don't exists
		$this->_baseURL		= $baseURL;
		if (empty($baseURL)) {
			$config			=& Factory::getConfig();
			$uri			= URL::getURI();
			
			$this->_baseURL	= $config->baseURL . $uri->_url;
		}
		
		// if true, URL::_ will be used
		$this->_automakeSEF			= $automakeSEF;
		$this->_detect_formatting	= $detect_formatting;
		
		// get component format
		if ($detect_formatting) {
			$component_format	= Request::getVar('format');
			
			if ($component_format) {
				$this->__component_format	= 'format=' . Request::getVar('format');
			}
			else {
				// turn it off if no special formatting found
				$this->_detect_formatting	= false;
			}
		}
	}
	
	/**
	Get total pagination pages
		@param $total int
		@param $limit int
		@public
	**/
	function getNumPages( $total=null, $limit=null )
	{
		if ($total === null) {
			$total	= $this->_total;
		}
		if ($limit === null) {
			$limit	= $this->_limit;
		}
		
		if ($total && $limit) {
			return ($total > $limit) ? ceil($total / $limit) : 0;
		}
		else {
			if (isset($this->_num_pages)) {
				return $this->_num_pages;
			}
			else {
				if (isset(self::$_num_pages)) {
					self::$_num_pages;
				}
			}
		}
		
		return 0;
	}
	
	/**
	Determine if pagination is empty
		@public
	**/
	function isEmpty()
	{
		return ($this->_total < 1);
	}
	
	/**
	Display pagination
		@public
	**/
	function showPages()
	{
		$is_SEFlike		= (strpos($this->_baseURL, '?') === false);
		
		if ($this->_limit > 0) {
			// create pages	
			if ($this->_total > $this->_limit) {
				$html	= "<ul class=\"pagination\">\n";
			
				if (1 < $this->_curpage) {
					$page	= ($this->_curpage-1 == 1 ? 1 : $this->_curpage - 1);
					$link	= $this->_baseURL .($is_SEFlike ? '?' : '&amp;'). '_PN='.$page;
					$link	= ($this->_automakeSEF ? URL::_($link) : $link);
					// add component format
					$link	.= ($this->_detect_formatting ? (strpos($link, '?')!==false?'&':'?'). $this->__component_format : '');
					$html	.=  "<li><a class=\"prev\" href=\"{$link}\" title=\"Prev\" rel=\"{$page}\">← Prev</a></li>\n";
				}
				
				if ($this->_num_pages > 1) {
					for ($i = 1; $i <= $this->_num_pages; $i++) {
						if ($this->_curpage == $i) {
							$html				.=  "<li><span id=\"current\">{$i}</span></li>\n";
						} 
						else {
							$p = false;
							if ($i < 3 || ( $i >= $this->_curpage - 3 && $i <= $this->_curpage + 3 ) || $i > $this->_num_pages - 3) {
								$in_pages		= true;
								$link	= $this->_baseURL . ($is_SEFlike ? '?' : '&amp;') . '_PN=' .$i;
								$link	= ($this->_automakeSEF ? URL::_($link) : $link);
								// add component format
								$link	.= ($this->_detect_formatting ? (strpos($link, '?')!==false?'&':'?'). $this->__component_format : '');
								
								$html			.= "<li><a class=\"page-numbers\" href=\"{$link}\" rel=\"{$i}\">{$i}</a></li>\n";
							} 
							elseif ($in_pages === true) {
								$in_pages		= false;
								$html			.= "<li>...</li>\n";
							}
						}
					} // for
				}
				
				if ($this->_curpage * $this->_limit < $this->_total || -1 == $this->_total) {
					$page	= $this->_curpage + 1;
					$link	= $this->_baseURL .($is_SEFlike ? '?' : '&amp;'). '_PN='.$page;
					$link	= ($this->_automakeSEF ? URL::_($link) : $link);
					// add component format
					$link	.= ($this->_detect_formatting ? (strpos($link, '?')!==false?'&':'?'). $this->__component_format : '');
					
					$html	.=  "<li><a class=\"next\" href=\"{$link}\" rel=\"{$page}\" title=\"Next\">Next →</a></li>\n";
				}
			
				$html	.= "</ul>\n";
		
				echo $html;
			}
		}
	}

	/**
	Get pagination stats
		@public
	*/
	function getStats()
	{
		$stats	= new stdclass;

		$stats->total	= $this->_total;
		$stats->single_page	= $this->_single_page;

		if (!$this->_single_page) {
			// pages
			$stats->pages	= new stdclass;
			$stats->pages->current	= $this->_curpage;
			$stats->pages->total	= $this->_num_pages;

			// range
			$stats->range	= new stdclass;
			$stats->range->start	= $this->_limit * ($this->_curpage - 1) + 1;
			$stats->range->end		= $stats->range->start + $this->_num_results - 1;
		}

		return $stats;
	}
	
	/**
	Display pagination counter
		@param $display_range boolean
		@public
	**/
	function showCounter( $display_range=false )
	{
		if ($this->_single_page) {
			echo $this->_total;
		} 
		else {
			if ($this->_curpage > $this->_total) {
				return;	// Page out of range.
			}
			else {
				if ($display_range) {
					$index	= $this->_limit * ($this->_curpage - 1) + 1;
					echo number_format($index,0) .' - '. number_format($index + $this->_num_results - 1,0)  .' of ' . number_format($this->_total,0);
				}
				else {
					echo 'page '. number_format($this->_curpage,0)  .' of '. number_format($this->_num_pages,0);
				}
			}
		}
	}
}