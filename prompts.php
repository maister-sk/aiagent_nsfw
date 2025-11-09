<?php 

$GLOBALS["PROMPTS"]["chatnf_sl"]=
[
    "cue"=>[
        "(Focus on intimate scene participants,moans and gasps,SHORT speech, explicit words) {$GLOBALS["TEMPLATE_DIALOG"]}",
        "(Focus on intimate scene description,moans and gasps,SHORT speech, explicit words) {$GLOBALS["TEMPLATE_DIALOG"]}",
        "(explain pleasure,moans and gasps,SHORT speech, explicit words) {$GLOBALS["TEMPLATE_DIALOG"]}",
        "(give a compliment,moans and gasps,SHORT speech, explicit words) {$GLOBALS["TEMPLATE_DIALOG"]}",
        "(moans and gasps,short speech, explicit words) {$GLOBALS["TEMPLATE_DIALOG"]}"
    ], // give way to
    "player_request"=>["The Narrator: "]  //requirement
];

$GLOBALS["PROMPTS"]["chatnf_sl_nr"]=
[
    "cue"=>[
        "(Focus on intimate scene participants) {$GLOBALS["TEMPLATE_DIALOG"]}",
        "(Focus on scene description) {$GLOBALS["TEMPLATE_DIALOG"]}",
        "(explain pleasure) {$GLOBALS["TEMPLATE_DIALOG"]}",
        "(give a compliment) {$GLOBALS["TEMPLATE_DIALOG"]}",
        "(moans and gasps) {$GLOBALS["TEMPLATE_DIALOG"]}"
    ], // give way to
    "player_request"=>[$GLOBALS["gameRequest"][3]]  //requirement
];

$GLOBALS["PROMPTS"]["chatnf_sl_climax"]=
[
    "cue"=>["({$GLOBALS["HERIKA_NAME"]} is orgasming!!!! CLIMAX!, Focus on intimate scene participants, {$GLOBALS["HERIKA_NAME"]} SHOUTS using moans and groans )  VERY SHORT sentence (3 words) {$GLOBALS["TEMPLATE_DIALOG"]}"], // give way to
    "player_request"=>"YEEAH!"  //requirement
];

// player_request prompt will be overwrriten
$GLOBALS["PROMPTS"]["chatnf_sl_end"]=
[
    "cue"=>[
        "({$GLOBALS["HERIKA_NAME"]} talks about intimate scene result) {$GLOBALS["TEMPLATE_DIALOG"]}",
        "({$GLOBALS["HERIKA_NAME"]} talks about best sex moment) {$GLOBALS["TEMPLATE_DIALOG"]}",
        "({$GLOBALS["HERIKA_NAME"]} talks about something people usually talks after sex) {$GLOBALS["TEMPLATE_DIALOG"]}",
    ]
];

// ExtCmdStartSelfMasturbation will generate a LLM call.

$GLOBALS["PROMPTS"]["afterfunc"]["cue"]["ExtCmdStartSelfMasturbation"]="{$GLOBALS["HERIKA_NAME"]} moans about being aroused, and starts self masturbation. {$GLOBALS["TEMPLATE_DIALOG"]}";

 
?>