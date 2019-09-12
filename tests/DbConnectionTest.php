<?php
declare(strict_types=1);

use SVBX\DbConnection;
use SVBX\DefCollection;
use SVBX\Deficiency;
use MysqliDb;
use PHPUnit\Framework\TestCase;

final class DbConnectionTest extends TestCase
{
    private static $dateFormat = 'Y-m-d';

    private static $_staticProvider = [
        [
            'dateCreated' => '2019-01-01',
            'status' => 1,
            'severity' => 3,
            'groupToResolve' => 1,
            'requiredBy' => 6
        ],
        [
            'dateCreated' => '2019-01-01',
            'status' => 2,
            'severity' => 3,
            'groupToResolve' => 1,
            'dateClosed' => '2019-03-31',
            'repo' => 1,
            'evidenceID' => 'test_evidenceID',
            'evidenceType' => 1,
            'requiredBy' => 5
        ],
        [
            'dateCreated' => '2019-03-01',
            'status' => 1,
            'severity' => 2,
            'groupToResolve' => 2,
            'requiredBy' => 4
        ],
        [
            'dateCreated' => '2019-03-01',
            'status' => 2,
            'severity' => 2,
            'groupToResolve' => 2,
            'dateClosed' => '2019-05-31',
            'repo' => 1,
            'evidenceID' => 'test_evidenceID',
            'evidenceType' => 1,
            'requiredBy' => 3
        ],
        [
            'dateCreated' => '2019-05-01',
            'status' => 1,
            'severity' => 1,
            'groupToResolve' => 3,
            'requiredBy' => 2
        ],
        [
            'dateCreated' => '2019-05-01',
            'status' => 2,
            'severity' => 1,
            'groupToResolve' => 3,
            'dateClosed' => '2019-07-31',
            'repo' => 1,
            'evidenceID' => 'test_evidenceID',
            'evidenceType' => 1,
            'requiredBy' => 1
        ],
        [
            'dateCreated' => '2019-07-01',
            'status' => 1,
            'severity' => 4,
            'groupToResolve' => 4,
            'requiredBy' => 6,
            'requiredBy' => 6
        ],
        [
            'dateCreated' => '2019-07-01',
            'status' => 2,
            'severity' => 4,
            'groupToResolve' => 4,
            'dateClosed' => '2019-07-31',
            'repo' => 1,
            'evidenceID' => 'test_evidenceID',
            'evidenceType' => 1,
            'requiredBy' => 5
        ],
    ];

    public static function setUpBeforeClass() : void
    {
        foreach (self::$_staticProvider as $defData) {
            (new Deficiency(false, $defData + [
                'safetyCert' => 1,
                'systemAffected' => 1,
                'location' => 1,
                'specLoc' => 'test_specLoc',
                'dueDate' => date(self::$dateFormat),
                'requiredBy' => 1,
                'contractID' => 1,
                'identifiedBy' => 'ckb',
                'defType' => 1,
                'description' => 'test_description',
                'created_by' => 'test_user'
            ]))->insert();
        }
    }

    public static function tearDownAfterClass() : void
    {
        try {
            $db = new MysqliDb(DB_CREDENTIALS);

            $db->delete('CDL');
            $db->query('ALTER TABLE CDL AUTO_INCREMENT = 1');
        } catch (Exception | Error $e) {
            print_r($e);
            throw $e;
        } finally {
            if (!empty($db) && is_a($db, 'MysqliDb')) $db->disconnect();
        }
    }

    public function testCanConstruct() : void
    {
        $db = new DbConnection(DB_CREDENTIALS);
        $this->assertInstanceOf(DbConnection::class, $db);
    }

    public function testLazyGetReturnsArrayResults() : void
    {
        $db = new DbConnection(DB_CREDENTIALS);
        $res = $db->lazyGet(...DefCollection::getFetchableNum(
            [ 'id', 'status', 'identifiedBy' ],
            [ 'status' => [ 1, 5 ] ]
        ));

        $this->assertIsArray($res);
    }
}
