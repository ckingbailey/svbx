<?php
declare(strict_types=1);

use SVBX\Deficiency;
use SVBX\BARTDeficiency;
use PHPUnit\Framework\TestCase;

final class DeficiencyTest extends TestCase
{
    protected $newDefID;
    protected $bartDefID;
    protected static $dateFormat = 'Y-m-d';

    protected function setUp(): void
    {
        $this->newDefID = null;
    }

    protected function tearDown(): void
    {
        try {
            $db = new MysqliDb(DB_CREDENTIALS);

            $db->where('defID', $this->newDefID);
            $db->delete('CDL');
        } catch (Exception $e) {
            error_log(print_r($e, true));
            throw $e;
        } catch (Error $e) {
            error_log(print_r($e, true));
            throw $e;
        } finally {
            if (!empty($link) && is_a($link, 'MysqliDb')) $db->disconnect();
        }
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
                'dueDate' => date(static::$dateFormat),
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
        $this->newDefID = (new Deficiency(false, [
            'safetyCert' => 1,
            'systemAffected' => 1,
            'location' => 1,
            'specLoc' => 'test_specLoc',
            'status' => 1,
            'severity' => 1,
            'dueDate' => date(static::$dateFormat),
            'groupToResolve' => 1,
            'requiredBy' => 1,
            'contractID' => 1,
            'identifiedBy' => 'ckb',
            'defType' => 1,
            'description' => 'test_description',
            'created_by' => 'test_user', // required creation info
        ]))->insert();
        
        $this->assertNotEquals(intval($this->newDefID), 0);

        $newDef = new Deficiency($this->newDefID);
        $this->assertEqualsIgnoringCase(
            $newDef->getReadable([ 'status' ])['status'],
            'open'
        );
        
        $dateCreated = $newDef->get('dateCreated');
        $d = DateTime::createFromFormat(static::$dateFormat, $dateCreated);
        $this->assertInstanceOf('DateTime', $d);
        $this->assertEquals($dateCreated, $d->format(static::$dateFormat));
    }

    public function testInsertWithStatusClosedGetsTimestamp(): void
    {
        $this->newDefID = (new Deficiency(false, [
            'safetyCert' => 1,
            'systemAffected' => 1,
            'location' => 1,
            'specLoc' => 'test_specLoc',
            'status' => 4, // this is the new status that has been added to prod. Potentially difficult to test
            'severity' => 1,
            'dueDate' => date(static::$dateFormat),
            'groupToResolve' => 1,
            'requiredBy' => 1,
            'contractID' => 1,
            'identifiedBy' => 'ckb',
            'defType' => 1,
            'description' => 'test_description',
            'created_by' => 'test_user', // required creation info
            'repo' => 1, // required closure info, fk
            'evidenceType' => 1, // required closure info, fk
            'evidenceID' => 'aaa000-bbb999' // required closure info
        ]))->insert();

        $this->assertNotEquals(intval($this->newDefID), 0);
        
        $dateClosed = (new Deficiency($this->newDefID))->get('dateClosed');
        $d = DateTime::createFromFormat(static::$dateFormat, $dateClosed);

        $this->assertInstanceOf('DateTime', $d);
        $this->assertEquals($d->format(static::$dateFormat), $dateClosed);
    }

    public function testUpdateWithStatusClosedGetsTimestamp(): void
    {
        $this->newDefID = (new Deficiency(false, [
            'safetyCert' => 1,
            'systemAffected' => 1,
            'location' => 1,
            'specLoc' => 'test_specLoc',
            'status' => 1,
            'severity' => 1,
            'dueDate' => date(static::$dateFormat),
            'groupToResolve' => 1,
            'requiredBy' => 1,
            'contractID' => 1,
            'identifiedBy' => 'ckb',
            'defType' => 1,
            'description' => 'test_description',
            'created_by' => 'test_user', // required creation info
        ]))->insert();

        $newDef = new Deficiency($this->newDefID);
        $newDef->set('status', 2);
        $newDef->set('repo', 1);
        $newDef->set('evidenceType', 1);
        $newDef->set('evidenceID', 'test_evidence-link');

        $success = $newDef->update();
        $this->assertEquals($success, 1);

        $dateClosed = $newDef->get('dateClosed');
        $d = DateTime::createFromFormat(static::$dateFormat, $dateClosed);
        $this->assertInstanceOf('DateTime', $d);
        $this->assertEquals($d->format(static::$dateFormat), $dateClosed);
    }

    public function testCanInsertWithBartId(): void
    {
        $this->newDefID = (new Deficiency(false, [
            'safetyCert' => 1,
            'systemAffected' => 1,
            'location' => 1,
            'specLoc' => 'test_specLoc',
            'status' => 1,
            'severity' => 1,
            'dueDate' => date(static::$dateFormat),
            'groupToResolve' => 1,
            'requiredBy' => 1,
            'contractID' => 1,
            'identifiedBy' => 'ckb',
            'defType' => 1,
            'description' => 'test_description',
            'bartDefID' => 5555,
            'created_by' => 'test_user', // required creation info
        ]))->insert();

        $this->assertNotEquals(intval($this->newDefID), 0);
    }

    public function testEmptyBartIdDoesNotInsertZero(): void
    {
        $this->newDefID = (new Deficiency(null, [
            'safetyCert' => 1,
            'systemAffected' => 1,
            'location' => 1,
            'specLoc' => 'test_specLoc',
            'status' => 1,
            'severity' => 1,
            'dueDate' => date(static::$dateFormat),
            'groupToResolve' => 1,
            'requiredBy' => 1,
            'contractID' => 1,
            'identifiedBy' => 'ckb',
            'defType' => 1,
            'description' => 'test_description',
            'bartDefID' => null,
            'created_by' => 'test_user', // required creation info
        ]))->insert();

        $newDef = new Deficiency($this->newDefID);
        $this->assertNotEquals($newDef->get('bartDefID'), 0);
        $this->assertEquals($newDef->get('bartDefID'), null);
    }
}