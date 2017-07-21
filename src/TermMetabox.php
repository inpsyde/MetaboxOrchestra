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