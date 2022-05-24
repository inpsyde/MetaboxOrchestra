# Metabox Orchestra [![Latest Stable Version](https://poser.pugx.org/inpsyde/metabox-orchestra/v/stable)](https://packagist.org/packages/inpsyde/metabox-orchestra) [![Project Status](http://opensource.box.com/badges/active.svg)](http://opensource.box.com/badges) [![Build Status](https://travis-ci.org/inpsyde/MetaboxOrchestra.svg?branch=master)](http://travis-ci.org/inpsyde/MetaboxOrchestra) [![License](https://poser.pugx.org/inpsyde/metabox-orchestra/license)](https://packagist.org/packages/inpsyde/metabox-orchestra)

> A Composer package that provides OOP metabox orchestration for WordPress.

---

## Features

- Allows to add metaboxes to taxonomy terms
- Automatically handles nonces and authorizations.
- Agnostic about the actual rendering and saving mechanism of metaboxes
- OOP infrastructure

---

## Bootstrap

"Metabox Orchestra" is **not** a plugin, but a Composer package. It can be required by themes, plugins or at website level for sites entirely managed by Composer.

After it is installed via Composer, and composer autoload is required, Metabox Orchestra needs to be bootstrapped, like this:

```php
MetaboxOrchestra\Bootstrap::bootstrap();
```

- This can be done in any plugin, MU plugin or theme `functions.php` with no need to wrap the call in any hook.
- There's no need to check if the library is _already_ bootstrapped, the snippet above can be called multiple times
  without any negative effect.
  
After this single line of code is in place, "Metabox Orchestra" is fully working and ready to be used.

---


## Usage

After "Metabox Orchestra" is loaded and bootstrapped, it's time to add some metaboxes.

**Each metabox is Composed by 4 simple objects**, for 3 of them "Metabox Orchestra" provides the interface, for the fourth it provides a ready made implementation.

The objects are:

- A metabox _builder_
- A metabox _info_
- A metabox _view_
- A metabox _action_



### Metabox Builder

The builder is an object implementing `PostMetabox` for boxes to be printed in posts edit screen, and `TermMetabox` for 
boxes to be printed in taxonomy terms edit screen.

Below the signature of `PostMetabox` public methods:

```php
interface PostMetabox extends Metabox {
    
	public function create_info( string $show_or_save, Entity $entity ): BoxInfo;

	public function accept( \WP_Post $post, string $save_or_show ): bool;
	
	public function create_view( \WP_Post $post ): BoxView;
	
	public function create_action( \WP_Post $post ): BoxAction;
}
```

`TermMetabox` interface is practically identical, only in places where `PostMetabox` expects a `WP_Post`,
`TermMetabox` expects a `WP_Term`.

> _**Note**: both `PostMetabox` and `TermMetabox` extend the `Metabox` interface which only contains the `create_info()` method, which (for the sake of readability) is shown as part of `PostMetabox` in the snippet above._ 



### Metabox Info

`PostMetabox::create_info()` (and `TermMetabox::create_info()`) must return an instance of `BoxInfo`.

This is a value object shipped with the library. It encapsulates the scalar arguments that are usually passed to `add_meta_box()` WordPress function: metabox id, title, context and priority.

From inside `create_info()` method an info object can be returned just by instantiating it:

```php
public function create_info( string $show_or_save, Entity $entity ): BoxInfo {

	return new BoxInfo( 'My Sample Metabox' );
}
```

The full constructor signature looks like this:

```php
public function __construct( string $title, string $id = '', string $context = '', string $priority = '' )
```

However, only the title is mandatory, all other arguments will be set to sensitive default when not provided.

`$context` and `$priority` are the same arguments taken by `add_meta_box()` WordPress function.

`BoxInfo` comes with a set of class constants that help in setting them, if one wants to. For example:

```php
public function create_info( string $show_or_save, Entity $entity ): BoxInfo {

	return new BoxInfo(
		__( 'My Sample Metabox', 'my-txt-domain' ),
		'sample-metabox',
		BoxInfo::CONTEXT_SIDE,
		BoxInfo::PRIORITY_HIGH,
	);
}
```

