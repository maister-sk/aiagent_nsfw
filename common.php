<?php 

require_once(__DIR__."/../../lib/chat_helper_functions.php");

function getIntimacyForActor($actorName) {

    $npcManager=new NpcMaster();
    $npcData=$npcManager->getByName($actorName);
    if (!$npcData) {
        $npcData=$npcManager->getByName(ucFirst(strtolower($actorName)));
    }
    if (isset($npcData["extended_data"]))
        $extended=json_decode($npcData["extended_data"],true);
    else
        $extended=[];

    if (isset($extended["aiagent_nsfw_intimacy_data"]) && isNonEmptyArray($extended["aiagent_nsfw_intimacy_data"])) {
        $intimacyStatus=$extended["aiagent_nsfw_intimacy_data"];
       
    } else {
        $intimacyStatus=["level"=>0,"sex_disposal"=>0];
    }

    //$extended["aiagent_nsfw_intimacy_data"]=$intimacyStatus;
    //$npcData["extended_data"]=json_encode($extended);
    //$npcManager->updateByArray($npcData);

    /*
    $codeName=npcNameToCodename($actorName);
    $propName = $GLOBALS["db"]->escape("{$codeName}_intimacy");
    $intimacyStatusData = $GLOBALS["db"]->fetchOne("select value from conf_opts where id='$propName'");
    if (isNonEmptyArray($intimacyStatusData)) {
        $intimacyStatus=json_decode($intimacyStatusData["value"],true);
       
    } else 
        $intimacyStatus=["level"=>0,"sex_disposal"=>0];
    */
    return $intimacyStatus;
}

function setSexSpeechStyle($actorName) {

    $npcManager=new NpcMaster();
    $npcData=$npcManager->getByName($actorName);
     if (!$npcData) {
        $npcData=$npcManager->getByName(ucFirst(strtolower($actorName)));
    }
    if (isset($npcData["extended_data"]))
        $extended=json_decode($npcData["extended_data"],true);
    else
        $extended=[];

    if (isset($extended["sex_speech_style"]) && !empty($extended["sex_speech_style"])) {
        $GLOBALS["HERIKA_SPEECHSTYLE"].="\n".$extended["sex_speech_style"];
       
    } 
}

function setSexPrompt($actorName) {

    $npcManager=new NpcMaster();
    $npcData=$npcManager->getByName($actorName);
    if (!$npcData) {
        $npcData=$npcManager->getByName(ucFirst(strtolower($actorName)));
    }

    if (isset($npcData["extended_data"]))
        $extended=json_decode($npcData["extended_data"],true);
    else
        $extended=[];

    if (isset($extended["sex_prompt"]) && !empty($extended["sex_prompt"])) {
        $GLOBALS["HERIKA_PERSONALITY"].="\n".$extended["sex_prompt"];
       
    } 
}

function updateIntimacyForActor($actorName,$idata) {

    error_log("[AIAGENTNSFW] Updating intimacy for $actorName. ".json_encode($idata));
    
    $currentIntimacy=getIntimacyForActor($actorName);
    $npcManager=new NpcMaster();
    $npcData=$npcManager->getByName($actorName);

    if (!$npcData) {
        $npcData=$npcManager->getByName(ucFirst(strtolower($actorName)));
    }
    
    $extended=json_decode($npcData["extended_data"],true);

    if (isset($extended["aiagent_nsfw_intimacy_data"]) && isNonEmptyArray($extended["aiagent_nsfw_intimacy_data"])) 
        $extended["aiagent_nsfw_intimacy_data"]=array_merge($extended["aiagent_nsfw_intimacy_data"],$idata);
    else
        $extended["aiagent_nsfw_intimacy_data"]=$idata;
    
    $npcData["extended_data"]=json_encode($extended);
    $npcManager->updateByArray($npcData);

    /*$codeName=npcNameToCodename($actorName);

    $currentIntimacy=getIntimacyForActor($actorName);
    $intimacyStatusFinal=array_merge($currentIntimacy,$idata);
    $propName = $GLOBALS["db"]->escape("{$codeName}_intimacy");
    error_log("Updating intimacy for $codeName: old:".json_encode($currentIntimacy)." new:".json_encode($intimacyStatusFinal));
    $GLOBALS["db"]->upsertRowOnConflict(
        "conf_opts",
        array(
            "id"    => $propName,
            "value" => json_encode($intimacyStatusFinal)
        ),
        'id'
    );
    */
}

