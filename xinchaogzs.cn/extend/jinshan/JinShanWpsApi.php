<?php


namespace jinshan;


use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class JinShanWpsApi
{
    private $api_url = "https://solution.wps.cn/api/developer/v1/";
    private $apiId, $apiKey, $client, $contnentType, $debug = false;

    public function __construct($apiKey, $apiId)
    {
        $this->apiKey = $apiKey;
        $this->apiId = $apiId;
        $this->client = new Client();
        $this->contnentType = "application/json";
    }

    public function convert($src, $ExportType = "pdf")
    {
//        print_r($src);die;
//        $src = "https://cs.scweichuang.com/storage/wendang/20220616/f735d2fa93ddb5f8a647f92afc1adeea.docx";
//$src = "https://hcwl.xinchaogzs.cn/storage/file/20240419/f25f3d054f74486a8f10ce2dba9a1a11.docx";

        $FileNameA = explode("/", $src);
        $FileName = $FileNameA[count($FileNameA) - 1];
        $CallBack = "{$_SERVER["REQUEST_SCHEME"]}://{$_SERVER["HTTP_HOST"]}/api/JinShanApi/CallBack";
        $TaskIdA = explode(".", $FileName);
        $TaskId = $TaskIdA[0] . mt_rand(10000, 100000);
        $FitToWidth= 0;
        if ($TaskIdA[1]=='xls' || $TaskIdA[1]== 'xlsx'){
            $FitToWidth=1;
        }
        $postData = json_encode(["FitToWidth"=>$FitToWidth,"SrcUri" => $src, "FileName" => $FileName, 'ExportType' => $ExportType, "CallBack" => $CallBack, 'TaskId' => $TaskId], JSON_UNESCAPED_SLASHES);
        $postData = json_encode(["url"=>$src,"filename" => $FileName], JSON_UNESCAPED_SLASHES);
        $contnentMd5 = md5($postData);
      //  $contnentMd5 = md5("url=https://hcwl.xinchaogzs.cn/storage/file/20240419/f25f3d054f74486a8f10ce2dba9a1a11.docx&filename=f25f3d054f74486a8f10ce2dba9a1a11.docx");
        //$contnentMd5 = bin2hex($contnentMd5);
        setlocale(LC_TIME, 'en_US');
        $date = gmdate('D, d M Y H:i:s T', time());
        $date = gmstrftime("%a, %d %b %Y %T %Z",time());
        $curl = curl_init();

curl_setopt_array($curl, [
	CURLOPT_URL => "https://solution.wps.cn/api/developer/v1/office/convert/to/pdf",
	CURLOPT_RETURNTRANSFER => true,
	CURLOPT_ENCODING => "",
	CURLOPT_MAXREDIRS => 10,
	CURLOPT_TIMEOUT => 30,
	CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	CURLOPT_CUSTOMREQUEST => "POST",
	CURLOPT_POSTFIELDS => "{\"url\":\"{$src}\",\"filename\":\"{$FileName}\"}",
	CURLOPT_HTTPHEADER => [
		"Authorization: ".$this->getAuthorization("POST", $contnentMd5, $date, "CONVERT"),
		"Content-Md5: ".$contnentMd5,
		"Content-Type: application/json",
		"Date: ".$date
	],
]);

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

if ($err) {
//	echo "cURL Error #:" . $err;
} else {
//	echo $response;
}
            $json = json_decode($response,true);
                $queryRec = false;
             //   while (!$queryRec) {
                  //  $queryRec = $this->query($json['data']['task_id']);
                  //  usleep(200);
               // }
              //  $json = json_decode($queryRec,true);
        return $json;
echo($queryRec);exit();
        
     
            $response = $this->client->request('POST', "{$this->api_url}office/convert/to/pdf", [
                //'debug' => $this->debug,
                'headers' => [
                    'Content-Type' => $this->contnentType,
                    'Date' => $date,
                    'Content-Md5' => $contnentMd5,
                    'Authorization' => $this->getAuthorization("POST", $contnentMd5, $date, "CONVERT")
                ]
            ]);
            $json = json_decode($response->getBody()->getContents());
            print_r($json);exit();
            return $json;
        
        try {
            $response = $this->client->request('POST', "{$this->api_url}/office/convert/to/pdf", [
                'debug' => $this->debug,
                'headers' => [
                    'Content-Type' => $this->contnentType,
                    'Date' => $date,
                    'Content-Md5' => $contnentMd5,
                    'Authorization' => $this->getAuthorization("POST", $contnentMd5, $date, "CONVERT")
                ],
                'body' => $postData
            ]);
            $json = json_decode($response->getBody()->getContents());
            return $json;
            print_r($json);exit();
            if ($json->Code == "OK") {
                $queryRec = false;
                while (!$queryRec) {
                    $queryRec = $this->query($TaskId);
                    usleep(200);
                }
                $json = $queryRec;
            } else return false;
        } catch (RequestException $e) {
            return false;
        }
        return $json;
    }

    public function query($TaskId)
    {
        $date = gmdate('D, d M Y H:i:s T', time());
        $sendData = ['AppId' => $this->apiId, "TaskId" => $TaskId];
        $contnentMd5 = md5(null);
        $date = gmstrftime("%a, %d %b %Y %T %Z",time());
        try {
        $curl = curl_init();
curl_setopt_array($curl, [
	CURLOPT_URL => "https://solution.wps.cn/api/developer/v1/tasks/".$TaskId,
	CURLOPT_RETURNTRANSFER => true,
	CURLOPT_ENCODING => "",
	CURLOPT_MAXREDIRS => 10,
	CURLOPT_TIMEOUT => 30,
	CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	CURLOPT_CUSTOMREQUEST => "GET",
	CURLOPT_HTTPHEADER => [
		"Authorization: ".$this->getAuthorization("POST", $contnentMd5, $date, "CONVERT"),
		"Content-Md5: ".$contnentMd5,
		"Content-Type: application/json",
		"Date: ".$date
	],
]);

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

//if ($err) {
 //   return false;
//	echo "cURL Error #:" . $err;
//} else {
  //      return $response;
//	echo $response;
//}
$res = json_decode($response,true);
$json = $response;
if($res['data']['progress'] <= 0){
   $json = false; 
}
        } catch (RequestException $e) {
            return false;
        }
        return $json;
        
        try {
            $response = $this->client->request('GET', "{$this->api_url}query" . ($sendData ? "?" . http_build_query($sendData) : ''), [
                'debug' => $this->debug,
                'headers' => [
                    'Content-Type' => $this->contnentType,
                    'Date' => $date,
                    'Content-Md5' => $contnentMd5,
                    'Authorization' => $this->getAuthorization("GET", $contnentMd5, $date, "QUERY", $sendData)
                ]
            ]);
            $json = json_decode($response->getBody()->getContents());
        } catch (RequestException $e) {
            return false;
        }
        return $json;
    }

    public function getAuthorization($method, $contnentMd5, $date, $uriType = "CONVERT", $queryData = [])
    {
        //"WPS-2:" + app_id + ":" + sha1( app_key + Content-Md5 + Content-Type + DATE)
        $uri = $_SERVER["REQUEST_URI"];
        if ($uriType == "CONVERT") $uri = "/pre/v1/convert";
        elseif ($uriType == "QUERY") $uri = "/pre/v1/query" . ($queryData ? "?" . http_build_query($queryData) : '');
        $signStr = "{$this->apiKey}{$contnentMd5}{$this->contnentType}{$date}";
        $Signature = base64_encode(hash_hmac("sha1", $signStr, $this->apiKey, true));
        $Signature = sha1($this->apiKey.$contnentMd5.$this->contnentType.$date);
        //$Signature = hash_hmac("sha1", $signStr, $this->apiKey, true);
    //echo $Signature;exit();
        return "WPS-2:{$this->apiId}:{$Signature}";
    }
}