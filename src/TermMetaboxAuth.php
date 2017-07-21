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
final class TermMetaboxAuth implements MetaboxAuth {

	/**
	 * @var \WP_Term
	 */
	private $term;

	/**
	 * @var NonceInterface
	 */
	private $nonce;

	/**
	 * @param \WP_Term       $term
	 * @param NonceInterface $nonce
	 */
	public function __construct( \WP_Term $term, NonceInterface $nonce ) {

		$this->term  = $term;
		$this->nonce = $nonce;
	}

	/**
	 * @return bool
	 */
	public function authorized(): bool {

		$taxonomy = get_taxonomy( $this->term->taxonomy );

		if ( ! $taxonomy || ! current_user_can( $taxonomy->cap->edit_terms ) ) {
			do_action(
				'metabox-orchestra.error',
				new \Error( 'User not allowed to save term.', (int) $this->term->term_id ),
				[
					'term'       => $this->term->term_id,
					'capability' => $taxonomy ? $taxonomy->capabilitites->edit_terms : '*taxonomy does not exists*',
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
				new \Error( 'Nonce did not validated.', (int) $this->term->term_id ),
				[
					'term'         => $this->term->term_id,
					'nonce_action' => $action,
					'nonce_value'  => $context->offsetExists( $action ) ? $context[ $action ] : '*not present*',
					'blog'         => get_current_blog_id(),
				]
			);
		}

		return $valid;
	}
}