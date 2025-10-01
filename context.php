<?php 

// This is called when NPC context is built, before funrect handling

if (isset($GLOBALS["AIAGENTNSFW_FORCE_STOP"]) &&  $GLOBALS["AIAGENTNSFW_FORCE_STOP"]) {

    if ($gameRequest[0]=="ext_nsfw_action") {    // This was changed by processInfoSexScene

        $actor=$GLOBALS["HERIKA_NAME"];
        $intimacyStatus=getIntimacyForActor($actor);
        if (!isset($intimacyStatus["orgasm_generated"]) || $intimacyStatus["orgasm_generated"]==false) {
            generateClimaxSpeech();
           
        } else {
            error_log("Orgams sound already generated");

        }

        terminate();

    
    } else {
        error_log(print_r($gameRequest,true));


    }

    // Don't do LLM request if some conditions unmet.
    Logger::info("Stopping processing  {$GLOBALS["gameRequest"][0]}");

    terminate();


}


// Minor change son context for special cases.

if ($GLOBALS["gameRequest"][0]=="chatnf_sl_end") {
  
    $GLOBALS["PATCH_PROMPT_ENFORCE_ACTIONS"]=false;
    $GLOBALS["COMMAND_PROMPT_ENFORCE_ACTIONS"]="";
    // Remove player request (last entry line)...useless
    array_pop($GLOBALS["contextDataFull"]);
}

if ($GLOBALS["gameRequest"][0]=="chatnf_sl") {
  
    // Remove player request (last entry line)...useless
    array_pop($GLOBALS["contextDataFull"]);

}


// Drunk status handling

$actorName=$GLOBALS["HERIKA_NAME"];
$npcManager=new NpcMaster();
$npcData=$npcManager->getByName($actorName);
$extended_data=$npcManager->getExtendedData($npcData);

if (in_array(getLastIssuedMood($GLOBALS["HERIKA_NAME"],$GLOBALS["gameRequest"][2]),["drunk","tipsy"])) {
    error_log("Forcing drunk mood: {$GLOBALS["HERIKA_NAME"]} {$GLOBALS["gameRequest"][2]}");
    $GLOBALS["FORCE_MOOD"]="drunk";
    $GLOBALS["EMOTEMOODS"]="drunk"; // Can be overwriten by LLM
    $GLOBALS["TTS_FFMPEG_FILTERS"]["tempo"]='atempo=0.65';//Force the ffmpeg filter
    $extended_data["aiagent_nsfw_last_time_drunk"]=$GLOBALS["gameRequest"][2];
} else {
    if (isset($extended_data["aiagent_nsfw_last_time_drunk"])) {
        unset($extended_data["aiagent_nsfw_last_time_drunk"]);
        $GLOBALS["FORCE_MOOD"]="sober";
        $GLOBALS["EMOTEMOODS"]="sober"; // Can be overwriten by LLM
    }
}

$npcData=$npcManager->setExtendedData($npcData,$extended_data);
$npcManager->updateByArray($npcData);

// Add note if player is naked
if (playerIsNaked()) {
    error_log("[AIAGENTNSFW] Player is naked");
    $GLOBALS["contextDataFull"][0]["content"].="\n#Note: {$GLOBALS["PLAYER_NAME"]} is nude, not wearing clothes\n";

}
?>