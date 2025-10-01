<?php 

// Postrequest tasks

if ($gameRequest[0]=="chatnf_sl") {    

    // Restore profile
    error_log(__FILE__);
    if (isset($_GET["profile"])) {
    
        
        if (file_exists($GLOBALS["ENGINE_PATH"] . "conf".DIRECTORY_SEPARATOR."conf_{$_GET["profile"]}.php")) {
            error_log("PROFILE: {$_GET["profile"]}");
            require($GLOBALS["ENGINE_PATH"] . "conf".DIRECTORY_SEPARATOR."conf_{$_GET["profile"]}.php");
    
        } else {
            error_log(__FILE__.". Using default profile because GET PROFILE NOT EXISTS");
        }
        
        
        
    } else {
        error_log(__FILE__.". Using default profile because NO GET PROFILE SPECIFIED");
        $GLOBALS["USING_DEFAULT_PROFILE"]=true;
    }

    // Check if already generated
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
        $updateProfilePrompt = "Write a VERY SHORT sentence (3 words) for {$GLOBALS["HERIKA_NAME"]} 
        to say when she/he reaches Climax based on Dialogue history, and {$GLOBALS["PLAYER_NAME"]} requests,stuttering";
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


        // We assume XTTS
        $sourceaudio=$GLOBALS["ENGINE_PATH"]."/data/voices/{$GLOBALS["TTS"]["XTTSFASTAPI"]["voiceid"]}.wav";
        
        $moan="";

        //function gasper($original_speech,$moan,$sourceaudio,$sourcevoiceaudio) {
        $GLOBALS["AIAGENT_NSFW"]["USE_GASP"]=false;
        $GLOBALS["AIAGENT_NSFW"]["USE_SVC"]=false;
        $sourceaudio=$GLOBALS["ENGINE_PATH"]."/data/voices/{$GLOBALS["TTS"]["XTTSFASTAPI"]["voiceid"]}.wav";
        //$sourceaudio=$GLOBALS["ENGINE_PATH"]."/soundcache/0d65892f75e23ee4151fb7637ea49e9a.wav";
        $finalSpeechText=gasper($original_speech,$moan,$generatedFile,$sourceaudio);

        $intimacyStatus["orgasm_generated"]=true;
        $intimacyStatus["orgasm_generated_text"]=$finalSpeechText;
        $intimacyStatus["orgasm_generated_text_original"]=trim(unmoodSentence($original_speech));

        updateIntimacyForActor($actor,$intimacyStatus);
    } else {
        error_log("Orgams sound already generated");

    }

   

} else {
    error_log(print_r($gameRequest,true));

    return;
}


?>