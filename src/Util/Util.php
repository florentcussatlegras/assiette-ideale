<?php

namespace App\Util;

class Util
{
	public static function slugify($str) {
		$slug = strtolower($str);

		$slug = preg_replace("/[^a-z0-9s-]/", "", $slug);
		$slug = trim(preg_replace("/[s-]+/", " ", $slug));
		$slug = preg_replace("/s/", "-", $slug);

		return $slug;
	}

	// public static function convertInGr($qty, $unit) {

	// 	$qty = (int)$qty;

	// 	switch ($unit) {
	// 		case 'ml':
	// 			return $qty;
	// 			break;
	// 		case 'cl': 
	// 		    return $qty * 10;
	// 		    break;
	// 		case 'dl':
	// 		    return $qty * 100;
	// 		    break;
	// 		case 'l': 
	// 		    return $qty * 1000;
	// 		    break;
	// 		case 'mg':
	// 			return $qty / 1000;
	// 			break;
	// 		case 'cg':
	// 			return $qty / 100;
	// 			break;
	// 		case 'dg':
	// 			return $qty / 10;
	// 		    break;
	// 		case 'g':
	// 		    return $qty;
	// 		    break;
	// 		case 'kg':
	// 		    return $qty * 1000;
	// 		    break;
	// 	}

	// }

	public function unique_multidim_array($array, $key) { 
	    $temp_array = array(); 
	    $i = 0; 
	    $key_array = array(); 
	    
	    foreach($array as $val) { 
	        if (!in_array($val[$key], $key_array)) { 
	            $key_array[$i] = $val[$key]; 
	            $temp_array[$i] = $val; 
	        } 
	        $i++; 
	    } 
	    return $temp_array; 
	}

	function uniquecol( $obj ) {
	  static $idlist = array();

	  if ( in_array( $obj['id'], $idlist ) )
	    return false;

	  $idlist[] = $obj['id'];
	  return true;    
	}

	public static function compareDishMeal($a, $b) {
	  return strcmp(strtolower($a->getName()), strtolower($b->getName()));
	}

	public static function compareDishMealOnRank($a, $b) {
	  return strcmp($a->getRank(), $b->getRank());
	}

	function roundCoeffCodingBis($n) {

	    $values = [0, 0.25, 0.33, 0.5, 0.66, 0.75, 1];
	    $ecart_min = $n;
	    $index_value_with_ecart_min = 0;

	    foreach($values as $index => $value)
	    {
	    	$ecart = abs($n - $value);
	    	if($ecart <= $ecart_min)
	    	{
	    		$ecart_min = $ecart;
	    		$index_value_with_ecart_min = $index;
	    	}
	    }

	    return $values[$index_value_with_ecart_min];
	}

	public function convertCoeffCodingBisInFraction($n)
	{

		switch($n){
			case 0:
				return '0';
				break;
			case 0.25:
				return '1/4';
				break;
			case 0.33:
				return '1/3';
				break;
			case 0.5:
				return '1/2';
				break;
			case 0.66:
				return '2/3';
				break;
			case 0.75:
				return '3/4';
				break;
			case 1:
				return '1';
				break;
		}

		return true;
	}

	public function nombreLePlusProche($n, $valeurs)
	{	 
	    foreach($valeurs as $new)
	    {
		//on cree une variable qui sera notre ecart en valeur absolue
		    $abs=abs($new-$notre_chiffre);
		             
		//et on cree un nouveau tableau $array qui contiendra la valeur "normale" associee a son ecart par rapport au nombre choisi en valeur absolue (ou plutot l'inverse)
	        $array[$abs]=$new;  
	    }
		         
		//on trie les clés dans l'ordre croissant
		ksort($array);
		 
		//on recupere le premier element du tableau $array
		$ecartok=current($array);
		 
		//et on affiche notre resultat
		return $ecartok; 
	}
}