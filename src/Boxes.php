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
	const ACTION_SHOW = 'metabox-orchestra.show-boxes';
	const ACTION_SAVE = 'metabox-orchestra.save-boxes';
	const ACTION_SAVED = 'metabox-orchestra.saved-boxes';

	/**
	 * @var bool
	 */
	private static $init = false;

	/**
	 * @var Metabox[]
	 */
	private $boxes = [];

	/**
	 * @var bool
	 */
	private $locked = true;

	/**
	 * @var Entity
	 */
	private $target;

	/**
	 * @var string
	 */
	private $registering_for = '';

	/**
	 * @var string
	 */
	private $saving = '';

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

		if (
			! $this->target->valid()
			|| ! in_array( $this->registering_for, [ Metabox::SAVE, Metabox::SHOW ], true )
		) {
			return $this;
		}

		$is_post = $this->target->is( \WP_Post::class );
		$is_term = $this->target->is( \WP_Term::class );

		foreach ( $boxes as $box ) {
			if ( $is_post && $box instanceof PostMetabox ) {
				$this->boxes[ $box->create_info( $this->registering_for, $this->target )->id() ] = $box;
			} elseif ( $is_term && $box instanceof TermMetabox ) {
				$this->boxes[ $box->create_info( $this->registering_for, $this->target )->id() ] = $box;
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
        // remove the term hook after being executed
        unset($wp_meta_boxes["edit-{$term->taxonomy}"]);
	}

	/**
	 * @return bool
	 */
	private function init_for_post(): bool {

		// Show Boxes
		add_action( 'add_meta_boxes', function ( $post_type, $post ) {
			if ( $post instanceof \WP_Post ) {

				$entity = new Entity( $post );

				$this->prepare_target( new Entity( $post ), Metabox::SHOW );

				do_action( self::ACTION_SHOW, $entity );

				array_walk( $this->boxes, [ $this, 'add_meta_box' ] );

				$this->release_target();
			}
		}, 100, 2 );

		// Save Boxes even if WordPress says content is empty.
		add_filter(
			'wp_insert_post_empty_content',
			function ( $empty, array $post_array ) {

				global $post;
				if ( ! $empty || ! $post instanceof \WP_Post || ! $post->ID ) {
					return $empty;
				}

				if ( apply_filters( 'metabox-orchestra.save-on-empty-post', TRUE, $post, $post_array ) ) {
					$this->on_post_save( $post );
				}

				return $empty;
			},
			PHP_INT_MAX,
			2
		);

		// Save Boxes
		add_action( 'wp_insert_post', function ( $post_id, \WP_Post $post ) {
			$this->on_post_save( $post );
		}, 100, 2 );

		return true;
	}

	/**
	 * @param string $taxonomy
	 *
	 * @return bool
	 */
	private function init_for_term( string $taxonomy ): bool {

		// Show Boxes
		add_action( "{$taxonomy}_pre_edit_form", function ( \WP_Term $term ) {

			$entity = new Entity( $term );

			$this->prepare_target( $entity, Metabox::SHOW );

			do_action( self::ACTION_SHOW, $entity );

			array_walk( $this->boxes, [ $this, 'add_meta_box' ] );

			$this->release_target();
		}, 1 );

		// Save Boxes
		add_action( 'edit_term', function ( $term_id, $term_taxonomy_id, $term_taxonomy ) use ( $taxonomy ) {

			// This check allows to edit term object inside BoxAction::save() without recursion.
			if ( $this->saving === 'term' ) {
				return;
			}

			$term = get_term_by( 'term_taxonomy_id', $term_taxonomy_id );

			if (
				! $term instanceof \WP_Term
				|| (int) $term->term_id !== (int) $term_id
				|| $term->taxonomy !== $term_taxonomy
				|| $term->taxonomy !== $taxonomy
			) {
				return;
			}

			$this->saving = 'term';

			$entity = new Entity( $term );

			$this->prepare_target( $entity, Metabox::SAVE );

			do_action( self::ACTION_SAVE, $entity );

			array_walk( $this->boxes, [ $this, 'save_meta_box' ] );

			do_action( self::ACTION_SAVED, $entity );

			$this->release_target();

			$this->saving = '';

		}, 100, 3 );

		return true;
	}

	/**
	 * @param Entity $entity
	 * @param string $show_or_save
	 */
	private function prepare_target( Entity $entity, string $show_or_save ) {

		$this->target          = $entity;
		$this->registering_for = $show_or_save;
		$this->boxes           = [];
		$this->locked          = false;
		$this->target->valid() and do_action( self::REGISTER_BOXES, $this, $this->target, $show_or_save );
		$this->locked = true;
	}

	/**
	 * @param Metabox|PostMetabox|TermMetabox $box
	 * @param string                          $type
	 *
	 * @return bool
	 */
	private function box_enabled( Metabox $box, string $type ): bool {

		if ( ! $this->target->valid() ) {
			return false;
		}

		$accept = false;
		/** @var \WP_Post|\WP_Term $object */
		$object = $this->target->expose();
		switch ( true ) {
			case $this->target->is( \WP_Post::class ) && $box instanceof PostMetabox:
				$accept = $box->accept_post( $object, $type );
				break;
			case $this->target->is( \WP_Term::class ) && $box instanceof TermMetabox:
				$accept = $box->accept_term( $object, $type );
				break;
		}

		return (bool) apply_filters( 'metabox-orchestra.box-enabled', $accept, $box, $object );
	}

	/**
	 * @param Metabox|PostMetabox|TermMetabox $box
	 * @param string                          $box_id
	 */
	private function add_meta_box( Metabox $box, string $box_id ) {

		if ( ! $this->box_enabled( $box, Metabox::SHOW ) ) {
			return;
		}

		$is_post = $this->target->is( \WP_Post::class );
		/** @var \WP_Post|\WP_Term $object */
		$object = $this->target->expose();
		$info   = $box->create_info( Metabox::SHOW, $this->target );
		$view   = $is_post ? $box->view_for_post( $object ) : $box->view_for_term( $object );

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
	 * @param \WP_Post $post
	 */
	private function on_post_save( \WP_Post $post ) {

		if (
            wp_is_post_autosave( $post )
            || wp_is_post_revision( $post )
            || ( is_multisite() && ms_is_switched() )
		) {
			return;
		}

		// This check allows to edit post object inside BoxAction::save() without recursion.
		if ( $this->saving === 'post' ) {
			return;
		}

		static $saved;
		if ( $saved ) {
			return;
		}

		$saved = true;

		$entity = new Entity( $post );

		$this->saving = 'post';

		$this->prepare_target( $entity, Metabox::SAVE );

		do_action( self::ACTION_SAVE, $entity );

		array_walk( $this->boxes, [ $this, 'save_meta_box' ] );

		do_action( self::ACTION_SAVED, $entity );

		$this->release_target();

		$this->saving = '';
	}

	/**
	 * @param Metabox|PostMetabox|TermMetabox $box
	 * @param string                          $box_id
	 */
	private function save_meta_box( Metabox $box, string $box_id ) {

		if ( ! $this->box_enabled( $box, Metabox::SAVE ) ) {
			return;
		}

		/** @var \WP_Post|\WP_Term $object */
		$object  = $this->target->expose();
		$is_post = $object instanceof \WP_Post;

		$screen     = $is_post ? $object->post_type : "edit-{$object->taxonomy}";
		$screen     = apply_filters( 'metabox-orchestra.box-notices-screen', $screen, $box, $object );
		$object_id  = $this->target->id();
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
		$this->target          = null;
		$this->registering_for = '';
		$this->boxes           = [];
		$this->locked          = false;
	}

}
