<?php # -*- coding: utf-8 -*-

namespace MetaboxOrchestra;

/**
 * @package metabox-orchestra
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