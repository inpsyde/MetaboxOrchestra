<?php # -*- coding: utf-8 -*-

namespace MetaboxOrchestra;

/**
 * @package metabox-orchestra
 */
interface BoxView {

	/**
	 * @param BoxInfo $info
	 *
	 * @return string
	 */
	public function render( BoxInfo $info ): string;

}