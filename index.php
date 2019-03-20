<?php

header("Access-Control-Allow-Origin: https://home.pacess.com");
header("Access-Control-Allow-Methods: POST");

header('Content-Type: text/html; charset=utf-8');
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Tue, 10 Mar 1987 00:00:00 GMT");

session_start();
date_default_timezone_set("Asia/Hong_Kong");
ini_set("default_charset", "UTF-8");
ini_set("memory_limit", "-1");
mb_internal_encoding("UTF-8");
set_time_limit(60);


//----------------------------------------------------------------------------------------
//  LINE app settings (Sita Chan LINE Bot)
$_channelAccessToken = "s8zJezfsklEOerB3jXNAgJfamWZGpxkDu+Jx9UzOPtSr2/7sZVgL/te3/kiQu95DlOIi5YVWKRaTaQViQ0zh7F4sr9vaUt2jkRg/EILEZhm2oH1fyccANZ/eg4PSik63BAH2Ni5fpcIWrm5gsBC+kwdB04t89/1O/w1cDnyilFU=";

$_key = "w13.pacess.com/binary/";

//----------------------------------------------------------------------------------------
function postLineAPI($url, $requestArray)  {
	global $_channelAccessToken;

	if ($_channelAccessToken == "")  {return null;}

	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: application/json", "Authorization: Bearer ".$_channelAccessToken)); 
	curl_setopt($curl, CURLOPT_POST, true); 
	curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($requestArray));
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	$response = curl_exec($curl);
	curl_close($curl);

	return $response;
}

//----------------------------------------------------------------------------------------
function sendText($userID, $message)  {
	global $_channelAccessToken;

	if ($userID == "")  {return null;}

	$requestArray = array();
	$requestArray["to"] = $userID;
	$requestArray["messages"] = array(array("type"=>"text", "text"=>$message));
	$response = postLineAPI("https://api.line.me/v2/bot/message/push", $requestArray);

	return $response;
}

//========================================================================================
//  Main program
$ipAddress = $_SERVER["REMOTE_ADDR"];
if ($ipAddress == null || $ipAddress == "")  {exit(0);}

$userAgent = "";
if (isset($_SERVER["HTTP_USER_AGENT"]) == true)  {
	$userAgent = $_SERVER["HTTP_USER_AGENT"];
}

//  Create a unique code
$sameSession = false;
$uniqueCode = md5(date("YmdHis").rand(0, 99));
if (isset($_SESSION[$_key]) == true)  {

	//  Use existing unique code
	$code = $_SESSION[$_key];
	if (strlen($code) > 10)  {
		$sameSession = true;
		$uniqueCode = $code;
	}
}

if (isset($_COOKIE[$_key]) == false)  {
	$_COOKIE[$_key] = $uniqueCode;
}  else  {
	$uniqueCode = $_COOKIE[$_key];
	if (strlen($code) > 10)  {
// 		$sameSession = true;
		$uniqueCode = $code;
	}
}

$_SESSION[$_key] = $uniqueCode;
setcookie($_key, $uniqueCode, time()+(60*60*24*30));

//----------------------------------------------------------------------------------------
//  Load HTML content
$html = file_get_contents("./index.html");
if (isset($_REQUEST["v"]))  {

	$code = $_REQUEST["v"];
	$html = str_replace("_code = 0;", "_code = {$code};", $html);
}
if (isset($_REQUEST["code"]))  {

	$code = $_REQUEST["code"];
	$html = str_replace("_code = 0;", "_code = {$code};", $html);
}
echo($html);

//----------------------------------------------------------------------------------------
// 	Convert IP to location  {
// 		ip: "59.152.241.226",
// 		type: "ipv4",
// 		continent_code: "AS",
// 		continent_name: "Asia",
// 		country_code: "HK",
// 		country_name: "Hong Kong",
// 		region_code: "KSS",
// 		region_name: "Sham Shui Po",
// 		city: "Cheung Sha Wan",
// 		zip: null,
// 		latitude: 22.2333,
// 		longitude: 114,
// 		location: {
// 			geoname_id: 1819952,
// 			capital: "City of Victoria",
// 			languages: [{
// 				code: "zh",
// 				name: "Chinese",
// 				native: "中文"
// 			}, {
// 				code: "en",
// 				name: "English",
// 				native: "English"
// 			}],
// 			country_flag: "http://assets.ipstack.com/flags/hk.svg",
// 			country_flag_emoji: "🇭🇰",
// 			country_flag_emoji_unicode: "U+1F1ED U+1F1F0",
// 			calling_code: "852",
// 			is_eu: false
// 		}
// 	}
$url = "http://api.ipstack.com/".$ipAddress."?access_key=9f35efefb370ad239df791b2331c4d3b&format=1";
$json = file_get_contents($url);
$jsonArray = json_decode($json);

$region_name = $jsonArray->region_name;
$city = $jsonArray->city;

if ($region_name == null || $region_name == "")  {exit(0);}
if ($city == null || $city == "")  {exit(0);}

//----------------------------------------------------------------------------------------
//  Send LINE notification
// 	curl -X POST -H 'Content-Type:application/json' -H 'Authorization: Bearer s8zJezfsklEOerB3jXNAgJfamWZGpxkDu+Jx9UzOPtSr2/7sZVgL/te3/kiQu95DlOIi5YVWKRaTaQViQ0zh7F4sr9vaUt2jkRg/EILEZhm2oH1fyccANZ/eg4PSik63BAH2Ni5fpcIWrm5gsBC+kwdB04t89/1O/w1cDnyilFU=' \
// 	-d '{"to":"'"$lineUser"'",
// 		"messages":[
// 			{
// 				"type":"text",
// 				"text":"A Packt ebook ['"$title"'] have been redeemed to '"$userID"'.  Thanks!"
// 			}
// 		]}' https://api.line.me/v2/bot/message/push
if ($sameSession != false)  {exit(0);}

$url = $_SERVER["REQUEST_URI"];

$userID = "Uec30c4e8ff21506619b9d483feb5469a";		// Sita Chan's Pacess
$message = "Buddy at $city, $region_name with IP address $ipAddress and code $uniqueCode access Binary website: ".$url."\n\n$userAgent ";

if (isset($_SERVER["HTTP_REFERER"]))  {

	//  No need LINE notification if come from same page, which means reload or change card only
	$refer = $_SERVER["HTTP_REFERER"];
	if (strpos($refer, "w13.pacess.com") !== false)  {exit(0);}

	$message .= " \nRefer from: ".$refer;
}

$response = sendText($userID, $message);

?>