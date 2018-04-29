<?php
	include 'config.php';
	
	$data = array(
		"UserId"=>$_CONFIG['dsb']['UserId'],
		"UserPw"=>$_CONFIG['dsb']['UserPw'],
		"Abos"=>array(),
		"Language"=>"de-DE",
		"OsVersion"=>"11.1",
		"AppVersion"=>"2.5.6",
		"AppId"=>"31966489-158C-4793-924D-224A258F74B0",
		"Device"=>"iPad",
		"PushId"=>"",
		"BundleId"=>"de.digitales-schwarzes-brett.dsblight",
		"Date"=>"2017-11-04T23:36:46+01:00",
		"LastUpdate"=>"2017-11-04T22:31:06+01:00"
	);
	$data = array(
		"req"=>array(
			"Data"=>base64_encode(gzencode (json_encode($data))),
			"DataType"=>1
		)
	);
	$c = json_encode($data);

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 0);
	
	//curl_setopt($ch, CURLOPT_PROXY, '192.168.1.166:8888');
	//curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	//curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		'Content-Type: application/json; charset=utf-8',
		'Accept: */*',
		'Accept-Encoding: gzip, deflate',
		'Accept-Language: de-DE;q=1',
		'User-Agent: DSB2Telegram script',
    ));

	curl_setopt($ch, CURLOPT_URL, "https://app.dsbcontrol.de/JsonHandler.ashx/GetData");
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $c);  //Post Fields
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	
	$raw = curl_exec ($ch);

	curl_close ($ch);

	$vertretungen = $dates = array();
	$current_class = "";
	if ($raw){
		$data = json_decode($raw, true);
		if ($data){
			if ($data["d"]){
				$data = json_decode(gzdecode(base64_decode($data["d"])), true);
				if ($data){
					if ($data["ResultMenuItems"]){
						foreach ($data["ResultMenuItems"] as $menuitem){
							if ($menuitem["Title"] == "Inhalte"){
								foreach ($menuitem["Childs"] as $menuitemchild){
									if ($menuitemchild["Title"] == "Pläne"){
										foreach ($menuitemchild["Root"]["Childs"] as $child){
											foreach ($child["Childs"] as $p => $page){
												$cache_fn = __DIR__."/cache/".md5($page["Detail"])."_".strtotime($page["Date"]).".html";
												if (!file_exists($cache_fn)){
													file_put_contents($cache_fn, file_get_contents($page["Detail"]));
												}
												$html = file_get_contents($cache_fn);
												$html = str_replace("\r\n","",$html);
												$html = preg_replace("~<style.*<\/style>~i","",$html);
												$html = preg_replace("~ ?style=\"[^\"]+\"~i","",$html);

												// parsing data
												// TODO: update to use DOMDocument instead of regex
												// <div class="mon_title">6.11.2017 Montag (Seite 1 / 2)</div>
												preg_match("~<div class=\"mon_title\">([^ ]+) ~i", $html, $matches); 
												$day = date("Y-m-d", strtotime($matches[1]));
												if (!isset($dates[$day])) $dates[$day] = array("last_update"=>"","urls"=>array(),"lastupdate"=>time(),"created"=>time());

												//  Stand: 05.11.2017 07:00</
												preg_match("~Stand: ([0-9]{2}\.[0-9]{2}\.[0-9]{4} [0-9]{2}:[0-9]{2})~i", $html, $matches); 
												//echo "Stand: ".date("Y-m-d H:i:s", strtotime($matches[1]))."\n";
												$dates[$day]["last_update"] = strtotime($matches[1]);
												$dates[$day]["urls"][] = $page["Detail"];

												$doc= new DOMDocument();
												@$doc->loadHTML($html);

												// Vertretungen
												$nodes = $doc->getElementsByTagName("tr");
												foreach ($nodes as $node){
													if (in_array("list", explode(" ",$node->getAttribute("class")))){
														$tds = $node->getElementsByTagName("td");
														if ($tds->length == 1 && in_array("inline_header", explode(" ",$tds[0]->getAttribute("class")))){
														//	echo "Klasse ".$tds[0]->textContent."\n";
															$current_class = strtolower(str_replace(" ","", $tds[0]->textContent));
														} elseif ($tds->length == 8){
															$vertretung_template = array(
																"date"=>$day,
																"class"=>$current_class,
																"lesson"=>$tds[0]->textContent,
																"teacher"=>$tds[1]->textContent,
																"teacher_substitute"=>$tds[2]->textContent,
																"subject"=>str_replace(" ", "", $tds[3]->textContent),
																"subject_substitute"=>$tds[4]->textContent,
																"room"=>$tds[5]->textContent,
																"dropped"=>(int)($tds[6]->textContent == "x"?true:false),
																"comment"=>$tds[7]->textContent,
																"lastupdate"=>time(),
																"created"=>time()
															);
															// empty comments are useless
															if (!preg_match("~[a-z0-9\?\!]~i", $vertretung_template["comment"])) $vertretung_template["comment"] = "";

															if (preg_match("~([0-9]{1,2}) ?- ?([0-9]{1,2})~i", $vertretung_template["lesson"], $matches_stunden)){
																if (
																	$matches_stunden[1] >= 0 && $matches_stunden[1] < 16 		// start lesson between 0 and 15
																	&& $matches_stunden[2] >= 0 && $matches_stunden[2] < 16 	// end lesson between 0 and 15
																	&& $matches_stunden[1] < $matches_stunden[2] 			 	// end lesson after start lesson
																){
																	for ($i = $matches_stunden[1]; $i <= $matches_stunden[2]; $i++){
																		$vertretung = $vertretung_template; 
																		$vertretung["lesson"] = $i;
																		$vertretungen[] = $vertretung;
																	}
																}
															} else {
																$vertretungen[] = $vertretung_template;
															}
														} else {
															//echo "Malformed tr!";
														}
													}
												}

												// parse additional infos/comments in header
												if (sizeof($dates[$day]["urls"]) == 1){
													$trs = $doc->getElementsByTagName("tr");
													$DB->exec("DELETE FROM infos WHERE date = ".$DB->prep($day).";");
													foreach ($trs as $tr){
														if (in_array("info", explode(" ",$tr->getAttribute("class")))){
															$tds = $tr->getElementsByTagName("td");
															if ($tds->length == 1){
																$plus_infos = explode(" +++ ", $tds[0]->textContent);
																foreach ($plus_infos as $plus_info){
																	$info = array(
																		"date"=>$day,
																		"text"=>str_replace("\r","",str_replace("\n","", $plus_info)),
																		"lastupdate"=>time(),
																		"created"=>time()
																	);
																	$DB->insert()->into("infos")->values($info)->exec();
																}
																
															}
														}
													}
												}
											}
										}
									}
								}
							}
						}
						//var_dump($vertretungen);
						foreach ($dates as $date => $data){
							$DB->exec("DELETE FROM substitutes WHERE `date` = ".$DB->prep($date).";");
							$data["date"] = $date;
							$data["urls"] = json_encode($data["urls"]);
							$DB->insert()->into("lastupdates")->values($data)->on_duplicate_key_update_with_value(array("last_update","urls","lastupdate"))->exec();
						}
						//foreach ($vertretungen as $vertretung){
						//	echo implode(" | ",$vertretung)."\n";
						//}
						$DB->insert()->into("substitutes")->values($vertretungen)->exec();
					} else {
						echo "ResultMenuItems not found!";
					}
				} else {
					echo "Data not parseble!";
				}
			} else {
				echo "Data not found!";
			}
		} else {
			echo "Received data is not a valid JSON!";
		}
	} else {
		echo "Request failed!";
	}
?>