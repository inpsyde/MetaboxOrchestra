<?php # -*- coding: utf-8 -*-

namespace MetaboxOrchestra\Tests;

use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use Brain\Nonces\NonceInterface;
use MetaboxOrchestra\MetaboxAuth;
use MetaboxOrchestra\TermMetaboxAuth;

class TermMetaboxAuthTest extends TestCase {

	public function testBasic() {

		/** @var \WP_Term $wp_term */
		$wp_term = \Mockery::mock( '\WP_Term' );
		/** @var NonceInterface $nonce */
		$nonce = \Mockery::mock( NonceInterface::class );

		static::assertInstanceOf( MetaboxAuth::class, new TermMetaboxAuth( $wp_term, $nonce ) );
	}

	public function testAuthorizedNoTaxonomyFound() {

		/** @var \WP_Term $wp_term */
		$wp_term           = \Mockery::mock( '\WP_Term' );
		$wp_term->taxonomy = NULL;
		$wp_term->term_id  = NULL;

		/** @var NonceInterface $nonce */
		$nonce = \Mockery::mock( NonceInterface::class );

		Functions\expect( 'get_taxonomy' )
			->once()
			->andReturn( FALSE );

		Functions\expect( 'current_user_can' )->never();

		Functions\expect( 'get_current_blog_id' )
			->once()
			->andReturn( NULL );

		Actions\expectDone( 'metabox-orchestra.error' )
			->once()
			->with( \Mockery::type( \Error::class ), \Mockery::type( 'array' ) );

		static::assertFalse( ( new TermMetaboxAuth( $wp_term, $nonce ) )->authorized() );
	}

	public function testAuthorizedCurrentUserCanNot() {

		/** @var \WP_Term $wp_term */
		$wp_term           = \Mockery::mock( '\WP_Term' );
		$wp_term->taxonomy = NULL;
		$wp_term->term_id  = NULL;

		/** @var NonceInterface $nonce */
		$nonce = \Mockery::mock( NonceInterface::class );

		Functions\expect( 'get_taxonomy' )
			->once()
			->andReturn(
				(object) [
					'cap'           => (object) [ 'edit_terms' => '' ],
					'capabilitites' => (object) [ 'edit_terms' => '' ]
				]
			);

		Functions\expect( 'current_user_can' )
			->once()
			->andReturn( FALSE );

		Functions\expect( 'get_current_blog_id' )
			->once()
			->andReturn( NULL );

		Actions\expectDone( 'metabox-orchestra.error' )
			->once()
			->with( \Mockery::type( \Error::class ), \Mockery::type( 'array' ) );

		static::assertFalse( ( new TermMetaboxAuth( $wp_term, $nonce ) )->authorized() );
	}

	public function testAuthorizedMultisite() {

		/** @var \WP_Term $wp_term */
		$wp_term           = \Mockery::mock( '\WP_Term' );
		$wp_term->taxonomy = NULL;

		/** @var NonceInterface $nonce */
		$nonce = \Mockery::mock( NonceInterface::class );

		Functions\expect( 'get_taxonomy' )
			->once()
			->andReturn(
				(object) [
					'cap'           => (object) [ 'edit_terms' => '' ],
					'capabilitites' => (object) [ 'edit_terms' => '' ]
				]
			);

		Functions\expect( 'current_user_can' )
			->once()
			->andReturn( TRUE );

		Functions\expect( 'is_multisite' )
			->once()
			->andReturn( TRUE );

		Functions\expect( 'ms_is_switched' )
			->once()
			->andReturn( TRUE );

		static::assertFalse( ( new TermMetaboxAuth( $wp_term, $nonce ) )->authorized() );
	}

	public function testAuthorizedInvalidNonce() {

		/** @var \WP_Term $wp_term */
		$wp_term           = \Mockery::mock( '\WP_Term' );
		$wp_term->taxonomy = NULL;
		$wp_term->term_id  = NULL;

		/** @var NonceInterface $nonce */
		$nonce = \Mockery::mock( NonceInterface::class );
		$nonce->shouldReceive( 'validate' )
			->once()
			->andReturn( FALSE );
		$nonce->shouldReceive( 'action' )
			->once()
			->andReturn( '' );

		Functions\expect( 'get_taxonomy' )
			->once()
			->andReturn(
				(object) [
					'cap'           => (object) [ 'edit_terms' => '' ],
					'capabilitites' => (object) [ 'edit_terms' => '' ]
				]
			);

		Functions\expect( 'current_user_can' )
			->once()
			->andReturn( TRUE );

		Functions\expect( 'is_multisite' )
			->once()
			->andReturn( FALSE );

		Functions\expect( 'ms_is_switched' )
			->never();

		Actions\expectDone( 'metabox-orchestra.error' )
			->once()
			->with( \Mockery::type( \Error::class ), \Mockery::type( 'array' ) );

		Functions\expect( 'get_current_blog_id' )
			->once()
			->andReturn( NULL );

		static::assertFalse( ( new TermMetaboxAuth( $wp_term, $nonce ) )->authorized() );
	}

	public function testAuthorizedValidNonce() {

		/** @var \WP_Term $wp_term */
		$wp_term           = \Mockery::mock( '\WP_Term' );
		$wp_term->taxonomy = NULL;

		Functions\expect( 'get_taxonomy' )
			->once()
			->andReturn(
				(object) [
					'cap'           => (object) [ 'edit_terms' => '' ],
					'capabilitites' => (object) [ 'edit_terms' => '' ]
				]
			);

		Functions\expect( 'current_user_can' )
			->once()
			->andReturn( TRUE );

		Functions\expect( 'is_multisite' )
			->once()
			->andReturn( FALSE );

		Functions\expect( 'ms_is_switched' )
			->never();

		/** @var NonceInterface $nonce */
		$nonce = \Mockery::mock( NonceInterface::class );
		$nonce->shouldReceive( 'validate' )
			->once()
			->andReturn( TRUE );

		static::assertTrue( ( new TermMetaboxAuth( $wp_term, $nonce ) )->authorized() );
	}

}