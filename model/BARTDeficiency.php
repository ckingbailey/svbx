<?php
namespace SVBX;

use MysqliDb;

class BARTDeficiency extends Deficiency {
    protected $timestampField = 'form_modified';

    protected $table = 'BARTDL';
    public $commentsTable = [
        'table' => 'bartdlComments',
        'field' => 'bdCommText',
        'defID' => 'bartdlID',
        'commID' => 'bdCommID'
    ];

    protected $props = [
        'id' => null,
        'created_by' => null,
        'dateCreated' => null,
        'updated_by' => null,
        'lastUpdated' => null,
        'creator' => null,
        'next_step' => null,
        'bic' => null,
        'status' => null,
        'descriptive_title_vta' => null,
        'root_prob_vta' => null,
        'resolution_vta' => null,
        'priority_vta' => null,
        'agree_vta' => null,
        'safety_cert_vta' => null,
        'resolution_disputed' => null,
        'structural' => null,
        'id_bart' => null,
        'description_bart' => null,
        'cat1_bart' => null,
        'cat2_bart' => null,
        'cat3_bart' => null,
        'level_bart' => null,
        'dateOpen_bart' => null,
        'dateClose_bart' => null,
        'dateClosed' => null,
        'repo' => null,
        'evidenceID' => null,
        'evidenceType' => null,
        'evidenceLink' => null,
        'closureComment' => null,
        'attachments' => [],
        'newAttachment' => null,
        'comments' => [],
        'newComment' => null
    ];

    protected $fields = [
        'id' => 'id',
        'created_by' => 'created_by',
        'dateCreated' => 'date_created',
        'updated_by' => 'updated_by',
        'lastUpdated' => 'form_modified',
        'creator' => 'creator',
        'next_step' => 'next_step',
        'bic' => 'bic',
        'status' => 'status',
        'descriptive_title_vta' => 'descriptive_title_vta',
        'root_prob_vta' => 'root_prob_vta',
        'resolution_vta' => 'resolution_vta',
        'priority_vta' => 'priority_vta',
        'agree_vta' => 'agree_vta',
        'safety_cert_vta' => 'safety_cert_vta',
        'resolution_disputed' => 'resolution_disputed',
        'structural' => 'structural',
        'id_bart' => 'id_bart',
        'description_bart' => 'description_bart',
        'cat1_bart' => 'cat1_bart',
        'cat2_bart' => 'cat2_bart',
        'cat3_bart' => 'cat3_bart',
        'level_bart' => 'level_bart',
        'dateOpen_bart' => 'dateOpen_bart',
        'dateClose_bart' => 'dateClose_bart',
        'dateClosed' => 'dateClosed',
        'repo' => 'repo',
        'evidenceID' => 'evidenceID',
        'evidenceType' => 'evidenceType',
        'evidenceLink' => 'evidenceLink',
        'closureComment' => 'closureComment'
    ];

    protected $requiredFields = [
        'creator',
        'status',
        'descriptive_title_vta',
        'root_prob_vta',
        'resolution_vta',
        'priority_vta',
        'safety_cert_vta'
    ];

    protected static $foreignKeys = [
        'safety_cert_vta' => [
            'table' => 'yesNo',
            'fields' => [ 'yesNoID', 'yesNoName' ]
        ],
        'next_step' => [
            'table' => 'bdNextStep',
            'fields' => [ 'bdNextStepID', 'nextStepName' ]
        ],
        'creator' => [
            'table' => 'bdParties',
            'fields' => [ 'partyID', 'partyName' ]
        ],
        'bic' => [
            'table' => 'bdParties',
            'alias' => 'bic',
            'fields' => [ 'partyID', 'partyName' ]
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
        'agree_vta' => [
            'table' => 'agreeDisagree',
            'fields' => [ 'agreeDisagreeID', 'agreeDisagreeName' ]
        ],
        'evidenceType' => [
            'table' => 'evidenceType',
            'fields' => [ 'eviTypeID', 'eviTypeName' ]
        ],
        'repo' => [
            'table' => 'repo',
            'fields' => [ 'repoID', 'repoName' ]
        ],
        'created_by' => [
            'table' => 'users_enc',
            'alias' => 'cb',
            'fields' => [ 'userid', 'firstname', ' ', 'lastname' ],
            'concat' => true
        ],
        'updated_by' => [
            'table' => 'users_enc',
            'alias' => 'ub',
            'fields' => [ 'userid', 'firstname', ' ', 'lastname' ],
            'concat' => true
        ]
    ];
}