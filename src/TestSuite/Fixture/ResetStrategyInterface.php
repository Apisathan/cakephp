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
namespace Cake\TestSuite\Fixture;

/**
 * Interface for fixture state management.
 *
 * This interface exposes hooks that run before and
 * after tests. This allows test database state to be 'prepared'
 * and 'cleaned up'.
 */
interface ResetStrategyInterface
{
    /**
     * Before test hook
     *
     * Fired before each test is started.
     *
     * @return void
     */
    public function setupTest(): void;

    /**
     * After test hook
     *
     * Fired after each test is complete.
     *
     * @return void
     */
    public function teardownTest(): void;
}
