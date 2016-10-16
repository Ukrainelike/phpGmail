<?php
include_once __DIR__ . '/vendor/autoload.php';
include_once "templates/base.php";
echo pageHeader('Get Message');

if (!$oauth_credentials = getOAuthCredentialsFile()) {
    echo missingOAuth2CredentialsWarning();
    return;
}

$redirect_uri = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
$client = new Google_Client();
$client->setAuthConfig($oauth_credentials);
$client->setRedirectUri($redirect_uri);
$client->setScopes(implode(' ', array(Google_Service_Gmail::GMAIL_READONLY)));


if (isset($_REQUEST['logout'])) {
    unset($_SESSION['access_token']);
}

if (isset($_GET['code'])) {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    $client->setAccessToken($token);
    $_SESSION['access_token'] = $token;
    header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
}

if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
    $client->setAccessToken($_SESSION['access_token']);
} else {
    header('Location: ' . filter_var($client->createAuthUrl(), FILTER_SANITIZE_URL));
}

if ($client->getAccessToken() && isset($_GET['url'])) {
    $url = new Google_Service_Urlshortener_Url();
    $url->longUrl = $_GET['url'];
    $short = $service->url->insert($url);
    $_SESSION['access_token'] = $client->getAccessToken();
}
if (isset($_POST['responseEmail'])) {
    getListMessages($client,$_POST['responseEmail']);

}
function getListMessages($client, $userId) {

    $service = new Google_Service_Gmail($client);
    $pageToken=NULL;
    $messages=array();
    $opt_param=array();
    do {
        try {
            if($pageToken) {
                $opt_param['pageToken']=$pageToken;
            }
            $opt_param['q'] = '"from:kyharskii_ruslan@ukr.net"';
            $messagesResponse=$service->users_messages->listUsersMessages("me",$opt_param);
            if($messagesResponse->getMessages()) {
                $messages=array_merge($messages,$messagesResponse->getMessages());
                $pageToken=$messagesResponse->getNextPageToken();
            }
        } catch (Exception $e) {
            print 'An error occorred: '. $e->getMessage();
        }
    } while ($pageToken);
    $table='<table border="1">';
    $i=1;
    foreach ($messages as $message) {
        $table.='<tr><td>'.$i.'</td><td><a href="/detailsmail.php?'.$message->getId().'">Email:'.$message->getId().'</a></td></tr>';
        $i++;
    }
    echo $table.'</$table>';
}
?>

<div class="box">
    <?php if (empty($short)): ?>
        <form id="url" method="POST" action="<?= $_SERVER['PHP_SELF'] ?>">
            <input name="responseEmail" type="email">
            <input type="submit" value="Get Message">
        </form>
        <a class='logout' href='?logout'>Logout</a>
    <?php endif ?>
</div>

