<?php

require_once __DIR__ . "/../../lib/chat_helper_functions.php";

/*
Post process info from lugin events:

    * ext_nsfw_sexcene
    * chatnf_sl_end
    * chatnf_sl_naked
    * chatnf_sl_climax
    * chatnf_sl_moan
    * ext_nsfw_action

*/

function processInfoSexScene()
{
    global $gameRequest;

    if ($gameRequest[0] == "ext_nsfw_sexcene") {
        // Parse info_sexscene data
        // Arrok Standing Foreplay/["Loving", "Standing", "LeadIn", "kissing", "Vaginal", "Penis", "Mouth", "Foreplay", "BBP", "Arrok", "FM", "MF"]/Arrok_StandingForeplay_A1_S1/Acto1Æctor2
        error_log("Rewriting info_sexscene data {$gameRequest[3]}");
        $infoSexSceneParts = explode("/", $gameRequest[3]);
        $sexSceneName      = $infoSexSceneParts[0];
        $sexTags           = explode(",", strtolower($infoSexSceneParts[1]));
        $sexStageName      = strtr($infoSexSceneParts[2], ["_A1" => ""]);
        $actorInfos        = array_slice($infoSexSceneParts, 3);

        $priority = $GLOBALS["PLAYER_NAME"];
        usort($actorInfos, function ($a, $b) use ($priority) {
            return ($a === $priority ? 1 : 0) + ($b === $priority ? -1 : 0);
        });

        $orderedActorList = [];

        foreach (array_reverse($actorInfos) as $actorinfo) {
            if (! empty($actorinfo)) {
                $orderedActorList[] = $actorinfo;
            }
        }

        error_log("[AIAGENTNSFW] Erotic Scene. Actors" . json_encode($orderedActorList));

        foreach ($actorInfos as $actor) {
            $intimacyStatus = getIntimacyForActor(($actor));
            if (in_array("idle", $sexTags)) {
                $intimacyStatus["level"] = 1;
            } else {
                $intimacyStatus["level"] = 2;
            }

            updateIntimacyForActor(($actor), $intimacyStatus);
        }

        error_log("Searching for description $sexStageName");

        // Fill descriptions

        $sceneDescription = findRowByFirstColumn(__DIR__ . "/ostim.csv", $sexStageName);
        if (! $sceneDescription) {
            $sceneDescription = "{actor0},{actor1},{actor2},{actor3},{actor4} are having an intimate moment";
        }
        $sceneDescriptionParsed = preg_replace_callback('/\{actor(\d+)\}/', function ($matches) use ($orderedActorList) {
            $index = (int) $matches[1];
            return $orderedActorList[$index] ?? $matches[0]; // fallback to original if key not found
        }, $sceneDescription);
        $cleanedSceneDesc = preg_replace('/\{actor\d+\}/', '', $sceneDescriptionParsed);

        // Rewrite data
        $GLOBALS["gameRequest"][3]         = "#INTIMATE SCENE: $cleanedSceneDesc. Scene tags:" . implode(",", $sexTags);
        $GLOBALS["AIAGENTNSFW_FORCE_STOP"] = true;
        logEvent($GLOBALS["gameRequest"]);

    } else if ($gameRequest[0] == "chatnf_sl_end") {
        // Set level to 0, this affects voice hook modifications

        error_log("[AIAGENT_NSFW] {$gameRequest[3]}");
        // Result
        $sceneResultParts = explode("/", $gameRequest[3]);
        $scoringPart      = array_slice($sceneResultParts, 1);
        $scoring          = [];
        foreach ($scoringPart as $part) {
            $actorResult = explode("@", $part);
            $scoring[]   = $actorResult[0] . " satisfaction score: " . $actorResult[1];
            updateIntimacyForActor($actorResult[0], ["level" => 0, "sex_disposal" => 10, "orgasmed" => false]);
        }
        $actor = $GLOBALS["HERIKA_NAME"];
        updateIntimacyForActor($actor, ["level" => 0, "sex_disposal" => 10, "orgasmed" => false]);

        // Overwrite prompt
        $GLOBALS["PROMPTS"]["chatnf_sl_end"]["player_request"] = ["The Narrator: " . implode(",", $scoring)];
        $GLOBALS["PATCH_PROMPT_ENFORCE_ACTIONS"]               = false;
        $GLOBALS["COMMAND_PROMPT_ENFORCE_ACTIONS"]             = "";

    } else if ($gameRequest[0] == "chatnf_sl_naked") {
        $actor                      = $GLOBALS["HERIKA_NAME"];
        $intimacyStatus             = getIntimacyForActor($actor);
        $intimacyStatus["is_naked"] = 2;
        updateIntimacyForActor($actor, $intimacyStatus);

    } else if ($gameRequest[0] == "chatnf_sl_climax") {

        $actor          = $GLOBALS["HERIKA_NAME"];
        $intimacyStatus = getIntimacyForActor($actor);

        if (isset($intimacyStatus["orgasm_generated"]) && $intimacyStatus["orgasm_generated"] && isset($intimacyStatus["orgasm_generated_text"])) {
            // We have used GASP. Let's use it.

            //echo "{$actor}|ScriptQueue|".trim(unmoodSentence($intimacyStatus["orgasm_generated_text"]))."////\r\n";

            if ($GLOBALS["AIAGENT_NSFW"]["USE_GASP"]) {
                echo "{$actor}|ScriptQueue|" . trim(unmoodSentence($intimacyStatus["orgasm_generated_text_original"])) . "////" . trim(unmoodSentence($intimacyStatus["orgasm_generated_text"])) . "\r\n";
            } else {
                echo "{$actor}|ScriptQueue|" . trim(unmoodSentence($intimacyStatus["orgasm_generated_text"])) . "////" . trim(unmoodSentence($intimacyStatus["orgasm_generated_text"])) . "\r\n";
                echo "{$actor}|ScriptQueue|" . trim(unmoodSentence($intimacyStatus["orgasm_generated_text_original"])) . "////" . trim(unmoodSentence($intimacyStatus["orgasm_generated_text_original"])) . "\r\n";
            }

            $intimacyStatus["orgasm_generated"]               = false;
            $intimacyStatus["orgasm_generated_text"]          = "";
            $intimacyStatus["orgasm_generated_text_original"] = "";

            updateIntimacyForActor($actor, $intimacyStatus);
            terminate();

        } else {
            // NPC will generate response via standard prompt
        }

    } else if ($gameRequest[0] == "chatnf_sl_moan") {

        $randomMoans = ["...Ahh ... Ohh..", "Yeah oh...yes", "... Mmmh ... ", "... Ahmmm ...", "..Ouch!... "];
        $moan        = $randomMoans[array_rand($randomMoans)];
        returnLines([$moan]);

        $actor=$GLOBALS["HERIKA_NAME"];
        $intimacyStatus=getIntimacyForActor($actor);
        if (!isset($intimacyStatus["orgasm_generated"]) || $intimacyStatus["orgasm_generated"]==false) {
            generateClimaxSpeech();
        
        } else {
            error_log("Orgams sound already generated");

        }

        //logEvent($GLOBALS["gameRequest"]); Don't log, chat will do
        terminate();

    } else if ($gameRequest[0] == "ext_nsfw_action") {

        // Just log the information

        $GLOBALS["AIAGENTNSFW_FORCE_STOP"] = true;
        logEvent($GLOBALS["gameRequest"]);

    }

}

