<?php

require __DIR__ . '/../lib/vendor/autoload.php';

use \LINE\LINEBot\SignatureValidator as SignatureValidator;

require_once 'db.class.php';

DB::$user = 'edoganteng';
DB::$password = 'Pptik@123';
DB::$dbName = 'eventkampus';
DB::$host = '167.205.7.228';

// load config
$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();

// initiate app
$configs =  [
	'settings' => ['displayErrorDetails' => true],
];
$app = new Slim\App($configs);

/* ROUTES */
$app->get('/', function ($request, $response) {
	$results = DB::query("SELECT * FROM line_user");
		foreach ($results as $row) {
		  echo "Name: " . $row['display_name'] . "\n";
		  echo "Age: " . $row['user_id'] . "\n";
		 
		}
   $accounts = DB::query('SELECT * FROM line_user ');

   echo 'test';
	//return "got it";
    return sizeof($accounts);

});

$app->post('/', function ($request, $response)
{

	$body 	   = file_get_contents('php://input');
	$signature = $_SERVER['HTTP_X_LINE_SIGNATURE'];
	file_put_contents('php://stderr', 'Body: '.$body);
	if (empty($signature)){
		return $response->withStatus(400, 'Signature not set');
	}
	if($_ENV['PASS_SIGNATURE'] == false && ! SignatureValidator::validateSignature($body, $_ENV['CHANNEL_SECRET'], $signature)){
		return $response->withStatus(400, 'Invalid signature');
	}

	$httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient($_ENV['CHANNEL_ACCESS_TOKEN']);
	$bot = new \LINE\LINEBot($httpClient, ['channelSecret' => $_ENV['CHANNEL_SECRET']]);
	$data = json_decode($body, true);
	foreach ($data['events'] as $event)
	{
		error_log($event['source']['userId']);
		if ($event['type'] == 'message') {
			if($event['message']['type'] == 'text')
			{
                $userId 	= $event['source']['userId'];
                $response = $bot->getProfile($userId);
                if ($response->isSucceeded()) {
                    $profile = $response->getJSONDecodedBody();
                    $displayName =  $profile['displayName'];
                    $accounts = DB::query('SELECT * FROM line_user WHERE user_id = %s', $userId);
                    if(sizeof($accounts) <= 0){
                        error_log($profile['displayName']." - ".$profile['userId']." - ".$profile['pictureUrl']." - ".$profile['statusMessage']);
                        DB::insert('line_user', array('display_name' => $profile['displayName'],
                            'user_id' => $profile['userId'],
                            'picture_url' => "none",
                            'status_message' => "none"));
                    }

                    $userMessage = $event['message']['text'];
                    if (strtolower($userMessage) == 'latest'){
                        $results = DB::query("SELECT tagline FROM event");
                        $taglines="";
                        foreach ($results as $row) {
                            $options[]=new \LINE\LINEBot\TemplateActionBuilder\MessageTemplateActionBuilder($row['tagline'],$row['tagline']);
                            $taglines=$taglines." ".$row['tagline'];
                        }

                        $buttontemplate=new \LINE\LINEBot\MessageBuilder\TemplateBuilder\ButtonTemplateBuilder("Latest EVENT : ","________","https://www.smart-holiday.com/images/img-event.jpg",$options);
                        $messageBuilder = new \LINE\LINEBot\MessageBuilder\TemplateMessageBuilder("Gunakan mobile app", $buttontemplate);
                        $result = $bot->pushMessage($profile['userId'],$messageBuilder);
                        $result = $bot->replyText($event['replyToken'],'Latest event, Silahkan ketik :'.$taglines);
                    }else{
                        $results1 = DB::query("SELECT * FROM event WHERE tagline=%s",$userMessage);
                        $imagesource=null;
                        $tesxtevent=null;
                            foreach ($results1 as $row) {
                                $imagesource = new \LINE\LINEBot\MessageBuilder\ImageMessageBuilder($row['source_gambar'], $row['source_gambar']);
                                $tesxtevent = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder( $row['nama_event']." ".$row['deskripsi_event']);
                            }
                        $result = $bot->pushMessage($profile['userId'],$imagesource );
                        $result = $bot->pushMessage($profile['userId'],$tesxtevent);

                    }

                }else{
                    $result = $bot->replyText($event['replyToken'], "Maaf, permintaan tidak dapat direspon");
                }
			}
            return $result->getHTTPStatus() . ' ' . $result->getRawBody();
		}
	}
});

