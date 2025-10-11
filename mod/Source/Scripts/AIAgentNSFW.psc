Scriptname AIAgentNSFW extends Quest  
import Utility

int map 
int descriptionsMap
int versionCheck = 1

int Property mdi auto
int Property mdo auto

Quest Property AIAgentPapyrusFunctionsQ  Auto  

Bool Property isSceneRunningInvolvingPlayer  Auto  

Faction Property noFacialExpressionsFaction Auto

function DoRegister()
	
	Debug.Notification("[CHIM-NSFW] OnInit")
	Debug.Trace("[CHIM-NSFW]: OnInit called")
		
	UnRegisterForModEvent("CHIM_CommandReceived")
	UnRegisterForModEvent("CHIM_SpeechStopped")
	UnRegisterForModEvent("CHIM_SpeechStarted")
	
	UnRegisterForModEvent("ostim_event")
	UnRegisterForModEvent("ostim_thread_start")
	UnRegisterForModEvent("ostim_actor_orgasm")
	;RegisterForModEvent("ocum_play_cum_shoot_effect", "OCumPlayCumShoot")
	UnRegisterForModEvent("ostim_scenechanged")
	UnRegisterForModEvent("ostim_end")
	UnRegisterForModEvent("ostim_thread_start")
	UnRegisterForModEvent("ostim_thread_scenechanged")
	UnRegisterForModEvent("ostim_thread_end")

	
	RegisterForModEvent("CHIM_CommandReceived", "CommandManager")
	RegisterForModEvent("CHIM_SpeechStopped", "HelperSpeechStop")
	RegisterForModEvent("CHIM_SpeechStarted", "HelperSpeechStart")
	
	RegisterForModEvent("ostim_event", "OstimEvent")
	RegisterForModEvent("ostim_thread_start", "OStimStart")
	RegisterForModEvent("ostim_actor_orgasm", "OStimOrgasm")
	;RegisterForModEvent("ocum_play_cum_shoot_effect", "OCumPlayCumShoot")
	RegisterForModEvent("ostim_scenechanged", "OStimSceneChanged")
	RegisterForModEvent("ostim_end", "OStimEnd")
	RegisterForModEvent("ostim_thread_start", "OStimThreadStart")
	RegisterForModEvent("ostim_thread_scenechanged", "OStimThreadSceneChanged")
	RegisterForModEvent("ostim_thread_end", "OStimThreadEnd")


EndFunction

Event OnInit()
		Debug.Notification("[CHIM-NSFW] First installed")
		Debug.Trace("[CHIM-NSFW]: OnInit called for the first time")
		map = JMap.object()
		versionCheck = 2
		
		
		DoRegister()
		RegisterForSleep()

EndEvent

Event OnSleepStart(float afSleepStartTime, float afDesiredSleepEndTime)

	DoRegister()
	;RegisterForModEvent("HookStageStart", "OnStageStart")
	;RegisterForModEvent("HookOrgasmStart", "PostSexScene")
	;RegisterForModEvent("HookAnimationEnd", "EndSexScene")
	;RegisterForModEvent("HookAnimationStart", "OnAnimationStart")
		
	
EndEvent

Event HelperSpeechStart(Form npc)
	Debug.Trace("[CHIM NSFW] HelperSpeechStart")
	StorageUtil.SetIntValue(npc, "IS_SPEAKING", 1)
	int running=OThread.GetThreadCount();
	if (running==0)
		return
	endif
	Actor akActor=npc as Actor
	if (akActor)
		OActor.Mute(akActor)
		OActor.ClearExpression(akActor)
		OActor.StallClimax(akActor)
		OActor.SetExcitementMultiplier(akActor,0)
		akActor.SetFactionRank(noFacialExpressionsFaction,1)

		Debug.Trace("[CHIM NSFW] "+akActor.GetDisplayName()+" is muted for moan (is speaking)")
	else
		Debug.Trace("[CHIM NSFW] no actor")
	EndIf

EndEvent

Event HelperSpeechStop(Form npc)

	Debug.Trace("[CHIM NSFW] HelperSpeechStop")
	StorageUtil.SetIntValue(npc, "IS_SPEAKING", 0)
		
	int running=OThread.GetThreadCount();
	if (running==0)
		return
	endif
	
	Actor akActor=npc as Actor
	if (akActor)
	
		OActor.SetExcitementMultiplier(akActor,1)
		OActor.UnMute(akActor)
		OActor.PermitClimax(akActor)
		akActor.RemoveFromFaction(noFacialExpressionsFaction)
		float excitement=OActor.GetExcitement(akActor)
		Debug.Trace("[CHIM NSFW] "+akActor.GetDisplayName()+" is unmuted for moan, excitement:"+excitement)
		if (excitement>=100)
			;OActor.Climax(akActor,true)
			OActor.SetExcitement(akActor, 99)
		endif
	EndIf
EndEvent