function saveAllDisposals() {
    
    audit_log(__FILE__." [AIAGENT NSFW]  ".__LINE__);
    $data=$GLOBALS["db"]->fetchAll("select * from conf_opts where id like '%_intimacy'");
    $datatoSave=[];
    foreach ($data as $rowactor) {
        $datatoSave[]=$rowactor;
    }

    $GLOBALS["db"]->upsertRowOnConflict(
        "conf_opts",
        array(
            "id"    => "aiagent_nsfw_intimacy",
            "value" => json_encode($datatoSave)
        ),
        'id'
    );
    audit_log(__FILE__." [AIAGENT NSFW]  ".__LINE__);

}

function loadAllDisposals() {
    
    audit_log(__FILE__." [AIAGENT NSFW]  ".__LINE__);

    $GLOBALS["db"]->execQuery("delete  from conf_opts where id like '%_intimacy'");

    $savedData=$GLOBALS["db"]->fetchOne("select value from conf_opts where id like 'aiagent_nsfw_intimacy'");
    $savedDataFull=[];

    if ($savedData) {
        $savedDataFull=json_decode($savedData["value"],true);

    }
    
    if (is_array($savedDataFull)) {
        foreach ($savedDataFull as $actorIntimacyData) {
            $GLOBALS["db"]->upsertRowOnConflict(
                "conf_opts",
                array(
                    "id"    => $actorIntimacyData["id"],
                    "value" => $actorIntimacyData["value"]
                ),
                'id'
            );
        }
    }

   
    audit_log(__FILE__." [AIAGENT NSFW]  ".__LINE__);

}

function getSexDisposalFromMood($actorName,$currentGamets) {

    
    $playerNameE=$GLOBALS["db"]->escape($GLOBALS["PLAYER_NAME"]);
    $actorNameE=$GLOBALS["db"]->escape($actorName);

    $sdQuery="
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

    $statData=$GLOBALS["db"]->fetchOne($sdQuery);
    error_log("[AIGANET NSFW] Mood speech analisys: ".json_encode($statData));
    if (isNonEmptyArray($statData)) {
        return $statData["avg_sentiment"];

    }

   
    return 0;

}

function getLastIssuedMood($actorName,$currentGamets,$timeFrameLimit=5) {

    $playerNameE=$GLOBALS["db"]->escape($GLOBALS["PLAYER_NAME"]);
    $actorNameE=$GLOBALS["db"]->escape($actorName);

    $sdQuery="
    select *
    FROM public.moods_issued
    WHERE mood IS NOT NULL
    and speaker like '$actorNameE'
    and ($currentGamets-gamets)<(1/ 0.0000024*$timeFrameLimit)
    order by gamets DESC
    limit 1";
    $statData=$GLOBALS["db"]->fetchOne($sdQuery);
    error_log("Last mood: ".json_encode($statData). "<$sdQuery>");
    if (isNonEmptyArray($statData)) {
        return $statData["mood"];

    }

    
    return "";

}


