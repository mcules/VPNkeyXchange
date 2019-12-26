<?php
/**
 * Handle VPN-Key-Exchange request for Freifunk-Franken.
 * fastd Blocklist
 *
 * @author  McUles <mcules@freifunk-hassberge.de>
 *
 * @license https://www.gnu.org/licenses/agpl-3.0.txt AGPL-3.0
 */

/* Include Database class */
include "db.class.php";

/* Output variable */
$strResult = "";

try {
    if (!$_REQUEST['cron']) {
        $strResult .= "<table>";
        $strResult .= "<tr><td>Timestamp</td><td>fastd Key</td><td>Begr&uuml;ndung</td>";
    }
    foreach (db::getInstance()->query("SELECT * FROM blocked_keys;") as $row) {
        if ($_REQUEST['cron']) {
            $strResult .= $row['fastd_key'] . "\n";
        } else {
            $strResult .= "<tr>";
            $strResult .= "<td>" . $row['timestamp'] . "</td>";
            $strResult .= "<td>" . $row['fastd_key'] . "</td>";
            $strResult .= "<td>" . $row['block_reason'] . "</td>";
            $strResult .= "</tr>";
        }
    }
    if (!$_REQUEST['cron']) {
        $strResult .= "</table>";
    }
} catch (PDOException $e) {
    $e->getTrace();
}
echo $strResult;