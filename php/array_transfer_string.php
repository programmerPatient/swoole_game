<?php

/*数组转字符串最多三维数组*/
function implodex( $glue, $array, $separator='' ) { 
	if ( ! is_array( $array ) ) return $array; 
	$string = array(); 
	$count = 0; 
	foreach ( $array as $key => $val ) { 
		if ( is_array( $val ) ) 
			$val = implode( $glue, $val ); 
			if($count == 0){ 
			$string[] = "{$val}"; 
		}else{ 
			$string[] = "{$glue}{$val}"; 
		} 
	} 
	 
	if(empty($separator))$separator = $glue; 
	return implode( $separator, $string ); 
}

/*字符串转数组*/
function emplodex($glue,$string,$separator){
	if(! is_string($string)) return $string;
	$array = explode($separator, $string);
	foreach($array as $key => &$val){
		if(is_string($val)){
			$val = explode($glue,$val);
			foreach($val as &$v){
				$v -= 0; 
			}
		}
	}

	return $array;

}