Event CommandManager(String npcname,String  command, String parameter)

	Debug.Notification("[CHIM NSFW] External command "+command+ " received for "+npcname)
	Debug.Trace("[CHIM NSFW] External command "+command+ " received for "+npcname)
	Actor npc=AIAgentFunctions.getAgentByName(npcname);
	
	if (command=="ExtCmdRemoveClothes")
	
		Int modIndex = Game.GetModByName("_GSPoses.esp")
		;FastRemoveClothes(npc)
		if modIndex != 255
			AIAgentFunctions.logMessage(npcname+" starts to remove clothing slowly","ext_nsfw_action")
			GSPoseRemoveClothes(npc,npc)
			FastRemoveClothes(npc)
		else
			FastRemoveClothes(npc)
		endIf
		
		
		;npc.UnequipAll()
		AIAgentFunctions.logMessageForActor("The Narrator:" + npcname+" is now naked.","chatnf_sl_naked",npcname)
		AIAgentFunctions.logMessageForActor("command@ExtCmdRemoveClothes@@"+npcname+" removes clothes and armor","funcret",npcname)
		
	endif
	
	if (command=="ExtCmdPutOnClothes")
		
		
		
		npc.EquipItem(GetBestArmorForSlot(npc, 0x00000004), false, true) ; Body
		npc.EquipItem(GetBestArmorForSlot(npc, 0x00000008), false, true) ; Hands
		npc.EquipItem(GetBestArmorForSlot(npc, 0x00000010), false, true) ; Forearms
		npc.EquipItem(GetBestArmorForSlot(npc, 0x00000020), false, true) ; Feet
		npc.EquipItem(GetBestArmorForSlot(npc, 0x00000040), false, true) ; Calves
		npc.EquipItem(GetBestArmorForSlot(npc, 0x00000080), false, true) ; Shield
	
		AIAgentFunctions.logMessageForActor("command@ExtCmdPutOnClothes@@"+npcname+" puts on clothes and armor","funcret",npcname)

	
	endIf	
	if (command=="ExtCmdKiss")
		
		Actor kissedActor=None
		bool IsPlayerInvolved=false
		
		Package doNothing = Game.GetForm(0x654e2) as Package ; Package Travelto
		ImageSpaceModifier FadeToBlack = Game.GetForm(0x000f756d) as ImageSpaceModifier
		ImageSpaceModifier FadeFromBlack = Game.GetForm(0x000f756f) as ImageSpaceModifier

		
		If (StringUtil.find(parameter,Game.GetPlayer().GetDisplayName()) !=-1)
			; PLayer involved
			string result = SkyMessage.Show(npc.GetDisplayName()+ " wants to kiss you. Allow?", "No, thanks", "Yes, please!")
			if result == "Yes, please!"
				kissedActor=Game.GetPlayer()
				IsPlayerInvolved=true;
				Game.DisablePlayerControls();
			else
				AIAgentFunctions.logMessageForActor("command@ExtCmdKiss@"+parameter+"@error. Player refused kiss","funcret",npcname)
				return;
			endif	
		else
			kissedActor = AIAgentFunctions.getAgentByName(parameter)
		endif 
		
		AIAgentAIMind.ResetPackages(npc)
		UnequipItemBySlot(npc, 0x00000080); Feet.
		
		if kissedActor==None 
			AIAgentFunctions.requestMessageForActor("command@ExtCmdKiss@"+parameter+"@error. target nout found;"+parameter+"","funcret",npcname)
			return
		endif


		if (kissedActor.IsOnMount())
			AIAgentFunctions.logMessageForActor("command@ExtCmdKiss@"+parameter+"@error. "+kissedActor+" is on mount","funcret",npcname)
		endif
		
		if (npc.IsOnMount())
			AIAgentFunctions.logMessageForActor("command@ExtCmdKiss@"+parameter+"@error. "+npcname+" is on mount","funcret",npcname)
		endif
		
		int kissedActorStatus= StorageUtil.GetIntValue(kissedActor, "chim_kiss_status", 0)
		int npcStatus= StorageUtil.GetIntValue(kissedActor, "chim_kiss_status", 0)

		if (kissedActorStatus == 0 && npcStatus == 0 )
		;
		else
			AIAgentFunctions.logMessageForActor("command@ExtCmdKiss@"+parameter+"@error. "+npcname+" is already kissing someone","funcret",npcname)
		endif
		
		Debug.trace("[CHIM-NSFW] "+npcname+" want to kiss "+kissedActor.GetDisplayName());
		
		if (npc.GetSitState()==3 || npc.GetSitState()==2)
			Debug.SendAnimationEvent(npc, "IdleForceDefaultState")
		endif
		
		
		if (kissedActor.GetSitState()==3 || kissedActor.GetSitState()==2)
			Debug.SendAnimationEvent(kissedActor, "IdleForceDefaultState")
		
		endif
	
		ActorUtil.AddPackageOverride(npc, doNothing,100)
		npc.EvaluatePackage()

		Wait(0.1)
		

		
		fadeToBlack.Apply() ; fade in 1 second
		
		StorageUtil.SetIntValue(kissedActor, "chim_kiss_status", 1)
		StorageUtil.SetIntValue(npc, "chim_kiss_status", 1)

		Wait(2)

		if (IsPlayerInvolved)
			Game.FadeOutGame(False,True,50, 1)
		endif


		npc.SetDontMove()
			
		if (IsPlayerInvolved)
			Utility.SetIniBool("bDisablePlayerCollision:Havok", True)
		endif;
		
		
		float angle = kissedActor.GetAngleZ()
		float forwardX = 0.5 * Math.Sin(angle)
		float forwardY = 0.5 * Math.Cos(angle)
		float rightX = 2.0 * Math.Cos(angle)
		float rightY = -2.0 * Math.Sin(angle)


		npc.SetAnimationVariableBool("bHumanoidFootIKDisable", True) ; disable inverse kinematics
		;npc.SetMotionType(4)
		Debug.trace(kissedActor.getDisplayName()+ ":" +kissedActor.GetHeight()+" "+kissedActor.GetScale()+", "+npc.getDisplayName()+ ":" +npc.GetHeight()+" "+npc.GetScale())
		float heightA = kissedActor.GetHeight() * kissedActor.GetScale()
		float heightB = npc.GetHeight() * npc.GetScale()
		float zOffset = heightA - heightB - 3.5

		;npc.MoveTo(kissedActor, forwardX + rightX, forwardY + rightY, kissedActor.GetHeight()-npc.GetHeight(),false )
		npc.MoveTo(kissedActor, forwardX + rightX, forwardY + rightY, zOffset)

		;int CameraState=Game.GetCameraState();
		if (IsPlayerInvolved)
			Game.ForceThirdPerson();
		endif
		
		
		npc.SetAnimationVariableInt("IsNPC", 0) ; disable head tracking
		npc.SetAlpha(1.0, true) ; true desactiva fading automático

		
		AIAgentFunctions.logMessage(npcname+" kisses "+parameter,"ext_nsfw_action")

		debug.sendanimationevent(kissedActor, "standingkiss2")
		debug.sendanimationevent(npc, "standingkiss1")
		
		if (IsPlayerInvolved)
			Game.FadeOutGame(False,True,0.1, 0.1)
			FadeToBlack.PopTo(FadeFromBlack)		
		endif
		

		MfgConsoleFunc.SetPhoneme(npc, 1, 90)
		MfgConsoleFunc.SetModifier(npc, 0, 90)
		MfgConsoleFunc.SetModifier(npc, 1, 90)
		
		AIAgentFunctions.setLocked(1,npcname)

		int totalTime = 20
		float stepTime = 0.5
		int steps = (totalTime / stepTime) as int
		float time = 0.0

		while (time < totalTime)
			float rawValue = 10.0 + 80.0 * Math.Sin((time / totalTime) * 6.28 * 32)
			int phonemeValue = rawValue as int
			if (phonemeValue<0)
				phonemeValue = 0
			endif
			;Debug.Trace(phonemeValue);
			MfgConsoleFunc.SetPhoneme(npc, 1, phonemeValue)
			MfgConsoleFunc.SetPhoneme(kissedActor, 1, phonemeValue)

			Wait(stepTime*2)
			time += stepTime*2
		endWhile

		; Reset to relaxed mouth
		MfgConsoleFunc.SetPhoneme(npc, 1, 10)
		MfgConsoleFunc.SetPhoneme(kissedActor, 1, 10)


		
		AIAgentFunctions.setLocked(0,npcname)
		AIAgentFunctions.logMessageForActor("command@ExtCmdKiss@"+parameter+"@"+npcname+" gave a kiss to "+parameter+"","funcret",npcname)
		;AIAgentFunctions.requestMessageForActor(npcname+" kissed "+parameter+"","chatnf_sl",npcname)

		debug.sendanimationevent(npc, "idleforcedefaultstate")
		debug.sendanimationevent(kissedActor, "idleforcedefaultstate")
		
		;int CameraState=Game.GetCameraState();
		;Game.ForceThirdPerson();

		npc.SetAnimationVariableInt("IsNPC", 1) ; enable head tracking
		npc.SetAnimationVariableBool("bHumanoidFootIKDisable", False) ; enable inverse kinematics

		npc.EquipItem(GetBestArmorForSlot(npc, 0x00000080), false, true) ; Feet
		
		MfgConsoleFunc.ResetPhonemeModifier(npc);reset
		ActorUtil.RemovePackageOverride(npc, doNothing)
		;npc.SetMotionType(1)
		npc.EvaluatePackage()
		npc.SetDontMove(false)
		
		if IsPlayerInvolved
			Utility.SetIniBool("bDisablePlayerCollision:Havok",false)
			Game.EnablePlayerControls();
		endif
			
		StorageUtil.SetIntValue(kissedActor, "chim_kiss_status", 0)
		StorageUtil.SetIntValue(npc, "chim_kiss_status", 0)
		
	endIf	
	
	if (command=="ExtCmdVampireBiteFeed")

		AIAgentAIMind.ResetPackages(npc)
		Wait(0.1)
		
		npc.PathToReference(Game.GetPlayer(), 0.5);Move it next to it
		
		Idle feedIdle=Game.GetForm(0x0200E6A8) as Idle
		if (npc.PlayIdleWithTarget(feedIdle,Game.GetPlayer()))
				Wait(5)
				
		endif		
		
		
		if (Game.GetPlayer().GetName()==parameter)
			;npc.StartVampireFeed(Game.GetPlayer())
		else
			;npc.StartVampireFeed(Game.GetPlayer())
		endif
		
		
		AIAgentFunctions.logMessageForActor("command@ExtCmdVampireBiteFeed@"+parameter+"@"+npcname+" bites an feeds from "+parameter+", arousal raises","funcret",npcname)

	
	endIf	
	
	if (command=="ExtCmdConsumeSoul")

		Actor victim=None
		bool IsPlayerInvolved=false
		
		If (StringUtil.find(parameter,Game.GetPlayer().GetDisplayName()) !=-1)
			; PLayer involved
			string result = SkyMessage.Show(npc.GetDisplayName()+ " wants to take a taste of you. Allow?", "No, thanks", "Yes, please!")
			if result == "Yes, please!"
				victim=Game.GetPlayer()
				IsPlayerInvolved=true;
				Game.DisablePlayerControls();
			else
				AIAgentFunctions.logMessageForActor("command@ExtCmdConsumeSoul@"+parameter+"@error. Player refused ritual","funcret",npcname)
				return;
			endif	
		else
			victim = AIAgentFunctions.getAgentByName(parameter)
		endif 

		if (victim && !IsPlayerInvolved)

			Package doNothing = Game.GetForm(0x654e2) as Package ; Package doNothing
			ActorUtil.AddPackageOverride(npc, doNothing,100)
			npc.EvaluatePackage()
			
			
			AIAgentFunctions.setAnimationBusy(1,npcname)
			npc.SetLookAt(victim,true)
			
			;npc.MoveTo(victim, 1);Move it next to it
			MiscObject sacrifficeCoin=Game.GetForm(0x0000000f)	as MiscObject; Necklace
			ObjectReference sacrifficeCoinRef=victim.PlaceAtMe(sacrifficeCoin,1);
			
			float heading = victim.GetAngleZ()
			;float headingRad = heading * 0.0174533 
			float dist = 120.0 ; or whatever distance you want
			float xOffset = dist * Math.Sin(heading)
			float yOffset = dist * Math.Cos(heading)
			
			sacrifficeCoinRef.MoveTo(victim, xOffset, yOffset, 0.0, true);
			
			npc.PathToReference(sacrifficeCoinRef, 1);Move it next to it
			
			; Move the NPC in front of the victim's facing
			
			;npc.MoveTo(sacrifficeCoinRef,0,0,0,true)
			;npc.SetAngle(0.0, 0.0, victim.GetAngleZ()*-1)
			Wait(1)
			Debug.SendAnimationEvent(npc, "IdleRitualSkull1")
			npc.SetDontMove(true)
			;victim.SplineTranslateToRef(npc, 1.0, 10.0, 10)
			Wait(5)
			Debug.SendAnimationEvent(npc, "IdleRitualSkull2")
			AIAgentFunctions.setLocked(1,npcname)
			
			Spell absorbHealthSpell = Game.GetForm(0x0008d5c3) as Spell ; Replace with the actual FormID and plugin name
			if absorbHealthSpell
				absorbHealthSpell.Cast(npc, victim)
			else
				Debug.Trace("[CHIM-NSFW] Absorb Health spell not found!")
			endif
			
			
			

			Wait(5)
			npc.InterruptCast();
			victim.KillSilent(npc)
			
			
			npc.SetDontMove(false)
			AIAgentFunctions.setLocked(0,npcname)
			AIAgentFunctions.setAnimationBusy(0,npcname)
			npc.activate(sacrifficeCoinRef)
			npc.ClearLookAt()
			
			ActorUtil.RemovePackageOverride(npc, doNothing)
			npc.EvaluatePackage()
			
			AIAgentFunctions.logMessageForActor("command@ExtCmdConsumeSoul@"+parameter+"@"+npcname+" consumed +"+parameter+" soul","funcret",npcname)

		endif
	endIf	
	
	if (command=="ExtCmdHug")
		
		
		string result = SkyMessage.Show(npc.GetDisplayName()+ " wants to hug you. Allow?", "No, thanks", "Yes, please!")
		if result == "Yes, please!"
			;
		else
			AIAgentFunctions.logMessageForActor("command@"+command+"@"+parameter+"@error. Player refused to hug","funcret",npcname)
			return;
		endif	
		
		
		
		Actor receiver=Game.GetPlayer();
		if (npc.GetSitState()==3 || npc.GetSitState()==2) ; Dont use feature if player is not sitting, or is on a mount
			Debug.SendAnimationEvent(npc, "IdleForceDefaultState")
		endif
		;npc.StartVampireFeed(Game.GetPlayer())

		if (npc.GetDistance(receiver)>512)
			AIAgentFunctions.logMessageForActor("command@"+command+"@"+parameter+"@error. Player is too far away to hug","funcret",npcname)
			return;
		endif

		AIAgentAIMind.ResetPackages(npc)
		Package doNothing = Game.GetForm(0x654e2) as Package ; Package doNothing
		ActorUtil.AddPackageOverride(npc, doNothing,100)
		npc.EvaluatePackage()
		Wait(0.1)
		
		
		MiscObject hugCoin=Game.GetForm(0x0000000f)	as MiscObject; Necklace
		ObjectReference hugCoinRef=receiver.PlaceAtMe(hugCoin,1);
		
		float heading = receiver.GetAngleZ()
		;float headingRad = heading * 0.0174533 
		float dist = 64.0 ; or whatever distance you want
		float xOffset = dist * Math.Sin(heading)
		float yOffset = dist * Math.Cos(heading)
		
		hugCoinRef.MoveTo(receiver, xOffset, yOffset, 0.0, true);
		
		
		
		AIAgentFunctions.setAnimationBusy(1,npcname)
		npc.SetLookAt(receiver)
		npc.PathToReference(hugCoinRef, 1);Move it next to it
		
		
		Utility.SetIniBool("bDisablePlayerCollision:Havok", True)
		npc.SetAnimationVariableBool("bHumanoidFootIKDisable", True) 
		
		
		;if (npc.GetDistance(receiver)<256)
		;	npc.MoveTo(receiver, 1);Move it next to it
		;else
		;	npc.MoveTo(receiver, 1);Move it next to it
		;endif
		
		
		
		Idle hugIdle=Game.GetForm(0x0f4699) as Idle
		;int CameraState=Game.GetCameraState();
		Game.ForceThirdPerson();
		npc.PlayIdleWithTarget(hugIdle,Game.GetPlayer())
		npc.SetDontMove(true)
		Wait(5)
		npc.SetDontMove(false)
		AIAgentFunctions.setLocked(0,npcname)
		AIAgentFunctions.setAnimationBusy(0,npcname)
		npc.activate(hugCoinRef)
		npc.ClearLookAt()
		Utility.SetIniBool("bDisablePlayerCollision:Havok", False)
		npc.SetAnimationVariableBool("bHumanoidFootIKDisable", False)
			
		ActorUtil.RemovePackageOverride(npc, doNothing)
		npc.EvaluatePackage()
		;if (CameraState==0)
		;	Game.ForceFirstPerson();
		;endif;
		
		AIAgentFunctions.logMessageForActor("command@ExtCmdHug@"+parameter+"@"+npcname+" gives a hug to "+parameter+"","funcret",npcname)
	
	endIf	
	
	if (command=="ExtCmdStartSex" || command=="ExtCmdStartThreesome" || command=="ExtCmdStartBlowJob" || command=="ExtCmdStartMassage" || command=="ExtCmdStartTitfuck" || command=="ExtCmdStartAnalSex" || command=="ExtCmdStartHandjobSex")
		
		
		noFacialExpressionsFaction = Game.GetFormFromFile(0xD92, "OStim.esp") as Faction ; Package Travelto
	
		If (StringUtil.find(parameter,Game.GetPlayer().GetDisplayName()) !=-1)
			; PLayer involvedºº
			string result = SkyMessage.Show(npc.GetDisplayName()+ " wants to have "+" sex with you. Allow?", "No, thanks", "Yes, please!")
			if result == "Yes, please!"
				;
			else
				AIAgentFunctions.logMessageForActor("command@"+command+"@"+parameter+"@error. Player refused to start sex","funcret",npcname)
				return;
			endif	
		endif 
			
		Actor[] actorsInvolved=PrepareScene(npc,parameter);
		
		
		
		;AIAgentFunctions.logMessage("ENJOY THE SEX SCENE!. NO QUEST, NO DUTIES NOW. ONLY PLEASURE","force_current_task")
		AIAgentFunctions.logMessageForActor("command@"+command+"@"+parameter+"@Intimate scene starts","funcret",npcname)
		
		mdi=AIAgentFunctions.get_conf_i("_max_distance_inside");
		mdo=AIAgentFunctions.get_conf_i("_max_distance_outside");
		
		Debug.Trace("[CHIM-NSFW] Enabling intimacy bubble: saving settings: "+mdi+","+mdo);
		
		AIAgentFunctions.setConf("_max_distance_inside",256,256,256);
		AIAgentFunctions.setConf("_max_distance_outside",256,256,256);
		
		
		Actor[] finalActorsInvolved= OActorUtil.Sort(actorsInvolved,OActorUtil.toArray());
		Debug.Trace("[CHIM-NSFW] Actors sorted");
		
		if (OActor.VerifyActors(actorsInvolved))
			Debug.Trace("[CHIM-NSFW] Actors verified");
			
			String initialSceneText="";
			if (command=="ExtCmdStartSex" )
				initialSceneText="idle";
			elseif (command=="ExtCmdStartThreesome" )
				initialSceneText="idle";
			elseif	(command=="ExtCmdStartBlowJob")
				;should wait here to stop speaking, if speaking
				int limit = 15
				int n = 0
				while (n < limit)
					Utility.Wait(2)
					if StorageUtil.GetIntValue(npc, "IS_SPEAKING") == 0
						n = limit
					else
						Debug.Trace("[CHIM-NSFW] Actor "+npc+" speaking, delaying oral");
					endif
					
					n = n+1
				EndWhile

				initialSceneText="blowjob";
				
			elseif (command=="ExtCmdStartMassage")
				
				initialSceneText="cuddling";
				
			elseif (command=="ExtCmdStartTitfuck")
				int limit = 15
				int n = 0
				while (n < limit)
					Utility.Wait(2)
					if StorageUtil.GetIntValue(npc, "IS_SPEAKING") == 0
						n = limit
					else
						Debug.Trace("[CHIM-NSFW] Actor "+npc+" speaking, delaying oral");
					endif
					
					n = n+1
				EndWhile
				initialSceneText="boobjob";
				
			elseif (command=="ExtCmdStartAnalSex")
				int limit = 15
				int n = 0
				while (n < limit)
					Utility.Wait(2)
					if StorageUtil.GetIntValue(npc, "IS_SPEAKING") == 0
						n = limit
					else
						Debug.Trace("[CHIM-NSFW] Actor "+npc+" speaking, delaying anal");
					endif
					
					n = n+1
				EndWhile
				initialSceneText="analsex";
				
			
			elseif (command=="ExtCmdStartHandjobSex")
				int limit = 15
				int n = 0
				while (n < limit)
					Utility.Wait(2)
					if StorageUtil.GetIntValue(npc, "IS_SPEAKING") == 0
						n = limit
					else
						Debug.Trace("[CHIM-NSFW] Actor "+npc+" speaking, delaying handjob");
					endif
					
					n = n+1
				EndWhile
				initialSceneText="handjob";
				
			
			EndIf
			
			
			
			
			;String initialScene=OLibrary.GetRandomSceneWithSceneTag(finalActorsInvolved,initialSceneText)
			String initialScene=OLibrary.GetRandomSceneWithAllActionsCSV(finalActorsInvolved,initialSceneText)

			int builderID = OThreadBuilder.create(finalActorsInvolved)
			OThreadBuilder.SetStartingAnimation(builderID, initialScene)
			int newThreadID = OThreadBuilder.Start(builderID)
			
			StorageUtil.SetIntValue(npc, "ostimThreadId", newThreadID)
			Debug.Trace("[CHIM-NSFW] Launched Scene thrId:"+newThreadID);

			
			;OThread.QuickStart(finalActorsInvolved)
		else
			Debug.Trace("[CHIM-NSFW] Could not verify actors:");
		endif
		
		
			
	endIf	
	
	
	if (command=="ExtCmdStartMassage~")
		;parameter
		
		If (StringUtil.find(parameter,Game.GetPlayer().GetDisplayName()) !=-1)
			; PLayer involved
			string result = SkyMessage.Show(npc.GetDisplayName()+ " wants to give you a massage. Allow?", "No, thanks", "Yes, please!")
			if result == "Yes, please!"
				;
			else
				AIAgentFunctions.logMessageForActor("command@ExtCmdStartMassage@"+parameter+"@error. Player refused to start sex","funcret",npcname)
				return;
			endif	
		endif 
		
		Actor[] actorsInvolved=PrepareScene(npc,parameter);
		
		AIAgentFunctions.logMessageForActor("command@ExtCmdStartMassage@"+parameter,"funcret",npcname)
		
		mdi=AIAgentFunctions.get_conf_i("_max_distance_inside");
		mdo=AIAgentFunctions.get_conf_i("_max_distance_outside");
		
		Debug.Trace("Enabling intimacy bubble: saving settings: "+mdi+","+mdo);
		
		AIAgentFunctions.setConf("_max_distance_inside",256,256,256);
		AIAgentFunctions.setConf("_max_distance_outside",256,256,256);
		
		;SexLabFramework _slf = SexLabUtil.GetAPI() 
		;_slf.QuickStart(npc, actorsInvolved[0],  actorsInvolved[1], actorsInvolved[2], actorsInvolved[3],actorsInvolved[4],"", "foreplay");
			
	endIf	
	
	if (command=="ExtCmdStartTitfuck~")
		
		If (StringUtil.find(parameter,Game.GetPlayer().GetDisplayName()) !=-1)
			; PLayer involved
			string result = SkyMessage.Show(npc.GetDisplayName()+ " wants to make you a titjob. Allow?", "No, thanks", "Yes, please!")
			if result == "Yes, please!"
				;
			else
				AIAgentFunctions.logMessageForActor("command@ExtCmdStartTitfuck@"+parameter+"@error. Player refused to start sex","funcret",npcname)
				return;
			endif	
		endif 
		
		Actor[] actorsInvolved=PrepareScene(npc,parameter);
		
		AIAgentFunctions.logMessage("ENJOY THE SEX SCENE!. NO QUEST, NO DUTIES NOW. ONLY PLEASURE","force_current_task")
		AIAgentFunctions.logMessageForActor("command@ExtCmdStartTitfuck@"+parameter,"funcret",npcname)
		
		mdi=AIAgentFunctions.get_conf_i("_max_distance_inside");
		mdo=AIAgentFunctions.get_conf_i("_max_distance_outside");
		
		Debug.Trace("[CHIM-NSFW] Enabling intimacy bubble: saving settings: "+mdi+","+mdo);
		
		AIAgentFunctions.setConf("_max_distance_inside",256,256,256);
		AIAgentFunctions.setConf("_max_distance_outside",256,256,256);
		;sslThreadController function QuickStart(actor a1, actor a2 = none, actor a3 = none, actor a4 = none, actor a5 = none, actor victim = none, string hook = "", string animationTags = "") global
		;SexLabFramework _slf = SexLabUtil.GetAPI() 
		;_slf.QuickStart(npc, actorsInvolved[0],  actorsInvolved[1], actorsInvolved[2], actorsInvolved[3],actorsInvolved[4],"", "boobjob");
	
			
	endIf	
	
	if (command=="ExtCmdStartSelfMasturbation")
		
		; Actor firing the event
		
		Actor[] actorsInvolved=PrepareScene(npc,"");
		AIAgentFunctions.logMessage("ENJOY THE SEX SCENE!. NO QUEST, NO DUTIES NOW. ONLY PLEASURE","force_current_task")
		AIAgentFunctions.logMessageForActor("command@ExtCmdStartSelfMasturbation@"+parameter,"funcret",npcname)
		
		mdi=AIAgentFunctions.get_conf_i("_max_distance_inside");
		mdo=AIAgentFunctions.get_conf_i("_max_distance_outside");
		
		Debug.Trace("[CHIM-NSFW] Enabling intimacy bubble: saving settings: "+mdi+","+mdo);
		
		AIAgentFunctions.setConf("_max_distance_inside",256,256,256);
		AIAgentFunctions.setConf("_max_distance_outside",256,256,256);
		
		OThread.QuickStart(actorsInvolved)
	
		
		Debug.Trace("[CHIM-NSFW] Launched Scene QuickStart mode");	
		
	endIf	
	
	
	if (command=="ExtCmdSexCommand")
		; 
		if (OActor.IsInOStim(npc))
			
			int thrId=StorageUtil.GetIntValue(npc, "ostimThreadId")
			int thrId2=OActor.GetSceneId(npc)
			
			String sceneId=OThread.GetScene(thrId2)
			
			Debug.Trace("[CHIM-NSFW] "+npc.GetDisplayName()+"@ExtCmdSexCommand@"+parameter+" thrId: "+thrId+" thrId2: "+thrId2);
			Actor[] actorsInvolved=OThread.GetActors(thrId)
			
			int i=0
			while i < actorsInvolved.Length
				Actor participant = actorsInvolved[i]
				
				Debug.Trace("[CHIM-NSFW] Participant: " + participant.GetDisplayName())
				
				if (participant == Game.GetPlayer())
					Debug.Trace("[CHIM-NSFW] Participant is player " + participant.GetDisplayName())
				else
					Debug.Trace("[CHIM-NSFW] Participant is NPC: " + participant.GetDisplayName())
				endif

				i += 1
			endwhile
		
			;OMetadata.FindActionForMate(string Id, int Position, string Type) Global Native
			;String newScene=OLibrary.GetRandomSceneWithMultiActorTagForAnyCSV(actorsInvolved,"")
			
			;String sequence=OSequence.GetRandomSequence(actorsInvolved)
			;OThread.PlaySequence(thrId, sequence, true)
			Debug.Trace("[CHIM-NSFW] GetScenesInRange: "+OCSV.ToCSVList(OLibrary.GetScenesInRange(sceneId,actorsInvolved,5)))
			Debug.Trace("[CHIM-NSFW] GetSceneTags: "+OCSV.ToCSVList(OMetadata.GetSceneTags(sceneId)))
			
			Debug.Trace("[CHIM-NSFW] GetActorTags 0: "+OCSV.ToCSVList(OMetadata.GetActorTags(sceneId,0)))
			Debug.Trace("[CHIM-NSFW] GetActorTags 1: "+OCSV.ToCSVList(OMetadata.GetActorTags(sceneId,1)))
			
			;Debug.Trace("[CHIM-NSFW] FindAllActionsCSV : "+(OMetadata.FindAllActionsCSV(sceneId,"blowjob").Length))
			;Debug.Trace("[CHIM-NSFW] FindAnyActionForActorCSV 1: "+(OMetadata.FindAnyActionForActorCSV(sceneId,1,"blowjob")))
			
			String[] tags=OMetadata.GetSceneTags(SceneID);
			String sceneTags=OCSV.ToCSVList(tags)
			
			
			string sanitizedTag="";
			
			if (StringUtil.Find(parameter,"blowjob")>=0)
				sanitizedTag="blowjob,deepthroat,lickingpenis"
				Utility.Wait(2); Give time to end speech
				AIAgentFunctions.setLocked(1,npcname)
				bool isMuted=npc.GetFactionRank(noFacialExpressionsFaction)>0
				if (isMuted);Actor is talking, lets wait to end speech
					int counter=1;
					while isMuted && counter < 15
						Utility.Wait(2); Give time to end speech
						counter = counter + 1 
						Debug.Trace("[CHIM-NSFW] want to use mouth but is still talking: "+npc.GetDisplayName())
						isMuted=npc.GetFactionRank(noFacialExpressionsFaction)>0
					endWhile
				endif
				
			elseif (StringUtil.Find(parameter,"boobjob")>=0)
				sanitizedTag="boobjob"
			elseif (StringUtil.Find(parameter,"analsex")>=0)
				sanitizedTag="analsex"
			elseif (StringUtil.Find(parameter,"cunnilingus")>=0)
				sanitizedTag="cunnilingus"
			elseif (StringUtil.Find(parameter,"frenchkissing")>=0)
				sanitizedTag="frenchkissing"
				Utility.Wait(2); Give time to end speech
				AIAgentFunctions.setLocked(1,npcname)
				bool isMuted=npc.GetFactionRank(noFacialExpressionsFaction)>0
				if (isMuted);Actor is talking, lets wait to end speech
					int counter=1;
					while isMuted && counter < 15
						Utility.Wait(2); Give time to end speech
						counter = counter + 1 
						Debug.Trace("[CHIM-NSFW] want to use mouth but is still talking: "+npc.GetDisplayName())
						isMuted=npc.GetFactionRank(noFacialExpressionsFaction)>0
					endWhile
				endif
					
			elseif (StringUtil.Find(parameter,"handjob")>=0)
				sanitizedTag="handjob"
			elseif (StringUtil.Find(parameter,"vaginalfingering")>=0)
				sanitizedTag="vaginalfingering"
			elseif (StringUtil.Find(parameter,"vaginalsex")>=0)
				sanitizedTag="vaginalsex"
				
			endif
			

			String actionScene2=OLibrary.GetRandomSceneWithAnyActionCSV(actorsInvolved,sanitizedTag)
			;String actionScene=OLibrary.GetRandomSceneWithAnyActionCSV(actorsInvolved,"blowjob")
			
			string furnitureType=OThread.GetFurnitureType(thrId2)
			if (furnitureType)
				actionScene2=OLibrary.GetRandomFurnitureSceneWithAnyActionCSV(actorsInvolved,furnitureType,sanitizedTag)
				if !actionScene2 
					actionScene2=OLibrary.GetRandomFurnitureScene(actorsInvolved,furnitureType)
				endif
			endif
			
			;String newScene=OLibrary.GetRandomSceneWithMultiActorTagForAnyCSV(actorsInvolved,"reversecowgirl")
			;String newScene2=OLibrary.GetRandomSceneWithMultiActorTagForAnyCSV(actorsInvolved,saniºtizedParameter)
			
			
			;String newScene=OLibrary.GetRandomScene(actorsInvolved)
			OThread.WarpTo(thrId,actionScene2,true)
			
			Debug.Trace("[CHIM-NSFW] <"+actionScene2+"> <"+sanitizedTag+">, Actors:"+actorsInvolved.length);			
		endif
	
	endif
