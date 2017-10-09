<?php declare( strict_types=1 ); # -*- coding: utf-8 -*-
/*
 * This file is part of the metabox-orchestra package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MetaboxOrchestra;

/**
 * @package metabox-orchestra
 * @license http://opensource.org/licenses/MIT MIT
 */
class Bootstrap {

	/**
	 * @var bool
	 */
	private static $done = FALSE;

	/**
	 * Launch all the bootstrap tasks, only on backend.
	 *
	 * Ensure to run once per request.
	 *
	 * @return bool
	 */
	public static function bootstrap() {

		if ( self::$done || ! is_admin() ) {
			return FALSE;
		}

		self::$done = (bool) add_action( 'admin_menu', function () {
			Boxes::init();
			AdminNotices::init();
		}, 0 );

		return TRUE;
	}
}