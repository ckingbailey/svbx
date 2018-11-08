<?php
// Thanks go to this gist in writing this code:
// https://gist.github.com/johanmeiring/2894568
// function to dump def query to file readable by spreadsheet program
function writeToFile($data) {
    $curTime = time();
    $fileName = "./assets/tmp/def_dump_$curTime.csv";
    
    if (!$csv = fopen($fileName, 'w'))
        throw new Exception('Failed to open file stream');

    $headings = array_keys($data[0]);
    fputcsv($csv, $headings);
        
    foreach ($data as $row) {
        fputcsv($csv, $row);
    }

    return $fileName;
}

// function to write csv to string
if (!function_exists('str_putcsv')) {
    function str_putcsv(array $input, $delimiter = ',', $enclosure = '"') {
        $pointer = fopen('php://temp', 'r+b');
        foreach ($input as $line) {
            fputcsv($pointer, $line, $delimiter, $enclosure); // puts a single line
        }
        rewind($pointer);
        $data = rtrim(stream_get_contents($pointer), "\n");
        fclose($pointer);
        return $data;
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST', true, 405);
    exit;
}

$post = trim(file_get_contents('php://input'));
$post = json_decode($post, true);
$post = filter_var_array($post, FILTER_SANITIZE_SPECIAL_CHARS);

header('Content-type: text/csv', true);

echo str_putcsv($post);

exit;

/* TODO:
    1. user clicks download button
    2. fetch POST, send form-encoded def data to endpoint
    3. PHP endpoint process form-encoded data to csv
    4. PHP echoes csv string as response to fetch request
    5. fetch blob-ifies the response and sends it to browser for download
*/