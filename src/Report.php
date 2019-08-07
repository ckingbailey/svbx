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
    private $fields = [];
    private $join = [];
    private $where = [];
    private $groupBy = null;

    public static function delta($milestone = null, $date = null, $system = null) {
        $openLastWeek = 'COUNT(CASE'
            . ' WHEN (CDL.dateCreated < CAST("%1$s" AS DATE)'
            . ' && (status = "1" || dateClosed > CAST("%1$s" AS DATE))) THEN defID'
            . ' ELSE NULL END) AS openLastWeek';
        $toDate = new CarbonImmutable($date);
        $fromDate = $toDate->subWeek()->toDateString();
        $fields = [
            'systemName AS system',
            sprintf($openLastWeek, $fromDate),
            "COUNT(IF(status = 1, defID, NULL)) as openThisWeek"
        ];
        
        $join = ['system', 'systemAffected = system.systemID', 'LEFT'];
        
        $link = new MysqliDb(DB_CREDENTIALS);

        $where = [
            [ 'status', '3', '<>']
        ];    

        // grab ID of by milestone name from db, if milestone provided
        if (!empty($milestone)) {
            $whereField = intval($milestone) ? 'reqByID' : 'requiredBy';
            $reqByID = $link
                ->where($whereField, $milestone)
                ->getValue('requiredBy', 'reqByID');
            
            if (empty($reqByID)) throw new \UnexpectedValueException("Could not find milestone for query term $milestone");
            
            $where[] = [ 'requiredBy', $reqByID, '<='];
        }
        
        if (!empty($system)) {
            list($groupBy, $where[]) = [ null, [ 'systemAffected', $system ] ];
        } else $groupBy = 'systemAffected';
    
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

    public function __toString() {
        print_r($this->get(), true);
    }

}