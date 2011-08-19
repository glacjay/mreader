<?php
require_once 'mr-common.php';
require_once 'mr-oauth.php';

if (!isset($_SESSION['access_token']))
    logout();
$url = "https://www.google.com/reader/api/0/user-info";
list($http_code, $result) = requestOAuth($url);
if ($http_code != 200)
    logout();
$json = json_decode($result, true);
if ($json['userEmail'] != $email)
    logout();

?>

<!doctype html>

<html lang="en">

<head>
    <meta charset="utf-8" />
    <title><?php echo $app_name; ?></title>
    <style type="text/css">
        .title { background-color: #ddddff; margin: 0; }
        .src { font-size: small; color: green; }
    </style>
</head>

<body>
    <div id="logo">
        <font color="blue">G</font><font color="red">o</font><font color="orange">o</font><font color="blue">g</font><font color="green">l</font><font color="red">e</font>
        Reader
    </div>
