<?php # -*- coding: utf-8 -*-

namespace MetaboxOrchestra;

/**
 * @package metabox-orchestra
 */
interface Metabox {

	const SAVE = 'save';
	const SHOW = 'show';

	/**
	 * @param string $show_or_save
	 * @param Entity $entity
	 *
	 * @return BoxInfo
	 */
	public function create_info( string $show_or_save, Entity $entity ): BoxInfo;

}