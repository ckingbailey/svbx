<?php
namespace SVBX;

use MysqliDb;

class Export {
    public static function csv($data) {
        return self::str_putcsv($data); // NOTE: first index of $data must be column headings if headings are desired
    }

    private static function str_putcsv(array $input, $delimiter = ',', $enclosure = '"') {
        $pointer = fopen('php://temp', 'r+b'); // open memory stream with read/write permission and binary mode on
        foreach ($input as $line) {
            $line = array_map(function($el) {
                return html_entity_decode($el, ENT_QUOTES, 'utf-8');
            }, $line);
            fputcsv($pointer, $line, $delimiter, $enclosure); // puts a single line
        }
        rewind($pointer);
        $data = rtrim(stream_get_contents($pointer), "\n"); // trim whitespace
        fclose($pointer);
        return $data;
    }
}