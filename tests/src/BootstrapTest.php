<?php # -*- coding: utf-8 -*-

namespace MetaboxOrchestra\Tests;

use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use MetaboxOrchestra\Bootstrap;

class BootstrapTest extends TestCase {

	public function testBasic() {

		$testee = new Bootstrap();
		static::assertInstanceOf( Bootstrap::class, $testee );
	}

	public function testBootstrap() {

		Functions\when( 'is_admin' )->justReturn( TRUE );
		Actions\expectAdded( 'admin_menu' );

		static::assertTrue( Bootstrap::bootstrap() );
		static::assertFalse( Bootstrap::bootstrap() );
	}

	public function testBootstrapInFrontend() {

		Functions\when( 'is_admin' )->justReturn( FALSE );

		static::assertFalse( Bootstrap::bootstrap() );
	}
}