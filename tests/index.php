<?php
/**
 * Handle VPN-Key-Exchange request for Freifunk-Franken.
 * Database Class
 *
 * @author  McUles <mcules@freifunk-hassberge.de>
 *
 * @license https://www.gnu.org/licenses/agpl-3.0.txt AGPL-3.0
 */

require_once "tests.class.php";

/* Get Blocklist - Browser */
tests::assertEquals(
    "<table><tr><td>Timestamp</td><td>fastd Key</td><td>Begr&uuml;ndung</td><tr><td>2019-12-25 18:47:54</td><td>b40d14fe6e833f13a0e483f038ca7e22b9acc83a48e09e776f6bf6ac79e24a08</td><td>Loop zwischen mehreren Hoods</td></tr><tr><td>2019-12-25 18:47:54</td><td>8ea0488eab12b97c1b82175f0d1c022140a2984965e7113b629819a6c73c713d</td><td>Loop zwischen mehreren Hoods</td></tr></table>",
    tests::get_web_page("https://keyserver.freifunk-franken.de/v2//blocklist.php"),
    "Get Blocklist - Browser");

/* Get Blocklist - Cron */
tests::assertEquals("b40d14fe6e833f13a0e483f038ca7e22b9acc83a48e09e776f6bf6ac79e24a08\n8ea0488eab12b97c1b82175f0d1c022140a2984965e7113b629819a6c73c713d",
    tests::get_web_page("https://keyserver.freifunk-franken.de/v2//blocklist.php?cron=1"),
    "Get Blocklist - Cron");

/* Get Hood Id 1 */
tests::assertEquals(
    '{
    "version": 1,
    "network": {
        "ula_prefix": "fd43:5602:29bd:3:\/64"
    },
    "vpn": [
        {
            "name": "fff-gw-dc-01",
            "protocol": "fastd",
            "address": "fff-gw-dc-01.servercreator.de",
            "port": "10000",
            "key": "ed059275d6386c05713474cef46acd0de94cc5af8c4e69027d651bffe05bcb2d"
        },
        {
            "name": "fff-hades",
            "protocol": "fastd",
            "address": "ds.hades.sgstbr.de",
            "port": "20010",
            "key": "74129c6d7cb5ee7470097679cfeb7b46a66b9983b938cc782c6d70248148878c"
        }
    ],
    "hood": {
        "id": "1",
        "name": "Nuernberg",
        "essid": "nuernberg.freifunk",
        "mesh_bssid": "ca:ff:ee:ba:be:03",
        "mesh_essid": "mesh.nuernberg.freifunk",
        "mesh_id": "mesh.nuernberg.freifunk",
        "protocol": "batman-adv-v15",
        "channel2": "13",
        "mode2": "ht20",
        "mesh_type2": "802.11s",
        "channel5": "40",
        "mode5": "ht20",
        "mesh_type5": "802.11s",
        "upgrade_path": "http:\/\/[fd43:5602:29bd:ffff::feee]:83",
        "ntp_ip": "fd43:5602:29bd:ffff::42",
        "timestamp": "1577226481"
    }
}',
    tests::get_web_page("https://keyserver.freifunk-franken.de/v2//index.php?hoodid=1"),
    "Get Hood Id 1");

/* Get Trainstation */
tests::assertEquals(
    '{
    "version": 1,
    "network": {
        "ula_prefix": "fd43:5602:29bd:0:\/64"
    },
    "vpn": [
        {
            "name": "fff-nue2-gw2",
            "protocol": "fastd",
            "address": "fff-nue2-gw2.fff.community",
            "port": "10000",
            "key": "07be3d18b703e6e040a6920afb3e226ded6aa474961d8eecbb77b623bdd21059"
        }
    ],
    "hood": {
        "id": "0",
        "name": "Trainstation",
        "essid": "trainstation.freifunk",
        "mesh_bssid": "ca:ff:ee:ba:be:00",
        "mesh_essid": "mesh.trainstation.freifunk",
        "mesh_id": "mesh.trainstation.freifunk",
        "protocol": "batman-adv-v15",
        "channel2": "13",
        "mode2": "ht20",
        "mesh_type2": "802.11s",
        "channel5": "40",
        "mode5": "ht20",
        "mesh_type5": "802.11s",
        "upgrade_path": "http:\/\/[fd43:5602:29bd:ffff::feee]:83",
        "ntp_ip": "fd43:5602:29bd:ffff::1",
        "timestamp": "1577303703"
    }
}',
    tests::get_web_page("https://keyserver.freifunk-franken.de/v2//"),
    "Get Trainstation");