EndEvent

Actor[] Function PrepareScene(Actor npc,String parameter)

	bool addplayer=false;
	
	
	String[] actorNames = StringUtil.Split(parameter,",")

	Int i = 0
	Int totalArraySize=0;
	While i < actorNames.Length 
		
		String currentNpcName = actorNames[i]
		; Get actor reference by name
		Actor foundNpc = AIAgentFunctions.getAgentByName(currentNpcName)
		
		If (StringUtil.find(currentNpcName,Game.GetPlayer().GetDisplayName()) !=-1)
			Debug.Trace("[CHIM-NSFW] [1] Adding (delayed) player <" + currentNpcName+">")	
			;actorsInvolved=PapyrusUtil.PushActor(actorsInvolved,Game.GetPlayer())
			totalArraySize=totalArraySize+1;
			addplayer=true
		endif
		if (foundNpc)
			Debug.Trace("[CHIM-NSFW] [1] Adding <" + currentNpcName+">")	
			totalArraySize=totalArraySize+1;
			;actorsInvolved=PapyrusUtil.PushActor(actorsInvolved,foundNpc)
			AIAgentFunctions.setAnimationBusy(1,currentNpcName)
		EndIf
		i = i+1
	EndWhile
	
	Actor[] actorsInvolved= PapyrusUtil.ActorArray(totalArraySize+1)
	int j=1
	actorsInvolved[0] = npc; Add caller NPC
	Debug.Trace("[CHIM-NSFW] [2] Adding action caller <" + npc.GetDisplayName()+">")	
	AIAgentFunctions.setAnimationBusy(1,npc.GetDisplayName())

	i = 0
	
	While i < actorNames.Length 
		
		String currentNpcName = actorNames[i]
		
		Actor foundNpc = AIAgentFunctions.getAgentByName(currentNpcName)
		
		If (StringUtil.find(currentNpcName,Game.GetPlayer().GetDisplayName()) !=-1)
			Debug.Trace("[CHIM-NSFW] [2] Adding (delayed) player <" + currentNpcName+">")	
			actorsInvolved[j]=Game.GetPlayer();
			j = j+1
		endif
		if (foundNpc)
			Debug.Trace("[CHIM-NSFW] [2] Adding <" + currentNpcName+">")	
			actorsInvolved[j]=foundNpc
			j = j+1
		EndIf
		
		i = i+1

	EndWhile
	
		
	return actorsInvolved;	
	
