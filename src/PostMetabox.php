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
interface PostMetabox extends Metabox {

	/**
	 * @param \WP_Post $post
	 * @param string   $save_or_show
	 *
	 * @return bool
	 */
	public function accept_post( \WP_Post $post, string $save_or_show ): bool;

	/**
	 * @param \WP_Post $post
	 *
	 * @return BoxView
	 */
	public function view_for_post( \WP_Post $post ): BoxView;

	/**
	 * @param \WP_Post $post
	 *
	 * @return BoxAction
	 */
	public function action_for_post( \WP_Post $post ): BoxAction;

}