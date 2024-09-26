# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.3.0] - 2024-09-26

### Added
- Support of woocommerce checkout blocks. (CMS-68)

### Changed
- Change the cb logo to the new logo (CMS-173)

### Fixed
- Fix plugin sometimes being active when is shouldn't (CMS-171)
- Fix payment description not showing correctly on redirect mode (CMS-137)


## [1.2.6] - 2024-08-08

### Added
- An automated script to help translation on `README` (CMS-179)

### Fixed
- Fix subscription description warning appearing when not useful (CMS-184)


## [1.2.5] - 2024-07-30

### Added
- Add Choice for order ID or subscription ID for renewal payments (CMS-123)
- Add renewal description customization for admins (CMS-123)

### Changed
- Changed Settings to better express the requirement of payment description (CMS-103)
- Adding deprecation messages for Popup (CMS-145)

### Fixed
- Fix Description of bad size can no longer cause problem with the payment process (CMS-103)
- Fix Admin scripts having a broader scope than intended (CMS-159)


## [1.2.4] - 2024-06-20

### Changed
- Refact devcontainer (CMS-98)

### Fixed
- Declaring [HPOS](https://woocommerce.com/document/high-performance-order-storage/) compatibility (CMS-126)
- Fix three product's refund considered as partial refund (CMS-118)
- Fix iframe payment method creating multiple payment when clicked fast enough (CMS-112)
- Fix Tag number beeing too many according to Wooommerce's guideline (CMS-114)


## [1.2.3] - 2024-04-25

### Deprecated
- Deprecate Popup page type Because of security problems, consider using other page types

### Fixed
- Fix the integration of the new payment page


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
