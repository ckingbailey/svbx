<?php
declare(strict_types=1);

use SVBX\Deficiency;
use SVBX\Report;
use PHPUnit\Framework\TestCase;

final class ReportTest extends TestCase
{
    protected static $dateFormat = 'Y-m-d';
    private static $fixtureIDs = [];
    private static $fixtureData = [
        [
            'dateCreated' => '2019-01-01',
            'status' => 1,
            'severity' => 3,
            'groupToResolve' => 1
        ],
        [
            'dateCreated' => '2019-01-01',
            'status' => 2,
            'severity' => 3,
            'groupToResolve' => 1,
            'dateClosed' => '2019-03-31',
            'repo' => 1,
            'evidenceID' => 'test_evidenceID',
            'evidenceType' => 1
        ],
        [
            'dateCreated' => '2019-03-01',
            'status' => 1,
            'severity' => 2,
            'groupToResolve' => 2
        ],
        [
            'dateCreated' => '2019-03-01',
            'status' => 2,
            'severity' => 2,
            'groupToResolve' => 2,
            'dateClosed' => '2019-05-31',
            'repo' => 1,
            'evidenceID' => 'test_evidenceID',
            'evidenceType' => 1
        ],
        [
            'dateCreated' => '2019-05-01',
            'status' => 1,
            'severity' => 1,
            'groupToResolve' => 3
        ],
        [
            'dateCreated' => '2019-05-01',
            'status' => 2,
            'severity' => 1,
            'groupToResolve' => 3,
            'dateClosed' => '2019-07-31',
            'repo' => 1,
            'evidenceID' => 'test_evidenceID',
            'evidenceType' => 1
        ],
        [
            'dateCreated' => '2019-07-01',
            'status' => 1,
            'severity' => 4,
            'groupToResolve' => 4
        ],
        [
            'dateCreated' => '2019-07-01',
            'status' => 2,
            'severity' => 4,
            'groupToResolve' => 4,
            'dateClosed' => '2019-07-31',
            'repo' => 1,
            'evidenceID' => 'test_evidenceID',
            'evidenceType' => 1
        ],
    ];

    public static function setUpBeforeClass(): void
    {
        self::$fixtureIDs = [];

        try {
            $db = new MysqliDb(DB_CREDENTIALS);

            foreach (self::$fixtureData as $defData) {
                $def = new Deficiency(null, [
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
                ] + $defData);

                array_push(self::$fixtureIDs, $def->insert());
            }
    
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
        
        $endDate = date(static::$dateFormat);
        $startDate = DateTime
        ::createFromFormat(static::$dateFormat, $endDate)
        ->sub(new DateInterval('P7D'));
        $expected = [
            'severity', $startDate->format(static::$dateFormat) , $endDate
        ];
        $headings = array_shift($reportData);

        $this->assertSame($headings, $expected);

        $severityOrder = ['Minor', 'Major', 'Critical', 'Blocker'];
        $this->assertTrue(usort($reportData, function($a, $b) use ($severityOrder) {
            $aIndex = array_search($a['fieldName'], $severityOrder);
            $bIndex = array_search($b['fieldName'], $severityOrder);
            if ($aIndex === false)
                throw new UnexpectedValueException("Unexpected value {$a['fieldName']} found in \$reportData");
            if ($bIndex === false)
                throw new UnexpectedValueException("Unexpected value {$a['fieldName']} found in \$reportData");
            return $aIndex - $bIndex;
        }));

        $this->assertSame($reportData, [
            [ 'fieldName' => 'Minor', 'fromDate' => 1, 'toDate' => 1 ],
            [ 'fieldName' => 'Major', 'fromDate' => 1, 'toDate' => 1 ],
            [ 'fieldName' => 'Critical', 'fromDate' => 1, 'toDate' => 1 ],
            [ 'fieldName' => 'Blocker', 'fromDate' => 1, 'toDate' => 1 ],
        ]);

        // fwrite(STDOUT, print_r($report->getQuery(), true));
    }
}