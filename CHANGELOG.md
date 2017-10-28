# Changelog

## 0.2.0
- Added `.travis.yml`
- Added more links to README.

## 0.1.0
- First release

## 0.1.1
- Minor, no logic change to AdminNotices
- Introduce unit tests

## 0.2.0
- Introduce `Entity` object to wrap supported objects for metaboxes (currently `\WP_Post` ad `\WP_Term`)
- **[BREAKING]** `Metabox::create_info()` new requires two arguments, the second being current `Entity`.