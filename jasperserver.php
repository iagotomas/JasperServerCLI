#! /usr/bin/php
<?php
require_once('jasperserverAPI.php');
date_default_timezone_set("Europe/Berlin");
$config = array(
"user"=>"jasperadmin",
"password"=>"jasperadmin",
"host"=>"http://localhost:8080/jasperserver/services/repository"
);
$options = getopt("m:p:c:l:d:h:t:");
$host = !empty($options["h"])?$options["h"]:$config["host"];
$client = new JasperApiClient($host,$config["user"],$config["password"]);

function doList($folder){
	global $client;
	$result = $client->listFolder($folder);

	$xml = simplexml_load_string($result);
	if($xml!=null&&!empty($xml)){
		$mask = "%-22.30s %-25.30s %-12.30s  %-40.30s %-30.30s %-30.30s \n";
		printf($mask,"Created","Name","Type","Uri","Label","Description");
		printf($mask,"-------","----","----","---","-----","-----------");

		foreach($xml->resourceDescriptor as $ar){

			$label = (string)$ar->label;
			$description = (string)$ar->description;
			$ts = intval($ar->creationDate)/1000;
			$d=date("Y-m-d h:i:s",$ts);
			$l = $ar->attributes();
			$name = (string)$l["name"];
			$uri = (string)$l["uriString"];
			$wstype = (string)$l["wsType"];
			printf($mask,$d, $name,$wstype,$uri,$label,$description);
		}
	}
}
function doCreate($parent,$folder,$label,$description){
	global $client;
	$result = $client->createFolder($parent,$folder,$label,$description);
	$xml = simplexml_load_string($result);
	if(intval($xml->returnCode)==0){
		echo "Success!\n";
	}
	else{
		echo (string) $xml->returnMessage."\n";
	}
}
function doDelete($parent,$folder){
	global $client;
	$result = $client->deleteFolder($parent,$folder);
	$xml = simplexml_load_string($result);
	if(intval($xml->returnCode)==0){
		echo "Success!\n";
	}
	else{
		echo (string) $xml->returnMessage."\n";
	}
}
function doGet($uri,$name,$type){
	global $client;
	$result = $client->getResource($uri,$name,$type,getcwd());
	print_r($result);
}
$result = null;
try{
switch($options["m"]){
	case "create":
	if(empty($options["c"])) {
		echo "Missing -c option"; 
		break;
	}
	if(empty($options["p"])){ 
		echo "Missing -p option"; 
		break;
	}
	$label = !empty($options["l"])?$options["l"]:$options["c"];
	$description = !empty($options["d"])?$options["d"]:"";
	doCreate($options["p"],$options["c"],$label,$description);
	break;
	case "list":
	if(empty($options["p"])){
		echo "Missing -p option"; 
		break;
	}
	doList($options["p"]);
	
	break;
	case "delete":
	if(empty($options["p"])){
		echo "Missing -p option"; 
		break;
	}
	if(empty($options["c"])) {
		echo "Missing -c option"; 
		break;
	}
	doDelete($options["p"],$options["c"]);
	break;
	case "get":
	if(empty($options["p"])){
		echo "Missing -p option"; 
		break;
	}
	if(empty($options["c"])) {
		echo "Missing -c option"; 
		break;
	}
	if(empty($options["t"])) {
		echo "Missing -t option"; 
		break;
	}
	doGet($options["p"],$options["c"],$options["t"]);
	break;
}
}
catch(Exception $e){
	echo "ERROR:\n".$e->getMessage()." (".$e->getFile().":".$e->getLine().")\n\n";
	exit(1);
}
?>
