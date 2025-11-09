<?php

// This is called at the very beginning, before any context is created
// Add info_sexscene to external_fast_commands for non-locking processing

$GLOBALS["external_fast_commands"][]="ext_nsfw_sexcene";
$GLOBALS["external_fast_commands"][]="fertility_notification";


require_once(__DIR__."/common.php");


if (isset($GLOBALS["gameRequest"])) {
    // Main
    // Disposal data should be handled by CHIM engine.
    if ($GLOBALS["gameRequest"][0]=="init") {
        
  
    }

    if ($GLOBALS["gameRequest"][0]=="infosave") {

  
    }
}

// Hook into BIOGRAPHY_BUILDER
$GLOBALS["HOOKS"]["BIOGRAPHY_BUILDER"]["fertility_handler"]=function($currentBio,$currentNpcData) {
     $extended = json_decode($currentNpcData["extended_data"], true);
     if (isset($extended["fertility_is_pregnant"]) && $extended["fertility_is_pregnant"]) {
        $currentBio.="\n<fertility>\n{$currentNpcData["npc_name"]} is currently pregnant\n<fertility>";
     }

     return $currentBio;
}

?>