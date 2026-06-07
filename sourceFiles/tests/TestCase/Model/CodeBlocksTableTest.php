<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model;

use App\Model\Table\CodeBlocksTable;
use Cake\TestSuite\TestCase;

class CodeBlocksTableTest extends TestCase
{
    protected CodeBlocksTable $CodeBlocks;

    public function setUp(): void
    {
        parent::setUp();
        $this->CodeBlocks = $this->getTableLocator()->get('CodeBlocks');
    }

    public function tearDown(): void
    {
        unset($this->CodeBlocks);
        parent::tearDown();
    }

    public function testDatabaseQueryRuns(): void
    {
        $result = $this->CodeBlocks->find()->limit(1)->all();
        $this->assertIsIterable($result);
    }
}

