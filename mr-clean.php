<?php
require_once 'mr-common.php';
require_once 'mr-oauth.php';

$stmt = $db->query('select id from item order by time');
if ($stmt === false)
    dieOnDb();
else
{
    $results = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $stmt->closeCursor();

    $count = 0;
    foreach ($results as $id)
    {
        echo "$count";
        $count++;

        $items = getItem($id, true);
        $item = $items['items'][0];
        $src = $items['title'];
        if (ignoreItem($item))
        {
            echo "$src\n";
            $id = $db->quote($id);
            $db->exec("delete from item where id=$id");
        }

        echo "\r";
    }
}

?>

<meta http-equiv="Refresh" content="0; url=mr-item.php" />