The `$show_or_save` argument can be used to distinguish if the `create_info()` is called when *showing* the metabox or when *saving* it; for this purpose the passed value has to be compared to the constants: `Metabox::SHOW` and `Metabox::SAVE`.

The `$entity` argument is an object wrapping the `WP_Post` (or `WP_Term`) the metabox will be shown for.

The object has a method `is()` to know what kind of object it actually wraps, and other useful methods, including `expose()` that returns the wrapped object.

For example, to use the post type label as part of metabox title it is possible to do:

```php
public function create_info( string $show_or_save, Entity $entity ): BoxInfo {
    
    $metabox_name = 'Term';
    if ( $entity->is( \WP_Post::class ) ) {
        $post_type = get_post_type_object( $entity->post_type );
        $metabox_name = $post_type->labels->singular_name;
    }

	return new BoxInfo( sprintf( 'My %s Metabox', $metabox_name ) );
}
```

Note how above the `post_type` property is accessed as public property of the `Entity` object, this works thanks to "magic" `__get()` method of `Entity` that delegates public properties access to the wrapped entity, being it a `WP_Post` or a `WP_Term` object.



### Metabox View

"Metabox Orchestra" does **not** provide any view class, but just a view _interface_ that is the same for post and term metaboxes.

The whole interface methods signature is:

```php
interface BoxView {

	public function render( BoxInfo $info ): string;
}
```

So it is a _very_ simple object. What happens inside `render()` it's up to you.

The `BoxInfo` instance passed to `render()` is the same that is returned by `Metabox::create_info()`.

Very likely the render method will need to access the current object that is being edited (either a `WP_Post` or a `WP_Term`), but `render()` does not receive it.

That's not an issue, because the view object is returned from `PostMetabox::create_view()` (or `TermMetabox::create_view()`) that receives that object.

Which means that the view object could accept it in the constructor the object.

For example:

```php
public function create_view( \WP_Post $post ): BoxView {

	$view = new MyAwesomeBoxView( $post );
	
	return $view;
}
```

Note that adding a nonce field inside the `BoxView::render()` method is **not** necessary: "Metabox Orchestra" handles all nonce things.



### Metabox Action

"Metabox Orchestra" does **not** provide any action class, but just an action _interface_ that is the same for post and term metaboxes.

The whole interface method signature is:

```php
interface BoxAction {

	public function save( AdminNotices $notices ): bool;
}
```

So it is a _very_ simple object. What happens inside `save()` it's up to you.

Very likely the render method will need to access the current object that is being saved (either a `WP_Post` or a `WP_Term`), but the `save()` does not receive it.

That's not an issue, because the action object is returned from `PostMetabox::create_action()` (or `TermMetabox::create_action()`) that receives that object.

Which means that the action object could accept it in the constructor the object.

For example:

```php
public function create_action( \WP_Post $post ): BoxAction {

	$action = new MyAwesomeBoxAction( $post );
	
	return $this->action;
}
```

The `AdminNotices` instance passed to the `BoxAction::save()` method, is an object that allows to show an error or a success message as admin notice.

It is absolutely optional and should not be abused, but can be useful especially to inform the user if same errors happen during the saving routine.

Note that checking for nonces or for capability inside the `BoxAction::save()` method is **not** necessary:
"Metabox Orchestra" does it for you.

When saving a post it is also not necessary to skip check for autosave or revision and skip saving, that's done by "Metabox Orchestra" as well.



### Add boxes

After all objects are written, it is just a matter of hooking **`Boxes::REGISTER_BOXES`** and calling the 
`Boxes::add_box()` method on the `Boxes` instance that is passed as argument to that hook.

For example:

```php
add_action( Boxes::REGISTER_BOXES, function ( Boxes $boxes ) {
	$boxes->add_box( new MyAwesomeMetabox() );
} );
```

---



## Complete Example

Below there's a trivial yet complete example on how to add a working box to the category edit screen.

First the "Box" object:


