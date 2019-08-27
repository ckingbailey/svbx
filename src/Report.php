<?php
namespace SVBX;

use MysqliDB;
use Carbon\Carbon;
use Carbon\CarbonImmutable;

/**
 * Class for outputting reports that are too complex for parameters of Deficiency object
 */
class Report {
    private $data = [];
    private $lastQuery = '';

    private $table = null;
    private $headings = [];
    private $fields = [];
    private $join = [];
    private $where = [];
    private $groupBy = null;

    public static function delta($field = 'severity', $from = null, $to = null, $milestone = null) {
        $countOpenFromDate = 'COUNT(CASE'
            . ' WHEN (CDL.dateCreated <= CAST("%1$s" AS DATE)'
            . ' && (status = "1" || dateClosed > CAST("%1$s" AS DATE))) THEN defID'
            . ' ELSE NULL END) AS %2$s';
        $toDate = new CarbonImmutable($to ?: date('Y-m-d'));
        $fromDate = ($from ?
            new CarbonImmutable($from)
            : $toDate->subWeek())->toDateString();

        $toDate = $toDate->toDateString();

        $params = [
            'severity' => [
                'select' => 'severityName AS fieldName',
                'join' => [ 'severity', 'severity = severity.severityID' ],
                'groupBy' => 'severity'
            ],
            'system' => [
                'select' => 'systemName AS fieldName',
                'join' => [ 'system', 'groupToResolve = system.systemID' ],
                'groupBy' => 'groupToResolve'
            ]
        ];

        $headings = [ $field, $fromDate, $toDate, $milestone ];
        
        $fields = [
            $params[$field]['select'],
            sprintf($countOpenFromDate, $fromDate, 'fromDate'),
            sprintf($countOpenFromDate, $toDate, 'toDate') // "COUNT(IF(status = 1, defID, NULL)) as toDate" // need to allow the `to` range to be set
        ];
        
        $where = [
            [ 'CDL.dateCreated', $toDate, '<='],
            [ 'status', '3', '<>']
        ];

        if (!empty($milestone))
            $where[] = [ 'requiredBy', $milestone, '<='];
        
        return new self('CDL', $headings, $fields, $params[$field]['join'], $where, $params[$field]['groupBy']);
    }    
    
    private function __construct($table = null, $headings = [], $fields = [], $join = null, $where = null, $groupBy = null) {
        $this->table = $table;
        $this->headings = $headings;
        $this->fields = $fields;

        if (!empty($join)) {
            if (is_array($join[0])) $this->join = $join;
            else array_push($this->join, $join);
        }
        if (!empty($where)) {
            if (is_array($where[0])) $this->where = $where;
            else $this->where[] = $where;
        }
        $this->groupBy = $groupBy;

        $this->fetch();
    }

    private function fetch() {
        $link = new MysqliDb(DB_CREDENTIALS);

        foreach ($this->join as $join) {
            if (empty($join[2])) {
                $link->join($join[0], $join[1]);
            } else $link->join($join[0], $join[1], $join[2]);
        }

        foreach($this->where as $where) {
            if (empty($where[2])) {
                $link->where($where[0], $where[1]);
            } else $link->where($where[0], $where[1], $where[2]);
        }

        if (!empty($this->groupBy)) $link->groupBy($this->groupBy);

        $result = $link
            ->get($this->table, null, $this->fields);
        $this->lastQuery = $link->getLastQuery();
        $link->disconnect();

        $this->data = $result;
    }

    public function getQuery() {
        return $this->lastQuery;
    }

    public function get() {
        return $this->data;
    }

    public function getWithHeadings() {
        $dataWithHeadings = $this->data;
        $headings = array_filter($this->headings);
        array_unshift($dataWithHeadings, $headings);
        return $dataWithHeadings;
    }

    public function __toString() {
        print_r($this->get(), true);
    }

}