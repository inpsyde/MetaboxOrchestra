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
 * @package MetaboxOrchestra
 * @license http://opensource.org/licenses/MIT MIT
 */
class Entity {

	/**
	 * @var object
	 */
	private $entity = null;

	/**
	 * @var int
	 */
	private $id = 0;

	/**
	 * @var array
	 */
	private $entity_array;

	/**
	 * @param $object
	 */
	public function __construct( $object ) {

		if ( ! is_object( $object ) ) {
			return;
		}

		switch ( TRUE ) {
			case ( $object instanceof \WP_Post ) :
				$this->entity = $object;
				$this->id     = (int) $object->ID;
				break;
			case ( $object instanceof \WP_Term ):
				$this->entity = $object;
				$this->id     = (int) $object->term_id;
				break;
			case ( $object instanceof Entity ) :
				$this->entity = $object->expose();
				$this->id     = $object->id();
				break;
		}
	}

	/**
	 * @param string $var
	 *
	 * @return mixed
	 */
	public function __get( string $var ) {

		return $this->prop( $var );
	}

	/**
	 * @return int
	 */
	public function id(): int {

		return $this->id;
	}

	/**
	 * @return bool
	 */
	public function valid(): bool {

		return $this->entity && $this->id() > 0;
	}

	/**
	 * @param string $type
	 *
	 * @return bool
	 */
	public function is( string $type ): bool {

		return $this->valid() && is_a( $this->entity, $type );
	}

	/**
	 * @param string $prop
	 * @param null   $default
	 *
	 * @return mixed
	 */
	public function prop( string $prop, $default = null ) {

		if ( ! $this->valid() ) {

			return $default;
		}

		if ( is_array( $this->entity_array ) ) {

			return $this->entity_array[ $prop ] ?? $default;
		}

		if ( is_callable( [ $this->entity, 'to_array' ] ) ) {
			$this->entity_array = $this->entity->to_array();

			return $this->entity_array[ $prop ] ?? $default;
		}

		$this->entity_array = get_object_vars( $this->entity );

		return $this->entity_array[ $prop ] ?? $default;
	}

	/**
	 * @return object|null
	 */
	public function expose() {

		return $this->valid() ? clone $this->entity : null;
	}

}
