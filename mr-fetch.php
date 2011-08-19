<?php
require_once 'mr-common.php';
require_once 'mr-oauth.php';

$access_token = fetchConfig('access_token');
if ($access_token === null)
{
    sendMail();
    exit;
}
$access_token_secret = fetchConfig('access_token_secret');
$last_fetch = intval(fetchConfig('last_fetch'));

$time = time();
while (time() - $time < 300)
{
    global $scope;

    $c = '';
    $xt = 'user/-/state/com.google/read';
    $nt = $last_fetch + 60 * 60 * 24 * 7;
    $ot = $last_fetch;

    while (true)
    {
        $url = "$scope/0/stream/contents/user/-/state/com.google/reading-list" .
            "?${c}xt=$xt&nt=$nt&ot=$ot&n=5&r=o&likes=false&comments=false";
        list($http_code, $result) = requestOAuth($url);
        if ($http_code == 200)
        {
            $json = json_decode($result, true);
            if (count($json['items']) == 0)
                break;
            foreach ($json['items'] as $item)
            {
                if (!isset($item['title']))
                    $item['title'] = '(title unknown)';
                saveItem($item['id'], $item['title'], $item['origin']['title'],
                    $item['crawlTimeMsec']);
            }
            if (isset($json['continuation']))
                $c = 'c=' . $json['continuation'] . '&';
            else
                break;
        }
        else
        {
            sendMail();
            exit;
        }
    }

    $last_fetch = $nt;
    if ($last_fetch < time() - 60 * 60 * 24)
        saveConfig('last_fetch', $last_fetch);
    else
        break;
}

function sendMail()
{
    global $app_url, $email;
    echo mail($email, 'Google Reader need to be authorized.',
        "Please visit <a href='$app_url/mr-item.php'>here</a>");
    die("send mail...\n");
}

function saveItem($id, $title, $src, $time)
{
    global $db;

    $id = $db->quote($id);
    $stmt = $db->query("select * from item where id=$id");
    if ($stmt === false)
        die($db->errorInfo());
    elseif ($stmt->fetch(PDO::FETCH_NUM) === false)
    {
        $title = $db->quote($title);
        $src = $db->quote($src);
        $db->exec("insert into item (id, title, src, time) values ($id, $title, $src, $time)");
    }
}

?>
