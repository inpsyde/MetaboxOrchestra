<?php # -*- coding: utf-8 -*-
/*
 * This file is part of the MetaboxOrchestra package.
 *
 * (c) Giuseppe Mazzapica
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MetaboxOrchestra\Tests;

use MetaboxOrchestra\PostMetaboxAuth;
use Brain\Nonces\NonceInterface;
use Brain\Monkey\Functions;
use Brain\Monkey\Actions;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @package MetaboxOrchestra
 */
class PostMetaboxAuthTest extends TestCase {

	public function testAuthorizedFailsIfUserCantEditPost() {

		/** @var \stdClass|\WP_Post $post */
		$post            = \Mockery::mock( '\WP_Post' );
		$post->ID        = 123;
		$post->post_type = 'post';
		/** @var \Mockery\MockInterface|NonceInterface $nonce */
		$nonce = \Mockery::mock( NonceInterface::class );

		Functions\when( 'get_current_blog_id' )->justReturn( 1 );

		Functions\expect( 'get_post_type_object' )
			->with( 'post' )
			->andReturn( (object) [ 'cap' => (object) [ 'edit_post' => 'edit_post' ] ] );

		Functions\expect( 'current_user_can' )
			->with( 'edit_post', 123 )
			->andReturn( FALSE );

		Actions\expectDone( 'metabox-orchestra.error' )
			->once()
			->with( \Mockery::type( 'Error' ), \Mockery::type( 'array' ) );

		$auth = new PostMetaboxAuth( $post, $nonce );

		static::assertFalse( $auth->authorized() );
	}

	public function testAuthorizedFailsIfMultisiteSwitched() {

		/** @var \stdClass|\WP_Post $post */
		$post            = \Mockery::mock( '\WP_Post' );
		$post->ID        = 123;
		$post->post_type = 'post';
		/** @var \Mockery\MockInterface|NonceInterface $nonce */
		$nonce = \Mockery::mock( NonceInterface::class );

		Functions\when( 'get_post_type_object' )->justReturn( (object) [ 'cap' => (object) [ 'edit_post' => 'edit_post' ] ] );
		Functions\when( 'current_user_can' )->justReturn( TRUE );
		Functions\when( 'is_multisite' )->justReturn( TRUE );
		Functions\when( 'ms_is_switched' )->justReturn( TRUE );

		Actions\expectDone( 'metabox-orchestra.error' )->never();

		$auth = new PostMetaboxAuth( $post, $nonce );

		static::assertFalse( $auth->authorized() );
	}

	public function testAuthorizedFailsIfNonceFails() {

		/** @var \stdClass|\WP_Post $post */
		$post            = \Mockery::mock( '\WP_Post' );
		$post->ID        = 123;
		$post->post_type = 'post';
		/** @var \Mockery\MockInterface|NonceInterface $nonce */
		$nonce = \Mockery::mock( NonceInterface::class );
		$nonce->shouldReceive( 'validate' )->atLeast()->once()->andReturn( FALSE );
		$nonce->shouldReceive( 'action' )->atLeast()->once()->andReturn( 'test' );

		Functions\when( 'get_current_blog_id' )->justReturn( 1 );
		Functions\when( 'get_post_type_object' )->justReturn( (object) [ 'cap' => (object) [ 'edit_post' => 'edit_post' ] ] );
		Functions\when( 'current_user_can' )->justReturn( TRUE );
		Functions\when( 'is_multisite' )->justReturn( TRUE );
		Functions\when( 'ms_is_switched' )->justReturn( FALSE );

		$auth = new PostMetaboxAuth( $post, $nonce );

		Actions\expectDone( 'metabox-orchestra.error' )
			->once()
			->with( \Mockery::type( 'Error' ), \Mockery::type( 'array' ) )
			->whenHappen( function ( \Error $error ) {
				static::assertStringContainsString( 'Nonce', $error->getMessage() );
			} );

		static::assertFalse( $auth->authorized() );
	}

	public function testAuthorizedSuccess() {

		/** @var \stdClass|\WP_Post $post */
		$post            = \Mockery::mock( '\WP_Post' );
		$post->ID        = 123;
		$post->post_type = 'post';
		/** @var \Mockery\MockInterface|NonceInterface $nonce */
		$nonce = \Mockery::mock( NonceInterface::class );
		$nonce->shouldReceive( 'validate' )->atLeast()->once()->andReturn( TRUE );

		Functions\when( 'get_current_blog_id' )->justReturn( 1 );
		Functions\when( 'get_post_type_object' )->justReturn( (object) [ 'cap' => (object) [ 'edit_post' => 'edit_post' ] ] );
		Functions\when( 'current_user_can' )->justReturn( TRUE );
		Functions\when( 'is_multisite' )->justReturn( TRUE );
		Functions\when( 'ms_is_switched' )->justReturn( FALSE );

		$auth = new PostMetaboxAuth( $post, $nonce );

		Actions\expectDone( 'metabox-orchestra.error' )->never();

		static::assertTrue( $auth->authorized() );
	}
}
