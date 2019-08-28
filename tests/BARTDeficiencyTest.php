<?php
declare(strict_types=1);

use SVBX\BARTDeficiency;
use PHPUnit\Framework\TestCase;

final class BARTDeficiencyTest extends TestCase
{
    protected $newDefID;
    protected static $dateFormat = 'Y-m-d';

    protected function setUp(): void
    {
        $this->newDefID = null;
    }

    public function testCanCreateWithRequiredProps() {
        $this->assertInstanceOF(
            BARTDeficiency::class,
            new BARTDeficiency(null, [
                'creator' => 1,
                'status' => 1,
                'descriptive_title_vta' => 'test description',
                'root_prob_vta' => 'test root problem',
                'resolution_vta' => 'test resolution vta',
                'priority_vta' => 1,
                'safety_cert_vta' => 1
            ])
        );
    }

    public function testCanInsertWithStatusOpen() {
        $this->newDefID = (new BARTDeficiency(null, [
            'creator' => 1,
            'status' => 1,
            'descriptive_title_vta' => 'test description',
            'root_prob_vta' => 'test root problem',
            'resolution_vta' => 'test resolution vta',
            'priority_vta' => 1,
            'safety_cert_vta' => 1,
            'created_by' => 1
        ]))->insert();

        $this->assertNotEquals(intval($this->newDefID), 0);

        $newDef = new BARTDeficiency($this->newDefID);
        $this->assertEqualsIgnoringCase(
            $newDef->getReadable(['status'])['status'],
            'open'
        );
    }

    public function testCanInsertWithStatusClosed() {
        $this->newDefID = (new BARTDeficiency(null, [
            'creator' => 1,
            'status' => 2,
            'descriptive_title_vta' => 'test description',
            'root_prob_vta' => 'test root problem',
            'resolution_vta' => 'test resolution vta',
            'priority_vta' => 1,
            'safety_cert_vta' => 1,
            'created_by' => 1,
            'repo' => 1,
            'evidenceID' => 'test-evidence_ID',
            'evidenceType' => 1
        ]))->insert();

        $this->assertNotEquals(intval($this->newDefID), 0);

        $newDef = new BARTDeficiency($this->newDefID);
        $this->assertEqualsIgnoringCase(
            $newDef->getReadable(['status'])['status'],
            'closed'
        );

        $dateClosed = $newDef->get('dateClosed');
        $d = DateTime::createFromFormat(static::$dateFormat, $dateClosed);
        $this->assertEquals($dateClosed, $d->format(static::$dateFormat));
    }

    public function testCanUpdateStatusClosed() {
        $this->newDefID = (new BARTDeficiency(null, [
            'creator' => 1,
            'status' => 1,
            'descriptive_title_vta' => 'test description',
            'root_prob_vta' => 'test root problem',
            'resolution_vta' => 'test resolution vta',
            'priority_vta' => 1,
            'safety_cert_vta' => 1,
            'created_by' => 1,
            'repo' => 1,
            'evidenceID' => 'test-evidence_ID',
            'evidenceType' => 1
        ]))->insert();

        $newDef = new BARTDeficiency($this->newDefID);
        $newDef->set('status', 2);

        $success = $newDef->update();
        $this->assertEquals($success, 1);

        $dateClosed = $newDef->get('dateClosed');
        $d = DateTime::createFromFormat(static::$dateFormat, $dateClosed);
        $this->assertEquals($dateClosed, $d->format(static::$dateFormat));
    }
}