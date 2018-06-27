<?php
require_once __DIR__ . '/vendor/autoload.php';

date_default_timezone_set('Asia/Tokyo');
$time1 = date('Y-m-d H:i:s');

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

if($event->type != "message")
  return;
$replyMessage = null;
// メッセージタイプが文字列の場合
if ($event->message->type == "text") {
    //送られてきたメッセージをそのまま変身
    //$replyMessage = $event->message->text;

    //docomoAPIに返信
    //このボットのプロファイルをget
    $res = $bot->getProfile($event->source->userId);
    if($res->isSucceeded()){
        //json形式にして変数に代入
      $userProfile = $res->getJSONDecodedBody();
      $displayName = $userProfile['displayName'];
    }
    $replyMessage = chat($event->message->text, $event->source->userId, $displayName, $time1);
}
else {
    return;
}

// メッセージ作成
$textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($replyMessage);

// メッセージ送信
error_log("a");
$response = $bot->replyMessage($event->replyToken, $textMessageBuilder);
error_log("b");
error_log(var_export($response,true));
error_log("c");
return;

function chat($text, $userID, $displayName, $time1)
{
    //docomo chatAPI
    //herokuにAPIKeyを環境変数として保存している
    //getenvによって環境変数をとってくる
    $api_key = getenv('docomoAPIKey');
    $api_url1 = sprintf('https://api.apigw.smt.docomo.ne.jp/naturalChatting/v1/registration?APIKEY=%s', $api_key);
    $req_body1 = array('botId' => 'Chatting',
                      'appKind' => 'Smart Phone'
                      );

    $headers1 = array(  
        'Content-Type: application/json; charset=UTF-8',
    );

    $options1 = array(
        'http'=>array(
            'method' => 'POST',
            'header' => implode("\r\n", $headers1),
            'content' => json_encode($req_body1),
            )
        );    

    $stream1 = stream_context_create($options1);
    $res1 = json_decode(file_get_contents($api_url1, false, $stream1));
//'context' => $userID,
//'nickname' => $displayName, 
    $api_url2 = sprintf('https://api.apigw.smt.docomo.ne.jp/naturalChatting/v1/dialogue?APIKEY=%s', $api_key);
    $req_body2 = array('voiceText' => $text, 
                      'language' => 'ja-JP', 
                      'botId' => 'Chatting',
                      'appId' => $res1->appId,
                      'clientData' => array(
                                    'option' => array(
                                               'mode' => $_SESSION['chat_mode'],
                                               ),
                                    ),
                      'appRecvTime' => $time1,
                      'appSendTime' => date('Y-m-d H:i:s')
                      );
     
    $headers2 = array(  
        'Content-Type: application/json; charset=UTF-8',
    );
    
    $options2 = array(
        'http'=>array(
            'method' => 'POST',
            'header' => implode("\r\n", $headers2),
            'content' => json_encode($req_body2),
            )
        );

//$_SESSION['chat_mode'] = $chat_mode->mode;

    $stream2 = stream_context_create($options2);
    $res2 = json_decode(file_get_contents($api_url2, false, $stream2));
    $cmd_mode = base64_decode($res2->command);
    $chat_mode = json_decode($cmd_mode);
    $_SESSION['chat_mode'] = $chat_mode->mode;

    error_log($res2->systemText->expression);
    return $res2->systemText->expression;
}
