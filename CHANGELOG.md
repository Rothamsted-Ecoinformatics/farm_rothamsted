# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Fixed

- Trailer Quick Form: single quantities not saving. [#249](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/249)

## [2.5.1](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/milestone/7) 2022-08-05

### Added

- Add harvest year to commercial asset quick form. [#229](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/229)

### Changed

- Don't require variety on commercial asset quick form. [#243](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/243)

### Fixed

- Substitute invalid UTF-8 characters when encoding column_descriptors json. [#245](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/245)
- Use correct php assert statement in column descriptor formatter. [#246](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/246)

## [2.5.0](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/milestone/7) 2022-07-29

### Added

- Add plot flag types. [#206](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/206)
- Add flags for plot restrictions. [#231](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/231)

### Changed

- Update plot type options. [#205](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/205)
- Change experiment treatment_factors to column_descriptors. [#190](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/190)
- Expand experiment data model for column types. [#223](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/223)

## [2.4.0](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/milestone/5) 2022-07-12

### Added

- Add optional Machine yield estimate quantity to combine harvest quick form. [#193](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/193)
- Add harvest storage location. [#192](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/192)

### Changed

- Allow multiple trailer weights in each Trailer harvest record. [#203](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/203)
- Require drupal/gin 3.0.0-beta5. [#212](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/212)

### Removed

- Remove number of bales on trailer quantity from Trailer harvest. [#203](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/203)

## [2.3.0](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/milestone/4) 2022-06-13

### Added

- Field formatter to render plan treatment_factors field in tables. [#162](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/162)
- Add view of logs for experiment plan. [#170](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/170)
- Add form field to change assets current location. [#196](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/196)

### Changed

- Only display plot type values used in the plan. [#180](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/180)

### Fixed

- Allow filtering by factor levels of the same value. [#200](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/200)
- Pin Gin to fix vertical tab whitespace issue. [#208](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/208)

## [2.2.0](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/milestone/3) 2022-05-24

### Changed

- Update the wind direction description. [#171](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/171)
- Change product application rate units to use "Volume per unit area" units. [#189](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/189)
- Copy the current asset location into the log location in quick forms. [#183](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/183)

## Fixed

- Fix bug that created empty material quantities.

## [2.1.0](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/milestone/2) 2022-05-06

### Added

- Support creating commercial assets with multiple locations. [#179](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/179)

### Changed

- Add plot fields to identify plots by serial id. [#166](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/166)
- Commercial asset naming convention. [#173](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/173)

### Fixed

- Allow duplicate factor levels across different treatment factors. [#168](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/168)
- Display validation error message in quick forms with vertical tabs. [#176](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/176)

## 2.0.3 2022-04-27

### Fixed

- Fix ArgumentCountError breaking quick forms. [eef221c](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/commit/eef221c8b657b9acf81d940d0e05c5040e34b9ed)

## 2.0.2 2022-04-04

### Added

- Add custom view of experiment plans. [#148](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/148)

## 2.0.1 2022-03-30

### Fixed

- Miscellaneous fixes. See #146, #147, #150, #154.

## 2.0.0 2022-03-19

The initial 2.0.0 release of this module for farmOS 2.x.

## 7.x-1.x

This module was first created for farmOS 1.x. See the [7.x-1.x branch](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/commits/7.x-1.x)
