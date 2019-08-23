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

    public function testCanInsertNewWithRequiredProps() {
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
}