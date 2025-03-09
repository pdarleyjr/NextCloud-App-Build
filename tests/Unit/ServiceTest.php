<?php

/**
 * @copyright Copyright (c) 2025 pdarleyjr <pdarleyjr@gmail.com>
 *
 * @license MIT
 */

namespace PdarleyJr\NextcloudAppBuild\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PdarleyJr\NextcloudAppBuild\Service;

/**
 * Class ServiceTest
 *
 * @package PdarleyJr\NextcloudAppBuild\Tests\Unit
 */
class ServiceTest extends TestCase
{
    /**
     * Test getAppName method
     */
    public function testGetAppName(): void
    {
        $service = new Service();
        $this->assertEquals('NextCloud App Build', $service->getAppName());
    }

    /**
     * Test getAppVersion method
     */
    public function testGetAppVersion(): void
    {
        $service = new Service();
        $this->assertEquals('0.1.0', $service->getAppVersion());
    }
}