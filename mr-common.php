<?php
require_once 'config';

$db_file = dirname(__FILE__) . '/reader.db';
try
{
    $db = new PDO("sqlite:$db_file");
}
catch (PDOException $ex)
{
    die("Open database failed: " . $ex->getMessage());
}

#ini_set('session.save_handler', 'sqlite');
#ini_set('session.save_path', dirname(__FILE__) . '/session.db');
ini_set('session.gc_maxlifetime', 60 * 60 * 24 * 7);
ini_set('session.cookie_lifetime', 60 * 60 * 24 * 365);
session_start();

function destroySession()
{
    $_SESSION = array();
    session_destroy();
}

function logout()
{
    destroySession();
    die("<meta http-equiv='Refresh' content='0; url=mr-login.php' />");
}

function dieOnDb()
{
    $error = $db->errorInfo();
    die('database error: ' . $error[2]);
}

function saveConfig($key, $value)
{
    global $db;

    $key = $db->quote($key);
    $value = $db->quote($value);
    $stmt = $db->query("select value from config where key=$key");
    if ($stmt === false)
        dieOnDb();
    elseif ($stmt->fetch(PDO::FETCH_NUM) === false)
    {
        $stmt->closeCursor();
        $db->exec("insert into config values ($key, $value)");
    }
    else
    {
        $stmt->closeCursor();
        $db->exec("update config set value=$value where key=$key");
    }
}

function fetchConfig($key)
{
    global $db;

    $key = $db->quote($key);
    $stmt = $db->query("select value from config where key=$key");
    if ($stmt === false)
        dieOnDb();
    $result = $stmt->fetch(PDO::FETCH_NUM);
    if ($result === false)
        return null;
    else
        return $result[0];
}

$ignoreList[] = 'http://www.verycd.com';
$ignoreList[] = 'http://www.daomubiji.com';
$ignoreList[] = 'http://www.bengou.com';
$ignoreList[] = 'http://www.youtube.com';
$ignoreList[] = 'http://www.hexieshe.com';
$ignoreList[] = 'http://golangwiki.org';

function ignoreItem($item)
{
    global $ignoreList;
    $url = $item['origin']['htmlUrl'];
    foreach ($ignoreList as $ignored)
        if (strlen($ignored) <= strlen($url) &&
                substr_compare($url, $ignored, 0, strlen($ignored)) == 0)
            return true;
    return false;
}

?>
