<?php
namespace SVBX;

use MysqliDB;
use Carbon\Carbon;
use Carbon\CarbonImmutable;

class Report {
    private $data = [];
    private $lastQuery = '';

    private $table = null;
    private $fields = [];
    private $join = [];
    private $where = [];
    private $groupBy = null;

    // private $queryString = "SELECT sy.systemName AS system, %s, %s FROM CDL c JOIN system sy ON c.systemAffected = sy.systemID WHERE dateClosed IS NOT NULL and c.requiredBy < %u GROUP BY c.systemAffected";

    public static function delta($milestone, $date = null, $system = null) {
        $openLastWeek = 'COUNT(CASE'
            . ' WHEN CDL.dateCreated > CAST("%1$s" AS DATE) THEN NULL' // didn't yet exist last week
            . ' WHEN dateClosed <= CAST("%1$s" AS DATE) THEN NULL' // already closed last week
            // . ' WHEN dateClosed > CAST("%1$s" AS DATE) THEN defID' // existed. closed sometime after last week
            . ' ELSE defID END) AS openLastWeek';
        // $caseStr = "COUNT(CASE WHEN dateClosed <= CAST('%s' AS DATE) THEN defID ELSE NULL END) AS %s";
        $toDate = new CarbonImmutable($date); // will Carbon accept any format as arg?
        $fromDate = $toDate->subWeek()->toDateString();
        $fields = [
            'systemName AS system',
            sprintf($openLastWeek, $fromDate),
            "COUNT(IF(status = 1, defID, NULL)) as openThisWeek"
        ];
        
        $join = ['system', 'systemAffected = system.systemID', 'LEFT'];
        
        $link = new MysqliDb(DB_CREDENTIALS);
        $whereField = is_int($milestone) ? 'reqByID' : 'requiredBy';
        $reqByID = $link
            ->where($whereField, $milestone)
            ->getValue('requiredBy', 'reqByID');
        $where = [
            [ 'requiredBy', $reqByID, '<='],
            [ 'status', '3', '<>']
        ];    
        
        if (!empty($system)) {
            list($groupBy, $where[]) = [ null, [ 'systemAffected', $system ] ];
        } else $groupBy = 'systemAffected';
    
        if (empty($reqByID)) throw new \Exception("Could not find milestone for query term $milestone");

        return new Report('CDL', $fields, $join, $where, $groupBy);
        
    }    
    
    private function __construct($table = null, $fields = [], $join = null, $where = null, $groupBy = null) {
        $this->table = $table;
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

        $result = $link // TODO: what happens if I pass null to any Joshcam metho?
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

    public function __toString() {
        print_r($this->get());
    }

}