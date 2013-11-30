<?php
/*
* Convert JSON file to CSV.
*
* Uses:
* php json-to-csv.php file.json -filter filter1 filter2 > file.csv
*/

// Global variables
$path; // Path name tree
$arrayDataLevel = array(); 
$arrayDataTree = array();
$arrayFirstLine = array();
$arrayFilter = array();

$levelTree = 0; // Variable level tree
$levelMaxTree = 0; // Max level deep tree

if (empty($argv[1])) die("The json file name or URL is missed\n");
$jsonFilename = $argv[1];

if ($argc > 2){
	if (strcmp($argv[3],"-filter")){
		for ($i=3; $i < $argc; $i++){
			array_push($arrayFilter,$argv[$i]);
		}
	}else
		die("Command invalid\n");
}

jsontocsv(); // Convert it!

// +++++++++++++++ JSON to CSV function +++++++++++++++
function jsontocsv(){

	$json = file_get_contents($GLOBALS['jsonFilename']);
	$array = json_decode($json, true);
	$f = fopen('php://output', 'w');

	foreach ($array as $key => $line)
	{			
		$GLOBALS['path'] = "";
		
		if (is_array($line)){
			if (!is_numeric($key))
			{
				$GLOBALS['path'] .= $key;
			}
			getTree($line, $GLOBALS['levelTree'], $GLOBALS['levelMaxTree']);
		}else{
			array_push($GLOBALS['arrayFirstLine'],$key);
			array_push($GLOBALS['arrayDataTree'],array($key=>$line));
		}
	}

	createCsv($f);
}

// +++++++++++++++ Create CSV file +++++++++++++++
function createCsv($f){
	resultFilter($GLOBALS['arrayFirstLine']);

	fputcsv($f, $GLOBALS['arrayFirstLine']);
		
	resultFilter($GLOBALS['arrayDataTree']);
		
	$arrayResultTree = array();
	foreach ($GLOBALS['arrayDataTree'] as $key => $line)
	{
		if (is_array($line))
		{
			foreach ($line as $keyData => $lineData)
			{
				$i = 0;
				foreach ($GLOBALS['arrayFirstLine'] as $keyFirst => $lineFirst)
				{					
					if ($keyData == $lineFirst)
					{
						$arrayResultTree[$i] = $lineData;	// Add info
						break;		
					}
					else
					{
						array_push($arrayResultTree,""); // Add white space column						
					}
					$i++;
				}
			}
		}
		fputcsv($f, $arrayResultTree); // Array to file
		$arrayResultTree = array();	
	}
}

// +++++++++++++++ Get tree json +++++++++++++++
function getTree($array, $level, $maxLevel)
{
	$level++;
			
	foreach ($array as $key => $line)
	{	
		if(is_array($line))
		{
			checkPath($key);
			
			getTree($line,$level, $GLOBALS['levelMaxTree']); // Call function

			$levelRemove = $GLOBALS['levelMaxTree'] - ($level+1); // Check current level
						
			if(!is_numeric($key) && ($levelRemove > 0))
			{
				$GLOBALS['levelMaxTree'] = $level;
				removePathLevel();							
			}
			else if(!is_numeric($key) && ($levelRemove == 0))
			{
				removePathLevel();
			}
		}
		else
		{
			checkPath($key);
				
			if (!in_array($GLOBALS['path'],$GLOBALS['arrayFirstLine']))
			{
				array_push($GLOBALS['arrayFirstLine'],$GLOBALS['path']);
			}

			$GLOBALS['arrayDataLevel'][$GLOBALS['path']]=$line;	
			$GLOBALS['levelMaxTree'] = $level;
			removePathLevel();
		}
	}
	
	if(!empty($GLOBALS['arrayDataLevel']))
	{
		array_push($GLOBALS['arrayDataTree'],$GLOBALS['arrayDataLevel']);
		$GLOBALS['arrayDataLevel'] = array();
	}	
}

// +++++++++++++++ Remove path level +++++++++++++++
function removePathLevel()
{
	$pattern = "/.+(?=-)/";
	preg_match_all($pattern, $GLOBALS['path'], $match);
	$GLOBALS['path'] = $match[0][0];
}

// +++++++++++++++ Check path +++++++++++++++
function checkPath($key){
	if (!is_numeric($key))
	{
		if ($GLOBALS['path'] == "")
			$GLOBALS['path'] .= $key;
		else
			$GLOBALS['path'] .= '-'.$key;
	}
}

// +++++++++++++++ Apply filter +++++++++++++++
function resultFilter($array){
	foreach ($array as $key => $line)
	{
		if (is_array($line))
		{
			foreach ($line as $keyData => $lineData)
			{
				foreach ($GLOBALS['arrayFilter'] as $keyFilter => $lineFilter)
				{
					preg_match_all('/'.$lineFilter.'/', $keyData, $match);
					if (isset($match[0][0]) && ($match[0][0] != ""))
					{
						unset($GLOBALS['arrayDataTree'][$key][$keyData]);
						if (empty($GLOBALS['arrayDataTree'][$key]))
							unset($GLOBALS['arrayDataTree'][$key]);			
					}
				}
			}
		}
		else
		{
			foreach ($GLOBALS['arrayFilter'] as $keyFilter => $lineFilter)
			{
				preg_match_all('/'.$lineFilter.'/', $line, $match);
				if (isset($match[0][0]) && ($match[0][0] != ""))
				{
					unset($GLOBALS['arrayFirstLine'][$key]);				
				}
			}
		}
	}
}

?>