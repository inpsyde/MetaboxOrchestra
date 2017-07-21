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

use Brain\Nonces\WpNonce;

/**
 * @package metabox-orchestra
 * @license http://opensource.org/licenses/MIT MIT
 */
class Boxes {

	const REGISTER_BOXES = 'metabox-orchestra.register-boxes';

	/**
	 * @var bool
	 */
	private static $init = FALSE;

	/**
	 * @var Metabox[]
	 */
	private $boxes = [];

	/**
	 * @var bool
	 */
	private $locked = TRUE;

	/**
	 * @var \WP_Term|\WP_Post
	 */
	private $target;

	/**
	 * Initialize class.
	 */
	public static function init() {

		self::$init or self::$init = add_action( 'current_screen', function ( \WP_Screen $screen ) {

			$instance = new static();

			if ( $screen->taxonomy ) {
				$instance->init_for_term( $screen->taxonomy );
				add_action( "{$screen->taxonomy}_edit_form", [ $instance, 'print_term_boxes' ] );

				return;
			}

			$instance->init_for_post();

		}, PHP_INT_MAX );
	}

	/**
	 * @param Metabox[] $boxes
	 *
	 * @return Boxes
	 */
	public function add_box( Metabox ...$boxes ) {

		if ( $this->locked ) {
			throw new \BadMethodCallException( 'Cannot add boxes when controller is locked.' );
		}

		$is_post = $this->target instanceof \WP_Post;
		$is_term = $this->target instanceof \WP_Term;

		if ( ! $is_post && ! $is_term ) {
			return $this;
		}

		foreach ( $boxes as $box ) {
			if ( $is_post && $box instanceof PostMetabox ) {
				$this->boxes[ $box->create_info()->id() ] = $box;
			} elseif ( $is_term && $box instanceof TermMetabox ) {
				$this->boxes[ $box->create_info()->id() ] = $box;
			}
		}

		return $this;
	}

	/**
	 * WordPress does not print metaboxes for terms, let's fix this.
	 *
	 * @param \WP_Term $term
	 */
	public function print_term_boxes( \WP_Term $term ) {

		if ( current_filter() !== "{$term->taxonomy}_edit_form" ) {
			return;
		}

		global $wp_meta_boxes;
		if ( empty( $wp_meta_boxes[ "edit-{$term->taxonomy}" ] ) ) {
			return;
		}

		$script = '!function(J,D){J(function(){J(".meta-box-sortables").sortable();'
		          . 'J(D).on("click",".termbox-container button.handlediv",function(){'
		          . 'var D=J(this),t=D.siblings(".inside");t.toggle();var e=t.is(":visible")?"true":"false";'
		          . 'D.attr("aria-expanded",e)})})}(jQuery,document);';
		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_add_inline_script( 'jquery-ui-sortable', $script );

		echo '<div id="poststuff"><div class="postbox-container termbox-container">';
		// WordPress does not print metaboxes for terms, let's fix this
		do_meta_boxes( "edit-{$term->taxonomy}", 'side', $term );
		do_meta_boxes( "edit-{$term->taxonomy}", 'normal', $term );
		do_meta_boxes( "edit-{$term->taxonomy}", 'advanced', $term );
		echo '</div></div>';
	}

	/**
	 * @return bool
	 */
	private function init_for_post(): bool {

		// Show Boxes
		add_action( 'add_meta_boxes', function ( $post_type, $post ) {
			if ( $post instanceof \WP_Post ) {
				$this->prepare_target( $post );
				array_walk( $this->boxes, [ $this, 'add_meta_box' ], $post );
				$this->release_target();
			}
		}, 100, 2 );

		// Save Boxes
		add_action( 'wp_insert_post', function ( $post_id, \WP_Post $post ) {

			$this->prepare_target( $post );
			array_walk( $this->boxes, [ $this, 'save_meta_box' ], $post );
			$this->release_target();

		}, PHP_INT_MAX, 2 );

		return TRUE;
	}

