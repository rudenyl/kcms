<?php
/* 
 * $Id: tree.php
*/
 
class HelperClassTree
{
	static function recurse( $id, $indent, $list, &$children, $maxlevel=9999, $level=0, $type=1, $compact=false )
	{
		if (@$children[$id] && $level <= $maxlevel) {
			foreach($children[$id] as $v) {
				$id	= $v->id;

				if ($type) {
					$pre	= '';
					if ($level) {
						$pre 	= '<sup>|_</sup>&nbsp;';
					}
					$spacer = str_repeat('&nbsp;', 6);
				} else {
					$pre 	= '- ';
					$spacer = '&nbsp;&nbsp;';
				}

				if ($v->parent == 0) {
					$txt 	= $v->text;
				} else {
					$txt 	= $pre . $v->text;
				}
				
				$list[$id]	= $v;
				if (!$compact) {
					$list[$id]->text		= "$indent$txt";
					$list[$id]->level		= $level;
					$list[$id]->children	= count( @$children[$id] );
				}
				
				$list	= HTMLHelper::_('tree.recurse', $id, $indent . $spacer, $list, $children, $maxlevel, $level+1, $type, $compact);
			}
		}
		
		return $list;
	}
}
