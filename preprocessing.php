<?php

// This is called at the very beginning, before any context is created
// Add info_sexscene to external_fast_commands for non-locking processing

$GLOBALS["external_fast_commands"][]="info_sexscene";

require_once(__DIR__."/common.php");


if (isset($GLOBALS["gameRequest"])) {
    // Main
    // Disposal data should be handled by CHIM engine.
    if ($GLOBALS["gameRequest"][0]=="init") {
        
        error_log("Should load sex_disposals here");
        //loadAllDisposals();

    }

    if ($GLOBALS["gameRequest"][0]=="infosave") {

        error_log("Should save sex_disposals here");
        //saveAllDisposals();
    }
}

?>