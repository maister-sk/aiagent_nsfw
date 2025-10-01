<?php

require_once(__DIR__."/common.php");

// Chnage default actors name, so descriptions can use NPC name.
if ($GLOBALS["HERIKA_NAME"]=="The Narrator") {
    $GLOBALS["HERIKA_NAME"]="Character";
}

/******
ExtCmdHug
******/
$GLOBALS["F_NAMES"]["ExtCmdHug"]="GiveHug";                           
$GLOBALS["F_TRANSLATIONS"]["ExtCmdHug"]="Gives a hug,squeeze,embrace to {$GLOBALS["PLAYER_NAME"]}";
$GLOBALS["FUNCTIONS"][] =
    [
        "name" => $GLOBALS["F_NAMES"]["ExtCmdHug"],
        "description" => $GLOBALS["F_TRANSLATIONS"]["ExtCmdHug"],
        "parameters" => [
            "type" => "object",
            "properties" => [
                "target" => [
                    "type" => "string",
                    "description" => "Target NPC, Actor, or being",
                ]
            ],
            "required" => ["target"],
        ],
    ]
;


$GLOBALS["FUNCRET"]["ExtCmdHug"]=function() {
    global $gameRequest,$returnFunction,$db,$request;
    // Probably we want to execute something, and put return value in $returnFunction[3] and $gameRequest[3];
    // We could overwrite also $request.
 
    // Participants
    $functionCallRet = explode("@", $gameRequest[3]); // Function returns here
    // Update intimacy status for al lparticipants

    // Actor who issued command
    $actors[]=npcNameToCodename($GLOBALS["HERIKA_NAME"]);;
    $actorList=explode(",",$functionCallRet[2]);
    foreach($actorList as $actor) {
        if (trim($actor)!=$GLOBALS["PLAYER_NAME"])
            $actors[]=($actor);

    }

    foreach ($actors as $actorName) {
        
        $intimacyStatus=getIntimacyForActor($actorName);
        $intimacyStatus["sex_disposal"]+=15;
        //$intimacyStatus["level"]=0;
        updateIntimacyForActor($actorName,$intimacyStatus);

    }
    
    $GLOBALS["AVOID_LLM_CALL"]=true;

 };

 
/******
ExtCmdRemoveClothes
******/

$GLOBALS["F_NAMES"]["ExtCmdRemoveClothes"]="RemoveClothes";                           
$GLOBALS["F_TRANSLATIONS"]["ExtCmdRemoveClothes"]="{$GLOBALS["HERIKA_NAME"]} removes worn clothes. ";
$GLOBALS["FUNCTIONS"][] =
    [
        "name" => $GLOBALS["F_NAMES"]["ExtCmdRemoveClothes"],
        "description" => $GLOBALS["F_TRANSLATIONS"]["ExtCmdRemoveClothes"],
        "parameters" => [
            "type" => "object",
            "properties" => [
                "target" => [
                    "type" => "string",
                    "description" => "Keep it blank",
                ]
            ],
            "required" => []
        ],
    ]
;

$GLOBALS["FUNCRET"]["ExtCmdRemoveClothes"]=function() {
    global $gameRequest,$returnFunction,$db,$request;
    // Probably we want to execute something, and put return value in $returnFunction[3] and $gameRequest[3];
    // We could overwrite also $request.
    error_log("Running ExtCmdRemoveClothes FUNCRET");
    
    $intimacyStatus=getIntimacyForActor($GLOBALS["HERIKA_NAME"]);
    $intimacyStatus["sex_disposal"]+=10;
    $intimacyStatus["is_naked"]=1;
    updateIntimacyForActor($GLOBALS["HERIKA_NAME"],$intimacyStatus);
    $GLOBALS["AVOID_LLM_CALL"]=true;


/*
    if (isset($frResponse["argName"]))
    $argName = $frResponse["argName"];
if (isset($frResponse["request"]))
    $request = $frResponse["request"];
if (isset($frResponse["useFunctionsAgain"]))
    $useFunctionsAgain = $frResponse["useFunctionsAgain"];
*/
};

/******
ExtCmdStartSex
******/

