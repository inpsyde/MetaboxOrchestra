# Changelog

## 0.4.0
- Save post boxes even if post has empty content and WordPress will not proceed in save it (so `"wp_insert_post"` is not triggered).
- Introduce `"metabox-orchestra.save-on-empty-post"` hook to add the possibility to disallow box saving if content is empty. 

## 0.3.4
- Added `Entity::id()` to obtain a type-safe id of wrapped entity and mark entity as not valid if its id is <= 0.

## 0.3.3
- Added three new hooks: before showing boxes and before and after saving them.
- Improved `Entity` object constructor, now accepts an instance of another `Entity`.

## 0.3.2
- Prevent recursion when editing post/term object form inside a `BoxAction::save()` method.

## 0.3.1
- Fix a bug in Boxes class.
- Update README code sample.

## 0.3.0
- Introduce `Entity` object to wrap supported objects for metaboxes (currently `\WP_Post` ad `\WP_Term`)
- **[BREAKING]** `Metabox::create_info()` new requires two arguments, the second being current `Entity`.

## 0.2.0
- Added `.travis.yml`
- Added more links to README.
- Added more tests.

## 0.1.1
- Minor, no logic change to AdminNotices
- Introduce unit tests