<?php # -*- coding: utf-8 -*-

namespace MetaboxOrchestra\Tests;

use Brain\Monkey\Functions;
use MetaboxOrchestra\BoxInfo;

class BoxInfoTest extends TestCase {

	public function testBasic() {

		Functions\stubs( [ 'sanitize_title_with_dashes' ] );

		$testee = new BoxInfo( 'foo' );
		static::assertInstanceOf( \ArrayAccess::class, $testee );

		static::assertSame( 'foo', $testee->title() );
		static::assertSame( 'foo', $testee->id() );
		static::assertSame( BoxInfo::PRIORITY_ADVANCED, $testee->priority() );
		static::assertSame( BoxInfo::CONTEXT_ADVANCED, $testee->context() );
	}

	public function testConstructorId() {

		Functions\stubs( [ 'sanitize_title_with_dashes' ] );
		$expected = 'unique-id';
		static::assertSame( $expected, ( new BoxInfo( 'foo', $expected ) )->id() );
	}

	/**
	 * @param string      $input
	 * @param null|string $expected
	 *
	 * @dataProvider provideConstructorContext
	 */
	public function testConstructorContext( $input, $expected = NULL ) {

		$expected = $expected ? : $input;
		Functions\stubs( [ 'sanitize_title_with_dashes' ] );
		static::assertSame(
			$expected,
			( new BoxInfo( 'foo', '', $input ) )->context()
		);
	}

	public function provideConstructorContext() {

		return [
			'side'            => [ BoxInfo::CONTEXT_SIDE ],
			'normal'          => [ BoxInfo::CONTEXT_NORMAL ],
			'advanced'        => [ BoxInfo::CONTEXT_ADVANCED ],
			'invalid context' => [ 'foo', BoxInfo::CONTEXT_ADVANCED ]
		];
	}

	/**
	 * @param string      $input
	 * @param null|string $expected
	 *
	 * @dataProvider provideConstructorPriority
	 */
	public function testConstructorPriority( $input, $expected = NULL ) {

		$expected = $expected ? : $input;
		Functions\stubs( [ 'sanitize_title_with_dashes' ] );
		static::assertSame(
			$expected,
			( new BoxInfo( 'foo', '', '', $input ) )->priority()
		);
	}

	public function provideConstructorPriority() {

		return [
			'high'             => [ BoxInfo::PRIORITY_HIGH ],
			'sorted'           => [ BoxInfo::PRIORITY_SORTED ],
			'core'             => [ BoxInfo::PRIORITY_CORE ],
			'normal'           => [ BoxInfo::PRIORITY_NORMAL ],
			'advanced'         => [ BoxInfo::PRIORITY_ADVANCED ],
			'invalid priority' => [ 'foo', BoxInfo::PRIORITY_ADVANCED ]
		];
	}

	public function testArrayAccess() {

		Functions\stubs( [ 'sanitize_title_with_dashes' ] );
		$testee = new BoxInfo( '' );

		$key      = 'foo';
		$expected = 'bar';

		$testee[ $key ] = $expected;

		static::assertSame( $expected, $testee[ $key ] );
		static::assertTrue( isset( $testee[ $key ] ) );

		unset( $testee[ $key ] );

		static::assertFalse( isset( $testee[ $key ] ) );
		static::assertNull( $testee[ $key ] );
	}
}