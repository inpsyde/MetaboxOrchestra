<?php # -*- coding: utf-8 -*-

namespace MetaboxOrchestra\Tests;

use Brain\Monkey\Functions;
use MetaboxOrchestra\BoxInfo;
use MetaboxOrchestra\BoxView;
use MetaboxOrchestra\NoopBoxView;

class NoopBoxViewTest extends TestCase {

	public function testBasic() {

		$testee = new NoopBoxView();
		static::assertInstanceOf( BoxView::class, $testee );

	}

	public function testRender() {

		Functions\stubs( [ 'sanitize_title_with_dashes' ] );

		static::assertSame(
			'',
			( new NoopBoxView() )->render( new BoxInfo( '', '', '' ) )
		);
	}
}