function getIntimacyForActor($actorName)
{

    $npcManager = new NpcMaster();
    $npcData    = $npcManager->getByName($actorName);
    if (! $npcData) {
        $npcData = $npcManager->getByName(ucFirst(strtolower($actorName)));
    }
    if (isset($npcData["extended_data"])) {
        $extended = json_decode($npcData["extended_data"], true);
    } else {
        $extended = [];
    }

    if (isset($extended["aiagent_nsfw_intimacy_data"]) && isNonEmptyArray($extended["aiagent_nsfw_intimacy_data"])) {
        $intimacyStatus = $extended["aiagent_nsfw_intimacy_data"];

    } else {
        $intimacyStatus = ["level" => 0, "sex_disposal" => 0];
    }

    return $intimacyStatus;
}

/*
 Custom SpeechStyle prompt to use when in intimacy scene
 extended_data->sex_speech_style
*/

function setSexSpeechStyle($actorName)
{

    $npcManager = new NpcMaster();
    $npcData    = $npcManager->getByName($actorName);
    if (! $npcData) {
        $npcData = $npcManager->getByName(ucFirst(strtolower($actorName)));
    }
    if (isset($npcData["extended_data"])) {
        $extended = json_decode($npcData["extended_data"], true);
    } else {
        $extended = [];
    }

    if (isset($extended["sex_speech_style"]) && ! empty($extended["sex_speech_style"])) {
        $GLOBALS["HERIKA_SPEECHSTYLE"] .= "\n#Sex Expressions\n" . $extended["sex_speech_style"];

    }
}

