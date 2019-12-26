<?php
/**
 * Handle VPN-Key-Exchange request for Freifunk-Franken.
 * Database Class
 *
 * @author  McUles <mcules@freifunk-hassberge.de>
 *
 * @license https://www.gnu.org/licenses/agpl-3.0.txt AGPL-3.0
 */

class tests
{
    function assertEquals($expected, $actual, $message = false)
    {
        echo "<b>Equals</b>->";
        if (strcmp(preg_replace('/\s/', '', $expected), preg_replace('/\s/', '', $actual)) == 0) {
            self::println(1, $expected, $actual, $message);
        } else {
            self::println(0, $expected, $actual, $message);
        }
    }

    function assertNotEqual($expected, $actual, $message = false)
    {
        echo "<b>notEqual</b>->";
        if (strcmp(preg_replace('/\s/', '', $expected), preg_replace('/\s/', '', $actual)) !== 0) {
            self::println(1, $expected, $actual, $message);
        } else {
            self::println(0, $expected, $actual, $message);
        }
    }

    private function println($pass, &$expected, &$actual, &$message)
    {
        if ($pass) {
            echo "<font style='color: green'>PASS</font> <b>$message</b><br/>";
        } else {
            echo "<font style='color: red'>FAILED</font> <b>$message</b>";
            echo "<b>Diff</b><br />" . self::diff($expected, $actual);
        }
    }

    /**
     * Get a web file (HTML, XHTML, XML, image, etc.) from a URL.  Return an
     * array containing the HTTP server response header fields and content.
     */
    public static function get_web_page($url)
    {
        $user_agent = 'Mozilla/5.0 (Windows NT 6.1; rv:8.0) Gecko/20100101 Firefox/8.0';

        $options = array(

            CURLOPT_CUSTOMREQUEST => "GET",        //set request type post or get
            CURLOPT_POST => false,        //set to GET
            CURLOPT_USERAGENT => $user_agent, //set user agent
            CURLOPT_COOKIEFILE => "cookie.txt", //set cookie file
            CURLOPT_COOKIEJAR => "cookie.txt", //set cookie jar
            CURLOPT_RETURNTRANSFER => true,     // return web page
            CURLOPT_HEADER => false,    // don't return headers
            CURLOPT_FOLLOWLOCATION => true,     // follow redirects
            CURLOPT_ENCODING => "",       // handle all encodings
            CURLOPT_AUTOREFERER => true,     // set referer on redirect
            CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
            CURLOPT_TIMEOUT => 120,      // timeout on response
            CURLOPT_MAXREDIRS => 10,       // stop after 10 redirects
        );

        $ch = curl_init($url);
        curl_setopt_array($ch, $options);
        $content = curl_exec($ch);
        $err = curl_errno($ch);
        $errmsg = curl_error($ch);
        $header = curl_getinfo($ch);
        curl_close($ch);

        $header['errno'] = $err;
        $header['errmsg'] = $errmsg;
        $header['content'] = $content;

        return $header['content'];
    }

    function diff($string1, $string2)
    {
        $diff = self::computeDiff(str_split($string1), str_split($string2));
        $diffval = $diff['values'];
        $diffmask = $diff['mask'];

        $n = count($diffval);
        $pmc = 0;
        $result = '';
        for ($i = 0; $i < $n; $i++) {
            $mc = $diffmask[$i];
            if ($mc != $pmc) {
                switch ($pmc) {
                    case -1:
                        $result .= '</font>';
                        break;
                    case 1:
                        $result .= '</font>';
                        break;
                }
                switch ($mc) {
                    case -1:
                        $result .= '<font color="red">';
                        break;
                    case 1:
                        $result .= '<font color="green">';
                        break;
                }
            }
            $result .= $diffval[$i];

            $pmc = $mc;
        }
        switch ($pmc) {
            case -1:
                $result .= '</del>';
                break;
            case 1:
                $result .= '</ins>';
                break;
        }

        return $result;
    }

    function computeDiff($from, $to)
    {
        $diffValues = array();
        $diffMask = array();

        $dm = array();
        $n1 = count($from);
        $n2 = count($to);

        for ($j = -1; $j < $n2; $j++) {
            $dm[-1][$j] = 0;
        }
        for ($i = -1; $i < $n1; $i++) {
            $dm[$i][-1] = 0;
        }
        for ($i = 0; $i < $n1; $i++) {
            for ($j = 0; $j < $n2; $j++) {
                if ($from[$i] == $to[$j]) {
                    $ad = $dm[$i - 1][$j - 1];
                    $dm[$i][$j] = $ad + 1;
                } else {
                    $a1 = $dm[$i - 1][$j];
                    $a2 = $dm[$i][$j - 1];
                    $dm[$i][$j] = max($a1, $a2);
                }
            }
        }

        $i = $n1 - 1;
        $j = $n2 - 1;
        while (($i > -1) || ($j > -1)) {
            if ($j > -1) {
                if ($dm[$i][$j - 1] == $dm[$i][$j]) {
                    $diffValues[] = $to[$j];
                    $diffMask[] = 1;
                    $j--;
                    continue;
                }
            }
            if ($i > -1) {
                if ($dm[$i - 1][$j] == $dm[$i][$j]) {
                    $diffValues[] = $from[$i];
                    $diffMask[] = -1;
                    $i--;
                    continue;
                }
            }
            {
                $diffValues[] = $from[$i];
                $diffMask[] = 0;
                $i--;
                $j--;
            }
        }

        $diffValues = array_reverse($diffValues);
        $diffMask = array_reverse($diffMask);

        return array('values' => $diffValues, 'mask' => $diffMask);
    }
}
