<?php
use Carbon\Carbon;
use Carbon\CarbonImmutable;

require_once 'vendor/autoload.php';
require_once 'config.php';

class WeeklyDelta {
    private $data = [];

    public $requiredBy = '';
    private $caseStr = "COUNT(CASE WHEN dateClosed <= CAST('%s' AS DATE) THEN defID ELSE NULL END) AS %s";
    private $queryString = "SELECT sy.systemName AS system, %s, %s FROM CDL c JOIN system sy ON c.systemAffected = sy.systemID WHERE dateClosed IS NOT NULL and c.requiredBy < %u GROUP BY c.systemAffected";

    private function connect() {
        return new MysqliDb(DB_HOST, DB_USER, DB_PWD, DB_NAME);
    }

    private function fetchData() {
        $queryStr = $this->buildQueryString();
        $link = $this->connect();
        $result = $link->rawQuery($queryStr);
        $link->disconnect();

        return $result;
    }

    private function buildQueryString() {
        $today = new CarbonImmutable();
        $caseThisWeek = sprintf($this->caseStr, $today, 'openThisWeek');
        $caseLastWeek = sprintf($this->caseStr, $today->subWeek(), 'openLastWeek');

        return sprintf($this->queryString, $caseThisWeek, $caseLastWeek, $this->requiredBy);
    }

    public function fetchRequiredByID($reqBy) {
        $link = $this->connect();
        $whereField = is_int($reqBy) ? 'reqByID' : 'requiredBy';

        $link->where($whereField, $reqBy);
        $result = $link->getOne('requiredBy', 'reqByID');
        $link->disconnect();

        return $result['reqByID'];
    }

    public function __construct($reqBy) {
        $this->requiredBy = $this->fetchRequiredByID($reqBy);
        if ($this->requiredBy) $this->data = $this->fetchData();
        else throw new Exception("No requiredBy data could be found for value $reqBy");
    }

    public function getData() {
        return $this->data;
    }

    public function __toString() {
        print_r($this->getData());
    }
}

$weeklySIT3Delta = new WeeklyDelta('SIT3');
echo $weeklySIT3Delta . PHP_EOL;