/*
Custom prompt added when in intimacy scene
extended_data->sex_prompt
*/

function setSexPrompt($actorName)
{

    $npcManager = new NpcMaster();
    $npcData    = $npcManager->getByName($actorName);
    if (! $npcData) {
        $npcData = $npcManager->getByName(ucFirst(strtolower($actorName)));
    }

    if (isset($npcData["extended_data"])) {
        $extended = json_decode($npcData["extended_data"], true);
    } else {
        $extended = [];
    }

    if (isset($extended["sex_prompt"]) && ! empty($extended["sex_prompt"])) {
        $GLOBALS["HERIKA_PERSONALITY"] .= "\n#Personality (sex scenes)\n" . $extended["sex_prompt"];

    }
}

function updateIntimacyForActor($actorName, $idata)
{

    error_log("[AIAGENTNSFW] Updating intimacy for $actorName. " . json_encode($idata));

    $currentIntimacy = getIntimacyForActor($actorName);
    $npcManager      = new NpcMaster();
    $npcData         = $npcManager->getByName($actorName);

    if (! $npcData) {
        $npcData = $npcManager->getByName(ucFirst(strtolower($actorName)));
    }

    $extended = json_decode($npcData["extended_data"], true);

    if (isset($extended["aiagent_nsfw_intimacy_data"]) && isNonEmptyArray($extended["aiagent_nsfw_intimacy_data"])) {
        $extended["aiagent_nsfw_intimacy_data"] = array_merge($extended["aiagent_nsfw_intimacy_data"], $idata);
    } else {
        $extended["aiagent_nsfw_intimacy_data"] = $idata;
    }

    $npcData["extended_data"] = json_encode($extended);
    $npcManager->updateByArray($npcData);

}

function saveAllDisposals()
{
    error_log("[AIAGENT NSFW] saveAllDisposals is deprecated");
    return;
    audit_log(__FILE__ . " [AIAGENT NSFW]  " . __LINE__);
    $data       = $GLOBALS["db"]->fetchAll("select * from conf_opts where id like '%_intimacy'");
    $datatoSave = [];
    foreach ($data as $rowactor) {
        $datatoSave[] = $rowactor;
    }

    $GLOBALS["db"]->upsertRowOnConflict(
        "conf_opts",
        [
            "id"    => "aiagent_nsfw_intimacy",
            "value" => json_encode($datatoSave),
        ],
        'id'
    );
    audit_log(__FILE__ . " [AIAGENT NSFW]  " . __LINE__);

}

function loadAllDisposals()
{
    error_log("[AIAGENT NSFW] loadAllDisposals is deprecated");
    return;

    audit_log(__FILE__ . " [AIAGENT NSFW]  " . __LINE__);

    $GLOBALS["db"]->execQuery("delete  from conf_opts where id like '%_intimacy'");

    $savedData     = $GLOBALS["db"]->fetchOne("select value from conf_opts where id like 'aiagent_nsfw_intimacy'");
    $savedDataFull = [];

    if ($savedData) {
        $savedDataFull = json_decode($savedData["value"], true);

    }

    if (is_array($savedDataFull)) {
        foreach ($savedDataFull as $actorIntimacyData) {
            $GLOBALS["db"]->upsertRowOnConflict(
                "conf_opts",
                [
                    "id"    => $actorIntimacyData["id"],
                    "value" => $actorIntimacyData["value"],
                ],
                'id'
            );
        }
    }

    audit_log(__FILE__ . " [AIAGENT NSFW]  " . __LINE__);

}

