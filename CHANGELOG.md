# Changelog

## _Unreleased_
- Prevent recursion when editing post/term object form inside a `BoxAction::save()` method.

## 0.1.1
- Minor, no logic change to AdminNotices
- Introduce unit tests

## 0.2.0
- Added `.travis.yml`
- Added more links to README.
- Added more tests.

## 0.3.0
- Introduce `Entity` object to wrap supported objects for metaboxes (currently `\WP_Post` ad `\WP_Term`)
- **[BREAKING]** `Metabox::create_info()` new requires two arguments, the second being current `Entity`.

## 0.3.1
- Fix a bug in Boxes class
- update README code sample.