```php
namespace MyProject;

use MetaboxOrchestra\Entity;
use MetaboxOrchestra\BoxInfo;
use MetaboxOrchestra\BoxView;
use MetaboxOrchestra\BoxAction;

class SampleMetabox implements MetaboxOrchestra\TermMetabox {

	public function create_info( string $show_or_save, Entity $entity ): BoxInfo {
    	return new BoxInfo( 'My Sample Box' );
    }
    
    public function accept_term( \WP_Term $term, string $save_or_show ): bool {
    	return true;
    }
    
    public function view_for_term( \WP_Term $term ): BoxView {  
    	return new SampleView( $term );
    }
    
    public function action_for_term( \WP_Term $term ): BoxAction {
    	return new SampleAction( $term );
    }
}
```

then the "View" object:

```php
namespace MyProject;

use MetaboxOrchestra\BoxView;
use MetaboxOrchestra\BoxInfo;

class SampleView implements BoxView {

	private $term;

	public function __construct( \WP_Term $term ) {
		$this->term = $term;
	}

	public function render( BoxInfo $info ): string {
		
        return sprintf(
            '<input name="_my_sample_key" type="text" value="%s">',
            esc_attr( get_term_meta( $this->term->term_id, '_my_sample_key', TRUE ) ?: '' )
        );
	}
}

```

and the "Action" object:

```php
namespace MyProject;

use MetaboxOrchestra\BoxAction;
use MetaboxOrchestra\AdminNotices;

class SampleAction implements BoxAction {

	private $term;

	public function __construct( \WP_Term $term ) {
		$this->term = $term;
	}

	public function save( AdminNotices $notices ): bool {
		
		$cur_value = get_term_meta( $this->term->term_id, '_my_sample_key', TRUE ) ? : '';
		$new_value = esc_html( $_POST[ '_my_sample_key' ] ?? '' );
		
		$success = TRUE;
        
		if ( $new_value && is_string( $new_value ) && $new_value !== $cur_value ) {
			$success = $this->update_value( $new_value, $notices );
		} elseif ( ! $new_value && $cur_value ) {
			$success = $this->delete_value( $new_value, $notices );
		}
        
		return $success;
	}
    
    
    private function update_value( string $value, AdminNotices $notices ): bool {
        
        if ( ! update_term_meta( $this->term->term_id, '_my_sample_key', $value ) ) {
            $notices->add('Error saving sample value.', 'Error!', AdminNotices::ERROR );
            
            return false;
        }
        
        $notices->add( 'Sample value saved.', 'Success!', AdminNotices::SUCCESS );
        
        return true;
    }
    
    
    private function delete_value( AdminNotices $notices ): bool {
        
        if ( ! delete_term_meta( $this->term->term_id, '_my_sample_key' ) ) {
            $notices->add( 'Error deleting sample value.', 'Error!', AdminNotices::ERROR );
            
            return false;
        }
        
        $notices->add( 'Sample value deleted.', 'Success!', AdminNotices::SUCCESS );
        
        return true;
    }
}

```

and finally the "bootstrapping" that will probably happen in the main plugin file:

```php
namespace MyProject;

use MetaboxOrchestra;

MetaboxOrchestra\Bootstrap::bootstrap();

add_action(
    MetaboxOrchestra\Boxes::REGISTER_BOXES,
    function ( MetaboxOrchestra\Boxes $boxes ) {
		$boxes->add_box( new SampleMetabox() );
	}
);
```

This is more code than it would be necessary with "normal" WordPress procedural approach, but it is modular, it is testable, enables re-usability and composition and it does all the boring repetitive tasks automatically.

Also is not _that_ more: adding proper checks for capability and nonces, adding the code to print the admin notices the "standard" WordPress procedural approach will not take _that_ less code.

Plus, the above snippets print the box on the *term* edit screen: doing it needs a big chunk of code that 
"Metabox Orchestra" does for you.

---



## Requirements

- PHP 7+
- Composer to install

---



## Installation

Via Composer, package name is **`inpsyde/metabox-orchestra`**.

---

## License and Copyright

Copyright (c) 2018 Inpsyde GmbH.

"Metabox Orchestra" code is licensed under [MIT license](https://opensource.org/licenses/MIT).

The team at [Inpsyde](https://inpsyde.com) is engineering the Web since 2006.