/*
Calculates intimacy disposal based on moods issued when talking.
*/

function getSexDisposalFromMood($actorName, $currentGamets)
{

    $playerNameE = $GLOBALS["db"]->escape($GLOBALS["PLAYER_NAME"]);
    $actorNameE  = $GLOBALS["db"]->escape($actorName);

    $sdQuery = "
    WITH mood_scores AS (
    SELECT
        speaker,
        listener,
        mood,
        CASE
            WHEN mood = 'playful' THEN 1
            WHEN mood = 'seductive' THEN 1
            WHEN mood = 'sexy' THEN 1
            WHEN mood = 'aroused' THEN 1
            WHEN mood = 'sensual' THEN 1
            WHEN mood = 'flirty' THEN 1
            WHEN mood = 'lovely' THEN 1
            WHEN mood = 'loving' THEN 1
            WHEN mood = 'drunk' THEN 1
            WHEN mood = 'tipsy' THEN 1
            WHEN mood = 'irritated' THEN -2
            WHEN mood = 'grumpy' THEN -1
            ELSE 0
        END AS sex_disposal_speech,gamets
    FROM public.moods_issued
    WHERE mood IS NOT NULL
    and speaker like '$actorNameE'
    and (listener like '$playerNameE' or 1=1)
    and ($currentGamets-gamets)<(7/ 0.0000024)
    order by gamets DESC
    limit 100
)
SELECT
    speaker,
    listener,
    SUM(sex_disposal_speech) AS total_sentiment,
    COUNT(*) AS interactions,
    ROUND(AVG(sex_disposal_speech), 2) AS avg_sentiment,
    MIN(gamets) AS gamets_from,
    MAX(gamets) AS gamets_to
FROM mood_scores
GROUP BY speaker, listener
ORDER BY total_sentiment DESC";

    $statData = $GLOBALS["db"]->fetchOne($sdQuery);
    error_log("[AIGANET NSFW] Mood speech analisys: " . json_encode($statData));
    if (isNonEmptyArray($statData)) {
        return $statData["avg_sentiment"];

    }

    return 0;

}

function getLastIssuedMood($actorName, $currentGamets, $timeFrameLimit = 5)
{

    $playerNameE = $GLOBALS["db"]->escape($GLOBALS["PLAYER_NAME"]);
    $actorNameE  = $GLOBALS["db"]->escape($actorName);

    $sdQuery = "
    select *
    FROM public.moods_issued
    WHERE mood IS NOT NULL
    and speaker like '$actorNameE'
    and ($currentGamets-gamets)<(1/ 0.0000024*$timeFrameLimit)
    order by gamets DESC
    limit 1";
    $statData = $GLOBALS["db"]->fetchOne($sdQuery);
    error_log("Last mood: " . json_encode($statData) . "<$sdQuery>");
    if (isNonEmptyArray($statData)) {
        return $statData["mood"];

    }

    return "";

}

function findRowByFirstColumn($filePath, $searchValue)
{
    if (($fh = fopen($filePath, 'r')) === false) {
        return null;
    }

    $header = fgetcsv($fh, 0, ",", '"', '\\'); // Read and skip header
    while (($row = fgetcsv($fh, 0, ",", '"', '\\')) !== false) {

        if (trim(mb_strtolower($row[0])) === trim(mb_strtolower($searchValue))) {
            error_log("Found description for $searchValue!");
            fclose($fh);
            return $row[1];
        }
    }

    fclose($fh);
    return null; // No match found
}

// Process info from  info_sexscene and chatnf_sl_end event. Will rewrite context info entry.

