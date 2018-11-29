<?php
namespace SVBX;

use MysqliDb;

class Deficiency
{
    private $dateFormat = 'Y-m-d';
    // NOTE: prop names do not nec. have to match db col names
    //  (but it could help)
    private $ID = null;
    private $safetyCert = null;
    private $systemAffected = null;
    private $location = null;
    private $specLoc = null;
    private $status = null;
    private $severity = null;
    private $dueDate = null;
    private $groupToResolve = null;
    private $requiredBy = null;
    private $contractID = null;
    private $identifiedBy = null;
    private $defType = null;
    private $description = null;
    private $spec = null;
    private $actionOwner = null;
    private $evidenceType = null;
    private $repo = null;
    private $evidenceID = null;
    private $evidenceLink = null;
    private $oldID = null;
    private $closureComments = null;
    private $created_by = null; // validate: userID
    private $updated_by = null; // validate: userID
    private $dateCreated = null; // validate: date (before lastUpdated; dateClosed?)
    private $lastUpdated = null;
    private $dateClosed = null; // validate against status || set
    private $closureRequested = null; // validate against status??
    private $closureRequestedBy = null; // validate: userID
    private $comments = [];
    private $newComment = null;
    private $pics = [];
    private $newPic = null;

    private $table = 'CDL';
    private $commentsTable = 'cdlComments';
    private $picssTable = 'cdlPics';

    private $fields = [
        'safetyCert',
        'systemAffected',
        'location',
        'specLoc',
        'status',
        'severity',
        'dueDate',
        'groupToResolve',
        'requiredBy',
        'contractID',
        'identifiedBy',
        'defType',
        'description',
        'spec',
        'actionOwner',
        'evidenceType',
        'repo',
        'evidenceID',
        'evidenceLink',
        'oldID',
        'closureComments',
        'created_by',
        'updated_by',
        'dateCreated',
        'lastUpdated',
        'dateClosed',
        'closureRequested',
        'closureRequestedBy'
    ];

    private $requiredFields = [
        'safetyCert',
        'systemAffected',
        'location',
        'specLoc',
        'status',
        'severity',
        'dueDate',
        'groupToResolve',
        'requiredBy',
        'contractID',
        'identifiedBy',
        'defType',
        'description'
    ];

    private $associatedObjects = [
        'comments', // these are not strictly 'props' but are actually associated Objects
        'newComment', // these are not strictly 'props' but are actually associated Objects
        'pics', // these are not strictly 'props' but are actually associated Objects
        'newPic' // these are not strictly 'props' but are actually associated Objects
    ];
    
    static private $foreignKeys = [
        'safetyCert' => [
            'table' => 'yesNo',
            'fields' => ['yesNoID', 'yesNoName']
        ],
        'systemAffected' => [
            'table' => 'system',
            'fields' => ['systemID', 'systemName']
        ],
        'groupToResolve' => [
            'table' => 'system',
            'fields' => ['systemID', 'systemName']
        ],
        'location' => [
            'table' => 'location',
            'fields' => ['locationID', 'locationName']
        ],
        'status' => [
            'table' => 'status',
            'fields' => ['statusID', 'statusName'],
            'where' => [
                [
                    'field' => 'statusName',
                    'value' => 'open'
                ],
                [
                    'field' => 'statusName',
                    'value' => 'closed'
                ]
            ]
        ],
        'severity' => [
            'table' => 'severity',
            'fields' => ['severityID', 'severityName']
        ],
        'requiredBy' => [
            'table' => 'requiredBy',
            'fields' => ['reqByID', 'requiredBy']
        ],
        'contract' => [
            'table' => 'contract',
            'fields' => ['contractID', 'contractName']
        ],
        'defType' => [
            'table' => 'defType',
            'fields' => ['defTypeID', 'defTypeName']
        ],
        'evidenceType' => [
            'table' => 'evidenceType',
            'fields' => ['eviTypeID', 'eviTypeName']
        ],
        'repo' => [
            'table' => 'repo',
            'fields' => ['repoID', 'repoName']
        ]
    ];
    
    // TODO: check for incoming defID in DB and validate persisted data against incoming data
    public function __construct($id = null, array $data = []) {
        if (!empty($id) && !empty($data)) { // This is a known Def
            $this->ID = $id;

            $this->assignDataToProps($data);
            // TODO: check for associated Objects => 'attachments' and 'comments'
            // TODO: check for new Attachment or Comment and link it to this Def
        } elseif (!empty($id)) {
            $this->ID = $id;
            try {
                $link = new MysqliDb(DB_CREDENTIALS);
                $link->where('defID', $this->ID);
                $data = $link->getOne('CDL', $this->fields);

                $this->assignDataToProps($data);
            } catch (Exception $e) {
                throw new Exception($e->getMessage);
            } finally {
                if (is_a($link, 'MysqliDb')) $link->disconnect();
            }
            // TODO: query for associated Objects => 'attachments' and 'comments'
        } elseif (!empty($data)) {
            $this->assignDataToProps($data);
            // TODO: the above should be a dedicated function
        } else {
            // no id or props = no good
            throw new Exception('What is this? You tried to instantiate a new Deficiency without passing any data or id');
        }

        //---------------------------------------------------------------------------------//
        // // TODO: check for defID before checking creation deets <-- this only pertains to update
        // // check creation details in db before setting them in obj
        // // if (empty($this->data['createdBy']) || empty($this->data['dateCreated'])) {
        //     // $link = new MysqliDb(DB_HOST, DB_USER, DB_PWD, DB_NAME);
        //     // $link->where('defID', $this->data['defID']);
        //     // $creationStamp = $link->get('deficiency', ['createdBy', 'dateCreated']);
        // if (empty($this->data['createdBy'])) $this->data['createdBy'] = $_SESSION['userID'];
        // if (empty($this->data['dateCreated'])) $this->data['dateCreated'] = date('Y-m-d H:i:s');
        // // }
    }