$GLOBALS["F_NAMES"]["ExtCmdStartSex"]="MakeLove";                           
$GLOBALS["F_TRANSLATIONS"]["ExtCmdStartSex"]="{$GLOBALS["HERIKA_NAME"]} starts an intimate scene with another actor";
$GLOBALS["FUNCTIONS"][] =
[
    "name" => $GLOBALS["F_NAMES"]["ExtCmdStartSex"],
    "description" => $GLOBALS["F_TRANSLATIONS"]["ExtCmdStartSex"],
    "parameters" => [
        "type" => "object",
        "properties" => [
            "target" => [
                "type" => "string",
                "description" => "Target NPC, Actor, or being",
            ]
        ],
        "required" => ["target"],
    ]
]
;


$GLOBALS["FUNCRET"]["ExtCmdStartSex"]=function() {
    global $gameRequest,$returnFunction,$db,$request;
    // Probably we want to execute something, and put return value in $returnFunction[3] and $gameRequest[3];
    // We could overwrite also $request.
    error_log("Running ExtCmdStartSex FUNCRET");

     // Participants
     $functionCallRet = explode("@", $gameRequest[3]); // Function returns here
     // Update intimacy status for al lparticipants
 
     // Actor who issued command
     $actors[]=npcNameToCodename($GLOBALS["HERIKA_NAME"]);;
     $actorList=explode(",",$functionCallRet[2]);
     foreach($actorList as $actor) {
         if (trim($actor)!=$GLOBALS["PLAYER_NAME"])
             $actors[]=($actor);
 
     }
 
     foreach ($actors as $actorName) {
         
         $intimacyStatus=getIntimacyForActor($actorName);
         $intimacyStatus["sex_disposal"]+=15;
         $intimacyStatus["level"]=1;
         updateIntimacyForActor($actorName,$intimacyStatus);
 
     }

     $GLOBALS["AVOID_LLM_CALL"]=true;


};

/******
ExtCmdStartBlowJob
******/

$GLOBALS["F_NAMES"]["ExtCmdStartBlowJob"]="GiveOralSex";                           
$GLOBALS["F_TRANSLATIONS"]["ExtCmdStartBlowJob"]="{$GLOBALS["HERIKA_NAME"]} starts an intimate scene with another actor, giving oral sex";
$GLOBALS["FUNCTIONS"][] =
[
    "name" => $GLOBALS["F_NAMES"]["ExtCmdStartBlowJob"],
    "description" => $GLOBALS["F_TRANSLATIONS"]["ExtCmdStartBlowJob"],
    "parameters" => [
        "type" => "object",
        "properties" => [
            "target" => [
                "type" => "string",
                "description" => "Target NPC, Actor, or being",
            ]
        ],
        "required" => ["target"],
    ]
]
;


$GLOBALS["FUNCRET"]["ExtCmdStartBlowJob"]=function() {
    global $gameRequest,$returnFunction,$db,$request;
    // Probably we want to execute something, and put return value in $returnFunction[3] and $gameRequest[3];
    // We could overwrite also $request.
    error_log("Running ExtCmdStartBlowJob FUNCRET");
  
    // Participants
    $functionCallRet = explode("@", $gameRequest[3]); // Function returns here
    // Update intimacy status for al lparticipants

    // Actor who issued command
    $actors[]=npcNameToCodename($GLOBALS["HERIKA_NAME"]);;
    $actorList=explode(",",$functionCallRet[2]);
    foreach($actorList as $actor) {
        if (trim($actor)!=$GLOBALS["PLAYER_NAME"])
            $actors[]=($actor);

    }

    foreach ($actors as $actorName) {
        
        $intimacyStatus=getIntimacyForActor($actorName);
        $intimacyStatus["sex_disposal"]+=15;
        $intimacyStatus["level"]=1;
        updateIntimacyForActor($actorName,$intimacyStatus);

    }

};


/******
ExtCmdStartMassage
******/