	/**
	 * @param string $taxonomy
	 *
	 * @return bool
	 */
	private function init_for_term( string $taxonomy ): bool {

		// Show Boxes
		add_action( "{$taxonomy}_pre_edit_form", function ( \WP_Term $term ) {
			$this->prepare_target( $term );
			array_walk( $this->boxes, [ $this, 'add_meta_box' ], $term );
			$this->release_target();
		}, 1 );

		// Save Boxes
		add_action( 'edit_term', function ( $term_id, $term_taxonomy_id, $term_taxonomy ) use ( $taxonomy ) {

			$term = get_term_by( 'term_taxonomy_id', $term_taxonomy_id );

			if (
				! $term instanceof \WP_Term
				|| (int) $term->term_id !== (int) $term_id
				|| $term->taxonomy !== $term_taxonomy
				|| $term->taxonomy !== $taxonomy
			) {
				return;
			}

			$this->prepare_target( $term );
			array_walk( $this->boxes, [ $this, 'save_meta_box' ], $term );
			$this->release_target();

		}, PHP_INT_MAX, 3 );

		return TRUE;
	}

	/**
	 * @param \WP_Term|\WP_Post $target
	 */
	private function prepare_target( $target ) {

		$this->target = $target;
		$this->boxes  = [];

		$this->locked = FALSE;

		do_action( self::REGISTER_BOXES, $this, $this->target );

		$this->locked = TRUE;
	}

	/**
	 * @param Metabox|PostMetabox|TermMetabox $box
	 * @param \WP_Term|\WP_Post               $object
	 * @param string                          $type
	 *
	 * @return bool
	 */
	private function box_enabled( Metabox $box, $object, string $type ): bool {

		$accept = $object instanceof \WP_Post
			? $box->accept_post( $object, $type )
			: $box->accept_term( $object, $type );

		return (bool) apply_filters( 'metabox-orchestra.box-enabled', $accept, $box, $object );
	}

	/**
	 * @param Metabox|PostMetabox|TermMetabox $box
	 * @param string                          $box_id
	 * @param \WP_Post|\WP_Term               $object
	 */
	private function add_meta_box( Metabox $box, string $box_id, $object ) {

		if ( ! $this->box_enabled( $box, $object, Metabox::SHOW ) ) {
			return;
		}

		$is_post = $object instanceof \WP_Post;
		$info    = $box->create_info();
		$view    = $is_post ? $box->view_for_post( $object ) : $box->view_for_term( $object );

		$box_suffix = $is_post ? '-postbox' : '-termbox';
		$context    = $info->context();
		$screen     = $is_post ? null : "edit-{$object->taxonomy}";
		( $context === BoxInfo::CONTEXT_SIDE && $is_post ) and $screen = $object->post_type;

		add_meta_box(
			$box_id . $box_suffix,
			$info->title(),
			static function ( $object ) use ( $box_id, $view, $box, $info ) {

				$object_id = $object instanceof \WP_Post ? $object->ID : $object->term_id;

				print \Brain\Nonces\formField( new WpNonce( $box_id . "-{$object_id}" ) );

				do_action( 'metabox-orchestra.inside-box-before', $box, $object, $info );

				print $view->render( $info );

				do_action( 'metabox-orchestra.inside-box-after', $box, $object, $info );
			},
			$screen,
			$context,
			$info->priority()
		);
	}

	/**
	 * @param Metabox|PostMetabox|TermMetabox $box
	 * @param string                          $box_id
	 * @param \WP_Post|\WP_Term               $object
	 */
	private function save_meta_box( Metabox $box, string $box_id, $object ) {

		if ( ! $this->box_enabled( $box, $object, Metabox::SAVE ) ) {
			return;
		}

		$is_post = $object instanceof \WP_Post;

		if ( $is_post && ( wp_is_post_autosave( $object ) || wp_is_post_revision( $object ) ) ) {
			return;
		}

		$screen     = $is_post ? $object->post_type : "edit-{$object->taxonomy}";
		$screen     = apply_filters( 'metabox-orchestra.box-notices-screen', $screen, $box, $object );
		$object_id  = $is_post ? $object->ID : $object->term_id;
		$auth_class = $is_post ? PostMetaboxAuth::class : TermMetaboxAuth::class;

		$notices = AdminNotices::init( (string) $screen );

		/** @var PostMetaboxAuth|TermMetaboxAuth $auth */
		$nonce = new WpNonce( "{$box_id}-{$object_id}" );
		$auth  = new $auth_class( $object, $nonce );

		if ( ! $auth->authorized() ) {
			do_action( 'metabox-orchestra.unauthorized-box-save', $box, $object, $nonce );

			return;
		}

		$action = $is_post ? $box->action_for_post( $object ) : $box->action_for_term( $object );
		$action->save( $notices );
	}

	/**
	 * Clean up state.
	 */
	private function release_target() {
		$this->target = null;
		$this->boxes  = [];
		$this->locked = FALSE;
	}

}