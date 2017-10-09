<?php # -*- coding: utf-8 -*-

namespace MetaboxOrchestra\Tests;

use MetaboxOrchestra\AdminNotices;
use MetaboxOrchestra\BoxAction;
use MetaboxOrchestra\NoopBoxAction;

class NoopBoxActionTest extends TestCase {

	public function testBasic() {

		$testee = new NoopBoxAction();
		static::assertInstanceOf( BoxAction::class, $testee );

	}

	public function testSave() {

		/** @var AdminNotices $stub */
		$stub = \Mockery::mock( AdminNotices::class );
		static::assertFalse( ( new NoopBoxAction() )->save( $stub ) );
	}
}