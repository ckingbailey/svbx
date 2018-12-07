<?php
namespace SVBX;

use MysqliDB;
use Carbon;
use CarbonImmutable;

class Report {
    private $data = [];

    private $table = null;
    private $fields = [];
    private $join = [];
    private $where = [];
    private $groupBy = null;

    // private $queryString = "SELECT sy.systemName AS system, %s, %s FROM CDL c JOIN system sy ON c.systemAffected = sy.systemID WHERE dateClosed IS NOT NULL and c.requiredBy < %u GROUP BY c.systemAffected";

    public static function delta($milestone, $date = null, $system = null) {
        $caseStr = "COUNT(CASE WHEN dateClosed <= CAST('%s' AS DATE) THEN defID ELSE NULL END) AS %s";
        $toDate = new CarbonImmutable($date); // will Carbon accept any format as arg?
        $fromDate = $toDate->subWeek();
        $fields = [
            'systemName AS system',
            sprintf($caseStr, $fromDate, 'openLastWeek'),
            sprintf($caseStr, $toDate, 'openThisWeek')
        ];
        
        $join = ['system', 'groupToResolve = system.systemID', 'LEFT'];
        
        $where = [
            [ 'dateClosed', 'IS NOT NULL' ],
            [ 'requiredBy', $reqByID, '<']
        ];    
        
        if (!empty($system)) {
            list($groupBy, $where[]) = [ null, [ 'groupToResolve', $system ] ];
        } else $groupBy = 'groupToResolve';
        
        $link = new MysqliDb(DB_CREDENTIALS);
        $whereField = is_int($milestone) ? 'reqByID' : 'requiredBy';
        $reqByID = $link
            ->where($whereField, $milestone)
            ->getValue('requiredBy', 'reqByID');
    
        if (empty($reqByID)) throw new \Exception("Could not find milestone for query term $milestone");

        return $this->__construct('CDL', $fields, $join, $where, $groupBy);
        
    }    
    
    private function __construct($table = null, $fields = [], $join = [], $where = [], $groupBy = null) {
        $this->fields = $fields;
        $this->join = $join;
        $this->where = $where;
        $this->groupBy = $groupBy;

        // TODO: constructor fetches data after setting query conds
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

        $result = $link // TODO: what happens if I pass null to any Joshcam metho?
            ->groupBy($this->groupBy)
            ->get($this->table, null, $this->fields);
        $link->disconnect();

        $this->data = $result;
    }

    // private function buildQueryString() {
    //     $today = new CarbonImmutable();
    //     $caseThisWeek = sprintf($this->caseStr, $today, 'openThisWeek');
    //     $caseLastWeek = sprintf($this->caseStr, $today->subWeek(), 'openLastWeek');

    //     return sprintf($this->queryString, $caseThisWeek, $caseLastWeek, $this->requiredBy);
    // }

    public function get() {
        return $this->data;
    }

    public function __toString() {
        print_r($this->get());
    }

}