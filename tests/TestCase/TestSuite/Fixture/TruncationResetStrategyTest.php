<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         4.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\TestSuite;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\Fixture\TruncationResetStrategy;
use Cake\TestSuite\TestCase;

/**
 * TruncationStrategy test
 */
class TruncationResetStrategyTest extends TestCase
{
    public $fixtures = ['core.Articles', 'core.Tags', 'core.ArticlesTags'];

    /**
     * Test that beforeTest truncates tables from the previous test
     */
    public function testTeardownSimple(): void
    {
        $articles = TableRegistry::getTableLocator()->get('Articles');
        $articlesTags = TableRegistry::getTableLocator()->get('ArticlesTags');

        $rowCount = $articles->find()->count();
        $this->assertGreaterThan(0, $rowCount);
        $rowCount = $articlesTags->find()->count();
        $this->assertGreaterThan(0, $rowCount);

        $strategy = new TruncationResetStrategy($this->fixtureManager);
        $strategy->teardownTest();

        $rowCount = $articles->find()->count();
        $this->assertEquals(0, $rowCount);
        $rowCount = $articlesTags->find()->count();
        $this->assertEquals(0, $rowCount);
    }
}
