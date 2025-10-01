<?php

$GLOBALS["external_fast_commands"][]="info_sexscene";

require_once(__DIR__."/common.php");


if (isset($GLOBALS["gameRequest"])) {
// Main
    if ($GLOBALS["gameRequest"][0]=="init") {
        
        error_log("Should load sex_disposals here");
        loadAllDisposals();

    }

    if ($GLOBALS["gameRequest"][0]=="infosave") {

        error_log("Should save sex_disposals here");
        saveAllDisposals();
    }
}

?>