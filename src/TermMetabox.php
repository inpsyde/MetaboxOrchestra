<?php # -*- coding: utf-8 -*-

namespace MetaboxOrchestra;

/**
 * @package metabox-orchestra
 */
interface TermMetabox extends Metabox {

	/**
	 * @param \WP_Term $term
	 * @param string   $save_or_show
	 *
	 * @return bool
	 */
	public function accept_term( \WP_Term $term, string $save_or_show ): bool;

	/**
	 * @param \WP_Term $term
	 *
	 * @return BoxView
	 */
	public function view_for_term( \WP_Term $term ): BoxView;

	/**
	 * @param \WP_Term $term
	 *
	 * @return BoxAction
	 */
	public function action_for_term( \WP_Term $term ): BoxAction;

}