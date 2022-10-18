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
final class BoxInfo implements \ArrayAccess {

	const PRIORITY_HIGH = 'high';
	const PRIORITY_SORTED = 'sorted';
	const PRIORITY_CORE = 'core';
	const PRIORITY_NORMAL = 'default';
	const PRIORITY_ADVANCED = 'low';
	const PRIORITIES = [
		self::PRIORITY_HIGH,
		self::PRIORITY_SORTED,
		self::PRIORITY_CORE,
		self::PRIORITY_NORMAL,
		self::PRIORITY_ADVANCED,
	];

	const CONTEXT_SIDE = 'side';
	const CONTEXT_NORMAL = 'normal';
	const CONTEXT_ADVANCED = 'advanced';
	const CONTEXTS = [
		self::CONTEXT_SIDE,
		self::CONTEXT_NORMAL,
		self::CONTEXT_ADVANCED,
	];

	/**
	 * @var array
	 */
	private $storage = [];

	/**
	 * @var array
	 */
	private $meta = [];

	/**
	 * @param string $title
	 * @param string $id
	 * @param string $context
	 * @param string $priority
	 */
	public function __construct( string $title, string $id = '', string $context = '', string $priority = '' ) {

		$priority = in_array( $priority, self::PRIORITIES, TRUE )
			? $priority
			: self::PRIORITY_ADVANCED;

		$context = in_array( $context, self::CONTEXTS, TRUE )
			? $context
			: self::CONTEXT_ADVANCED;

		$id or $id = sanitize_title_with_dashes( $title );

		$this->storage = compact( 'title', 'id', 'context', 'priority' );
	}

	/**
	 * @return string
	 */
	public function id(): string {

		return $this->storage[ 'id' ];
	}

	/**
	 * @return string
	 */
	public function title(): string {

		return $this->storage[ 'title' ];
	}

	/**
	 * @return string
	 */
	public function context(): string {

		return $this->storage[ 'context' ];
	}

	/**
	 * @return string
	 */
	public function priority(): string {

		return $this->storage[ 'priority' ];
	}

	/**
	 * @inheritdoc
	 */
	#[\ReturnTypeWillChange] 
	public function offsetExists( $offset ) {

		return array_key_exists( $offset, $this->meta );
	}

	/**
	 * @inheritdoc
	 */
	#[\ReturnTypeWillChange] 
	public function offsetGet( $offset ) {

		return $this->offsetExists( $offset ) ? $this->meta[ $offset ] : NULL;
	}

	/**
	 * @inheritdoc
	 */
	#[\ReturnTypeWillChange] 
	public function offsetSet( $offset, $value ) {

		$this->meta[ $offset ] = $value;
	}

	/**
	 * @inheritdoc
	 */
	#[\ReturnTypeWillChange] 
	public function offsetUnset( $offset ) {

		unset( $this->meta[ $offset ] );
	}
}