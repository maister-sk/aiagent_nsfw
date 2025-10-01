<?php 

// Current historic context data here. $GLOBALS["CONTEXT_BUILDING_DATA"]



foreach ($GLOBALS["CONTEXT_BUILDING_DATA"] as $n => $line) {
    if ($line["role"] == "ext_nsfw_scene") {
        $GLOBALS["CONTEXT_BUILDING_DATA"][$n]["role"] = "user";
        
    } else  if ($line["role"] == "ext_nsfw_sexcene") {
        $GLOBALS["CONTEXT_BUILDING_DATA"][$n]["role"] = "user";
        
    } else  if ($line["role"] == "ext_nsfw_action") {
        $GLOBALS["CONTEXT_BUILDING_DATA"][$n]["role"] = "user";
    } 
}
?>