$GLOBALS["F_NAMES"]["ExtCmdStartMassage"]="GiveMassage";                           
$GLOBALS["F_TRANSLATIONS"]["ExtCmdStartMassage"]="{$GLOBALS["HERIKA_NAME"]} receives a massage from {$GLOBALS["PLAYER_NAME"]}. You must issue this action to allow starting a massage or if you want one.";
$GLOBALS["FUNCTIONS"][] =
[
    "name" => $GLOBALS["F_NAMES"]["ExtCmdStartMassage"],
    "description" => $GLOBALS["F_TRANSLATIONS"]["ExtCmdStartMassage"],
    "parameters" => [
        "type" => "object",
        "properties" => [
            "target" => [
                "type" => "string",
                "description" => "Target NPC, Actor, or being",
            ]
        ],
        "required" => ["target"],
    ]
]
;


$GLOBALS["FUNCRET"]["ExtCmdStartMassage"]=function() {
    global $gameRequest,$returnFunction,$db,$request;
    // Probably we want to execute something, and put return value in $returnFunction[3] and $gameRequest[3];
    // We could overwrite also $request.
    error_log("Running ExtCmdStartMassage FUNCRET");
   
    // Participants
    $functionCallRet = explode("@", $gameRequest[3]); // Function returns here
    // Update intimacy status for al lparticipants

    // Actor who issued command
    $actors[]=npcNameToCodename($GLOBALS["HERIKA_NAME"]);;
    $actorList=explode(",",$functionCallRet[2]);
    foreach($actorList as $actor) {
        if (trim($actor)!=$GLOBALS["PLAYER_NAME"])
            $actors[]=($actor);

    }

    foreach ($actors as $actorName) {
        
        $intimacyStatus=getIntimacyForActor($actorName);
        $intimacyStatus["sex_disposal"]+=15;
        $intimacyStatus["level"]=1;
        updateIntimacyForActor($actorName,$intimacyStatus);

    }

};

/******
ExtCmdStartThreesome
******/

$GLOBALS["F_NAMES"]["ExtCmdStartThreesome"]="StartThreesome";                           
$GLOBALS["F_TRANSLATIONS"]["ExtCmdStartThreesome"]="{$GLOBALS["HERIKA_NAME"]} starts a sex scene, (put partners in target property, comma separated)";
$GLOBALS["FUNCTIONS"][] =
[
    "name" => $GLOBALS["F_NAMES"]["ExtCmdStartThreesome"],
    "description" => $GLOBALS["F_TRANSLATIONS"]["ExtCmdStartThreesome"],
    "parameters" => [
        "type" => "object",
        "properties" => [
            "target" => [
                "type" => "string",
                "description" => "involved partners",
            ]
        ],
        "required" => ["target"],
    ]
]
;


$GLOBALS["FUNCRET"]["ExtCmdStartThreesome"]=function() {
    global $gameRequest,$returnFunction,$db,$request;
    // Probably we want to execute something, and put return value in $returnFunction[3] and $gameRequest[3];
    // We could overwrite also $request.
    error_log("Running ExtCmdStartThreesome FUNCRET, status {$gameRequest[3]}");

    // Participants
    $functionCallRet = explode("@", $gameRequest[3]); // Function returns here
    // Update intimacy status for al lparticipants

    // Actor who issued command
    $actors[]=npcNameToCodename($GLOBALS["HERIKA_NAME"]);;
    $actorList=explode(",",$functionCallRet[2]);
    foreach($actorList as $actor) {
        if (trim($actor)!=$GLOBALS["PLAYER_NAME"])
            $actors[]=($actor);

    }

    foreach ($actors as $actorName) {
        
        $intimacyStatus=getIntimacyForActor($actorName);
        $intimacyStatus["sex_disposal"]+=15;
        $intimacyStatus["level"]=1;
        updateIntimacyForActor($actorName,$intimacyStatus);

    }
    
    
    $GLOBALS["AVOID_LLM_CALL"]=true;

};


/******
ExtCmdStartTitfuck
******/

