<?php
declare(strict_types=1);

use SVBX\DefCollection;
use PHPUnit\Framework\TestCase;

final class DefCollectionTest extends TestCase
{
    public function testCanGetFetchable(): void
    {
        $fetchable = DefCollection::getFetchableAssoc(
            [ 'id', 'safetyCert', 'systemAffected', 'location', 'specLoc' ],
            [ 'safetyCert' => 2, 'id' => 1 ]
        );

        $this->assertIsArray($fetchable);
        $this->assertEquals('CDL', $fetchable['table']);
        $this->assertArrayHasKey('select', $fetchable);
        $this->assertArrayHasKey('join', $fetchable);
        $this->assertArrayHasKey('where', $fetchable);
        $this->assertContains([ 'CDL.safetyCert', 2, '=' ], $fetchable['where']);
        $this->assertContains([ 'CDL.defID', 1, 'LIKE' ], $fetchable['where']);
    }

    public function testGetFetchableIgnoresInvalidFields(): void
    {
        $fetchable = DefCollection::getFetchableAssoc(
            [ 'id', 'status', 'severity', 'dueDate', 'shoobob', 'snod', 'vorpal' ],
            [ 'location' => 1, 'vorpal' => 'snicker snack', 'quiGon' => 'midiclorians' ]
        );
        
        $this->assertContains('CDL.severity', $fetchable['select']);
        $this->assertNotContains('CDL.shoobob', $fetchable['select']);
        $this->assertNotContains('CDL.snod', $fetchable['select']);
        $this->assertNotContains('CDL.vorpal', $fetchable['select']);
        $this->assertContains([ 'CDL.location', 1, '=' ], $fetchable['where']);
        $this->assertNotContains('CDL.vorpal', array_column($fetchable['where'], 0));
        $this->assertNotContains('midiclorians', array_column($fetchable['where'], 1));
    }

    public function testGetFetchableAssocReturnsWellFormedJoinedCols() : void
    {
        $fetchable = DefCollection::getFetchableAssoc(
            [ 'id', 'status', 'locationName location', 'CONCAT(firstname, " ", lastname) updated_by', 'yesNoName safetyCert' ]
        );

        $this->assertContains('location.locationName location', $fetchable['select']);
    }

    public function testArrayWhereReturnsArrayWhere() : void
    {
        $fetchable = DefCollection::getFetchableAssoc(
            [ 'id', 'groupToResolve', 'requiredBy', 'contractID' ],
            [ 'status' => [ 2, 4 ] ]
        );
        
        $this->assertIsArray($fetchable['where'][0][1]);
    }

    public function testCanGetNumericallyIndexedFetchable() : void
    {
        $numeric = DefCollection::getFetchableNum(
            [ 'id', 'identifiedBy', 'defType', 'description' ],
            [ 'severity' => 3 ],
            null,
            [ 'identifiedBy ASC' ]
        );

        $this->assertIsArray($numeric);
    }

    public function testSelectRequiredByAliasReturnsRequiredByName() : void
    {
        $fetchable = DefCollection::getFetchableNum(
            [ 'requiredBy requiredBy' ]
        );

        $this->assertContains('requiredBy.requiredBy requiredBy', $fetchable[1]);
    }

    public function testSelectRequiredByNameAndWhereRequiredByNumDoNotCollide() : void
    {
        $fetchable = DefCollection::getFetchableNum(
            [ 'requiredBy requiredBy' ],
            [ 'requiredBy' => 10 ]
        );

        $this->assertContains([
            'CDL.requiredBy',
            '10',
            '='
        ], $fetchable[3]);
    }

    public function testCanPassRawWhereConditionAsString() : void
    {
        $fetchable = DefCollection::getFetchableNum(
            [ 'requiredBy requiredBy' ],
            [ 'requiredBy < 40' ]
        );

        print_r($fetchable);
        $this->assertContains([
            'CDL.requiredBy',
            '40',
            '<'
        ], $fetchable[3]);
    }
}