<?php # -*- coding: utf-8 -*-
/*
 * This file is part of the MetaboxOrchestra package.
 *
 * (c) Giuseppe Mazzapica
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MetaboxOrchestra\Tests;

use MetaboxOrchestra\AdminNotices;
use Brain\Monkey\Functions;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @package MetaboxOrchestra
 * @license http://opensource.org/licenses/MIT MIT
 */
class AdminNoticesTest extends TestCase {

	public function testAddedNoticesAreRecorded() {

		Functions\when( 'get_current_user_id' )->justReturn( 1 );
		Functions\when( 'get_current_screen' )->justReturn( (object) [ 'id' => 'screen_test' ] );

		$notices = new AdminNotices();

		$notices->add( 'This is an error', 'Error!', AdminNotices::ERROR );
		$notices->add( 'This is a success', 'Success!', AdminNotices::SUCCESS );

		Functions\expect( 'update_user_option' )
			->once()
			->with( 1, AdminNotices::OPTION_NAME, \Mockery::type( 'array' ) )
			->andReturnUsing( function ( $id, $option, $messages ) {
				static::assertArrayHasKey( 'screen_test', $messages );
				static::assertArrayHasKey( AdminNotices::ERROR, $messages[ 'screen_test' ] );
				static::assertArrayHasKey( AdminNotices::SUCCESS, $messages[ 'screen_test' ] );
				static::assertContains( 'This is an error', $messages[ 'test' ][ AdminNotices::ERROR ] );
				static::assertContains( 'Error!', $messages[ 'test' ][ AdminNotices::ERROR ] );
				static::assertContains( 'This is a success', $messages[ 'test' ][ AdminNotices::SUCCESS ] );
				static::assertContains( 'Success!', $messages[ 'test' ][ AdminNotices::SUCCESS ] );
			} );

		$notices->record();
	}

	public function testAddedNoticesArePrinted() {

		Functions\when( 'get_current_user_id' )->justReturn( 123 );
		Functions\when( 'get_current_screen' )->justReturn( (object) [ 'id' => 'screen_test' ] );

		$notices = new AdminNotices();

		$notices->add( 'This is an error', 'Error!', AdminNotices::ERROR );
		$notices->add( 'This is a success', 'Success!', AdminNotices::SUCCESS );

		$to_print = null;

		Functions\expect( 'update_user_option' )
			->andReturnUsing( function ( $id, $option, $messages ) use ( &$to_print ) {
				$to_print = $messages;

				return TRUE;
			} );

		$notices->record();

		Functions\expect( 'doing_action' )->once()->with( 'admin_notices' )->andReturn( TRUE );
		Functions\expect( 'get_user_option' )->once()->with( AdminNotices::OPTION_NAME, 123 )->andReturn( $to_print );
		Functions\expect( 'delete_user_option' )->once()->with( 123, AdminNotices::OPTION_NAME, $to_print );

		ob_start();
		$notices->do_notices();
		$notices = ob_get_clean();

		static::assertContains( 'notice-' . AdminNotices::ERROR, $notices );
		static::assertContains( 'notice-' . AdminNotices::SUCCESS, $notices );
		static::assertContains( 'Error!', $notices );
		static::assertContains( 'Success!', $notices );
		static::assertContains( 'This is an error', $notices );
		static::assertContains( 'This is a success', $notices );
	}
}