endFunction

; OSTIM related
Event OstimEvent(Int ThreadId,String type, Form eActor,Form eTarget,Form ePerformer)

	Debug.Trace("[CHIM-NSFW] OSTIM event: "+ThreadId+","+type+","+eActor.GetName())
	Debug.Trace("[CHIM-NSFW] OSTIM event: "+OThread.GetScene(ThreadId))

EndEvent

Event OStimStart(string eventName, string strArg, float numArg, Form sender)

	Debug.Trace("[CHIM-NSFW] OStimStart: "+eventName+","+StrArg+","+numArg+","+sender.GetName())
	if (sender.GetName()=="OSexIntegrationMainQuest")
		OSexIntegrationMain osex = sender as OSexIntegrationMain
	
	endif
	
	int threadID = numArg as int
	
	
EndEvent

Event OstimOrgasm(string eventName, string strArg, float numArg, Form sender)

	int threadID = numArg as int
	Actor orgasmer = sender as Actor

	Debug.Trace("[CHIM-NSFW] OstimOrgasm: "+eventName+","+StrArg+","+numArg+","+orgasmer.GetDisplayName())
	if (sender.GetName()=="OSexIntegrationMainQuest")
		OSexIntegrationMain osex = sender as OSexIntegrationMain
	
	endif
	
	Actor npc=AIAgentFunctions.getAgentByName(orgasmer.GetDisplayName());
	if (npc) 
		AIAgentFunctions.requestMessageForActor("The Narrator: "+orgasmer.GetDisplayName()+" had an orgasm!","chatnf_sl_climax",orgasmer.GetDisplayName())
	else
		AIAgentFunctions.logMessage(orgasmer.GetDisplayName()+" is orgasming","ext_nsfw_action")
	endif
	
