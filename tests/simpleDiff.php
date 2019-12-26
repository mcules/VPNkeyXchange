<?php

/*
    Paul's Simple Diff Algorithm v 0.1
    (C) Paul Butler 2007 <http://www.paulbutler.org/>
    May be used and distributed under the zlib/libpng license.

    This code is intended for learning purposes; it was written with short
    code taking priority over performance. It could be used in a practical
    application, but there are a few ways it could be optimized.

    Given two arrays, the function diff will return an array of the changes.
    I won't describe the format of the array, but it will be obvious
    if you use print_r() on the result of a diff on some test data.

    htmlDiff is a wrapper for the diff command, it takes two strings and
    returns the differences in HTML. The tags used are <ins> and <del>,
    which can easily be styled with CSS.
*/

function diffline($line1, $line2)
{
    $diff = computeDiff(str_split($line1), str_split($line2));
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
                    $result .= '</del>';
                    break;
                case 1:
                    $result .= '</ins>';
                    break;
            }
            switch ($mc) {
                case -1:
                    $result .= '<del>';
                    break;
                case 1:
                    $result .= '<ins>';
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

function htmlDiff($old, $new)
{
    $ret = '';
    $diff = diff(preg_split("/[\s]+/", $old), preg_split("/[\s]+/", $new));
    foreach ($diff as $k) {
        if (is_array($k)) {
            $ret .= (!empty($k['d']) ? "<del>" . implode(' ', $k['d']) . "</del> " : '') .
                (!empty($k['i']) ? "<ins>" . implode(' ', $k['i']) . "</ins> " : '');
        } else {
            $ret .= $k . ' ';
        }
    }

    return $ret;
}

?>