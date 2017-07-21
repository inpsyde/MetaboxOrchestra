<?php declare( strict_types=1 ); # -*- coding: utf-8 -*-
/*
 * This file is part of the metabox-orchestra package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MetaboxOrchestra;

use Brain\Nonces\ArrayContext;
use Brain\Nonces\NonceInterface;

/**
 * @package metabox-orchestra
 * @license http://opensource.org/licenses/MIT MIT
 */
final class PostMetaboxAuth implements MetaboxAuth {

	/**
	 * @var \WP_Post
	 */
	private $post;

	/**
	 * @var NonceInterface
	 */
	private $nonce;

	/**
	 * @param \WP_Post       $post
	 * @param NonceInterface $nonce
	 */
	public function __construct( \WP_Post $post, NonceInterface $nonce ) {

		$this->post  = $post;
		$this->nonce = $nonce;
	}

	/**
	 * @return bool
	 */
	public function authorized(): bool {

		$type = get_post_type_object( $this->post->post_type );

		if ( ! current_user_can( $type->cap->edit_post, $this->post->ID ) ) {
			do_action(
				'metabox-orchestra.error',
				new \Error( 'User not allowed to save post.', (int) $this->post->ID ),
				[
					'post'       => $this->post->ID,
					'capability' => $type->cap->edit_post,
					'blog'       => get_current_blog_id(),
				]
			);

			return FALSE;
		}

		if ( is_multisite() && ms_is_switched() ) {
			return FALSE;
		}

		$context = new ArrayContext( $_POST );
		$valid   = $this->nonce->validate( $context );

		if ( ! $valid ) {
			$action = $this->nonce->action();
			do_action(
				'metabox-orchestra.error',
				new \Error( 'Nonce did not validated.', (int) $this->post->ID ),
				[
					'post'         => $this->post->ID,
					'nonce_action' => $action,
					'nonce_value'  => $context->offsetExists( $action ) ? $context[ $action ] : '*not present*',
					'blog'         => get_current_blog_id(),
				]
			);
		}

		return $valid;
	}
}