;	Debug.Trace("[CHIM-NSFW] OSTIM event: "+OThread.GetScene(threadID))
EndEvent

Event OStimSceneChanged(string EventName, string SceneID, float NumArg, Form Sender)
	Debug.Trace("[CHIM-NSFW] OStimSceneChanged: "+EventName+","+SceneID+","+numArg+","+sender.GetName())
	
	string sexPos=SceneID
	
	String[] tags=OMetadata.GetSceneTags(SceneID);
	String sceneTags=OCSV.ToCSVList(tags)
	
	
	
	;String d1=OCSV.ToCSVList(OMetadata.GetActorTags(SceneID,0))
	;String d2=OCSV.ToCSVList(OMetadata.GetActorTags(SceneID,1))
	
	Debug.Trace("[CHIM-NSFW] OStimSceneChanged: Scene Tags: "+sceneTags)
	string furnitureType=OThread.GetFurnitureType(threadId)

	Debug.Trace("[CHIM-NSFW] Furniture used: "+furnitureType)
	
	Int threadId=numArg as Int
	Actor participantTalk = None;

	Actor[] participants=OThread.GetActors(threadId)
	String actorList;
	if (participants != none)
		int i = 0
		while i < participants.Length
			Actor participant = participants[i]
			; Do something with actor
			actorList=actorList+"/"+participant.GetDisplayName()
			
			
			String actorTags=OCSV.ToCSVList(OMetadata.GetActorTags(SceneID,i))
			Debug.Trace("[CHIM-NSFW] Participant: " + participant.GetDisplayName()+" tags:"+actorTags)
			
			if (participant == Game.GetPlayer())
				Debug.Trace("[CHIM-NSFW] Participant is player " + participant.GetDisplayName())
			else
				bool isMouthOpen=OActor.HasExpressionOverride(participant)
				if (isMouthOpen)
					Debug.Trace("[CHIM-NSFW] Participant is 'mouth busy' :" + participant.GetDisplayName())
					AIAgentFunctions.setLocked(1,participant.GetDisplayName())
				else
					Debug.Trace("[CHIM-NSFW] Participant is NOT 'mouth busy' " + participant.GetDisplayName())
					AIAgentFunctions.setLocked(0,participant.GetDisplayName())
					if (OMetadata.HasAnySceneTagCSV(SceneID,"sex,reversecowgirl,cowgirl,missionary,cunnilingus,doggystyle,prone")) ; Define this policy
						participantTalk=participant
					endif
				endif
				AIAgentFunctions.setAnimationBusy(1,participant.GetDisplayName())
			endif

			i += 1
		endwhile
	endif
	
	AIAgentFunctions.logMessage(sexPos+"/"+sceneTags+"/"+SceneID+actorList,"ext_nsfw_sexcene")
	
	if (participantTalk)
		float daysPassed=Utility.GetCurrentGameTime();
		float lastTalkedTime=StorageUtil.GetFloatValue(participantTalk, "chim_ostim_talk_cooldown", 0)
		if ((daysPassed-lastTalkedTime)>0.00694)	;30 irl seconds in in-game days passed
			float excitement=OActor.GetExcitement(participantTalk)
			Debug.Trace("[CHIM NSFW] "+participantTalk.GetDisplayName()+" is unmuted, excitement:"+excitement)
			if (excitement<=80)
				AIAgentFunctions.requestMessageForActor("","chatnf_sl",participantTalk.GetDisplayName())
				StorageUtil.SetFloatValue(participantTalk, "chim_ostim_talk_cooldown", daysPassed)
			else
				AIAgentFunctions.requestMessageForActor("","chatnf_sl_moan",participantTalk.GetDisplayName())
			endif
		else
			Debug.Trace("[CHIM-NSFW] Auto Talk in cooldown for "+participantTalk.GetDisplayName())
			AIAgentFunctions.requestMessageForActor("","chatnf_sl_moan",participantTalk.GetDisplayName())
		endif
	endIf
	
