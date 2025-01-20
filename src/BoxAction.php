<?php # -*- coding: utf-8 -*-

namespace MetaboxOrchestra;

/**
 * @package metabox-orchestra
 */
interface BoxAction {

	/**
	 * @param AdminNotices $notices
	 *
	 * @return bool
	 */
	public function save( AdminNotices $notices ): bool;

}