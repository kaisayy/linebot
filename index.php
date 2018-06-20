<?php
require_once __DIR__ . '/vendor/autoload.php';

error_log("start");

// POSTを受け取る
$postData = file_get_contents('php://input');
error_log($postData);

// jeson化
$json = json_decode($postData);
$event = $json->events[0];
error_log(var_export($event, true));

// ChannelAccessTokenとChannelSecret設定
$httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient(getenv('LineMessageAPIChannelAccessToken'));
$bot = new \LINE\LINEBot($httpClient, ['channelSecret' => getenv('LineMessageAPIChannelSecret')]);

// イベントタイプがmessage以外はスルー
if ($event->type != "message")
    return;
$replyMessage = null;
// メッセージタイプが文字列の場合
if ($event->message->type == "text") {
    //$replyMessage = $event->message->text;
    //docomo返信
    $res = $bot->getProfile($event->source->userId);
    if ($res->isSucceeded()) {
      $userProfile = $res->getJSONDecodedBody();
      $displayName = $userProfile['displayName'];
    }
    $replyMessage = chat($event->message->text, $event->source->userId, $displayName);

}
//文字列以外は無視
else {
    return;
}

// メッセージ作成
$textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($replyMessage);

// メッセージ送信
$response = $bot->replyMessage($event->replyToken, $textMessageBuilder);
var_export($response, true);
error_log(var_export($response,true));
return;


//ドコモの雑談APIから雑談データを取得
//From "https://qiita.com/Yuta_Fujiwara/items/281d3e36845b37872a16"
function chat($text, $userID, $displayName)
{
    // docomo chatAPI
    $api_key = getenv('docomoAPIKey');
    $api_url = sprintf('https://api.apigw.smt.docomo.ne.jp/dialogue/v1/dialogue?APIKEY=%s', $api_key);
    $req_body = array('utt' => $text, 'context' => $userID, 'nickname' => $displayName, 'place' => '松江');

    $headers = array(
        'Content-Type: application/json; charset=UTF-8',
    );
    $options = array(
        'http'=>array(
            'method'  => 'POST',
            'header'  => implode("\r\n", $headers),
            'content' => json_encode($req_body),
            )
        );
    $stream = stream_context_create($options);
    $res = json_decode(file_get_contents($api_url, false, $stream));
    error_log($res->utt);
    return $res->utt;
}