/* Get not existing Hood */
tests::assertEquals(
    '{"error":{"msg":"Hood not found","url":"http:\/\/fff.itstall.de\/?hoodid=-1"}}',
    tests::get_web_page("https://keyserver.freifunk-franken.de/v2//?hoodid=-1"),
    "Get not existing Hood");

/* Get Polyhood from lat 50.94090, lon 9.66831 (freifunk-hersfeld.de) */
tests::assertEquals(
    '
{
    "version": 1,
    "network": {
        "ula_prefix": "fd43:5602:29bd:40:\/64"
    },
    "vpn": [
        {
            "name": "fff-gw-mc",
            "protocol": "fastd",
            "address": "fff-gw-mc.fff.community",
            "port": "10016",
            "key": "d3d27b6bdc161970a93bb900a8ba51cd0da0a5d80b301e3784f90e322d3d8423"
        },
        {
            "name": "fff-gw1-nixxda",
            "protocol": "fastd",
            "address": "hersfeld.fff.nixxda.net",
            "port": "10013",
            "key": "aecde3f1f34f5b94c122cb28a1d289463c720151265c61b0f5bbd717ae03e596"
        }
    ],
    "hood": {
        "id": "68",
        "name": "Bad Hersfeld",
        "essid": "freifunk-hersfeld.de",
        "mesh_bssid": "",
        "mesh_essid": "mesh.freifunk-hersfeld.de",
        "mesh_id": "mesh.freifunk-hersfeld.de",
        "protocol": "batman-adv-v15",
        "channel2": "13",
        "mode2": "ht20",
        "mesh_type2": "802.11s",
        "channel5": "40",
        "mode5": "ht20",
        "mesh_type5": "802.11s",
        "upgrade_path": "http:\/\/[fd43:5602:29bd:ffff::feee]:83",
        "ntp_ip": "fd43:5602:29bd:ffff::1",
        "timestamp": "1577226481"
    }
}',
    tests::get_web_page("https://keyserver.freifunk-franken.de/v2//index.php?lat=50.94090&long=9.66831"),
    "Get Polyhood from lat 50.94090, lon 9.66831 (freifunk-hersfeld.de)");

/* Get voronoi from lat 52.5219184, lon 13.411026 (berlin.freifunk-franken.de) */
tests::assertEquals(
    '{
    "version": 1,
    "network": {
        "ula_prefix": "fd43:5602:28bd:b9:\/64"
    },
    "vpn": [
        {
            "name": "fff-gw-mc",
            "protocol": "fastd",
            "address": "fff-gw-mc.fff.community",
            "port": "10015",
            "key": "37181622203cfcfd0d8f6fe2000c6a409095e13c962b0ca7c15714d478794532"
        },
        {
            "name": "fff-gw1",
            "protocol": "fastd",
            "address": "fff-gw1.fra2.sis-netz.de",
            "port": "10005",
            "key": "13b2707fcb8865af9327cf4daf4384c8464fddc0379b7de94f5e24f72df39eec"
        }
    ],
    "hood": {
        "id": "67",
        "name": "Berlin",
        "essid": "berlin.freifunk-franken.de",
        "mesh_bssid": "",
        "mesh_essid": "mesh.berlin.freifunk-franken.net",
        "mesh_id": "mesh.berlin.freifunk-franken.net",
        "protocol": "batman-adv-v15",
        "channel2": "13",
        "mode2": "ht20",
        "mesh_type2": "802.11s",
        "channel5": "40",
        "mode5": "ht20",
        "mesh_type5": "802.11s",
        "upgrade_path": "http:\/\/[fd43:5602:29bd:ffff::feee]:83",
        "ntp_ip": "fd43:5602:29bd:ffff::1",
        "timestamp": "1577226481"
    }
}',
    tests::get_web_page("https://keyserver.freifunk-franken.de/v2//index.php?lat=52.5219184&long=13.411026"),
    "Get voronoi from lat 52.5219184, lon 13.411026 (berlin.freifunk-franken.de)");
