<?php
include("config.php");

// Add uniques you want to allow / display in the following array:
$allowed = array(
    'MOB_CH_TIGERWOMAN' => 'Tiger Girl',
    'MOB_OA_URUCHI'     => 'Uruchi',
    'MOB_KK_ISYUTARU'   => 'Isyutaru',
    'MOB_TK_BONELORD'   => 'Lord Yarkan',
    'MOB_EU_KERBEROS'   => 'Cerberus',
    'MOB_AM_IVY'        => 'Captain Ivy',
    'MOB_RM_TAHOMET'    => 'Demon Shaitan'
);

foreach($allowed as $k => $v) {
    $uniques = empty($uniques) ? "" : $uniques . ",";
    $uniques .= "'" . $k . "'";
}

echo "Top 15 Unique Killers:<br />\n";
$query = $mssql->query("SHARD", "SELECT TOP 15 CharName16, COUNT(*) AS Kills FROM UniqueKills WHERE Monster IN(".$uniques.") GROUP BY CharName16 ORDER BY Kills DESC");
while($row=$mssql->fetch($query)) {
    echo $row['CharName16'] . " - " . $row['Kills'] . " kills<br />\n";
}

echo "Last 15 Unique Kills:<br />\n";
$query = $mssql->query("SHARD", "SELECT TOP 15 * FROM UniqueKills WHERE Monster IN(".$uniques.") ORDER BY Timestamp DESC");
while($row=$mssql->fetch($query)) {
    echo $row['CharName16'] . " killed " . $allowed[$row['Monster']] . " at " . date("d-m-Y H:i", $row['Timestamp']) . "<br />\n";
}
?>