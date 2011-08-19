<?php
require_once 'mr-common.php';
require_once 'mr-oauth.php';

$request_token_url = "https://www.google.com/accounts/OAuthGetRequestToken?scope=$scope";
$authorize_url = "https://www.google.com/accounts/OAuthAuthorizeToken";

$params = array();
$params['oauth_callback'] = "$app_url/mr-login2.php";
$params['scope'] = $scope;

$request = OAuthRequest::from_consumer_and_token(
    $consumer, null, 'GET', $request_token_url, $params);
$request->sign_request($sig_method, $consumer, null);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $request->to_url());
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

$result = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
if ($http_code == 200)
{
    $access_params = parseTokenSecret($result);
    $_SESSION['tmp_secret'] = $access_params['oauth_token_secret'];
    header("Location: $authorize_url?oauth_token=" . $access_params['oauth_token']);
}
else
    oauthError($http_code, $result);

?>
