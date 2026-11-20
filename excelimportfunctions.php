<?php

function detectheaderrow($exceldata){
	$rowlengths = array();
	foreach($exceldata as $rowdata){
		$totalelements=0;
		 for ($col=0; $col <count($rowdata) ; $col++) 
		{ 
			if($rowdata[$col]) 
				$totalelements++;	
		}
		array_push($rowlengths,$totalelements);
	}
	return array_search(max($rowlengths),$rowlengths);
}

function getheadingelementindex($exceldata,$findelement,$headerrow){
	$row=0;
	$keyscore = array();
	$levscore = array();
	$simscore = array();
	$incindex = -1;
	foreach($xlsx->rows() as $worksheet){
		if($row != $headerrow){
			$row++;
			continue;
		}
		for ($col=0; $col <count($worksheet) ; $col++) 
		{ 
			if($worksheet[$col]){
				$colparts = explode(" ",$worksheet[$col]);
				array_push($colparts,preg_replace('/\s+/', '',strtolower($worksheet[$col])));
				$mc=0;
				foreach($colparts as $colpart){
					if(stristr($findelement,$colpart)){
					$mc++;
					}
				}
				$score = $mc/count($colparts)*100;
				array_push($keyscore,$score);
				$lev = levenshtein($findelement, $worksheet[$col]);
				array_push($levscore,$lev);
				$sim = similar_text($findelement, $worksheet[$col]);
				array_push($simscore,$sim);
				if(stristr(strtolower($findelement),strtolower($worksheet[$col]))){
					$incindex = $col;
				}
			}
		} 
		break;
	}	
	if(max($keyscore)>=50){
		return array_search(max($keyscore),$keyscore);
	}
	else{
		$foundindexes = array();
		array_push($foundindexes,array_search(max($keyscore),$keyscore));
		if(count($levscore)){
			array_push($foundindexes,array_search(min($levscore),$levscore));
		}
		if(count($simscore)){
			array_push($foundindexes,array_search(max($simscore),$simscore));
		}
		array_push($foundindexes,$incindex);
		$countarray = array_count_values($foundindexes);
		return array_search(max($countarray),$countarray);
	}
}
?>