<?php
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
    function str_putcsv($input, $delimiter = ',', $enclosure = '"') {
        $fp = fopen('php://temp', 'r+b');
        fputcsv($fp, $input, $delimiter, $enclosure);
        rewind($fp);
        $data = rtrim(stream_get_contents($fp), "\n");
        fclose($fp);
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

error_log(print_r($post[0], true));
echo str_putcsv($post);

exit;

/* TODO:
    1. user clicks download button
    2. fetch POST, send form-encoded def data to endpoint
    3. PHP endpoint process form-encoded data to csv
    4. PHP echoes csv string as response to fetch request
    5. fetch blob-ifies the response and sends it to browser for download
*/