$app->get('/latest', function ($args)
{
    $results = DB::query("SELECT * FROM event WHERE tagline=%s",'tes');
    $taglines="";
    foreach ($results as $row) {
        $taglines=$taglines." ".$row['tagline'];

    }

    return 'latest event, Silahkan ketik :'.$taglines;
});
$app->get('/push2/{to}/{message}', function ($request, $response, $args)
{
    $httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient($_ENV['CHANNEL_ACCESS_TOKEN']);
    $bot = new \LINE\LINEBot($httpClient, ['channelSecret' => $_ENV['CHANNEL_SECRET']]);

    $results = DB::query("SELECT tagline FROM event");
    foreach ($results as $row) {
        $options[]=new \LINE\LINEBot\TemplateActionBuilder\MessageTemplateActionBuilder($row['tagline'],$row['tagline']);
    }
    print_r($options);
    $buttontemplate=new \LINE\LINEBot\MessageBuilder\TemplateBuilder\ButtonTemplateBuilder("Latest EVENT : ","Check This Out","https://www.smart-holiday.com/images/img-event.jpg",$options);
    $messageBuilder = new \LINE\LINEBot\MessageBuilder\TemplateMessageBuilder("Gunakan mobile app", $buttontemplate);
    $result = $bot->pushMessage($args['to'],$messageBuilder);
    return $result->getHTTPStatus() . ' ' . $result->getRawBody();
});
$app->get('/push/{to}/{message}', function ($request, $response, $args)
{
 	$httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient($_ENV['CHANNEL_ACCESS_TOKEN']);
 	$bot = new \LINE\LINEBot($httpClient, ['channelSecret' => $_ENV['CHANNEL_SECRET']]);

 	$textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($args['message']);
 	$result = $bot->pushMessage($args['to'], $textMessageBuilder);
    $imagesource2 = new \LINE\LINEBot\MessageBuilder\ImageMessageBuilder('https://mir-s3-cdn-cf.behance.net/project_modules/disp/28bce821819767.56308068538f2.png', 'https://mir-s3-cdn-cf.behance.net/project_modules/disp/28bce821819767.56308068538f2.png');
    $tesxtevent2 = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder('tes' );

    $result = $bot->pushMessage($args['to'],$imagesource2 );
    $result = $bot->pushMessage($args['to'],$tesxtevent2);
 	return $result->getHTTPStatus() . ' ' . $result->getRawBody();
 });

function pushMessage($toUser, $msg){
    $httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient($_ENV['CHANNEL_ACCESS_TOKEN']);
    $bot = new \LINE\LINEBot($httpClient, ['channelSecret' => $_ENV['CHANNEL_SECRET']]);

    $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($msg);
    $result = $bot->pushMessage($toUser, $textMessageBuilder);
}

function pushCallMessage($toUser, $noAntrian){
    $httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient($_ENV['CHANNEL_ACCESS_TOKEN']);
    $bot = new \LINE\LINEBot($httpClient, ['channelSecret' => $_ENV['CHANNEL_SECRET']]);
    $response = $bot->getProfile($toUser);
    if ($response->isSucceeded()) {
        $profile = $response->getJSONDecodedBody();
        $displayName = $profile['displayName'];
        $txt = "Bapak/Ibu ".$displayName." dengan Nomor Antrian : ".$noAntrian." Silahkan ke counter. \nJika dalam waktu 1 menit tidak datang, maka dianggap selesai";
        $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($txt);
        $result = $bot->pushMessage($toUser, $textMessageBuilder);
    }
}

function broadcastQueueState(array $users){
    $httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient($_ENV['CHANNEL_ACCESS_TOKEN']);
    $bot = new \LINE\LINEBot($httpClient, ['channelSecret' => $_ENV['CHANNEL_SECRET']]);
    foreach ($users as $user) {
        $detail = getQueueById($user -> user_id);
        $detail = json_decode($detail);
        $sesion = getActiveSession();
        $sesion = json_decode($sesion);
        $response = $bot->getProfile($user -> user_id);
        $profile = $response->getJSONDecodedBody();
        $displayName =  $profile['displayName'];

        $txt = "-----------------------------------------------\n";
        $txt2 = "Nomor Antrian : ".$detail -> queue_no."\n";
        $txt3 = "Nama : " . $displayName . "\n";
        $txt4 = "Antrian Sekarang : ".$sesion -> current_queue."\n";
        $txt5 = "Total Antrian : ".$sesion -> total_queue."\n";
        $txt6 = "-----------------------------------------------";
        $txtfinal = $txt . $txt2 . $txt3 . $txt4 . $txt5 . $txt6;
        pushMessage($user -> user_id, $txtfinal);
    }
}

