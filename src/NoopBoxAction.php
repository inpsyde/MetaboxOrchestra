<?php # -*- coding: utf-8 -*-
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
final class NoopBoxAction implements BoxAction {

	/**
	 * @param AdminNotices $notices
	 *
	 * @return bool
	 */
	public function save( AdminNotices $notices ): bool {
		return FALSE;
	}

}