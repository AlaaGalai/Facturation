#!/usr/bin/php
<?php
error_reporting(0);

/**
 * Calcul du chiffre-clé en PHP, modulo 10, récursif BVR Suisse
 * Berechnung der Prüfziffer nach Modulo 10, rekursiv BESR Schweiz
 * Calculating the Check Digits Using Module 10, Recursively
 * 
 * @author gbaudoin
 * @license GPL
 * @see https://www.postfinance.ch/content/dam/pf/de/doc/consult/manual/dldata/efin_recdescr_man_fr.pdf
 * @see https://gist.github.com/christianmeichtry/9348451
 */
 
class Bvr {
    private static $table = [
        [0,9,4,6,8,2,7,1,3,5],
        [9,4,6,8,2,7,1,3,5,0],
        [4,6,8,2,7,1,3,5,0,9],
        [6,8,2,7,1,3,5,0,9,4],
        [8,2,7,1,3,5,0,9,4,6],
        [2,7,1,3,5,0,9,4,6,8],
        [7,1,3,5,0,9,4,6,8,2],
        [1,3,5,0,9,4,6,8,2,7],
        [3,5,0,9,4,6,8,2,7,1],
        [5,0,9,4,6,8,2,7,1,3]
    ];
    public static function calculate($number) {
        $report = 0;
        foreach(str_split($number) as $key => $value) {
            $report = self::$table[$report][(int)$value];
        }
        return (10 - $report) %10;
    }

    public static function validate($number, $digit) {
        return $digit == self::calculate($number);
    }
}

 $number = $argv[1];

$digit = Bvr::calculate($number);
echo "$digit\n";