EndEvent

Event OStimEnd(string EventName, string Json, float NumArg, Form Sender)
	; the following code only works with API version 7.3.1 or higher
	Debug.Trace("[CHIM-NSFW] OStimEnd: "+EventName+","+Json+","+numArg+","+sender.GetName())
	Actor[] Actors = OJSON.GetActors(Json)
	string SceneID = OJSON.GetScene(Json)

	bool playerInScene=false
	int i=0;
	string scoring=""
	while i < Actors.Length
			Actor participant = Actors[i]
			
			Debug.Trace("[CHIM-NSFW] OStimEnd. Participant: " + participant.GetDisplayName())
			
			if (participant == Game.GetPlayer())
				Debug.Trace("[CHIM-NSFW] OStimEnd. Participant is player " + participant.GetDisplayName())
				playerInScene = true
			else
				AIAgentFunctions.setLocked(0,participant.GetDisplayName())
				AIAgentFunctions.setAnimationBusy(0,participant.GetDisplayName())
			endif

			scoring="/"+ participant.GetDisplayName()+"@100";

			i += 1
	endwhile
	
	; Will do on thread end
	;AIAgentFunctions.requestMessage(scoring,"chatnf_sl_end")

	; Change this to restore from CHIM MCM directly
	if (playerInScene)
		Debug.Trace("[CHIM-NSFW] Restoring settings because intimacy bubble");
		
		AIAgentFunctions.setConf("_max_distance_inside",mdi,mdi,mdi);
		AIAgentFunctions.setConf("_max_distance_outside",mdo,mdo,mdo);
	endif;
	
	
	AIAgentFunctions.logMessage("","force_current_task")
		
EndEvent

Event OStimThreadStart(string EventName, string Json, float ThreadID, Form Sender)

	Actor[] Actors = OJSON.GetActors(Json)
	string SceneID = OJSON.GetScene(Json)
	Debug.Trace("[CHIM-NSFW] OStimThreadStart: "+EventName+","+Json+","+ThreadID+","+sender.GetName())
EndEvent

Event OStimThreadSceneChanged(string EventName, string SceneID, float ThreadID, Form Sender)
EndEvent

Event OStimActorOrgasm(string EventName, string SceneID, float ThreadID, Form Sender)
	Actor OrgasmedActor = Sender as Actor
	
	Debug.Trace("[CHIM-NSFW] OStimActorOrgasm: "+EventName+","+SceneID+","+ThreadID+","+sender.GetName())


EndEvent

Event OStimThreadEnd(string EventName, string Json, float ThreadID, Form Sender)
	; the following code only works with API version 7.3.1 or higher
	Actor[] Actors = OJSON.GetActors(Json)
	string SceneID = OJSON.GetScene(Json)
	Debug.Trace("[CHIM-NSFW] OStimThreadEnd: "+EventName+","+Json+","+ThreadID+","+sender.GetName())
	int i = 0 
	string scoring=""

	while i < Actors.Length
			Actor participant = Actors[i]
			
			Debug.Trace("[CHIM-NSFW] OStimThreadEnd. Participant: " + participant.GetDisplayName())
			
			if (participant == Game.GetPlayer())
				Debug.Trace("[CHIM-NSFW] OStimThreadEnd. Participant is player " + participant.GetDisplayName())
			else
				AIAgentFunctions.setLocked(0,participant.GetDisplayName())
				AIAgentFunctions.setAnimationBusy(0,participant.GetDisplayName())
			endif

			scoring="/"+ participant.GetDisplayName()+"@100";

			i += 1
	endwhile
	
	AIAgentFunctions.requestMessage(scoring,"chatnf_sl_end")

EndEvent


; SEXLAB RELATED

;/
function StartIntimateSceneWithPlayer(Actor npc, int level=0,string tags)
	
	SexLabFramework _slf = SexLabUtil.GetAPI() 
	
	_slf.QuickStart(npc,Game.GetPlayer(),none, none,  none, none, "", tags);
	
endFunction

 Event OnAnimationStart(int tid, bool HasPlayer)

	SexLabFramework SexLab = SexLabUtil.GetAPI() 
	
	Actor[] actorList = SexLab.GetController(tid).Positions
	Actor[] sortedActorList = SexLab.SortActors(actorList,true)
	int i = sortedActorList.Length
	bool playerInScene=false
	while(i > 0)
            i -= 1
            if (sortedActorList[i].GetFormID()==0x14) 
				playerInScene=true;
			else
				AIAgentFunctions.setAnimationBusy(1,sortedActorList[i].GetDisplayName())
			endif
    endwhile
	
	if (playerInScene)
		
	endif;
	
	Debug.Notification("[CHIM-NSFW] Started intimate scene")
	Debug.Trace("[CHIM-NSFW] Started intimate scene")
	
EndEvent


