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

function redirectToOldestItem()
{
    global $db;

    $stmt = $db->query('select id from item order by time limit 1');
    if ($stmt === false)
        dieOnDb();
    else
    {
        $result = $stmt->fetch(PDO::FETCH_NUM);
        $stmt->closeCursor();
        $id = urlencode($result[0]);
        die("<meta http-equiv='Refresh' content='0; url=mr-item.php?id=$id' />");
    }
}

if (! isset($_GET['id']))
    redirectToOldestItem();

$id = $_GET['id'];

if (isset($_GET['action']) &&
    ($_GET['action'] == 'read' || $_GET['action'] == 'star') &&
    isset($_GET['id']))
{
    $prev = $_GET['id'];

    $items = getItem($prev);
    $item = $items['items'][0];
    $stream = $item['origin']['streamId'];

    if ($_GET['action'] == 'star')
        addStar($prev, $stream);
    markRead($prev, $stream);

    $quoted_prev = $db->quote($prev);
    $db->exec("delete from item where id=$quoted_prev");

    redirectToOldestItem();
}

$count = 0;
$stmt = $db->query('select count(*) from item');
if ($stmt === false)
    dieOnDb();
else
{
    $result = $stmt->fetch(PDO::FETCH_NUM);
    $stmt->closeCursor();
    $count = $result[0];
}

$stmt = $db->query('select time from item order by time desc limit 1');
if ($stmt === false)
    dieOnDb();
else
{
    $result = $stmt->fetch(PDO::FETCH_NUM);
    $stmt->closeCursor();
    $newest = date('Y-m-d H:i:s', $result[0] / 1000);
}

$items = getItem($id);
$item = $items['items'][0];

$src = $items['title'];
$time = date('Y-m-d H:i:s', $item['crawlTimeMsec'] / 1000);
$origin = $item['alternate'][0]['href'];

if (isset($item['title']))
    $title = $item['title'];
else
    $title = '(title unknown)';

if (isset($item['author']))
    $author = $item['author'];
else
    $author = '(someone)';

if (isset($item['content']))
    $content = $item['content']['content'];
elseif (isset($item['summary']))
    $content = $item['summary']['content'];
else
    $content = 'There is no content?!';

$id = urlencode($id);
$stream = urlencode(base64_encode($item['origin']['streamId']));
$read_url = "mr-item.php?id=$id&action=read";
$star_url = "mr-item.php?id=$id&action=star";
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title><?php echo $title; ?></title>
    <style type="text/css">
        .title { background-color: #ddddff; }
        .src { color: green; }
        a.button {
            display: inline-block;
            text-decoration: none;
            margin: 2px;
            padding: 5px 20px;
            border: solid 1px blue;
            color: DarkBlue;
            text-align: center;
            background: -webkit-gradient(
                linear,
                left top,
                left bottom,
                color-stop(0, white),
                color-stop(1, LightBlue)
            );
        }
        a.button:active {
            background: -webkit-gradient(
                linear,
                left top,
                left bottom,
                color-stop(0, LightBlue),
                color-stop(1, white)
            );
        }
    </style>
</head>

<body>
    <div id="logo">
        Status: (<?php echo $count; ?>) <?php echo $newest; ?>
    </div>
    <div class="title">
        <?php echo $title; ?><br />
        <?php echo "by " . $author . " at " . $time; ?>
    </div>
    <div class="src"><?php echo $src; ?></div>
    <br />
    <div class="content">
        <?php echo $content; ?>
    </div>
    <hr />
    <a class="button" href="<?php echo $origin; ?>">origin</a>
    <a class="button" href="<?php echo $star_url; ?>">star</a>
    <a class="button" href="<?php echo $read_url; ?>">next</a>
</body>
</html>
