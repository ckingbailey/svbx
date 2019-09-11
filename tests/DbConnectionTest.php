<?php
declare(strict_types=1);

use SVBX\DbConnection;
use SVBX\DefCollection;
use PHPUnit\Framework\TestCase;

final class DbConnectionTest extends TestCase
{
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
