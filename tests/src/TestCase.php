<?php declare( strict_types=1 ); # -*- coding: utf-8 -*-

namespace MetaboxOrchestra\Tests;

use Brain\Monkey;

/**
 * @package MetaboxOrchestra
 */
class TestCase extends \PHPUnit\Framework\TestCase {

	protected function setUp(): void {

		parent::setUp();
		Monkey\setUp();

	}

	protected function tearDown(): void {

		Monkey\tearDown();
		parent::tearDown();
	}
}
