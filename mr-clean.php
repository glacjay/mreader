<?php
require_once 'mr-common.php';
require_once 'mr-oauth.php';

$stmt = $db->query('select id from item order by time limit 500');
if ($stmt === false)
    dieOnDb();
else
{
    while ($result = $stmt->fetch(PDO::FETCH_NUM))
    {
        $id = $result[0];
        $items = getItem($id, true);
        $item = $items['items'][0];

        $src = $items['title'];
        if (ignoreItem($item))
        {
            echo "$src\n";
            $id = $db->quote($id);
            $db->exec("delete from item where id=$id");
        }
    }

    $stmt->closeCursor();
}

?>

<meta http-equiv="Refresh" content="0; url=mr-item.php" />
