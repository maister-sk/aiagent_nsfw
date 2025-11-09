<?php 

// This is called after NPC profile is loaded.

require_once(__DIR__."/common.php");


// Read animations/stages descriptions from file


// Main code
// Will update intimacyStatus every iteration here

$GLOBALS["EMOTEMOODS"].=",flirty";// Gonna track this mood to manage sex_disposal


// Check current intimacy level
$codeName = npcNameToCodename($GLOBALS["HERIKA_NAME"]);
$intimacyStatus=getIntimacyForActor($GLOBALS["HERIKA_NAME"]);

if (!isset($intimacyStatus["level"]))
    $intimacyStatus["level"]=0;

// Process AIAgentNSFW events
processInfoSexScene();

processInfoFertility();

// Reload
$intimacyStatus=getIntimacyForActor($GLOBALS["HERIKA_NAME"]);


if ($codeName=="the_narrator") {
    //no further procsssing needed
    return;
}

// From here should apply to profiled actors

// Disposal modifiers per iteration
// Every iteration we lower sex_disposal by 1
if (isset($intimacyStatus["sex_disposal"])) {
    if ($intimacyStatus["sex_disposal"]>0) {
        
        $intimacyStatus["sex_disposal"]=$intimacyStatus["sex_disposal"]-1;
        error_log("Lowering sex_disposal {$intimacyStatus["sex_disposal"]}");
    } else if ($intimacyStatus["sex_disposal"]<1) {
        
        $intimacyStatus["sex_disposal"]=-1;
        error_log("Limting sex_disposal {$intimacyStatus["sex_disposal"]}");
    }
} else {
    $intimacyStatus["sex_disposal"]=0;
    $intimacyStatus["level"]=0;
    error_log("Resetting sex_disposal {$intimacyStatus["sex_disposal"]}");

}

$actorName=$GLOBALS["HERIKA_NAME"];
$npcManager=new NpcMaster();
$npcData=$npcManager->getByName($actorName);
$extended_data=$npcManager->getExtendedData($npcData);
$metadata=$npcManager->getMetadata($npcData);


// Detect 
$modsToCheck=[
    "The Naked DragonSSE.esp",
    "prostitutes.esp"
];

$isCourtesan=false;
if (is_array($metadata["mods"])) {
    foreach ($modsToCheck as $mod) {
        $isCourtesan=$isCourtesan||in_array($mod,$metadata["mods"]);
    }
}


// Prostitutes always have sex disposal over 19
if ($isCourtesan) {    // Need npc table with tags here
    $intimacyStatus["sex_disposal"]=($intimacyStatus["sex_disposal"]<20)?20: $intimacyStatus["sex_disposal"];
    $intimacyStatus["adult_entertainment_services_autodetected"]=true;

} else {
    $intimacyStatus["adult_entertainment_services_autodetected"]=false;
}


// Arousal from NPC data or profile.

if (isset($GLOBALS["AIAGENT_NSFW_DEFAULT_AROUSAL"]) && $GLOBALS["AIAGENT_NSFW_DEFAULT_AROUSAL"]) {    // Need npc table with tags here
    $intimacyStatus["sex_disposal"]=($intimacyStatus["sex_disposal"]<$GLOBALS["AIAGENT_NSFW_DEFAULT_AROUSAL"])?$GLOBALS["AIAGENT_NSFW_DEFAULT_AROUSAL"]: $intimacyStatus["sex_disposal"];

}

// If current task is relax we increase sex disposal every itteration
$currentTask=DataGetCurrentTask();
if (strpos($currentTask,"relax")!==false) {
    $intimacyStatus["sex_disposal"]+=2;
    error_log("Increasing sex_disposal {$intimacyStatus["sex_disposal"]} because relax mode");
}

// Speech mood modifier

$moodModif=getSexDisposalFromMood($GLOBALS["HERIKA_NAME"],$GLOBALS["gameRequest"][2]);
if ($moodModif>0.45) 
    $intimacyStatus["sex_disposal"]+=2;
else if ($moodModif<0) 
    $intimacyStatus["sex_disposal"]-=2;


// Force mood if level>0, this forces XTTS to hook audio modifier. Should only be 1 when nsfw scene
if ($intimacyStatus["level"]>0) {
    $GLOBALS["FORCE_MOOD"]="sexy";


} else
    unset($GLOBALS["FORCE_MOOD"]);

// Fallback, if chatnf_sl, then we're on a intimate scene
if ($gameRequest[0]=="chatnf_sl") {    

    if ($intimacyStatus["level"]!=2) {
        // In case we miss the event
        $intimacyStatus["level"]=2;
        $intimacyStatus["sex_disposal"]=11;
    }

}

// Set intimate prompts
if ($intimacyStatus["level"]>0) {
    error_log("AIAGENTNSFW] Changing PROMPTS");
    setSexPrompt($GLOBALS["HERIKA_NAME"]);
    setSexSpeechStyle($GLOBALS["HERIKA_NAME"]);
}

error_log("[AIAGENTNSFW ] updateIntimacyForActor({$GLOBALS["HERIKA_NAME"]})".json_encode($intimacyStatus));
updateIntimacyForActor($GLOBALS["HERIKA_NAME"],$intimacyStatus);        

// Add hook  to XTTS to insert some oh's and ah's into the speech.
// Also will change XTTS settings
// If level 2 -> Intimate scene, NPC should talk slower, and we add some random gasps.

if ($intimacyStatus["level"]==2) {
    error_log("Adding XTTS hook {$intimacyStatus["level"]}");
    $GLOBALS["HOOKS"]["XTTS_TEXTMODIFIER"][]=function($text) {

        $randomStrings = [" ... oh ... ", " ... ah ... ", " ... mmm ... "];
        $result = $text;

        // Generate a random index
        $randomIndex = mt_rand(0, count($randomStrings) - 1);

        // Split the sentence into an array of words
        $words = explode(' ', $text);

        // Select a random word index to insert the random string
        $wordIndex = mt_rand(0, count($words) - 1);

        // Insert the random string into the selected word
        $randomWord = $words[$wordIndex];
        $insertPosition = strpos($result, $randomWord);
        $result = substr_replace($result, $randomStrings[$randomIndex], $insertPosition, 0);
        Logger::info("Applying text modifier for XTTS $text => $result ".__FILE__);

        xtts_fastapi_settings(["temperature"=>1,"speed"=>0.7,"enable_text_splitting"=>false,"top_p"=> 1,"top_k"=>100],true);
        return $result;

    };
}

// If level 1 -> Pre intimate scene, NPC should talk slower.
if ($intimacyStatus["level"]==1) {
    error_log("Adding XTTS hook {$intimacyStatus["level"]}");
    $GLOBALS["HOOKS"]["XTTS_TEXTMODIFIER"][]=function($text) {

        Logger::info("Applying speed  modifier for XTTS $text => $text ".__FILE__);

        xtts_fastapi_settings(["temperature"=>1,"speed"=>0.8,"enable_text_splitting"=>false,"top_p"=> 1,"top_k"=>100],true);
        return $text;

    };
}


?>