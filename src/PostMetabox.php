<?php # -*- coding: utf-8 -*-

namespace MetaboxOrchestra;

/**
 * @package metabox-orchestra
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