<?php # -*- coding: utf-8 -*-

namespace MetaboxOrchestra;

/**
 * @package metabox-orchestra
 */
interface MetaboxAuth {

	/**
	 * @return bool
	 */
	public function authorized(): bool;
}