$GLOBALS["F_NAMES"]["ExtCmdStartTitfuck"]="StartBoobjob";                           
$GLOBALS["F_TRANSLATIONS"]["ExtCmdStartTitfuck"]="{$GLOBALS["HERIKA_NAME"]} starts a sex scene using her breasts. (aka  Titfuck,boobjob,titjob,paizuri ) (put partner in target poperty)";
$GLOBALS["FUNCTIONS"][] =
[
    "name" => $GLOBALS["F_NAMES"]["ExtCmdStartTitfuck"],
    "description" => $GLOBALS["F_TRANSLATIONS"]["ExtCmdStartTitfuck"],
    "parameters" => [
        "type" => "object",
        "properties" => [
            "target" => [
                "type" => "string",
                "description" => "Target NPC, Actor, or being",
            ]
        ],
        "required" => ["target"],
    ]
]
;


$GLOBALS["FUNCRET"]["ExtCmdStartTitfuck"]=function() {
    global $gameRequest,$returnFunction,$db,$request;
    // Probably we want to execute something, and put return value in $returnFunction[3] and $gameRequest[3];
    // We could overwrite also $request.
    error_log("Running ExtCmdStartTitfuck FUNCRET");

    // Participants
    $functionCallRet = explode("@", $gameRequest[3]); // Function returns here
    // Update intimacy status for al lparticipants

    // Actor who issued command
    $actors[]=npcNameToCodename($GLOBALS["HERIKA_NAME"]);;
    $actorList=explode(",",$functionCallRet[2]);
    foreach($actorList as $actor) {
        if (trim($actor)!=$GLOBALS["PLAYER_NAME"])
            $actors[]=($actor);

    }

    foreach ($actors as $actorName) {
        
        $intimacyStatus=getIntimacyForActor($actorName);
        $intimacyStatus["sex_disposal"]+=15;
        $intimacyStatus["level"]=1;
        updateIntimacyForActor($actorName,$intimacyStatus);

    }
    
    
    $GLOBALS["AVOID_LLM_CALL"]=true;

};


/******
ExtCmdStartSelfMasturbation
******/

$GLOBALS["F_NAMES"]["ExtCmdStartSelfMasturbation"]="StartSelfMasturbation";                           
$GLOBALS["F_TRANSLATIONS"]["ExtCmdStartSelfMasturbation"]="{$GLOBALS["HERIKA_NAME"]} starts self masturbation";
$GLOBALS["FUNCTIONS"][] =
[
    "name" => $GLOBALS["F_NAMES"]["ExtCmdStartSelfMasturbation"],
    "description" => $GLOBALS["F_TRANSLATIONS"]["ExtCmdStartSelfMasturbation"],
    "parameters" => [
        "type" => "object",
        "properties" => [
            "target" => [
                "type" => "string",
                "description" => "Target NPC, Actor, or being",
            ]
        ],
        "required" => ["target"],
    ]
]
;


$GLOBALS["FUNCRET"]["ExtCmdStartSelfMasturbation"]=function() {
    global $gameRequest,$returnFunction,$db,$request;
    // Probably we want to execute something, and put return value in $returnFunction[3] and $gameRequest[3];
    // We could overwrite also $request.
    error_log("Running ExtCmdStartSelfMasturbation FUNCRET");
   
    $intimacyStatus=getIntimacyForActor($GLOBALS["HERIKA_NAME"]);
    $intimacyStatus["sex_disposal"]+=5;
    $intimacyStatus["level"]=1;
    updateIntimacyForActor($GLOBALS["HERIKA_NAME"],$intimacyStatus);    

    $GLOBALS["AVOID_LLM_CALL"]=true;

};



/******
ExtCdmKiss
******/

$GLOBALS["F_NAMES"]["ExtCmdKiss"]="Kiss";                           
$GLOBALS["F_TRANSLATIONS"]["ExtCmdKiss"]="{$GLOBALS["HERIKA_NAME"]} kisses target actor";
$GLOBALS["FUNCTIONS"][] =
[
    "name" => $GLOBALS["F_NAMES"]["ExtCmdKiss"],
    "description" => $GLOBALS["F_TRANSLATIONS"]["ExtCmdKiss"],
    "parameters" => [
        "type" => "object",
        "properties" => [
            "target" => [
                "type" => "string",
                "description" => "Target NPC, Actor, or being",
            ]
        ],
        "required" => ["target"],
    ]
]
;


