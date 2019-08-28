<?php
declare(strict_types=1);

use SVBX\Deficiency;
use SVBX\Report;
use PHPUnit\Framework\TestCase;

final class ReportTest extends TestCase
{
    protected static $dateFormat = 'Y-m-d';
    protected static $severityOrder = ['Minor', 'Major', 'Critical', 'Blocker'];
    protected static $systemOrder = ['Electrical', 'Mechanical', 'SCADA', 'Fire Protection'];
    private static $fixtureIDs = [];
    private static $fixtureData = [
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

    public static function setUpBeforeClass(): void
    {
        self::$fixtureIDs = [];

        try {
            $db = new MysqliDb(DB_CREDENTIALS);

            foreach (self::$fixtureData as $defData) {
                $def = new Deficiency(null, $defData + [
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
                ]);

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

    public function testDefaultSeverityReportContainsExpectedData(): void
    {
        $report = Report::delta();
        $this->assertInstanceOf(Report::class, $report);

        $reportData = $report->getWithHeadings();
        
        $endDate = date(static::$dateFormat);
        $startDate = DateTime
        ::createFromFormat(static::$dateFormat, $endDate)
        ->sub(new DateInterval('P7D'));
        $expected = [ 'severity', $startDate->format(static::$dateFormat) , $endDate ];
        $headings = array_shift($reportData);

        $this->assertSame($headings, $expected);

        $this->assertTrue($this->sortByArrayOrder($reportData, static::$severityOrder));

        $expected = [
            [ 'fieldName' => 'Minor', 'fromDate' => 1, 'toDate' => 1 ],
            [ 'fieldName' => 'Major', 'fromDate' => 1, 'toDate' => 1 ],
            [ 'fieldName' => 'Critical', 'fromDate' => 1, 'toDate' => 1 ],
            [ 'fieldName' => 'Blocker', 'fromDate' => 1, 'toDate' => 1 ],
        ];
        $this->assertSame($expected, $reportData);
    }

    public function testSeverityReportWithEarlierStartDateContainsExpectedData(): void
    {
        $startDateStr = '2019-07-01';

        $report = Report::delta('severity', $startDateStr)->getWithHeadings();

        $headings = array_shift($report);
        $expected = [ 'severity', $startDateStr, date('Y-m-d')];
        $this->assertSame($expected, $headings);

        $this->assertTrue($this->sortByArrayOrder($report, static::$severityOrder));

        $expected = [
            [ 'fieldName' => 'Minor', 'fromDate' => 1, 'toDate' => 1 ],
            [ 'fieldName' => 'Major', 'fromDate' => 1, 'toDate' => 1 ],
            [ 'fieldName' => 'Critical', 'fromDate' => 2, 'toDate' => 1 ],
            [ 'fieldName' => 'Blocker', 'fromDate' => 2, 'toDate' => 1 ],
        ];
        $this->assertSame($expected, $report);
    }

    public function testSeverityReportWithEarlierStartAndEndDatesContainsExpectedData(): void
    {
        $startDateStr = '2019-05-01';
        $endDateStr = '2019-07-01';

        $report = Report::delta('severity', $startDateStr, $endDateStr)->getWithHeadings();

        $headings = array_shift($report);
        $expected = [ 'severity', $startDateStr, $endDateStr ];
        $this->assertSame($expected, $headings);

        $this->assertTrue($this->sortByArrayOrder($report, static::$severityOrder));

        $expected = [
            [ 'fieldName' => 'Minor', 'fromDate' => 1, 'toDate' => 1 ],
            [ 'fieldName' => 'Major', 'fromDate' => 2, 'toDate' => 1 ],
            [ 'fieldName' => 'Critical', 'fromDate' => 2, 'toDate' => 2 ],
            [ 'fieldName' => 'Blocker', 'fromDate' => 0, 'toDate' => 2 ],
        ];
        $this->assertSame($expected, $report);
    }

    public function testDefaultSeverityReportDatesWithMilestoneContainsExpectedData(): void
    {
        $milestone = 4;
        $report = Report::delta('severity', null, null, $milestone)->getWithHeadings();

        $headings = array_shift($report);
        $endDateStr = date('Y-m-d');
        $startDateStr = DateTime
        ::createFromFormat(static::$dateFormat, $endDateStr)
        ->sub(new DateInterval('P7D'))
        ->format(static::$dateFormat);
        $expect = [ 'severity', $startDateStr, $endDateStr, 'milestone' ];

        $this->assertSame($expect, $headings);

        $this->assertTrue($this->sortByArrayOrder($report, static::$severityOrder));

        $expected = [
            [ 'fieldName' => 'Major', 'fromDate' => 1, 'toDate' => 1, 'milestone' => $milestone ],
            [ 'fieldName' => 'Critical', 'fromDate' => 1, 'toDate' => 1, 'milestone' => $milestone ],
        ];
        $this->assertSame($expected, $report);
    }

    public function testSystemReportWithDefaultDatesReturnsExpectedData(): void
    {
        $report = Report::delta('system')->getWithHeadings();
        $endDateStr = date('Y-m-d');
        $startDateStr = DateTime
        ::createFromFormat(static::$dateFormat, $endDateStr)
        ->sub(new DateInterval('P7D'))
        ->format(static::$dateFormat);

        $headings = array_shift($report);
        $expect = [ 'system', $startDateStr, $endDateStr ];
        $this->assertSame($expect, $headings);

        $this->assertTrue($this->sortByArrayOrder($report, static::$systemOrder));

        $expect = [
            [ 'fieldName' => 'Electrical', 'fromDate' => 1, 'toDate' => 1 ],
            [ 'fieldName' => 'Mechanical', 'fromDate' => 1, 'toDate' => 1 ],
            [ 'fieldName' => 'SCADA', 'fromDate' => 1, 'toDate' => 1 ],
            [ 'fieldName' => 'Fire Protection', 'fromDate' => 1, 'toDate' => 1 ]
        ];
        $this->assertSame($expect, $report);
    }

    public function testSystemReportWithEarlierStartDateReturnsExpectedData(): void
    {
        $startDateStr = '2019-06-30';

        $report = Report::delta('system', $startDateStr)->get();
        $this->assertTrue($this->sortByArrayOrder($report, static::$systemOrder));

        $expect = [
            [ 'fieldName' => 'Electrical', 'fromDate' => 1, 'toDate' => 1 ],
            [ 'fieldName' => 'Mechanical', 'fromDate' => 1, 'toDate' => 1 ],
            [ 'fieldName' => 'SCADA', 'fromDate' => 2, 'toDate' => 1 ],
            [ 'fieldName' => 'Fire Protection', 'fromDate' => 0, 'toDate' => 1 ]
        ];

        $this->assertSame($expect, $report);
    }

    public function testSystemReportWithEarlierStartAndEndDateReturnsExpectedData(): void
    {
        $startDateStr = '2019-04-01';
        $endDateStr = '2019-04-30';

        $report = Report::delta('system', $startDateStr, $endDateStr)->get();
        $this->assertTrue($this->sortByArrayOrder($report, static::$systemOrder));

        $expect = [
            [ 'fieldName' => 'Electrical', 'fromDate' => 1, 'toDate' => 1 ],
            [ 'fieldName' => 'Mechanical', 'fromDate' => 2, 'toDate' => 2 ],
        ];

        $this->assertSame($expect, $report);
    }

    public function testDefaultSystemReportWithMilestoneReturnsExpectedData(): void
    {
        $milestone = 5;
        $report = Report::delta('system', null, null, $milestone)->get();

        $this->assertTrue($this->sortByArrayOrder($report, static::$systemOrder));

        $expect = [
            [ 'fieldName' => 'Mechanical', 'fromDate' => 1, 'toDate' => 1, 'milestone' => $milestone ],
            [ 'fieldName' => 'SCADA', 'fromDate' => 1, 'toDate' => 1, 'milestone' => $milestone ]
        ];

        $this->assertSame($expect, $report);
    }

    public function testSystemReportWithEarlierDateRangeAndMilestoneReturnsExpectedData(): void
    {
        $startDateStr = '2019-04-01';
        $endDateStr = '2019-06-01';
        $milestone = 5;

        $report = Report::delta('system', $startDateStr, $endDateStr, $milestone)->get();
        $this->assertTrue($this->sortByArrayOrder($report, static::$systemOrder));

        $expect = [
            [ 'fieldName' => 'Mechanical', 'fromDate' => 2, 'toDate' => 1, 'milestone' => $milestone ],
            [ 'fieldName' => 'SCADA', 'fromDate' => 0, 'toDate' => 2, 'milestone' => $milestone ],
        ];

        $this->assertSame($expect, $report);
    }

    private function sortByArrayOrder(&$target, $order) {
        return usort($target, function ($a, $b) use ($order) {
            $aIndex = array_search($a['fieldName'], $order);
            $bIndex = array_search($b['fieldName'], $order);
            if ($aIndex === false || $bIndex === false)
                throw new UnexpectedValueException(sprintf(
                    'Unexpected value %s found in $reportData',
                    !$aIndex ? $a['fieldName'] : $b['fieldName']
                ));
            return $aIndex - $bIndex;
        });
    }
}