$app->post('/createsession', function ($request, $response, $args)
{
    $paramValue = $request->getParsedBody();

    $res = DB::insert('tb_session', array(
        'status' => 1,
        'start_at' => date("Y-m-d H:i:s"),
        'end_at' => "0000-00-00 00:00:00",
        'total_queue' => 0,
        'current_queue' => 0
    ));

    $state = (object) ['success' => $res];
    return json_encode($state);
    //return $paramValue['Test'];
});

$app->get('/getsession', function ($request, $response, $args)
{

    return getActiveSession();
});

$app->post('/endsession', function ($request, $response, $args)
{
    $idSession = $request->getParsedBody();
    $idSession = $idSession['id_session'];

    $query = DB::query('UPDATE tb_session SET status = %i, end_at = %s WHERE id = %i', 0, date("Y-m-d H:i:s"), $idSession);
    $state = (object) ['success' => $query];
    return json_encode($state);
});


function getActiveSession(){
    $query = DB::query('SELECT * FROM tb_session WHERE status = %i', 1);
    $query = json_encode($query[0]);
    return $query;
}

function updateTotalQueue($idSession, $totalQueue){
    $query = DB::query('UPDATE tb_session SET total_queue = %i WHERE id = %i', $totalQueue, $idSession);
    $state = (object) ['success' => $query];
    return json_encode($state);
}

function addQueue($lineId){
    $session = getActiveSession();
    $session = json_decode($session);
    $idSession = $session -> id;
    $queueNo = $session -> total_queue;

    $res = DB::insert('tb_queue', array(
        'user_id' => $lineId,
        'session_id' => $idSession,
        'queue_no' => $queueNo+1,
        'status' => 1
    ));

    updateTotalQueue($idSession, $queueNo+1);

  return $session-> id;
}

function getQueueById($userId){
    $query = DB::query('SELECT * FROM tb_queue WHERE user_id = %s AND status = %i', $userId, 1);
    $query = json_encode($query[0]);
    return $query;
}

function getCurrentQueue(){
    $query = DB::query('SELECT * FROM tb_queue WHERE status = %i ORDER BY id ', 1);
    $query = $query;
    return json_encode($query);
}


function updateQueueStatus($idQueue, $idSession, $idUser){
    DB::query('UPDATE tb_queue SET status = %i WHERE id = %i', 0, $idQueue);
    pushMessage($idUser, "Terimakasih telah memakai layanan kami, Jika Anda ingin menggunakan layanan ini kembali, silahkan ketik 'Ticket'");
    $query = getCurrentQueue();
    $query = json_decode($query);
    $query = $query[0];

    $res = DB::query('UPDATE tb_session SET current_queue = %i WHERE id = %i', $query -> queue_no, $idSession);
    $state = (object) ['success' => $res];

    $q = getCurrentQueue();
    $q = json_decode($q);
    broadcastQueueState($q);
    pushCallMessage($query -> user_id, $query -> queue_no);

    return json_encode($state);
}


$app->post('/addqueue', function ($request, $response, $args)
{
    addQueue('sdsdedwewe');

    return getQueueById('sdsdedwewe');
});

$app->post('/finishcurrentqueue', function ($request, $response, $args)
{
    $query = getCurrentQueue();
    $query = json_decode($query);
    if(sizeof($query) > 0){
        $query = $query[0];
        $idqueue = $query -> id;
        $idUser = $query -> user_id;
        $query = getActiveSession();
        $query = json_decode($query);
        $idSession = $query -> id;
        updateQueueStatus($idqueue, $idSession, $idUser);
    }

    return getCurrentQueue();
});



$app->post('/adduser', function ($request, $response, $args)
{
    $r = DB::insert('line_user', array('display_name' => "testt",
        'user_id' => "testtt",
        'picture_url' => "testtt",
        'status_message' => "testtttt"));

    return json_encode($r);
});


$app->run();
