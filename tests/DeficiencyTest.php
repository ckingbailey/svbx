<?php
declare(strict_types=1);
error_reporting(E_ALL & ~E_NOTICE);

use SVBX\Deficiency;
use PHPUnit\Framework\TestCase;

final class DeficiencyTest extends TestCase
{
    private $newDefIDs = [];
    private $db;

    protected function setUp(): void
    {
        $this->db = new MysqliDb(DB_CREDENTIALS);
        $this->db->startTransaction();
    }

    protected function tearDown(): void
    {
        $this->db->rollback();
        $this->db->disconnect();
    }

    public function testCanCreateNewWithRequiredProps(): void
    {
        $this->assertInstanceOf(
            Deficiency::class,
            new Deficiency(false, [
                'safetyCert' => 1,
                'systemAffected' => 1,
                'location' => 1,
                'specLoc' => 'test_specLoc',
                'status' => 1,
                'severity' => 1,
                'dueDate' => date('Y-m-d'),
                'groupToResolve' => 1,
                'requiredBy' => 1,
                'contractID' => 1,
                'identifiedBy' => 'ckb',
                'defType' => 1,
                'description' => 'test_description'
            ])
        );
    }

    public function testCanCreateNewWithStatusClosed(): void
    {
        $this->assertInstanceOf(
            Deficiency::class,
            new Deficiency(false, [
                'safetyCert' => 1,
                'systemAffected' => 1,
                'location' => 1,
                'specLoc' => 'test_specLoc',
                'status' => 4,
                'severity' => 1,
                'dueDate' => date('Y-m-d'),
                'groupToResolve' => 1,
                'requiredBy' => 1,
                'contractID' => 1,
                'identifiedBy' => 'ckb',
                'defType' => 1,
                'description' => 'test_description'
            ])
        );
    }

    public function testCanInsertNewWithStatusOpen(): void
    {
        array_push(
            $this->newDefIDs,
            $newDefID = (new Deficiency(false, [
                'safetyCert' => 1,
                'systemAffected' => 1,
                'location' => 1,
                'specLoc' => 'test_specLoc',
                'status' => 1,
                'severity' => 1,
                'dueDate' => date('Y-m-d'),
                'groupToResolve' => 1,
                'requiredBy' => 1,
                'contractID' => 1,
                'identifiedBy' => 'ckb',
                'defType' => 1,
                'description' => 'test_description',
                'created_by' => 'demo', // required creation info
            ]))->insert()
        );
        $this->assertNotEquals(intval($newDefID), 0);

        $newDef = new Deficiency($newDefID);
        $this->assertEqualsIgnoringCase(
            $newDef->getReadable([ 'status' ])['status'],
            'open'
        );
        
        $format = 'Y-m-d';
        $dateCreated = $newDef->get('dateCreated');
        $d = DateTime::createFromFormat($format, $dateCreated);
        $this->assertInstanceOf('DateTime', $d);
        $this->assertEquals($dateCreated, $d->format($format));
    }

    public function testInsertWithStatusClosedGetsTimestamp(): void
    {
        array_push(
            $this->newDefIDs,
            $newDefID = (new Deficiency(false, [
                'safetyCert' => 1,
                'systemAffected' => 1,
                'location' => 1,
                'specLoc' => 'test_specLoc',
                'status' => 4,
                'severity' => 1,
                'dueDate' => date('Y-m-d'),
                'groupToResolve' => 1,
                'requiredBy' => 1,
                'contractID' => 1,
                'identifiedBy' => 'ckb',
                'defType' => 1,
                'description' => 'test_description',
                'created_by' => 'demo', // required creation info
                'repo' => 1, // required closure info, fk
                'evidenceType' => 1, // required closure info, fk
                'evidenceID' => 'aaa000-bbb999' // required closure info
            ]))->insert()
        );

        $this->assertNotEquals(intval($newDefID), 0);
        
        $format = 'Y-m-d';
        $dateClosed = (new Deficiency($newDefID))->get('dateClosed');
        $d = DateTime::createFromFormat($format, $dateClosed);

        $this->assertInstanceOf('DateTime', $d);
        $this->assertEquals($d->format($format), $dateClosed);
    }
}