Event OnStageStart(int tid, bool HasPlayer)

		SexLabFramework SexLab = SexLabUtil.GetAPI() 
		sslThreadController controller = SexLab.GetController(tid)
		; Why OnAnimationStart isnt registering?
		if (controller.Stage==1) 
		endif
		

		Actor[] actorList = SexLab.GetController(tid).Positions
		Bool playerInScene=false;
		Actor[] targetactorList = actorList
		Int howmuch
		
		If (actorList.length < 1)
			return
		EndIf
		
		String pleasure=""

		Actor[] sortedActorList = SexLab.SortActors(actorList,false)
		
		
		int i = sortedActorList.Length
		;while(i > 0)
        ;    i -= 1
        ;    pleasure=pleasure+sortedActorList[i].GetDisplayName()+" pleasure score "+SexLab.GetEnjoyment(tid,sortedActorList[i])+","
        ;endwhile
		
		String sceneTags=""+controller.Animation.GetTags()+"/"
		if (controller.Animation.GetTags()=="")
			sceneTags="/";
		EndIf
		
		String sexPos="" +controller.Animation.Name+"/";
		;String pleasureFull=pleasure
		
		String description1=controller.Animation.FetchStage(controller.Stage)[0]+"/"
		String description2="";
		i = actorList.Length
		while(i > 0)
            i -= 1
			description2=description2+actorList[i].GetDisplayName()+"/"
			if (actorList[i]==Game.GetPlayer())
				playerInScene=true;
			else
				if (SexLab.isMouthOpen(actorList[i]))
					Debug.Trace("[CHIM NSFW] "+actorList[i].GetDisplayName()+" has mouth open");
					AIAgentFunctions.setLocked(1,actorList[i].GetDisplayName())
				else
					AIAgentFunctions.setLocked(0,actorList[i].GetDisplayName())
				endif
			endif

            ;pleasure=pleasure+sortedActorList[i].GetDisplayName()+" pleasure score "+SexLab.GetEnjoyment(tid,sortedActorList[i])+","
        endwhile
		
		
		Actor firstPartipant=actorList[0]; Get Female (assuming player is male, and he is having part in this sex scene)
		
		; Send event, AI can be aware SEX is happening here
		AIAgentFunctions.logMessage(sexPos+sceneTags+description1+description2,"info_sexscene")
		;.GetDisplayName()+ " and "+actorList[1].GetDisplayName()+ " are having a intimate moment."+description+description2+"("+pleasureFull+")","infoaction")
		
		Utility.wait(1);
		
		i = sortedActorList.Length
		bool participantTalk=false
		while(i > 0)
            i -= 1
			Actor participant=sortedActorList[i];
			if participant.GetDisplayName()!=Game.GetPlayer().GetDisplayName()
				if (!SexLab.isMouthOpen(participant))
					AIAgentFunctions.requestMessageForActor("","chatnf_sl",participant.GetDisplayName())
					participantTalk=true;
					i=0
				else
					Debug.Trace("[CHIM NSFW] "+actorList[i].GetDisplayName()+" has mouth open");
				endif
				
			endif
        endwhile
		
		if (!participantTalk)
			AIAgentFunctions.requestMessageForActor("The Narrator: seems everyone is **busy**. Narrator is hot too and comments about the scene","chatnf_sl_nr","The Narrator")
		endIf
		
EndEvent

Event PostSexScene(int tid, bool HasPlayer)
	
		SexLabFramework SexLab = SexLabUtil.GetAPI() 

		sslThreadController controller = SexLab.GetController(tid)

		Actor[] actorList = SexLab.HookActors(tid)
		Actor[] targetactorList = actorList
		Int howmuch
		

		If (actorList.length < 1)
			return
		EndIf
		
		String pleasure=""

		int i = actorList.Length
		while(i > 0)
            i -= 1
            pleasure=pleasure+actorList[i].GetDisplayName()+" is reaching orgasm,"
        endwhile
		String pleasureFull="Pleasure:"+pleasure
		; Send event, AI can be aware SEX is happening here
		AIAgentFunctions.logMessage(pleasureFull,"infoaction")
		Utility.wait(1);
		
		Actor[] sortedActorList = SexLab.SortActors(actorList,true)
		Actor firstPartipant=actorList[0]; Get Female (assuming player is male, and he is having part in this sex scene)
					
		i = sortedActorList.Length
		while(i > 0)
            i -= 1
			Actor participant=sortedActorList[i];
			if participant.GetDisplayName()!=Game.GetPlayer().GetDisplayName()
				if (!SexLab.isMouthOpen(participant))
					AIAgentFunctions.requestMessageForActor("The Narrator: "+participant.GetDisplayName()+" had an orgasm!","chatnf_sl_climax",participant.GetDisplayName())
				endif
			endif
        endwhile
		
EndEvent

Event EndSexScene(int tid, bool HasPlayer)
	
		SexLabFramework SexLab = SexLabUtil.GetAPI() 

		JValue.release(descriptionsMap)
		Debug.Notification("[CHIM-NSFW] Ended intimate scene")
		sslThreadController controller = SexLab.GetController(tid)

		Actor[] actorList = SexLab.HookActors(tid)
		Actor[] targetactorList = actorList
		Int howmuch
		Actor[] sortedActorList = SexLab.SortActors(actorList,true)

		int i = sortedActorList.Length
		string score=""
		while(i > 0)
            i -= 1
            score=score+"/"+sortedActorList[i].GetDisplayName()+"@"+SexLab.GetEnjoyment(tid,sortedActorList[i])
			
        endwhile
		
		; Send event, AI can be aware SEX is happening here
			
		AIAgentFunctions.logMessage("# END OF SEX SCENE","infoaction")
		
		; POst comment
		Utility.wait(1);
		AIAgentFunctions.requestMessage(score,"chatnf_sl_end")

		bool playerInScene=false
		i = actorList.Length
		while(i > 0)
				i -= 1
				if (sortedActorList[i].GetFormID()==0x14) 
					playerInScene=true;
				else
					AIAgentFunctions.setAnimationBusy(1,sortedActorList[i].GetDisplayName())
				endif
		endwhile
		
		; Change this to restore from CHIM MCM directly
		if (playerInScene)
			Debug.Trace("Restoring settings because intimacy bubble");
			
			AIAgentFunctions.setConf("_max_distance_inside",mdi,mdi,mdi);
			AIAgentFunctions.setConf("_max_distance_outside",mdo,mdo,mdo);
		endif;
		
		i = sortedActorList.Length
		while(i > 0)
            i -= 1
			Actor participant=actorList[i];
			if participant.GetDisplayName()!=Game.GetPlayer().GetDisplayName()
				AIAgentFunctions.setAnimationBusy(1,participant.GetDisplayName())
				AIAgentFunctions.setLocked(0,participant.GetDisplayName())
			endif
			
        endwhile
		
		AIAgentFunctions.logMessage("","force_current_task")
	
EndEvent


/;

; UTILITIES

 Armor Function GetBestArmorForSlot(Actor akActor, Int aiSlotMask)
    Armor bestArmor = None
    Float bestRating = 0.0
    
    Int itemCount = akActor.GetNumItems()
    
    Int i = 0
    While (i < itemCount)
        Form itemForm = akActor.GetNthForm(i)
        
        If (itemForm.GetType() == 26) ; ARMO - Armor
            Armor armorItem = itemForm as Armor
            
            ; Check if armor fits the slot mask
            If (armorItem.GetSlotMask() == aiSlotMask)
                Float armorRating = armorItem.GetArmorRating()
                Debug.Trace("[CHIM NSFW] Checking "+itemForm.GetName()+ " "+itemForm.GetType());
                ; Compare with current best
                If (armorRating >= bestRating)
                    bestRating = armorRating
                    bestArmor = armorItem
                EndIf
            EndIf
        EndIf
        
        i += 1
    EndWhile
    
    Return bestArmor
EndFunction

Weapon Function GetBestWeapon(Actor akActor)
    Weapon bestWeapon = None
    Float bestDamage = 0.0
    
    Int itemCount = akActor.GetNumItems()
    
    Int i = 0
    While (i < itemCount)
        Form itemForm = akActor.GetNthForm(i)
        
        If (itemForm.GetType() == 41) ; WEAP - Weapon
            Weapon weaponItem = itemForm as Weapon
            Float weaponDamage = weaponItem.GetBaseDamage()
            
            ; Compare with current best
            If (weaponDamage > bestDamage)
                bestDamage = weaponDamage
                bestWeapon = weaponItem
            EndIf
        EndIf
        
        i += 1
    EndWhile
    
    Return bestWeapon