function gasper($original_speech, $moan, $sourceaudio, $sourcevoiceaudio)
{

    //based on $moan
    $moanfile = "/opt/ai/debian-stable/opt/ai/seed-vc/gasper/cough.wav";
    //$moanTranscription="Ahhm Ahm Ahm mmm Ah Ah mm ";

    $moanLibrary = [

        ["transcription" => "Ahhm Ahm ahms", "file" => "/opt/ai/debian-stable/opt/ai/seed-vc/gasper/library/ClimaxD1.wav"],
        ["transcription" => "Ahhm Ahm ahms", "file" => "/opt/ai/debian-stable/opt/ai/seed-vc/gasper/library/ClimaxE1.wav"],
        ["transcription" => "Ahhm Ahm ahms", "file" => "/opt/ai/debian-stable/opt/ai/seed-vc/gasper/library/ClimaxE2.wav"],
        ["transcription" => "Ahhm Ahm ahms", "file" => "/opt/ai/debian-stable/opt/ai/seed-vc/gasper/library/ClimaxE4.wav"],
        ["transcription" => "Ahhm Ahm ahms", "file" => "/opt/ai/debian-stable/opt/ai/seed-vc/gasper/library/ClimaxE5.wav"],
        ["transcription" => "Ahhm Ahm ahms", "file" => "/opt/ai/debian-stable/opt/ai/seed-vc/gasper/library/ClimaxE6.wav"],
        ["transcription" => "Ahhm Ahm ahms", "file" => "/opt/ai/debian-stable/opt/ai/seed-vc/gasper/library/ClimaxF1.wav"],
        ["transcription" => "Ahhm Ahm ahms", "file" => "/opt/ai/debian-stable/opt/ai/seed-vc/gasper/library/ClimaxG1.wav"],
        ["transcription" => "Ahhm Ahm ahms", "file" => "/opt/ai/debian-stable/opt/ai/seed-vc/gasper/library/ClimaxA1.wav"],
        ["transcription" => true, "file" => "/opt/ai/debian-stable/opt/ai/seed-vc/gasper/library/ClimaxA2.wav"],
        ["transcription" => true, "file" => "/opt/ai/debian-stable/opt/ai/seed-vc/gasper/library/ClimaxA3.wav"],

    ];
    $selectedIndex = rand(0, sizeof($moanLibrary) - 1);
    //$selectedIndex=sizeof($moanLibrary)-1;

    if (isset($GLOBALS["AIAGENT_NSFW"]["USE_GASP"]) && $GLOBALS["AIAGENT_NSFW"]["USE_GASP"]) {
        $tempfile = "/tmp/" . uniqid() . ".wav";
        $command  = "/usr/local/bin/gasp $sourceaudio {$moanLibrary[$selectedIndex]["file"]} \"$original_speech\" $tempfile";

        $output = shell_exec($command);
        error_log("[GASP] Command output: " . $output);
        error_log("[GASP] Source {$moanLibrary[$selectedIndex]["file"]}, Out file: " . $tempfile);

        // Step 1: Remove double dots
        $input = str_replace("..", " ", trim($output));

        // Step 2: Define substitution patterns (order matters: longest to shortest)
        $patterns = [
            '/AAaa/' => 'AAah',
            '/aaAA/' => 'aaAH',
            '/AAAA/' => 'AAAA', // in case you want to map that differently
            '/AA/'   => 'AH',
            '/aaaa/' => 'Aaah',
            '/aa/'   => 'Ah',
        ];

        // Step 3: Apply replacements
        $output = preg_replace(array_keys($patterns), array_values($patterns), $input);
        $output .= "  $original_speech";

        $finalPseudoPhonetic = $output;

        $finalPseudoPhonetic = trim(unmoodSentence($finalPseudoPhonetic));
    } else {
        $tempfile    = "/tmp/" . uniqid() . ".wav";
        $sourceaudio =
        $command     = "/usr/local/bin/gasp /opt/ai/debian-stable/opt/ai/seed-vc/gasper/library/silence.wav {$moanLibrary[$selectedIndex]["file"]} \"$original_speech\" $tempfile";

        $output = shell_exec($command);
        error_log("[GASP] Command output: " . $output);
        error_log("[GASP] Out file: " . $tempfile);

        // Step 1: Remove double dots
        $input = str_replace("..", " ", trim($output));

        // Step 2: Define substitution patterns (order matters: longest to shortest)
        $patterns = [
            '/AAaa/' => 'AAah',
            '/aaAA/' => 'aaAH',
            '/AAAA/' => 'AAAA', // in case you want to map that differently
            '/AA/'   => 'AH',
            '/aaaa/' => 'Aaah',
            '/aa/'   => 'Ah',
        ];

        // Step 3: Apply replacements
        $output = preg_replace(array_keys($patterns), array_values($patterns), $input);

        $finalPseudoPhonetic = $output;

        $finalPseudoPhonetic = trim(unmoodSentence($finalPseudoPhonetic));

    }

    error_log("[GASP] finalPseudoPhonetic: $finalPseudoPhonetic");

    if (! file_exists($tempfile)) {
        error_log("[GASP] Source audio file not found: $tempfile");
    }
    if (! file_exists($sourcevoiceaudio)) {
        error_log("[GASP] Reference audio file not found: $sourcevoiceaudio");
    }

    $sourceAudioPath    = realpath($tempfile);
    $referenceAudioPath = realpath($sourcevoiceaudio);

    if (! $sourceAudioPath || ! $referenceAudioPath) {
        error_log("[GASP] File path resolution failed.");
    }

    // Check if files actually exist and are readable
    if (! file_exists($sourceAudioPath) || ! is_readable($sourceAudioPath)) {
        error_log("[GASP] Source audio file not accessible: " . $sourceAudioPath);
    }
    if (! file_exists($referenceAudioPath) || ! is_readable($referenceAudioPath)) {
        error_log("[GASP] Reference audio file not accessible: " . $referenceAudioPath);
    }

    $tempResfile = $GLOBALS["ENGINE_PATH"] . "/soundcache/" . md5($finalPseudoPhonetic) . ".wav";

    $original_speech_cleaned = trim(unmoodSentence($original_speech));
    $tempResfile2            = $GLOBALS["ENGINE_PATH"] . "/soundcache/" . md5($original_speech_cleaned) . ".wav";

    copy($tempfile, $tempResfile);
    //copy($tempfile,$tempResfile2);

    error_log("[GASP] $tempResfile saved successfully.");

    return $finalPseudoPhonetic;

}

