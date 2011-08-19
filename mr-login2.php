<?php
require_once 'mr-common.php';
require_once 'mr-oauth.php';

$access_token_url = "https://www.google.com/accounts/OAuthGetAccessToken";

$oauth_token = $_REQUEST['oauth_token'];
$oauth_verifier = $_REQUEST['oauth_verifier'];
$oauth_token_secret = $_SESSION['tmp_secret'];
$_SESSION['tmp_secret'] = null;

$params = array();
$params['oauth_verifier'] = $oauth_verifier;

$token = new OAuthConsumer($oauth_token, $oauth_token_secret, null);
$request = OAuthRequest::from_consumer_and_token(
    $consumer, $token, 'GET', $access_token_url, $params);
$request->sign_request($sig_method, $consumer, $token);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $request->to_url());
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

$result = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
if ($http_code == 200)
{
    $access_token = parseTokenSecret($result);
    saveConfig('access_token', $access_token['oauth_token']);
    saveConfig('access_token_secret', $access_token['oauth_token_secret']);
    $_SESSION['access_token'] = $access_token['oauth_token'];
    header("Location: $app_url/mr-item.php");
}
else
    oauthError($http_code, $result);

?>
