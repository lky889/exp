<?php
// Code to randomly generate conjoint profiles to send to a Qualtrics instance

// Terminology clarification: 
// Task = Set of choices presented to respondent in a single screen (i.e. pair of candidates)
// Profile = Single list of attributes in a given task (i.e. candidate)
// Attribute = Category characterized by a set of levels (i.e. education level)
// Level = Value that an attribute can take in a particular choice task (i.e. "no formal education")

// Attributes and Levels stored in a 2-dimensional Array 

// Function to generate weighted random numbers
function weighted_randomize($prob_array, $at_key)
{
	$prob_list = $prob_array[$at_key];
	
	// Create an array containing cutpoints for randomization
	$cumul_prob = array();
	$cumulative = 0.0;
	for ($i=0; $i<count($prob_list); $i++){
		$cumul_prob[$i] = $cumulative;
		$cumulative = $cumulative + floatval($prob_list[$i]);
	}

	// Generate a uniform random floating point value between 0.0 and 1.0
	$unif_rand = mt_rand() / mt_getrandmax();

	// Figure out which integer should be returned
	$outInt = 0;
	for ($k = 0; $k < count($cumul_prob); $k++){
		if ($cumul_prob[$k] <= $unif_rand){
			$outInt = $k + 1;
		}
	}

	return($outInt);

}
                    

$featurearray = array("您进入数字平台的方式" => array("微信小程序","下载应用软件（APP)","扫描二维码"),"每一次低碳行为都会获得相应的积分，并显示您和好友在城市的排名" => array("有","没有"),"平台中有一座“虚拟城市”，当采取更多的低碳行为时，会看到它的生态环境的变化" => array("有","没有"),"根据打卡低碳的次数多少，获得政府颁发的不同等级的电子证书" => array("有","没有"),"界面中显示所在的城市已参与低碳行为的用户比例" => array("5%","15%","25%","35%"),"平台公布所有用户总的碳减排量，低碳公益项目等平台运作信息" => array("不公布","每半年公布一次","每年公布一次","每个月公布一次"));

$restrictionarray = array();

// Indicator for whether weighted randomization should be enabled or not
$weighted = 0;

// K = Number of tasks displayed to the respondent
$K = 2;

// N = Number of profiles displayed in each task
$N = 2;

// num_attributes = Number of Attributes in the Array
$num_attributes = count($featurearray);

// Should duplicate profiles be rejected?
$noDuplicateProfiles = True;


$attrconstraintarray = array();


// Re-randomize the $featurearray

// Place the $featurearray keys into a new array
$featureArrayKeys = array();
$incr = 0;

foreach($featurearray as $attribute => $levels){	
	$featureArrayKeys[$incr] = $attribute;
	$incr = $incr + 1;
}

// Backup $featureArrayKeys
$featureArrayKeysBackup = $featureArrayKeys;

// If order randomization constraints exist, drop all of the non-free attributes
if (count($attrconstraintarray) != 0){
	foreach ($attrconstraintarray as $constraints){
		if (count($constraints) > 1){
			for ($p = 1; $p < count($constraints); $p++){
				if (in_array($constraints[$p], $featureArrayKeys)){
					$remkey = array_search($constraints[$p],$featureArrayKeys);
					unset($featureArrayKeys[$remkey]);
				}
			}
		}
	}
} 
// Re-set the array key indices
$featureArrayKeys = array_values($featureArrayKeys);
// Re-randomize the $featurearray keys
shuffle($featureArrayKeys);

// Re-insert the non-free attributes constrained by $attrconstraintarray
if (count($attrconstraintarray) != 0){
	foreach ($attrconstraintarray as $constraints){
		if (count($constraints) > 1){
			$insertloc = $constraints[0];
			if (in_array($insertloc, $featureArrayKeys)){
				$insert_block = array($insertloc);
				for ($p = 1; $p < count($constraints); $p++){
					if (in_array($constraints[$p], $featureArrayKeysBackup)){
						array_push($insert_block, $constraints[$p]);
					}
				}
				
				$begin_index = array_search($insertloc, $featureArrayKeys);
				array_splice($featureArrayKeys, $begin_index, 1, $insert_block);
			}
		}
	}
}