$GLOBALS["FUNCRET"]["ExtCmdKiss"]=function() {
    global $gameRequest,$returnFunction,$db,$request;
    // Probably we want to execute something, and put return value in $returnFunction[3] and $gameRequest[3];
    // We could overwrite also $request.
    error_log("Running ExtCmdKiss FUNCRET");
   
    $functionCallRet = explode("@", $gameRequest[3]); // Function returns here
    // Update intimacy status for al lparticipants

    // Actor who issued command
    $actors[]=($GLOBALS["HERIKA_NAME"]);;
    $actorList=explode(",",$functionCallRet[2]);
    foreach($actorList as $actor) {
        if (trim($actor)!=$GLOBALS["PLAYER_NAME"])
            $actors[]=($actor);

    }

    foreach ($actors as $actorName) {
        
        $intimacyStatus=getIntimacyForActor($actorName);
        $intimacyStatus["sex_disposal"]+=5;
        $intimacyStatus["level"]=0;
        updateIntimacyForActor($actorName,$intimacyStatus);

    }   

    
    $GLOBALS["AIAGENTNSFW_FORCE_STOP"]=false;

};


/******
ExtCmdPutOnClothes
******/

$GLOBALS["F_NAMES"]["ExtCmdPutOnClothes"]="PutOnClothes";                           
$GLOBALS["F_TRANSLATIONS"]["ExtCmdPutOnClothes"]="{$GLOBALS["HERIKA_NAME"]} puts clothes on";
$GLOBALS["FUNCTIONS"][] =
[
    "name" => $GLOBALS["F_NAMES"]["ExtCmdPutOnClothes"],
    "description" => $GLOBALS["F_TRANSLATIONS"]["ExtCmdPutOnClothes"],
    "parameters" => [
        "type" => "object",
        "properties" => [
            "target" => [
                "type" => "string",
                "description" => "Keep it blank",
            ]
        ],
        "required" => []
    ],
]
;


$GLOBALS["FUNCRET"]["ExtCmdPutOnClothes"]=function() {
    global $gameRequest,$returnFunction,$db,$request;
    // Probably we want to execute something, and put return value in $returnFunction[3] and $gameRequest[3];
    // We could overwrite also $request.
    error_log("Running ExtCmdPutOnClothes FUNCRET");
   
    $intimacyStatus=getIntimacyForActor($GLOBALS["HERIKA_NAME"]);
    $intimacyStatus["sex_disposal"]-=10;
    $intimacyStatus["level"]=0;
    $intimacyStatus["is_naked"]=0;
    updateIntimacyForActor($GLOBALS["HERIKA_NAME"],$intimacyStatus);    

    $GLOBALS["AVOID_LLM_CALL"]=true;



};


/******
ExtCmdSexCommand. This will be only be offere when NPC is on sexual scene
******/

$GLOBALS["F_NAMES"]["ExtCmdSexCommand"]="SexAction";                           
$GLOBALS["F_TRANSLATIONS"]["ExtCmdSexCommand"]="{$GLOBALS["HERIKA_NAME"]} performs a sexual action/position/practise";
$GLOBALS["FUNCTIONS"][] =
[
    "name" => $GLOBALS["F_NAMES"]["ExtCmdSexCommand"],
    "description" => $GLOBALS["F_TRANSLATIONS"]["ExtCmdSexCommand"],
    "parameters" => [
        "type" => "object",
        "properties" => [
            "target" => [
                "type" => "string",
                "description" => "blowjob,boobjob,analsex,vaginalsex",
            ]
        ],
        "required" => ["target"],
    ]
]
;


$GLOBALS["FUNCRET"]["ExtCmdSexCommand"]=function() {
    global $gameRequest,$returnFunction,$db,$request;
    // Probably we want to execute something, and put return value in $returnFunction[3] and $gameRequest[3];
    // We could overwrite also $request.
    error_log("Running ExtCmdSexCommand FUNCRET");
   
    $functionCallRet = explode("@", $gameRequest[3]); // Function returns here
    // Update intimacy status for al lparticipants

    

    
    $GLOBALS["AIAGENTNSFW_FORCE_STOP"]=false;

};



/******
ExtCmdConsumeSoul
******/