EndFunction

Function GSPoseRemoveClothes(Actor aktarget, Actor akcaster)


	aktarget.SetAnimationVariableInt("IsNPC", 1) ; disable head tracking
	aktarget.SetAnimationVariableBool("bHumanoidFootIKDisable", True) ; disable inverse kinematics
	debug.sendanimationevent(aktarget, "stripteasehands")
	utility.wait(2.5)
	akTarget.unequipitemslot(33) ;hands
	akTarget.unequipitemslot(34) ;forearms
	
	debug.sendanimationevent(aktarget, "stripteaseshoes")
	utility.wait(3)
	akTarget.unequipitemslot(37) ;feet
	akTarget.unequipitemslot(38) ;calves
	
	debug.sendanimationevent(aktarget, "stripteasebuttom")
	utility.wait(3)
	akTarget.unequipitemslot(52) ;pelvis secondary or undergarment
	akTarget.unequipitemslot(49) ;pelvis primary or outergarment
	
	debug.sendanimationevent(aktarget, "stripteasetop")
	utility.wait(3)
	akTarget.unequipitemslot(32) ;body (full)
	akTarget.unequipitemslot(46) ;chest primary or outergarment
	akTarget.unequipitemslot(56) ;chest secondary or undergarment
	
	debug.sendanimationevent(aktarget, "IdleDialogueExpresiveStart")
	utility.wait(3)
	aktarget.SetAnimationVariableInt("IsNPC", 1) ; enable head tracking
	aktarget.SetAnimationVariableBool("bHumanoidFootIKDisable", False) ; enable inverse kinematics
	
	
endfunction

Function StartStripTease(Actor aktarget, Actor akcaster)
	
	AIAgentFunctions.setAnimationBusy(1,aktarget.GetDisplayName())	
	aktarget.SetAnimationVariableInt("IsNPC", 1) ; disable head tracking
	aktarget.SetAnimationVariableBool("bHumanoidFootIKDisable", True) ; disable inverse kinematics
	debug.sendanimationevent(aktarget, "gs44")
	utility.wait(3)
	debug.sendanimationevent(aktarget, "gs1")
	utility.wait(3)
	debug.sendanimationevent(aktarget, "gs25")
	utility.wait(3)
	debug.sendanimationevent(aktarget, "stripteasehands")
	utility.wait(2.5)
	akTarget.unequipitemslot(33) ;hands
	akTarget.unequipitemslot(34) ;forearms
	debug.sendanimationevent(aktarget, "gs2")
	utility.wait(3)

	debug.sendanimationevent(aktarget, "gs103")
	utility.wait(3)
	debug.sendanimationevent(aktarget, "gs52")
	utility.wait(3)
	debug.sendanimationevent(aktarget, "stripteaseshoes")
	utility.wait(3)
	akTarget.unequipitemslot(37) ;feet
	akTarget.unequipitemslot(38) ;calves
	debug.sendanimationevent(aktarget, "gs3")
	utility.wait(3)
	debug.sendanimationevent(aktarget, "gs30")
	utility.wait(3)
	debug.sendanimationevent(aktarget, "gs54")
	utility.wait(3)
	debug.sendanimationevent(aktarget, "stripteasebuttom")
	utility.wait(3)
	akTarget.unequipitemslot(52) ;pelvis secondary or undergarment
	akTarget.unequipitemslot(49) ;pelvis primary or outergarment
	debug.sendanimationevent(aktarget, "stripteasetop")
	utility.wait(3)
	akTarget.unequipitemslot(32) ;body (full)
	akTarget.unequipitemslot(46) ;chest primary or outergarment
	akTarget.unequipitemslot(56) ;chest secondary or undergarment
	debug.sendanimationevent(aktarget, "gs16")
	utility.wait(3)
	debug.sendanimationevent(aktarget, "gs35")
	utility.wait(3)
	debug.sendanimationevent(aktarget, "gs97")
	utility.wait(3)
	aktarget.unequipall()
	debug.sendanimationevent(aktarget, "gs17")
	utility.wait(3)
	debug.sendanimationevent(aktarget, "gs39")
	utility.wait(3)
	debug.sendanimationevent(aktarget, "gs37")
	utility.wait(2.5)
	debug.sendanimationevent(aktarget, "idleforcedefaultstate")

	aktarget.SetAnimationVariableInt("IsNPC", 1) ; enable head tracking
	aktarget.SetAnimationVariableBool("bHumanoidFootIKDisable", False) ; enable inverse kinematics
	AIAgentFunctions.setAnimationBusy(0,aktarget.GetDisplayName())	

EndFunction

function FastRemoveClothes(Actor npc)
	Form[] equippedItems=PO3_SKSEFunctions.AddAllEquippedItemsToArray(npc);
	Int iElement = equippedItems.Length
	Int iIndex = 0
	
		
	while iIndex < equippedItems.Length
		Form currentItem = equippedItems[iIndex]
		Armor armorItem = currentItem as Armor

		if armorItem != None
			; npc.UnequipItem(armorItem, false, false)
			int slotMask = armorItem.GetSlotMask()

			; These are standard head-related slots
			bool isHeadItem = (Math.LogicalAnd(slotMask, 0x00000001) != 0 || Math.LogicalAnd(slotMask, 0x00000002) != 0 || Math.LogicalAnd(slotMask, 0x00000800) != 0 || Math.LogicalAnd(slotMask, 0x00001000) != 0)

			if (Math.LogicalAnd(slotMask, 0x00000004) != 0)	; Explicity if body. Robes also occup head slot
				isHeadItem=false;
			endif
			
			if !isHeadItem
				npc.UnequipItem(armorItem, false, false)
			endif
		endif
		
		Weapon WeaponItem = currentItem as Weapon

		if WeaponItem != None
			npc.UnequipItem(WeaponItem, false, false)
		endif
		
		;Utility.Wait(1)
		iIndex += 1
	endwhile
endfunction

function UnequipItemBySlot(Actor npc, int slot)
	Form[] equippedItems=PO3_SKSEFunctions.AddAllEquippedItemsToArray(npc);
	Int iElement = equippedItems.Length
	Int iIndex = 0
	
		
	while iIndex < equippedItems.Length
		Form currentItem = equippedItems[iIndex]
		Armor armorItem = currentItem as Armor

		if armorItem != None
			; npc.UnequipItem(armorItem, false, false)
			Debug.Trace("[CHIM NSFW] Checking "+armorItem.getName());
			int slotMask = armorItem.GetSlotMask()

			
			bool matchesSlot = Math.LogicalAnd(slotMask, slot) != 0 

			
			if matchesSlot
				npc.UnequipItem(armorItem, false, false)
			endif
		endif
		
		iIndex += 1
	endwhile
endfunction

Function Dance(Actor aktarget, Actor akcaster)
		
	aktarget.SetAnimationVariableInt("IsNPC", 1) ; disable head tracking
	aktarget.SetAnimationVariableBool("bHumanoidFootIKDisable", True) ; disable inverse kinematics
	debug.sendanimationevent(aktarget, "gs44")
	utility.wait(3)
	debug.sendanimationevent(aktarget, "gs1")
	utility.wait(3)
	debug.sendanimationevent(aktarget, "gs25")
	utility.wait(3)
	debug.sendanimationevent(aktarget, "gs2")
	utility.wait(3)

	debug.sendanimationevent(aktarget, "gs103")
	utility.wait(3)
	debug.sendanimationevent(aktarget, "gs52")
	utility.wait(3)

	debug.sendanimationevent(aktarget, "gs3")
	utility.wait(3)
	debug.sendanimationevent(aktarget, "gs30")
	utility.wait(3)
	debug.sendanimationevent(aktarget, "gs54")
	utility.wait(3)
	debug.sendanimationevent(aktarget, "gs16")
	utility.wait(3)
	debug.sendanimationevent(aktarget, "gs35")
	utility.wait(3)
	debug.sendanimationevent(aktarget, "gs97")
	utility.wait(3)
	debug.sendanimationevent(aktarget, "gs17")
	utility.wait(3)
	debug.sendanimationevent(aktarget, "gs39")
	utility.wait(3)
	debug.sendanimationevent(aktarget, "gs37")
	utility.wait(2.5)
	debug.sendanimationevent(aktarget, "idleforcedefaultstate")

	aktarget.SetAnimationVariableInt("IsNPC", 1) ; enable head tracking
	aktarget.SetAnimationVariableBool("bHumanoidFootIKDisable", False) ; enable inverse kinematics

EndFunction


