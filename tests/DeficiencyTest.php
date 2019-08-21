<?php
declare(strict_types=1);

use SVBX\Deficiency;
use PHPUnit\Framework\TestCase;

final class DeficiencyTest extends TestCase
{
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
}