$GLOBALS["F_NAMES"]["ExtCmdConsumeSoul"]="RitualConsumeSoul";                           
$GLOBALS["F_TRANSLATIONS"]["ExtCmdConsumeSoul"]="{$GLOBALS["HERIKA_NAME"]} consumes soul of a captured foe (target)";
$GLOBALS["FUNCTIONS"][] =
[
    "name" => $GLOBALS["F_NAMES"]["ExtCmdConsumeSoul"],
    "description" => $GLOBALS["F_TRANSLATIONS"]["ExtCmdConsumeSoul"],
    "parameters" => [
        "type" => "object",
        "properties" => [
            "target" => [
                "type" => "string",
                "description" => "Victim, captured foe",
            ]
        ],
        "required" => ["target"]
    ],
]
;


$GLOBALS["FUNCRET"]["ExtCmdConsumeSoul"]=function() {
    global $gameRequest,$returnFunction,$db,$request;
    // Probably we want to execute something, and put return value in $returnFunction[3] and $gameRequest[3];
    // We could overwrite also $request.
    error_log("Running ExtCmdConsumeSoul FUNCRET");
   

    $GLOBALS["AIAGENTNSFW_FORCE_STOP"]=false;



};


// POST FILTER HOOK. Used for cleaning actions returned by LLM
$GLOBALS["action_post_process_fnct_ex"][]=function($actions) {

    foreach ($actions as $n=>$action) {
        
        $actionParts=explode("|",$action);
        $actionParts2=explode("@",$actionParts[2]);
        
        if (isset($actionParts2[1])) {
            // Parameter part 
            if ($actionParts2[0]=="ExtCmdStartSex") {
                // Lets polish the parameters
                $localtarget=$actionParts2[1];
                $mang1=explode(",",$localtarget);
                $mang2=explode(" and ",$mang1[0]);
                $mang3=explode("(",$mang2[0]);

                if (empty(trim($mang3[0])))
                    $mang4=$GLOBALS["PLAYER_NAME"];
                else
                    $mang4=FindClosestNPCName($mang3[0]);

                if (empty($mang4))
                    $mang4=$mang3[0];

                $actions[$n]="{$actionParts[0]}|{$actionParts[1]}|ExtCmdStartSex@{$mang4}";

                error_log("[ACTION POSTFILTER ExtCmdStartSex] $localtarget => {$mang3[0]} => $mang4");

            } else  if ($actionParts2[0]=="ExtCmdKiss") {
                // Lets polish the parameters
                $localtarget=$actionParts2[1];
                $mang1=explode(",",$localtarget);
                $mang2=explode(" and ",$mang1[0]);
                $mang3=explode("(",$mang2[0]);

                if (empty(trim($mang3[0])))
                    $mang4=trim($GLOBALS["PLAYER_NAME"]);
                else
                    $mang4=trim(FindClosestNPCName($mang3[0]));

                if (empty($mang4))
                    $mang4=$mang3[0];

                $actions[$n]="{$actionParts[0]}|{$actionParts[1]}|ExtCmdKiss@{$mang4}";

                // When looking for cooldown, Kiss cooldown will apply globally if player involved, if not, ony apply for involved actors.
                if ($mang4==trim($GLOBALS["PLAYER_NAME"]))
                    $GLOBALS["PATCH_ACTION_ALL_ACTORS"]="*";
                else
                    $GLOBALS["PATCH_ACTION_ALL_ACTORS"]="{$actionParts[0]},{$mang4}";

                error_log("[ACTION POSTFILTER ExtCmdKiss] $localtarget => {$mang3[0]} => $mang4. Cooldown will apply to {$GLOBALS["PATCH_ACTION_ALL_ACTORS"]}");
            
            } else  if ($actionParts2[0]=="ExtCmdStartBlowJob") {
                // Lets polish the parameters
                $localtarget=$actionParts2[1];
                $mang1=explode(",",$localtarget);
                $mang2=explode(" and ",$mang1[0]);
                $mang3=explode("(",$mang2[0]);

                if (empty(trim($mang3[0])))
                    $mang4=trim($GLOBALS["PLAYER_NAME"]);
                else
                    $mang4=trim(FindClosestNPCName($mang3[0]));

                if (empty($mang4))
                    $mang4=$mang3[0];


                    
                $actions[$n]="{$actionParts[0]}|{$actionParts[1]}|ExtCmdStartBlowJob@{$mang4}";

                error_log("[ACTION POSTFILTER ExtCmdStartBlowJob] $localtarget => {$mang3[0]} => $mang4");
            } else  if ($actionParts2[0]=="ExtCmdStartMassage") {
                // Lets polish the parameters
                $localtarget=$actionParts2[1];
                $mang1=explode(",",$localtarget);
                $mang2=explode(" and ",$mang1[0]);
                $mang3=explode("(",$mang2[0]);

                if (empty(trim($mang3[0])))
                    $mang4=trim($GLOBALS["PLAYER_NAME"]);
                else
                    $mang4=trim(FindClosestNPCName($mang3[0]));

                if (empty($mang4))
                    $mang4=$mang3[0];


                    
                $actions[$n]="{$actionParts[0]}|{$actionParts[1]}|ExtCmdStartMassage@{$mang4}";

                error_log("[ACTION POSTFILTER ExtCmdStartMassage] $localtarget => {$mang3[0]} => $mang4");
           
            } else  if ($actionParts2[0]=="ExtCmdStartThreesome") {
                // Lets polish the parameters
                $localtarget=$actionParts2[1];
                $mang1=explode(",",$localtarget);
                

                $sanitized=[];
                if (!is_array($mang1)) {
                    error_log("[ACTION POSTFILTER ExtCmdStartThreesome] Error $localtarget ");

                } else {
                    foreach ($mang1 as $participator) {
                        $sanitized[]=trim($participator);

                    }


                }
                $mang4=implode(",",$sanitized);
                $GLOBALS["PATCH_ACTION_ALL_ACTORS"]="*";//Improve this
                    
                $actions[$n]="{$actionParts[0]}|{$actionParts[1]}|ExtCmdStartThreesome@{$mang4}";

                error_log("[ACTION POSTFILTER ExtCmdStartThreesome] $localtarget => {$mang3[0]} => $mang4");
            }
        }
    }

    return $actions;
};

