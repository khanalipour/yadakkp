<?php
/**
 * Jalali (Shamsi) Date Functions for PHP
 * Lightweight JDF implementation for Persian date rendering
 */

function jdate($format, $timestamp = '', $none = '', $time_zone = 'Asia/Tehran', $tr_num = 'fa') {
    $T_sec = 0;
    if ($time_zone != 'local') {
        date_default_timezone_set($time_zone);
    }
    $ts = ($timestamp === '') ? time() : $timestamp;
    $date = explode('_', date('Y_m_d_H_i_s_w', $ts));
    list($j_y, $j_m, $j_d) = gregorian_to_jalali($date[0], $date[1], $date[2]);
    $doy = ($j_m < 7) ? (($j_m - 1) * 31 + $j_d) : (186 + (($j_m - 7) * 30) + $j_d);

    $out = '';
    $len = strlen($format);
    for ($i = 0; $i < $len; $i++) {
        $sub = substr($format, $i, 1);
        if ($sub == '\\') {
            $out .= substr($format, ++$i, 1);
            continue;
        }
        switch ($sub) {
            case 'Y': $out .= $j_y; break;
            case 'y': $out .= substr($j_y, 2, 2); break;
            case 'm': $out .= ($j_m < 10) ? '0' . $j_m : $j_m; break;
            case 'n': $out .= $j_m; break;
            case 'd': $out .= ($j_d < 10) ? '0' . $j_d : $j_d; break;
            case 'j': $out .= $j_d; break;
            case 'H': $out .= $date[3]; break;
            case 'i': $out .= $date[4]; break;
            case 's': $out .= $date[5]; break;
            case 'F':
                $months = ['', 'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];
                $out .= $months[$j_m];
                break;
            case 'l':
                $days = ['یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه', 'شنبه'];
                $out .= $days[$date[6]];
                break;
            default: $out .= $sub; break;
        }
    }
    return ($tr_num === 'fa') ? tr_num($out) : $out;
}

function gregorian_to_jalali($gy, $gm, $gd) {
    $g_d_m = array(0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334);
    if ($gy > 1600) {
        $jy = 979;
        $gy -= 1600;
    } else {
        $jy = 0;
        $gy -= 621;
    }
    $gy2 = ($gm > 2) ? ($gy + 1) : $gy;
    $days = (365 * $gy) + ((int)(($gy2 + 3) / 4)) - ((int)(($gy2 + 99) / 100)) + ((int)(($gy2 + 399) / 400)) - 80 + $gd + $g_d_m[$gm - 1];
    $jy += 33 * ((int)($days / 12053));
    $days %= 12053;
    $jy += 4 * ((int)($days / 1461));
    $days %= 1461;
    if ($days > 365) {
        $jy += (int)(($days - 1) / 365);
        $days = ($days - 1) % 365;
    }
    $jm = ($days < 186) ? 1 + (int)($days / 31) : 7 + (int)(($days - 186) / 30);
    $jd = 1 + (($days < 186) ? ($days % 31) : (($days - 186) % 30));
    return array($jy, $jm, $jd);
}

function tr_num($str, $mod = 'fa') {
    $num_a = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9');
    $key_a = array('۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹');
    return ($mod === 'fa') ? str_replace($num_a, $key_a, $str) : str_replace($key_a, $num_a, $str);
}
