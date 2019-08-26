<?php
declare(strict_types=1);

use SVBX\Deficiency;
use SVBX\Report;
use PHPUnit\Framework\TestCase;

final class ReportTest extends TestCase
{
    protected static $dateFormat = 'Y-m-d';
    public static $fixtureIDs = [];

    public static function setUpBeforeClass(): void
    {
        self::$fixtureIDs = [];

        try {
            $db = new MysqliDb(DB_CREDENTIALS);

            $def = new Deficiency(null, [
                'safetyCert' => 1,
                'systemAffected' => 1,
                'location' => 1,
                'specLoc' => 'test_specLoc',
                'status' => 1,
                'severity' => 3,
                'dueDate' => date(self::$dateFormat),
                'groupToResolve' => 1,
                'requiredBy' => 1,
                'contractID' => 1,
                'identifiedBy' => 'ckb',
                'defType' => 1,
                'description' => 'test_description',
                'dateCreated' => (DateTime::createFromFormat(self::$dateFormat, '2019-01-01'))->format(self::$dateFormat),
                'created_by' => 'test_user'
            ]);
    
            array_push(self::$fixtureIDs, $def->insert());
        } catch (Exception $e) {
            error_log(print_r($e, true));
            throw $e;
        } catch (Error $e) {
            error_log(print_r($e, true));
            throw $e;
        } finally {
            if (!empty($db) && is_a($db, 'MysqliDb')) $db->disconnect();
        }
    }

    public static function tearDownAfterClass(): void
    {
        try {
            $db = new MysqliDb(DB_CREDENTIALS);
    
            foreach (self::$fixtureIDs as $id) {
                $db->where('defID', $id);
                $db->delete('CDL');
            }
        } catch (Exception $e) {
            error_log(print_r($e, true));
            throw $e;
        } catch (Error $e) {
            error_log(print_r($e, true));
            throw $e;
        } finally {
            if (!empty($db) && is_a($db, 'MysqliDb')) $db->disconnect();
            self::$fixtureIDs = [];
        }
    }

    public function testInstantiateReport(): void
    {
        $report = Report::delta();
        $this->assertInstanceOf(Report::class, $report);

        $reportData = $report->getWithHeadings();

        $this->assertEquals($reportData[0][0], 'severity');

        fwrite(STDOUT, print_r($report->getQuery(), true));
        fwrite(STDOUT, print_r($reportData, true));
    }
}