// $GLOBALS["COOLDOWNMAP"]["ExtCmdRemoveClothes"]=120/0.00864;
$GLOBALS["COOLDOWNMAP"]["ExtCmdStartMassage"]=300/0.00864;
$GLOBALS["COOLDOWNMAP"]["ExtCmdRemoveClothes"]=300/0.00864;
$GLOBALS["COOLDOWNMAP"]["ExtCmdPutOnClothes"]=100/0.00864;
$GLOBALS["COOLDOWNMAP"]["ExtCmdStartSelfMasturbation"]=300/0.00864;
$GLOBALS["COOLDOWNMAP"]["ExtCmdStartBlowJob"]=300/0.00864;
$GLOBALS["COOLDOWNMAP"]["ExtCmdStartSex"]=300/0.00864;
$GLOBALS["COOLDOWNMAP"]["ExtCmdStartThreesome"]=300/0.00864;
$GLOBALS["COOLDOWNMAP"]["ExtCmdStartTitfuck"]=300/0.00864;
$GLOBALS["COOLDOWNMAP"]["ExtCmdKiss"]=100/0.00864;
$GLOBALS["COOLDOWNMAP"]["ExtCdmHug"]=300/0.00864;
$GLOBALS["COOLDOWNMAP"]["ExtCmdSexCommand"]=15/0.00864;
$GLOBALS["COOLDOWNMAP"]["ExtCmdConsumeSoul"]=300/0.00864;


