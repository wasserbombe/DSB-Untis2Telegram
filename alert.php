<?php
	include 'config.php';

	$ttoken = $_CONFIG['telegram']['token'];
	
	// read mode from CLI
	$_REQUEST = array_merge($_REQUEST, getopt("", array("mode::")));

	$alerts = $_CONFIG["alerts"];

	foreach ($alerts as $alert){
		$sendmsg = false;
		$msg = "";
		$ts = time();
		$date = date("Y-m-d", $ts);
		if (!empty($_REQUEST) && $_REQUEST["mode"] == "evening"){
			// Nachricht abends
			$sendmsg = true;
			
			$msg = "";
			$msg .= "<b>N'Abend!</b>\n";
			$ts = time()+60*60*24;
			$date = date("Y-m-d", $ts);
			$msg .= "Morgen, am ".date("d.m.Y", $ts).", gibt es ";

			$dbres = $DB->query("SELECT  
							*
						FROM substitutes s
						WHERE 
							(class LIKE ".$DB->prep($alert["classes"][0])." OR LOWER(class) IN ('".$alert["classes"][0]."_".implode("','".$alert["classes"][0]."_",$alert["subjects"])."') OR LOWER(class) = ".$DB->prep(strtolower($alert["classes"][0])."_").")
							AND `date` = ".$DB->prep($date)."
							AND LOWER(`subject`) IN ('".implode("','",$alert["subjects"])."')
							;")->fetchAll();
			
			
		} elseif (!empty($_REQUEST) && $_REQUEST["mode"] == "morning"){
			// Nachricht morgens
			$sendmsg = true;
			
			$msg = "";
			$msg .= "<b>Einen wunderschönen guten Morgen!</b>\nHeute, am ".date("d.m.Y", $ts).", gibt es ";

			$dbres = $DB->query("SELECT  
							*
						FROM substitutes s
						WHERE 
							(class LIKE ".$DB->prep($alert["classes"][0])." OR LOWER(class) IN ('".$alert["classes"][0]."_".implode("','".$alert["classes"][0]."_",$alert["subjects"])."') OR LOWER(class) = ".$DB->prep(strtolower($alert["classes"][0])."_").")
							AND `date` = ".$DB->prep($date)."
							AND LOWER(`subject`) IN ('".implode("','",$alert["subjects"])."')
							;")->fetchAll();
		} else {
			// Nachricht bei Änderungen
			$sendmsg = false;

			$sql = "SELECT  
						*
					FROM substitutes s
					WHERE 
						(class LIKE ".$DB->prep($alert["classes"][0])." OR LOWER(class) IN ('".$alert["classes"][0]."_".implode("','".$alert["classes"][0]."_",$alert["subjects"])."') OR LOWER(class) = ".$DB->prep(strtolower($alert["classes"][0])."_").")
						AND `date` = ".$DB->prep($date)."
						AND (LOWER(`subject`) IN ('".implode("','",$alert["subjects"])."') OR LENGTH(subject) = 0 OR subject = ' ')
						;";
						//echo $sql; 
			$dbres = $DB->query($sql)->fetchAll();

			$stand = $DB->query("SELECT * FROM lastupdates WHERE `date` = ".$DB->prep($date).";")->fetch();
			$stand = $stand["last_update"];
			// Already sent?
			$sent = $DB->query("
				SELECT *
				FROM notifications n
				WHERE n.date = ".$DB->prep($date)."
					AND (n.last_update = ".$DB->prep($stand)." OR n.last_update = 0)
					AND n.recipient = ".$DB->prep($alert["chat_id"]).";")->fetchAll();
			if ($sent){

			} else {
				$sendmsg = true;
				$msg .= "Der Vertretungsplan für heute wurde gerade aktualisiert.\nEs gibt "; 
			}
		}

		// Ausfälle / Vertretungsstunden
		if ($dbres){
			if (sizeof($dbres) == 1){
				$msg .= "<b>eine</b> Änderung";
			} else {
				$msg .= "<b>".sizeof($dbres)."</b> Änderungen";
			}
			$msg .= " für die Klasse ".strtoupper($alert["classes"][0]).", die Dich betreffen:\n";
			foreach ($dbres as $subst){
				$msg .= "&#128204; ".$subst["lesson"].". Stunde (".$subst["subject"].") ";
				if ($subst["dropped"]){
					$msg .= "fällt aus";
				} else {
					if ($subst["subject"] == $subst["subject_substitute"]){
						$msg .= "wird von <i>".$subst["teacher_substitute"]."</i> in <i>Raum ".$subst["room"]."</i> vertreten";
					} else {
						$msg .= "wird durch <i>".$subst["subject_substitute"]."</i> ersetzt und findet in <i>Raum ".$subst["room"]."</i> statt";
					}
				}
				if (strlen($subst["comment"])){
					$msg .= " (".$subst["comment"].")";
				}
				$msg .= ".\n";
			}
			$msg .= "\n";
		} else {
			if (!in_array($_REQUEST["mode"], array("morning","evening"))){
				$msg .= "aber ";
			}
			$msg .= "<b>keine</b> Änderungen, die Dich betreffen.\n\n";
		}

		// Infos
		$dbres = $DB->query("SELECT 
								* 
							FROM infos 
							WHERE `date` = ".$DB->prep($date).";")->fetchAll();
		$infos_class = array();
		$infos_other = array();

		foreach ($dbres as $info){
			if (!preg_match("~(^| )([1-9]{1,2}[a-g]{1}|K[12])( |$)~i", $info["text"])){
				$infos_other[] = $info;
			} elseif (preg_match("~\b".$alert["classes"][0]."\b~i", $info["text"])){
				$infos_class[] = $info;
			}
		}

		if (sizeof($infos_other) || sizeof($infos_class)){
			$msg .= "<b>INFOS</b>\n";
		}
		if (sizeof($infos_class)){
			$msg .= "... die ".strtoupper($alert["classes"][0])." betreffen:\n";
			foreach ($infos_class as $info){
				$msg .= "&#128204; ".$info["text"]."\n";
			}
			$msg .= "\n";
		}
		if (sizeof($infos_other)){
			$msg .= "... die alle betreffen (oder keiner Klasse zuzuordnen waren):\n";
			foreach ($infos_other as $info){
				$msg .= "&#128204; ".$info["text"]."\n";
			}
			$msg .= "\n";
		}

		// Stand
		$dbres = $DB->query("SELECT * FROM lastupdates WHERE `date` = ".$DB->prep($date).";")->fetch();
		if ($dbres){
			$msg .= "Stand: ".date("d.m., H:i", $dbres["last_update"])." Uhr\n";
			$dbres["urls"] = json_decode($dbres["urls"], true);
			foreach ($dbres["urls"] as $u => $url){
				$msg .= "<a href='".$url."'>Seite ".($u+1)."</a>";
				if ($u < sizeof($dbres["urls"])-1){
					$msg .= " | ";
				}
			}
		}

		$msg .= "\n\n&#9888; <b>BETA-Modus</b> aktiv.";

				
		if ($sendmsg){
			$request = array(
				"chat_id"=>$alert["chat_id"],
				"text"=>$msg,
				"parse_mode"=>"html"
			);
			//var_dump($request);
				
			$ch = curl_init();
			curl_setopt($ch,CURLOPT_URL, "https://api.telegram.org/bot".$ttoken."/sendMessage");
			//curl_setopt($ch,CURLOPT_POST, count($fields));
			$request_str = json_encode($request);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
			curl_setopt($ch,CURLOPT_POSTFIELDS, $request_str);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
				'Content-Type: application/json',                                                                                
				'Content-Length: ' . strlen($request_str))                                                                       
			);             
			$result = json_decode(curl_exec($ch), true);
			curl_close($ch);
			if ($result["ok"] !== true){
				echo "ERROR!!!!";
			} else {
				$notification = array(
					"date"=>$date,
					"last_update"=>$dbres["last_update"]?:0,
					"recipient"=>$alert["chat_id"],
					"message_id"=>$result["result"]["message_id"],
					"sent"=>time(),
					"lastupdate"=>time(),
					"created"=>time()
				);
				$DB->insert()->into("notifications")->values($notification)->on_duplicate_key_update_with_value(array("message_id","sent","lastupdate"))->exec();
				//var_dump($notification);
			}
			//var_dump($result);		
		}
	}

?>