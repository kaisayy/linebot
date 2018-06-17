<?php
require_once __DIR__ . '/vendor/autoload.php';

error_log("start");

// POSTを受け取る
$postData = file_get_contents('php://input');
error_log($postData);

//POSTで受け取った値をjson化
$json = json_decode($postData);
$event = $json->events[0];
error_log(var_export($event, true));

// ChannelAccessTokenとChannelSecret設定
$httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient(getenv('LineMessageAPIChannelAccessToken'));
$bot = new \LINE\LINEBot($httpClient, ['channelSecret' => getenv('LineMessageAPIChannelSecret')]);


$replyMessage = null;
// メッセージタイプが文字列の場合
if ($event->message->type == "text") {
    //送られてきたメッセージをそのまま変身
    //$replyMessage = $event->message->text;

    //docomoAPIに返信
    //このボットのプロファイルをget
    $res = $bot->getprofile($event->source->userId);
    if($res->isSucceeded()){
        //json形式にして変数に代入
      $userProfile = $res->getJSONdecodedBody();
      $displayName = $userProfile['displayName'];
    }
    $replyMessage = chat($event->message->text, $event->source->userId, $displayName);
}
else {
    return;
}

// メッセージ作成
$textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($replyMessage);

// メッセージ送信
$response = $bot->replyMessage($event->replyToken, $textMessageBuilder);
error_log(var_export($response,true));

return;

function chat($text, $userID, $displayName): string
{
    //docomo chatAPI
    //herokuにAPIKeyを環境変数として保存している
    //getenvによって環境変数をとってくる
    $api_key = getenv('docomoAPIKey');
    $api_url = sprintf('https://api.apigw.smt.docomo.ne.jp/dialogue/v1/dialogue?APIKEY=%s', $api_key);
    $req_body = array('utt' => $text 'context' => $userID, 'nickname' => $displayName, 'place' => '松江');
    
    $headers = array(
        'Content-Type: application/json; charset=UTF-8';
    );
    
    options = array(
        'http'=>array(
            'method' => 'POST',
            'header' => implode("\r\n", $headers),
            'content' => json_encode($req_body),
            )
        );
    
    $stream = stream_context_create($options);
    $res = json_decode(file_get_contents($api_url, false, $stream));
    error_log($res->utt);
    return $res->utt;
}
