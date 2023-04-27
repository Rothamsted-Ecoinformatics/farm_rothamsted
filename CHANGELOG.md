# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Add Planning status to experiment and design entities. [#402](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/402)
- Add Physical Obstructions restriction option. [#406](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/406)
- Add requested location field to proposal entity. [#409](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/409)
- Add status messages in quick forms if required taxonomy hierarchy does not exist. [#360](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/360)
- Add initial quote field to proposal entity. [#411](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/411)
- Add agreed quote file field to experiment plan. [#415](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/415)
- Add filter tabs for experiment plans. [#414](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/414)

### Changed

- Change Related Programs text to be Related Research Programs for clarity. [#403](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/403)
- Add treatment checkbox in UI to allow adding a rotation separate from rotation as treatment. [#404](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/404)
- Experiment plan deviations tab and make fields multivalue. [#405](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/405)
- Change proposal statistical design to text_long. [#407](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/407)
- Split proposal Design tab in two. [#408](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/408)
- Remove operations links from research entities table views. [#412](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/412)
- Remove bulk operations from experiment plan views. [#396](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/396)

## [2.10.1](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/milestone/16) 2023-04-05

### Changed

- Move rotation tab to design entity. [#400](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/400)

## [2.10.0](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/milestone/16) 2023-03-28

### Added

- Add farm_rothamsted_researcher module [#354](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/354)
- Add farm_rothamsted_experiment_research module [#298](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/298)
- Add farm_rothamsted_notification module and send CRUD emails [#299](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/299)
- Add farm_rothamsted_date date format
- Allow editing columns and column levels. [#276](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/276)
- Allow editing plot geometry. [#337](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/337)
- Add fields to experiment plan. [#358](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/358)
- Add proposal entity. [#380](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/380)

### Changed

- Process experiment plot uploads in a batch operation. [#335](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/335)
- Link plot attributes to column and level names. [#275](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/275)
- Hide help text below text format form fields.

## [2.9.3](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/milestone/15) 2023-02-14

### Fixed

- Specify quick form quantities to be standard. [#359](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/359)
- Update previously submitted material quantities to be standard. [#359](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/359)

## [2.9.2](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/milestone/15) 2022-12-12

### Fixed

- Replace @codingStandards with phpcs:ignore. [#349](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/349)
- Add quantity permissions to rothamsted sponsor role. [#347](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/347)
- Quick form locations slow to load with many plots. [#345](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/345)

## [2.9.1](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/milestone/15) 2022-11-29

### Changed

- Depend on farmOS ^2.0.0-beta8.

### Fixed

- Make experiment admin and sponsor roles managed roles. [#76](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/76)

## [2.9.0](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/milestone/15) 2022-11-21

### Added

- Add view of uncategorized logs. [#309](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/309)
- Add validation for plot types in upload form.
- Add baseline and seed_multiplication plot types. [#333](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/333)
- Add seed_dressing field to drilling logs. [#311](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/311)
- Add separate views of experiment logs in secondary tabs. [#303](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/303)
- Add calibration plot type. [#205](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/205)
- Add sponsor and experiment admin role [#76](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/76)
- Run PHPUnit tests in github action.

### Changed

- Migrate contacts to a user reference field. [#322](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/322)
- Replace field locations with location asset reference. [#314](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/314)
- Save crop and variety in drilling and commercial quick forms. [#310](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/310)
- Change plot assets to not default as locations. [#336](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/336)

### Fixed

- Only display experiment flags on experiment plan view.

### Removed

- Remove asset parent action to use core action. [#321](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/321)

## [2.8.0](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/milestone/13) 2022-10-28

### Added

- Add drain structure type with support in quick forms. [#316](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/316)
- Save selected location to quick form log location. [#315](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/315)
- Create action for bulk assigning the asset's parent. [#283](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/283)
- Add update hook for log categories.  [#288](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/288)

### Changed

- Break column_descriptors filter into separate filter for each column. [#305](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/305)
- Add asset checkboxes for each location. [#289](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/289)

### Fixed

- File validation error does not prevent from submission. [#318](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/318)
- Cannot prepopulate assets into quick form. [#307](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/307)

## [2.7.0](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/milestone/12) 2022-09-27

### Added

- Add location field to experiment upload form.  [#259](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/259)
- Add validation for allowed column types.  [#279](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/279)
- Add experiment boundary land type. [#292](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/292)
- Add experiment link fields for external documents. [#284](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/284)

### Changed

- Remove add crop logic from experiment upload form.  [#235](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/235)
- Include plant assets that have been moved to sub-locations in quick form asset field. [#293](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/293)

### Fixed

- Single product material quantities not saving. [#297](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/297)

## [2.6.0](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/milestone/11) 2022-09-12

### Added

- Add All locations layers to experiment plots map.  [#261](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/261)
- Add log category question to all experiment quick forms.  [#160](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/160)
- Add justification/target to operations quick form.  [#228](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/228)

### Changed

- Display plot numbers in plots tab. [#264](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/264)
- Move start time and tractor hours to setup tab. [#233](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/233)
- Change experiment surrounds to experiment boundary. [#256](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/256)
- Minor amendments to feriliser and spraying quick form fields. [#237](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/237)
- Filter assets by location in quick forms. [#260](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/260)

### Fixed

- Archived assets still appearing in quick forms. [#251](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/251)

## 2.5.2 2022-08-15

### Fixed

- Trailer Quick Form: single quantities not saving. [#249](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/249)
- Experiment Module: Error if plot numbers don't start at one [#248](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues/248)

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