//  Guess if player is naked
function playerIsNaked()
{

    audit_log(__FILE__ . " [AIAGENT NSFW]  " . __LINE__);

    $val = $GLOBALS["db"]->fetchOne("select value from conf_opts where id='player_naked'");
    if ($val["value"] == 1) {
        return true;

    }

    return false;
}


function generateClimaxSpeech() {

    $actor=$GLOBALS["HERIKA_NAME"];
    $intimacyStatus=getIntimacyForActor($actor);

    error_log("[GASP] $actor");

    if (!isset($intimacyStatus["orgasm_generated"]) || $intimacyStatus["orgasm_generated"]==false) {
        
        error_log("Generating gasped orgasm sound");
        
        
        $historyData="";
        $lastPlace="";
        $lastListener="";
        $lastDateTime = "";

        // Determine how much context history to use for dynamic profiles
        $dynamicProfileContextHistory = 50; // Default value
        if (isset($GLOBALS["CONTEXT_HISTORY_DYNAMIC_PROFILE"]) && $GLOBALS["CONTEXT_HISTORY_DYNAMIC_PROFILE"] > 0) {
            $dynamicProfileContextHistory = $GLOBALS["CONTEXT_HISTORY_DYNAMIC_PROFILE"];
        } elseif (isset($GLOBALS["CONTEXT_HISTORY"]) && $GLOBALS["CONTEXT_HISTORY"] > 0) {
            $dynamicProfileContextHistory = $GLOBALS["CONTEXT_HISTORY"];
        }
        
        foreach (json_decode(DataSpeechJournal($GLOBALS["HERIKA_NAME"], $dynamicProfileContextHistory),true) as $element) {
          if ($element["listener"]=="The Narrator") {
                continue;
          }
          if ($lastListener!=$element["listener"]) {
            
            $listener=" (talking to {$element["listener"]})";
            $lastListener=$element["listener"];
          }
          else
            $listener="";
      
          if ($lastPlace!=$element["location"]){
            $place=" (at {$element["location"]})";
            $lastPlace=$element["location"];
          }
          else
            $place="";

            if ($lastDateTime != substr($element["sk_date"], 0, 15)) {
                $date = substr($element["sk_date"], 0, 10);
                $time = substr($element["sk_date"], 11);
                $dateTime = "(on date {$date} at {$time})";
                $lastDateTime = substr($element["sk_date"], 0, 15); 
            } else {
                $dateTime = "";
            }
      
          $historyData.=trim("{$element["speaker"]}:".trim($element["speech"])." $listener $place $dateTime").PHP_EOL;
          
        }
        
        $partyConf=DataGetCurrentPartyConf();
		$partyConfA=json_decode($partyConf,true);
		Logger::debug($partyConf);
		// Use the global DYNAMIC_PROMPT
        $updateProfilePrompt = "Write a VERY SHORT sentence (3/4 words) for {$GLOBALS["HERIKA_NAME"]} 
        to say when she/he reaches Climax based on Dialogue history, and {$GLOBALS["PLAYER_NAME"]} requests,stuttering. Read  {$GLOBALS["HERIKA_NAME"]} character sheet to figure out her sexual preferences.";
		// Database Prompt (Dynamic Profile Head)    
		$head[]   = ["role"	=> "system", "content"	=> "You are an NSFW writer.  ", ];
		
		// Use centralized function from data_functions.php
		$currentDynamicProfile = buildDynamicProfileDisplay();
        
		$prompt[] = ["role" => "user", "content" => "Current character profile you are generating content for:\n" . "Character name:\n"  . $GLOBALS["HERIKA_NAME"] . "\nCharacter static biography:\n" . $GLOBALS["HERIKA_PERS"] . "\n" . $currentDynamicProfile];
        $prompt[] = ["role"	=> "user", "content"	=> "* Dialogue history:\n" .$historyData ];
		$prompt[] = ["role"=> "user", "content"	=> $updateProfilePrompt, ];
		$contextData       = array_merge($head, $prompt);

         if (isset($GLOBALS["CHIM_CORE_CURRENT_CONNECTOR_DATA"])) {
            $connector=new LLMConnector();
            $connectionHandler = $connector->getConnector($GLOBALS["CHIM_CORE_CURRENT_CONNECTOR_DATA"]);
            error_log("[CORE SYSTEM] Using new profile system {$GLOBALS["CHIM_CORE_CURRENT_CONNECTOR_DATA"]["driver"]}/{$GLOBALS["CHIM_CORE_CURRENT_CONNECTOR_DATA"]["model"]}");
        } else {
            error_log("No connector defined");
            return;
        }

        $GLOBALS["FORCE_MAX_TOKENS"]=50;
		$buffer=$connectionHandler->fast_request($contextData, ["max_tokens"=>50]);
       
        $original_speech=" ... Ohh .. ".(strtr(trim($buffer),['"'=>'',"{$GLOBALS["HERIKA_NAME"]}:"=>""]));


        $GLOBALS["PATCH_DONT_STORE_SPEECH_ON_DB"]=true;
        unset($GLOBALS["HOOKS"]["XTTS_TEXTMODIFIER"]);

        $GLOBALS["HOOKS"]["XTTS_TEXTMODIFIER"][]=function($text) {
            
            $randomStrings = ["  ", "  "];
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
            error_log("Applying text modifier for XTTS (speed=>0.6) $text => $result ".__FILE__);
    
            xtts_fastapi_settings(["temperature"=>1,"speed"=>0.6,"enable_text_splitting"=>false,"top_p"=> 1,"top_k"=>100],true);
            return $result;
    
        };
        returnLines([$original_speech],false);
        $generatedFile=end($GLOBALS["TRACK"]["FILES_GENERATED"]);

        $intimacyStatus["orgasm_generated"]=true;
        $intimacyStatus["orgasm_generated_text"]=$original_speech;
        $intimacyStatus["orgasm_generated_text_original"]=trim(unmoodSentence($original_speech));

        updateIntimacyForActor($actor,$intimacyStatus);
    } else {
        error_log("Orgams sound already generated");

    }
}