if (isset($GLOBALS["gameRequest"]) && $GLOBALS["gameRequest"][0]!="instruction" && $GLOBALS["gameRequest"][0]!="funcret") {
    $intimacyStatus=getIntimacyForActor($GLOBALS["HERIKA_NAME"]);

// Only offer this action if sex disposal is >20 for this actor
    if (isset($intimacyStatus["level"])&&$intimacyStatus["level"]==0) {

        if (isset($intimacyStatus["sex_disposal"])) {
            if ($intimacyStatus["sex_disposal"]>=1) {
                $GLOBALS["ENABLED_FUNCTIONS"][]="ExtCmdKiss";
            } 
            if ($intimacyStatus["sex_disposal"]>=5 && $intimacyStatus["is_naked"]<1) {
                $GLOBALS["ENABLED_FUNCTIONS"][]="ExtCmdRemoveClothes";
            } 
            if ($intimacyStatus["sex_disposal"]>=10) {
                $GLOBALS["ENABLED_FUNCTIONS"][]="ExtCmdStartMassage";
                $GLOBALS["ENABLED_FUNCTIONS"][]="ExtCmdStartSelfMasturbation";
            }
            if ($intimacyStatus["sex_disposal"]>=20) {
                $GLOBALS["ENABLED_FUNCTIONS"][]="ExtCmdStartBlowJob";
                $GLOBALS["ENABLED_FUNCTIONS"][]="ExtCmdStartSex";
                $GLOBALS["ENABLED_FUNCTIONS"][]="ExtCmdStartThreesome";
                $GLOBALS["ENABLED_FUNCTIONS"][]="ExtCmdStartTitfuck";
            }
            /*if (isset($intimacyStatus["is_naked"]) && $intimacyStatus["is_naked"]>1  ) {
                $GLOBALS["ENABLED_FUNCTIONS"][]="ExtCmdPutOnClothes";
            }*/
        }

        // Always available
        $GLOBALS["ENABLED_FUNCTIONS"][]="ExtCmdHug";
        $GLOBALS["ENABLED_FUNCTIONS"][]="ExtCmdPutOnClothes";
        $GLOBALS["ENABLED_FUNCTIONS"][]="ExtCmdConsumeSoul";

    } else if (isset($intimacyStatus["level"])&&$intimacyStatus["level"]>=1) {
        unset($GLOBALS["ENABLED_FUNCTIONS"]);
        $GLOBALS["ENABLED_FUNCTIONS"][]="ExtCmdSexCommand";

        $GLOBALS["HOOKS"]["JSON_TEMPLATE"][]=function() {
            $GLOBALS["responseTemplate"]["target"]="blowjob,boobjob,analsex,vaginalsex,handjob,frenchkissing,cunnilingus";
            $GLOBALS["structuredOutputTemplate"]["json_schema"]["schema"]["properties"]["target"]["description"]="blowjob,boobjob,analsex,vaginalsex,handjob,frenchkissing,cunnilingus";
        };


    }
} else {
    
    $GLOBALS["ENABLED_FUNCTIONS"][]="ExtCmdKiss";
    $GLOBALS["ENABLED_FUNCTIONS"][]="ExtCmdRemoveClothes";
    $GLOBALS["ENABLED_FUNCTIONS"][]="ExtCmdStartMassage";
    $GLOBALS["ENABLED_FUNCTIONS"][]="ExtCmdStartSelfMasturbation";
    $GLOBALS["ENABLED_FUNCTIONS"][]="ExtCmdStartBlowJob";
    $GLOBALS["ENABLED_FUNCTIONS"][]="ExtCmdStartSex";
    $GLOBALS["ENABLED_FUNCTIONS"][]="ExtCmdStartThreesome";
    $GLOBALS["ENABLED_FUNCTIONS"][]="ExtCmdStartTitfuck";
    $GLOBALS["ENABLED_FUNCTIONS"][]="ExtCmdHug";
    $GLOBALS["ENABLED_FUNCTIONS"][]="ExtCmdPutOnClothes";
    $GLOBALS["ENABLED_FUNCTIONS"][]="ExtCmdConsumeSoul";
    error_log("[AIAGENTNSFW] All functions available");
}



// Add this function to enabled array
$GLOBALS["IS_NPC"]=isset($GLOBALS["IS_NPC"])?$GLOBALS["IS_NPC"]:false;
if  (!$GLOBALS["IS_NPC"]) {
   
}

// Restore standard behaviour
if ($GLOBALS["HERIKA_NAME"]=="Character") {
    $GLOBALS["HERIKA_NAME"]="The Narrator";
}

?>