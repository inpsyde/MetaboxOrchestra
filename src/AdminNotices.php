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

/**
 * @package metabox-orchestra
 * @license http://opensource.org/licenses/MIT MIT
 */
class AdminNotices {

	const ERROR = 'error';
	const SUCCESS = 'success';
	const INFO = 'info';
	const OPTION_NAME = 'metabox_orchestra_notice_';
	const DEFAULT_TTL = 300;

	/**
	 * @var AdminNotices
	 */
	private static $init;

	/**
	 * @var array
	 */
	private $messages = [];

	/**
	 * @var string
	 */
	private $default_screen = '';

	/**
	 * @param string $default_screen
	 *
	 * @return AdminNotices
	 */
	public static function init( string $default_screen = '' ): AdminNotices {

		if ( self::$init ) {
			return self::$init->use_default_screen( $default_screen );
		}

		self::$init = new static();

		add_action( 'shutdown', function () {
			self::$init->record();
		} );

		add_action( 'admin_notices', function () {
			$user_id = get_current_user_id();
			$target  = get_current_screen()->id;
			$data    = (array) get_user_option( self::OPTION_NAME, $user_id );
			if ( ! empty( $data[ $target ] ) ) {
				self::$init->print_messages( (array) $data[ $target ] );
				unset( $data[ $target ] );
				update_user_option( $user_id, self::OPTION_NAME, $data );
			}
		} );

		return self::$init->use_default_screen( $default_screen );
	}

	/**
	 * @param string      $message
	 * @param string      $title
	 * @param string      $type
	 * @param string|null $target_screen
	 *
	 * @return AdminNotices
	 */
	public function add(
		string $message,
		string $title = '',
		string $type = self::ERROR,
		string $target_screen = null
	): AdminNotices {

		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return $this;
		}

		$target_screen or $target_screen = $this->default_screen ? : get_current_screen()->id;
		$now = new \DateTimeImmutable( 'now', new \DateTimeZone( 'UTC' ) );

		isset( $this->messages[ $target_screen ] ) or $this->messages[ $target_screen ] = [];
		isset( $this->messages[ $target_screen ][ $type ] ) or $this->messages[ $target_screen ][ $type ] = [];
		isset( $this->messages[ $target_screen ][ $type ] ) or $this->messages[ $target_screen ][ $type ] = [];
		$this->messages[ $target_screen ][ $type ][] = [ $message, $title, $now->getTimestamp() ];

		return $this;
	}

	/**
	 * Store (or delete) messages on shutdown.
	 */
	private function record() {

		$user_id = get_current_user_id();
		if ( $user_id ) {
			$this->messages
				? update_user_option( $user_id, self::OPTION_NAME, $this->messages )
				: delete_user_option( $user_id, self::OPTION_NAME );
		}
	}

	/**
	 * @param array $messages
	 */
	private function print_messages( array $messages ) {

		foreach ( $messages as $type => $type_messages ) {

			if ( ! in_array( $type, [ self::ERROR, self::INFO, self::SUCCESS ], TRUE ) ) {
				continue;
			}

			foreach ( (array) $type_messages as list( $message, $title, $timestamp ) ) {

				$now_ts    = ( new \DateTimeImmutable( 'now', new \DateTimeZone( 'UTC' ) ) )->getTimestamp();
				$target_ts = $now_ts - (int) apply_filters( 'metabox-orchestra.notice-ttl', self::DEFAULT_TTL );

				if ( $target_ts > $timestamp ) {
					continue;
				}

				?>
				<div class="notice notice-<?= $type ?> is-dismissible">
					<p>
						<?php if ( $title ) : ?>
							<strong><?= esc_html( $title ) ?></strong><br>
						<?php endif ?>
						<?php if ( $message ) : ?>
							<?= wp_kses_post( $message ) ?>
						<?php endif ?>
					</p>
				</div>
				<?php
			}
		}
	}

	/**
	 * @param string $screen
	 *
	 * @return AdminNotices
	 */
	private function use_default_screen( string $screen ): AdminNotices {

		$screen and $this->default_screen = $screen;

		return $this;
	}

}