    private function assignDataToProps($data) {
        foreach ($data as $key => $val) {
            if (property_exists(__CLASS__, $key))
                $this->$key = $val;
        }
    }

    public function getNonNullProps() {
        return array_reduce($this->fields, function ($acc, $prop) {
            if ($this->$prop !== null) $acc[$prop] = $this->$prop;
            return $acc;
        }, []);
    }

    public function validate($props = null) { // TODO: takes an optional (String) single prop or (Array) of props to validate
        // TODO: validate dates, required info, closure info where appropriate
    }

    public function setDateCreated() {
        return $this->dateCreated = date($this->dateFormat);
    }
    
    private function setDateClosed() {
        $this->dateClosed = date($this->dateFormat);
    }

    public function set(string $prop, $val) {
        if (property_exists(__CLASS__, $prop)) {
            if (strpos(strtolower($prop), 'date') !== false) {
                $val = $val ?: time();
                $this->$prop = date($this->dateFormat, $val);
            } else $this->$prop = $val;
        }
    }
    
    // TODO: add fn to handle relatedAsset, newComment, newAttachment
    public function insert() {
        $this->ID = null; // defID gets created by autoincrement in db
        $this->lastUpdated = null; // lastUpdated gets timestamp by mysql

        // validate / set creation info
        if (empty($this->created_by)) throw new \Exception('Missing value @ `created_by`');
        if (empty($this->dateCreated)) $this->setDateCreated();
        
        // validate / set mod info
        if (empty($this->updated_by)) throw new \Exception('Missing value @ `updated_by`');
        
        // TODO: validate / set required info
        foreach ($this->requiredFields as $field) {
            if (empty($this->$field)) throw new \Exception("Missing required info @ `$field`");
        }
        
        // validate / set closure info if appropriate
        if ($this->status === 2) {
            if (empty($this->repo)) throw new \Exception('Missing closure info @ `repo`');
            if (empty($this->evidenceID)) throw new \Exception('Missing closure info @ `evidenceID`');
            if (empty($this->evidenceType)) throw new \Exception('Missing closure info @ `evidenceType`');
            if (empty($this->dateClosed)) $this->setDateClosed();
        }
        
        $insertableData = $this->getNonNullProps();
        $cleanData = filter_var_array($insertableData, FILTER_SANITIZE_SPECIAL_CHARS);
        
        try {
            $link = new MysqliDb(DB_CREDENTIALS);
            $this->ID = $link->insert($this->table, $cleanData);
            $link->disconnect();
        } catch (\Exception $e) { throw new \Exception($e); }
        finally { if (is_a($link, 'MysqliDb')) $link->disconnect(); }
        
        return $this->ID;
    }
    
    public function update() {
        // TODO: strip fields that never get updated, e.g., dateCreated
        return false;
    }
    
    // TODO: this could check for which value should be selected (the value that is on data for the corresponding field)
    // in that case it would have to take a Defificency object as argument
    static function getLookUpOptions() {
        try {
            $link = new MysqliDb(DB_CREDENTIALS);
            $options = [];
            
            foreach (self::$foreignKeys as $childField => $lookup) {
                $table = $lookup['table'];
                $fields = $lookup['fields'];
                $fields[0] .= ' AS id';
                $fields[1] .= ' AS name';
                
                if (!empty($lookup['where'])) {
                    $i = 0;
                    foreach ($lookup['where'] as $where) {
                        if ($i === 0) $link->where($where['field'], $where['value']);
                        else $link->orWhere($where['field'], $where['value']);
                        $i++;
                    }
                }
                
                $options[$childField] = $link->get($table, null, $fields);
                // $options[$childField] = [$table => [ $fields ]];
            }
            
            if (!empty($link) && is_a($link, 'MysqliDb')) $link->disconnect();
            return $options;
        } catch (Exception $e) {
            error_log($e);
            throw $e;
        }
    }
    
    public function __toString() {
        return print_r([
            'ID' => $this->ID,
            'safetyCert' => $this->safetyCert,
            'systemAffected' => $this->systemAffected,
            'location' => $this->location,
            'specLoc' => $this->specLoc,
            'status' => $this->status,
            'severity' => $this->severity,
            'dueDate' => $this->dueDate,
            'groupToResolve' => $this->groupToResolve,
            'requiredBy' => $this->requiredBy,
            'contract' => $this->contract,
            'identifiedBy' => $this->identifiedBy,
            'defType' => $this->defType,
            'description' => $this->description,
            'spec' => $this->spec,
            'actionOwner' => $this->actionOwner,
            'evidenceType' => $this->evidenceType,
            'repo' => $this->repo,
            'evidenceID' => $this->evidenceID,
            'evidenceLink' => $this->evidenceLink,
            'oldID' => $this->oldID,
            'closureComments' => $this->closureComments,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'dateCreated' => $this->dateCreated,
            'lastUpdated' => $this->lastUpdated,
            'dateClosed' => $this->dateClosed,
            'closureRequested' => $this->closureRequested,
            'closureRequestedBy' => $this->closureRequestedBy,
            'comments' => $this->comments,
            'newComment' => $this->newComment,
            'attachments' => $this->attachments,
            'newAttachment' => $this->newAttachment
        ], true);
    }
}
