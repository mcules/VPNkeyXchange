<?php
/**
 * Handle VPN-Key-Exchange request for Freifunk-Franken.
 * Database Class
 *
 * @author  McUles <mcules@freifunk-hassberge.de>
 *
 * @license https://www.gnu.org/licenses/agpl-3.0.txt AGPL-3.0
 */

/**
 * Singelton DB instance
 */
class db
{
    private static $instance = null;

    private function __construct()
    {
    }

    public static function getInstance()
    {
        if (!self::$instance) {
            require('config.inc.php');
            self::$instance = new PDO('mysql:host=' . $mysql_server . ';dbname=' . $mysql_db, $mysql_user, $mysql_pass);
            self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }

        return self::$instance;
    }

    private function __clone()
    {
    }
}
