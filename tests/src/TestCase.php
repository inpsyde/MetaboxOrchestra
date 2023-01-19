<?php declare( strict_types=1 ); # -*- coding: utf-8 -*-
/*
 * This file is part of the metabox-orchestra package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MetaboxOrchestra\Tests;

use Brain\Monkey;

/**
 * @package MetaboxOrchestra
 * @license http://opensource.org/licenses/MIT MIT
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
