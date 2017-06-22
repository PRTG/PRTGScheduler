<?php

defined('BASEPATH') OR exit('No direct script access allowed');

if ( ! function_exists('nth_applicator'))
{
	/**
	 * Element
	 *
	 * Lets you determine whether an array index is set and whether it has a value.
	 * If the element is empty it returns NULL (or whatever you specify as the default value.)
	 *
	 * @param	item array, consisting of the actual array item and the prefix / appendix
	 * @return the changed array item
	 */

   function nth_applicator($array,$prefix){

		 if(is_string($array))
		 { $array = explode(",",$array); }

		 sort($array);
		 $result = array();

		 foreach($array as $item)
		 	array_push($result,$prefix.$item);

		 return join(",",$result);

   }

}


?>
