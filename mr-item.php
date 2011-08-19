<?php
require_once 'mr-header.php';
require_once 'mr-oauth.php';

if (isset($_GET['action']) &&
    ($_GET['action'] == 'read' || $_GET['action'] == 'star') &&
    isset($_GET['prev']) &&
    isset($_GET['stream']))
{
    $prev = $_GET['prev'];
    $stream = base64_decode($_GET['stream']);
    if ($_GET['action'] == 'star')
        addStar($prev, $stream);
    markRead($prev, $stream);
    $prev = $db->quote($prev);
    $db->exec("delete from item where id=$prev");
}

$stmt = $db->query('select id from item order by time limit 1');
if ($stmt === false)
    die($db->errorInfo());
else
{
    $result = $stmt->fetch(PDO::FETCH_NUM);
    if ($result === false)
        echo "There is no more items.<br />\n";
    else
    {
        $id = $result[0];
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
        $read_url = "mr-item.php?action=read&prev=$id&stream=$stream";
        $star_url = "mr-item.php?action=star&prev=$id&stream=$stream";
?>

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
    <a href="<?php echo $origin; ?>">see origin</a></br />
    <a href="<?php echo $read_url; ?>">mark read then next</a><br />
    <a href="<?php echo $star_url; ?>">add star then next</a><br />

<?php
    }
}
require_once 'mr-footer.php';
?>
