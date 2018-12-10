<?php
use SVBX\Report;
use SVBX\Export;

require '../vendor/autoload.php';

// TODO: authorize requester using session cookie
// TODO: clean data
// TODO: validate query params
// TODO: check `format` param
echo Export::csv(
    Report::delta($_GET['milestone'])->get()
);

exit;