// Re-generate the new $featurearray - label it $featureArrayNew

$featureArrayNew = array();
foreach($featureArrayKeys as $key){
	$featureArrayNew[$key] = $featurearray[$key];
}
// Initialize the array returned to the user
// Naming Convention
// Level Name: F-[task number]-[profile number]-[attribute number]
// Attribute Name: F-[task number]-[attribute number]
// Example: F-1-3-2, Returns the level corresponding to Task 1, Profile 3, Attribute 2 
// F-3-3, Returns the attribute name corresponding to Task 3, Attribute 3

$returnarray = array();

// For each task $p
for($p = 1; $p <= $K; $p++){

	// For each profile $i
	for($i = 1; $i <= $N; $i++){

		// Repeat until non-restricted profile generated
		$complete = False;

		while ($complete == False){

			// Create a count for $attributes to be incremented in the next loop
			$attr = 0;
			
			// Create a dictionary to hold profile's attributes
			$profile_dict = array();

			// For each attribute $attribute and level array $levels in task $p
			foreach($featureArrayNew as $attribute => $levels){	
				
				// Increment attribute count
				$attr = $attr + 1;

				// Create key for attribute name
				$attr_key = "F-" . (string)$p . "-" . (string)$attr;

				// Store attribute name in $returnarray
				$returnarray[$attr_key] = $attribute;

				// Get length of $levels array
				$num_levels = count($levels);

				// Randomly select one of the level indices
				if ($weighted == 1){
					$level_index = weighted_randomize($probabilityarray, $attribute) - 1;

				}else{
					$level_index = mt_rand(1,$num_levels) - 1;	
				}	

				// Pull out the selected level
				$chosen_level = $levels[$level_index];
			
				// Store selected level in $profileDict
				$profile_dict[$attribute] = $chosen_level;

				// Create key for level in $returnarray
				$level_key = "F-" . (string)$p . "-" . (string)$i . "-" . (string)$attr;

				// Store selected level in $returnarray
				$returnarray[$level_key] = $chosen_level;

			}

			$clear = True;
			// Cycle through restrictions to confirm/reject profile
			if(count($restrictionarray) != 0){

				foreach($restrictionarray as $restriction){
					$false = 1;
					foreach($restriction as $pair){
						if ($profile_dict[$pair[0]] == $pair[1]){
							$false = $false*1;
						}else{
							$false = $false*0;
						}
						
					}
					if ($false == 1){
						$clear = False;
					}
				}
			}
            // Cycle through all previous profiles to confirm no identical profiles
            if ($noDuplicateProfiles == True){
    			if ($i > 1){
    
    				// For each previous profile
    				for($z = 1; $z < $i; $z++){
    					
    					// Start by assuming it's the same
    					$identical = True;
    					
    					// Create a count for $attributes to be incremented in the next loop
    					$attrTemp = 0;
    					
    					// For each attribute $attribute and level array $levels in task $p
    					foreach($featureArrayNew as $attribute => $levels){	
    						
    						// Increment attribute count
    						$attrTemp = $attrTemp + 1;
    
    						// Create keys 
    						$level_key_profile = "F-" . (string)$p . "-" . (string)$i . "-" . (string)$attrTemp;
    						$level_key_check = "F-" . (string)$p . "-" . (string)$z . "-" . (string)$attrTemp;
    						
    						// If attributes are different, declare not identical
    						if ($returnarray[$level_key_profile] != $returnarray[$level_key_check]){
    							$identical = False;
    						}
    					}
    					// If we detect an identical profile, reject
    					if ($identical == True){
    						$clear = False;
    					}
    				} 
                }
            }
			$complete = $clear;
		}
	}


}

// Return the array back to Qualtrics
print  json_encode($returnarray);
?>
