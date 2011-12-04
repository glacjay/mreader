<?php
require_once 'config';
require_once 'OAuth.php';

$sig_method = new OAuthSignatureMethod_HMAC_SHA1();

$scope = "https://www.google.com/reader/api";

$consumer = new OAuthConsumer($consumer_key, $consumer_secret, null);

function parseTokenSecret($result)
{
    $arr = array();
    $param_pairs = explode('&', $result);
    foreach ($param_pairs as $param_pair)
    {
        if (trim($param_pair) == '')
            continue;
        list($key, $value) = explode('=', $param_pair);
        $arr[$key] = urldecode($value);
    }
    return $arr;
}

function requestOAuth($url, $post_data=null, $fetching=false)
{
    global $sig_method, $consumer_key, $consumer_secret, $scope, $consumer;

    if ($fetching)
    {
        $access_token = fetchConfig('access_token');
        $access_token_secret = fetchConfig('access_token_secret');
    }
    else
    {
        $access_token = $_SESSION['access_token'];
        $access_token_secret = $_SESSION['access_token_secret'];
    }

    $token = new OAuthConsumer($access_token, $access_token_secret);
    if ($post_data === null)
        $request = OAuthRequest::from_consumer_and_token(
            $consumer, $token, 'GET', $url, array());
    else
        $request = OAuthRequest::from_consumer_and_token(
            $consumer, $token, 'POST', $url, $post_data);
    $request->sign_request($sig_method, $consumer, $token);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array($request->to_header()));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    if ($post_data !== null)
    {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    }

    $result = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    return array($http_code, $result);
}

function oauthError($http_code, $result)
{
    echo <<<_END
        OAuth error ($http_code): $result<br />
        You can try to refresh this page,
        or <a href="mr-logout.php">logout</a> and login again.
_END;
    exit;
}

function getActionToken()
{
    global $scope;

    if (isset($_SESSION['action_token']) && time() < $_SESSION['action_token_time'] + 60 * 25)
        return $_SESSION['action_token'];

    list($http_code, $result) = requestOAuth("$scope/0/token");
    if ($http_code == 200)
    {
        $_SESSION['action_token'] = $result;
        $_SESSION['action_token_time'] = time();
        return $result;
    }
    else
        oauthError($http_code, $result);
}

function markItem($id, $stream, $action)
{
    global $scope;

    $post_data = array(
        'async' => 'true',
        'a' => "user/-/state/com.google/$action",
        'i' => $id,
        's' => $stream,
        'T' => getActionToken());
    list($http_code, $result) = requestOAuth("$scope/0/edit-tag", $post_data);
    if ($http_code != 200)
        oauthError($http_code, $result);
}

function addStar($id, $stream)
{
    markItem($id, $stream, 'starred');
}

function markRead($id, $stream)
{
    markItem($id, $stream, 'read');
}

function getItem($id, $fetching=false)
{
    global $scope;

    $url = "$scope/0/stream/items/contents?i=$id";
    list($http_code, $result) = requestOAuth($url, null, $fetching);
    if ($http_code == 200)
        return json_decode($result, true);
    else
        oauthError($http_code, $result);
}

?>
