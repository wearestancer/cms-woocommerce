# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [unreleased]

### Deprecated
- Deprecate Popup page type Because of security problems, consider using other page types.

### Fixed
- Fix the integration of the new payment page.


## [1.2.2] - 2024-04-02

### Fixed
- Fix config bug (CMS-116)
- Fix a bug that changed order statuses (CMS-117)


## [1.2.1] - 2024-03-22

### Fixed
- Fix some locales
- Fix scoped dependencies on PHP 7.4


## [1.2.0] - 2024-03-22

### Added
- Alert is shown if API keys are missing (CMS-33)
- Refund option to Woo back-office (CMS-70)

### Fixed
- Scoping dependencies to prevent conflicts (CMS-109)
- Some errors on payment receipt (CMS-50)


## [1.1.2] - 2024-02-19

### Fixed
- Amount could not have decimals depending on Woo settings


## [1.1.1] - 2024-02-09

### Changed
- Cleaner TS file


## [1.1.0] - 2024-02-08

### Added
- Auto-publish to WordPress SVN
- Automatically create an archive for every merge request (CMS-88)
- New integration mode "Inside the page"
- Support [Woo Subscriptions](https://woo.com/products/woocommerce-subscriptions/)

### Changed
- iFrame security (CMS-66)
- Optimize autoloader

### Removed
- `timeout` setting, will be reintegrated later

### Fixed
- Some errors may not be catch by the module


## [1.0.0] - 2023-04-18

- First implementation
