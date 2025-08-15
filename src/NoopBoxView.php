<?php # -*- coding: utf-8 -*-

namespace MetaboxOrchestra;

/**
 * @package metabox-orchestra
 */
final class NoopBoxView implements BoxView {

	/**
	 * @param BoxInfo $info
	 *
	 * @return string
	 */
	public function render( BoxInfo $info ): string {

		return '';
	}

}