function findRowByFirstColumn($filePath, $searchValue) {
    if (($fh = fopen($filePath, 'r')) === false) return null;

    $header = fgetcsv($fh, 0, ",",'"','\\'); // Read and skip header
    while (($row = fgetcsv($fh, 0, ",",'"','\\')) !== false) {
        
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

function processInfoSexScene() {
    global $gameRequest;

    if ($gameRequest[0]=="ext_nsfw_sexcene") {
        // Parse info_sexscene data 
        // Arrok Standing Foreplay/["Loving", "Standing", "LeadIn", "kissing", "Vaginal", "Penis", "Mouth", "Foreplay", "BBP", "Arrok", "FM", "MF"]/Arrok_StandingForeplay_A1_S1/Acto1Æctor2
        error_log("Rewriting info_sexscene data {$gameRequest[3]}");
        $infoSexSceneParts=explode("/",$gameRequest[3]);
        $sexSceneName=$infoSexSceneParts[0];
        $sexTags=explode(",",strtolower($infoSexSceneParts[1]));
        $sexStageName=strtr($infoSexSceneParts[2],["_A1"=>""]);
        $actorInfos=array_slice($infoSexSceneParts, 3);
       
        $priority=$GLOBALS["PLAYER_NAME"];
        usort($actorInfos, function($a, $b) use ($priority) {
            return ($a === $priority ? 1 : 0) + ($b === $priority ? -1 : 0);
        });
        
        $orderedActorList=[];

        foreach (array_reverse($actorInfos) as $actorinfo)
        if (!empty($actorinfo))
            $orderedActorList[]=$actorinfo;

        error_log("[AIAGENTNSFW] Erotic Scene. Actors".json_encode($orderedActorList));

        foreach ($actorInfos as $actor) {
            $intimacyStatus=getIntimacyForActor(($actor));
            if (in_array("idle",$sexTags)) {
                $intimacyStatus["level"]=1;
            } else
                $intimacyStatus["level"]=2;

            
            updateIntimacyForActor(($actor),$intimacyStatus);
        }

        error_log("Searching for description $sexStageName");
        
        // Fill descriptions

        $sceneDescription=findRowByFirstColumn(__DIR__."/ostim.csv",$sexStageName);
        if (!$sceneDescription) {
            $sceneDescription="{actor0},{actor1},{actor2},{actor3},{actor4} are having an intimate moment";
        }
        $sceneDescriptionParsed = preg_replace_callback('/\{actor(\d+)\}/', function($matches) use ($orderedActorList) {
            $index = (int)$matches[1];
            return $orderedActorList[$index] ?? $matches[0]; // fallback to original if key not found
        }, $sceneDescription);
        $cleanedSceneDesc = preg_replace('/\{actor\d+\}/', '', $sceneDescriptionParsed);

        // Rewrite data
        $GLOBALS["gameRequest"][3]="#INTIMATE SCENE: $cleanedSceneDesc. Scene tags:".implode(",",$sexTags);
        $GLOBALS["AIAGENTNSFW_FORCE_STOP"]=true;
        logEvent($GLOBALS["gameRequest"]);
        
    
    } else  if ($gameRequest[0]=="chatnf_sl_end") {
        // Set level to 0, this affects voice hook modifications
        
        error_log("[AIAGENT_NSFW] {$gameRequest[3]}");
        // Result
        $sceneResultParts=explode("/",$gameRequest[3]);
        $scoringPart=array_slice($sceneResultParts,1);
        $scoring=[];
        foreach ($scoringPart as $part) {
            $actorResult=explode("@",$part);
            $scoring[]=$actorResult[0]." satisfaction score: ".$actorResult[1];
            updateIntimacyForActor($actorResult[0],["level"=>0,"sex_disposal"=>10,"orgasmed"=>false]) ;
        }
        $actor=$GLOBALS["HERIKA_NAME"];
        updateIntimacyForActor($actor,["level"=>0,"sex_disposal"=>10,"orgasmed"=>false]) ;

        // Overwrite prompt
        $GLOBALS["PROMPTS"]["chatnf_sl_end"]["player_request"]=["The Narrator: ".implode(",",$scoring)];
        $GLOBALS["PATCH_PROMPT_ENFORCE_ACTIONS"]=false;
        $GLOBALS["COMMAND_PROMPT_ENFORCE_ACTIONS"]="";
    
    } else if ($gameRequest[0]=="chatnf_sl_naked") {
        $actor=$GLOBALS["HERIKA_NAME"];
        $intimacyStatus=getIntimacyForActor($actor);
        $intimacyStatus["is_naked"]=2;
        updateIntimacyForActor($actor,$intimacyStatus);

    } else if ($gameRequest[0]=="chatnf_sl_climax") {

        $actor=$GLOBALS["HERIKA_NAME"];
        $intimacyStatus=getIntimacyForActor($actor);

        if (isset($intimacyStatus["orgasm_generated"]) && $intimacyStatus["orgasm_generated"] && isset($intimacyStatus["orgasm_generated_text"])) {
            // We have used GASP. Let's use it.

            //echo "{$actor}|ScriptQueue|".trim(unmoodSentence($intimacyStatus["orgasm_generated_text"]))."////\r\n";

            if ($GLOBALS["AIAGENT_NSFW"]["USE_GASP"]) {
                echo "{$actor}|ScriptQueue|".trim(unmoodSentence($intimacyStatus["orgasm_generated_text_original"]))."////".trim(unmoodSentence($intimacyStatus["orgasm_generated_text"]))."\r\n";
            } else {
                echo "{$actor}|ScriptQueue|".trim(unmoodSentence($intimacyStatus["orgasm_generated_text"]))."////".trim(unmoodSentence($intimacyStatus["orgasm_generated_text"]))."\r\n";
                echo "{$actor}|ScriptQueue|".trim(unmoodSentence($intimacyStatus["orgasm_generated_text_original"]))."////".trim(unmoodSentence($intimacyStatus["orgasm_generated_text_original"]))."\r\n";
            }

            
            // New generation
            //$GLOBALS["AIAGENT_NSFW"]["USE_GASP"]=false;
            //$sourceaudio=$GLOBALS["ENGINE_PATH"]."/data/voices/{$GLOBALS["TTS"]["XTTSFASTAPI"]["voiceid"]}.wav";
            //$finalSpeechText=gasper($original_speech,$moan,$generatedFile,$sourceaudio);
    
            $intimacyStatus["orgasm_generated"]=false;
            $intimacyStatus["orgasm_generated_text"]="";
            $intimacyStatus["orgasm_generated_text_original"]="";

            updateIntimacyForActor($actor,$intimacyStatus);
            terminate();

        }

    } else if ($gameRequest[0]=="chatnf_sl_moan") {

        $randomMoans=["...Ahh ... Ohh..","Yeah oh...yes","... Mmmh ... ","... Ahmmm ...","..Ouch!... "];
        $moan=$randomMoans[array_rand($randomMoans)];
        returnLines([$moan]);
        terminate();

    } else if ($gameRequest[0]=="ext_nsfw_action") {

        // Just log the information
        
        $GLOBALS["AIAGENTNSFW_FORCE_STOP"]=true;
        logEvent($GLOBALS["gameRequest"]);

    }  
        
}


function gasper($original_speech,$moan,$sourceaudio,$sourcevoiceaudio) {

    
    //based on $moan
    $moanfile="/opt/ai/debian-stable/opt/ai/seed-vc/gasper/cough.wav";
    //$moanTranscription="Ahhm Ahm Ahm mmm Ah Ah mm ";

    $moanLibrary=[
        
        ["transcription"=>"Ahhm Ahm ahms","file"=>"/opt/ai/debian-stable/opt/ai/seed-vc/gasper/library/ClimaxD1.wav"],
        ["transcription"=>"Ahhm Ahm ahms","file"=>"/opt/ai/debian-stable/opt/ai/seed-vc/gasper/library/ClimaxE1.wav"],
        ["transcription"=>"Ahhm Ahm ahms","file"=>"/opt/ai/debian-stable/opt/ai/seed-vc/gasper/library/ClimaxE2.wav"],
        ["transcription"=>"Ahhm Ahm ahms","file"=>"/opt/ai/debian-stable/opt/ai/seed-vc/gasper/library/ClimaxE4.wav"],
        ["transcription"=>"Ahhm Ahm ahms","file"=>"/opt/ai/debian-stable/opt/ai/seed-vc/gasper/library/ClimaxE5.wav"],
        ["transcription"=>"Ahhm Ahm ahms","file"=>"/opt/ai/debian-stable/opt/ai/seed-vc/gasper/library/ClimaxE6.wav"],
        ["transcription"=>"Ahhm Ahm ahms","file"=>"/opt/ai/debian-stable/opt/ai/seed-vc/gasper/library/ClimaxF1.wav"],
        ["transcription"=>"Ahhm Ahm ahms","file"=>"/opt/ai/debian-stable/opt/ai/seed-vc/gasper/library/ClimaxG1.wav"],
        ["transcription"=>"Ahhm Ahm ahms","file"=>"/opt/ai/debian-stable/opt/ai/seed-vc/gasper/library/ClimaxA1.wav"],
        ["transcription"=>true,"file"=>"/opt/ai/debian-stable/opt/ai/seed-vc/gasper/library/ClimaxA2.wav"],
        ["transcription"=>true,"file"=>"/opt/ai/debian-stable/opt/ai/seed-vc/gasper/library/ClimaxA3.wav"],



    ];
    $selectedIndex=rand(0,sizeof($moanLibrary)-1);
    //$selectedIndex=sizeof($moanLibrary)-1;

    if (isset($GLOBALS["AIAGENT_NSFW"]["USE_GASP"]) && $GLOBALS["AIAGENT_NSFW"]["USE_GASP"]) {
        $tempfile= "/tmp/".uniqid().".wav";
        $command = "/usr/local/bin/gasp $sourceaudio {$moanLibrary[$selectedIndex]["file"]} \"$original_speech\" $tempfile";
    
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
        $output.="  $original_speech";
        
        $finalPseudoPhonetic=$output;

        $finalPseudoPhonetic=trim(unmoodSentence($finalPseudoPhonetic));
    } else {
        $tempfile= "/tmp/".uniqid().".wav";
        $sourceaudio=
        $command = "/usr/local/bin/gasp /opt/ai/debian-stable/opt/ai/seed-vc/gasper/library/silence.wav {$moanLibrary[$selectedIndex]["file"]} \"$original_speech\" $tempfile";
    
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
        
        $finalPseudoPhonetic=$output;

        $finalPseudoPhonetic=trim(unmoodSentence($finalPseudoPhonetic));
        
    }

    error_log("[GASP] finalPseudoPhonetic: $finalPseudoPhonetic");


    if (!file_exists($tempfile)) {
        error_log("[GASP] Source audio file not found: $tempfile");
    }
    if (!file_exists($sourcevoiceaudio)) {
        error_log("[GASP] Reference audio file not found: $sourcevoiceaudio");
    }

    $sourceAudioPath = realpath($tempfile);
    $referenceAudioPath = realpath($sourcevoiceaudio);
    
    if (!$sourceAudioPath || !$referenceAudioPath) {
        error_log("[GASP] File path resolution failed.");
    }
    
    // Check if files actually exist and are readable
    if (!file_exists($sourceAudioPath) || !is_readable($sourceAudioPath)) {
        error_log("[GASP] Source audio file not accessible: " . $sourceAudioPath);
    }
    if (!file_exists($referenceAudioPath) || !is_readable($referenceAudioPath)) {
        error_log("[GASP] Reference audio file not accessible: " . $referenceAudioPath);
    }
    

    if (!isset($GLOBALS["AIAGENT_NSFW"]["USE_SVC"]) || $GLOBALS["AIAGENT_NSFW"]["USE_SVC"]==false) {
        $tempResfile = $GLOBALS["ENGINE_PATH"]."/soundcache/".md5($finalPseudoPhonetic).".wav";

        $original_speech_cleaned=trim(unmoodSentence($original_speech));
        $tempResfile2 = $GLOBALS["ENGINE_PATH"]."/soundcache/".md5($original_speech_cleaned).".wav";

        copy($tempfile,$tempResfile);
        //copy($tempfile,$tempResfile2);

        error_log("[GASP] $tempResfile saved successfully.");

        return $finalPseudoPhonetic;
    }

    $postFields = [
        'source_audio' => new CURLFile($sourceAudioPath, 'audio/wav', basename($sourceAudioPath)),
        'reference_audio' => new CURLFile($referenceAudioPath, 'audio/wav', basename($referenceAudioPath)),
        'diffusion_steps' => '10',
        'length_adjust' => '1.0',
        'inference_cfg_rate' => '0.4',
        //'auto_f0_adjust'=>$GLOBALS["AIAGENT_NSFW"]["USE_GASP"]?'False':'True',
        'auto_f0_adjust'=>'False'
        
    ];
    
    $url = 'http://127.0.0.1:8000/voice_conversion';
    
    // Initialize cURL
    $ch = curl_init();
    
    // Set cURL options
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => $postFields,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            // Don't set Content-Type manually for multipart/form-data
            // cURL will set it automatically with boundary
        ],
        CURLOPT_VERBOSE => false,
        CURLOPT_TIMEOUT => 300, // 5 minutes timeout
        CURLOPT_FOLLOWLOCATION => true,
    ]);
    
    // Execute request
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    // Handle error
    if (curl_errno($ch)) {
        error_log( '[GASP-SVC] Curl error: ' . curl_error($ch) );
        error_log( '[GASP-SVC] Curl errno: ' . curl_errno($ch) );
    } else {
        //error_log("HTTP Status Code: " . $httpCode );
        
        if ($httpCode >= 200 && $httpCode < 300) {
            // Save the output to a file
            $tempResfile = $GLOBALS["ENGINE_PATH"]."/soundcache/".md5($finalPseudoPhonetic).".wav";
            $original_speech_cleaned=trim(unmoodSentence($original_speech));

            $tempResfile2 = $GLOBALS["ENGINE_PATH"]."/soundcache/".md5($original_speech_cleaned).".wav";

            file_put_contents($tempResfile.".tmp", $response);
            shell_exec("ffmpeg -y -i $tempResfile.tmp  -ar 24000 -ac 1 -sample_fmt s16  $tempResfile 2>/dev/null >/dev/null");
            copy($tempResfile,$tempResfile2);
            error_log("[GASP-SVC] $tempResfile saved successfully.");
        } else {
            error_log("[GASP-SVC] Server returned error. Response: " . $response);
        }
    }
    
    // Close cURL
    curl_close($ch);

    return $finalPseudoPhonetic;
}


function playerIsNaked() {
    
    audit_log(__FILE__." [AIAGENT NSFW]  ".__LINE__);

    $val=$GLOBALS["db"]->fetchOne("select value from conf_opts where id='player_naked'");
    if ($val["value"]==1) {
        return true;

    }

    return false;
}
/*
$GLOBALS["AIAGENT_NSFW"]["USE_GASP"]=true;
$GLOBALS["AIAGENT_NSFW"]["USE_SVC"]=true;
$GLOBALS["ENGINE_PATH"]="/var/www/html/HerikaServer/";
$finalSpeechText=gasper("Oh Oh yes Volkur",
$moan,"/opt/ai/debian-stable/opt/ai/seed-vc/gasper/56f66be252d0ce5d675f7dc39b731ab0.wav",
"/var/www/html/HerikaServer/soundcache/femaleyoungeager/05cc82c4b8882face2f1567e5a132ee8.wav");
*/
?>