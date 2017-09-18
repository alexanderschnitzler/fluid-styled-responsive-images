8.7.1 / 17-09-19
==================

  * 2017-09-18  3c81598  [TASK] Add TYPO3 8.7.3 and 8.7.4 to testing matrix (Alexander Schnitzler)
  * 2017-09-18  2d6b793  [BUGFIX] Fix cropping with TYPO3 >= 8.7.4 (Devid Messner)

8.7.0 / 17-08-06
==================

  * 2017-08-06  940a966  [BUGFIX] Respect new cropping format during image rendering (Heiko Bihlmaier)
  * 2017-08-06  d65359d  [TASK] Add functional tests to check the new cropping functionality (Alexander Schnitzler)
  * 2017-08-06  f911935  [TASK] Adjust tests for TYPO3 8.7 (Alexander Schnitzler)
  * 2017-08-06  6e2df51  [TASK] Add .gitattributes (Alexander Schnitzler)
  * 2017-08-06  e034f46  [TASK] Prepare master to become TYPO3 7 LTS branch (Alexander Schnitzler)
  * 2017-06-29  824c04b  [TASK] Make code PSR-2 compatible (Alexander Schnitzler)
  * 2017-06-29  34b23d5  [BUGFIX] Set width and height correctly (Alexander Schnitzler)
  * 2017-06-29  859b8a6  [TASK] Implement code climate analysis (Alexander Schnitzler)
  * 2017-06-29  623303e  [TASK] Implement functional tests (Alexander Schnitzler)
  * 2017-03-22  6e51673  TYPO3 8 compatibility (#27) (Cedric Ziel)
  * 2017-03-07  e9933e3  [TASK] Mention dataKey in README (Cedric Ziel)
  * 2017-03-07  8bca7ad  [TASK] Run travis tests with current TYPO3 CMS 8.6 (Cedric Ziel)
  * 2016-11-08  662623c  [TASK] Add a table of contents to the README (Cedric Ziel)
  * 2016-11-08  30f7b2a  [TASK] Ignore composer lock and exclude vendor files (Cedric Ziel)
  * 2016-11-08  5338244  [TASK] Provide documentation through a small README (Cedric Ziel)
  * 2016-11-06  4c7664c  [TASK] Adjust test to w3c compliance (Cedric Ziel)
  * 2016-11-06  13b3cc6  [TASK] Avoid sizes attribute if no srcset attribute set. (Claus Fassing)

1.2.0 / 16-05-23
==================

  * 2016-05-23  37134d2  [TASK] Raise version number (Alexander Schnitzler)
  * 2016-05-23  093650d  [TASK] Add documentation for version 1.2.0 (Alexander Schnitzler)
  * 2016-04-08  8d739f1  [TASK] Enable CI builds for TYPO3 8.0.0 and dev-master (Alexander Schnitzler)
  * 2016-04-05  b78adbe  [FEATURE] Make small default image optional (Alexander Schnitzler)
  * 2016-04-05  958b865  [TASK] Enable Travis CI builds (Alexander Schnitzler)
  * 2016-04-05  aeca6ec  [FEATURE] Respect attributes of MediaViewHelper (Alexander Schnitzler)

1.1.1 / 16-02-05
==================

  * 2016-02-05  731fb6d  [BUGFIX] Reset renderer before each image processing (Alexander Schnitzler)

1.1.0 / 16-02-05
==================

  * 2016-02-05  15baf8a  [TASK] Use short array syntax (Alexander Schnitzler)
  * 2016-02-05  aec3df8  [TASK] Replace instanceof check with is_callable (Alexander Schnitzler)
  * 2016-02-05  ad2e8d0  [TASK] Add missing documentation (Alexander Schnitzler)
  * 2016-02-05  065da47  [!!!][BUGFIX] Set 360 pixels as default image width (Alexander Schnitzler)
  * 2016-02-05  41191d4  [TASK] Extract configuration part into ImageRendererConfiguration (Alexander Schnitzler)
  * 2016-02-04  e6b517c  [TASK] Split code into multiple methods (Alexander Schnitzler)
  * 2016-01-30  8dad51f  [TASK] Add unit tests (Alexander Schnitzler)
  * 2016-01-30  471d86d  [TASK] Set default layoutKey to srcset (Alexander Schnitzler)
  * 2016-01-30  72768a9  [TASK] Add missing ext_icon.png (Alexander Schnitzler)
  * 2016-01-30  8877c03  [BUGFIX] Fix wrong path generation with config.absRefPrefix = auto (Alexander Schnitzler)
  * 2016-01-30  88d486a  [TASK] Raise depencency version of typo3 to 7.6.0 (Alexander Schnitzler)
  * 2016-01-30  605b995  [BUGFIX] Add fluid_styled_content as dependency (Alexander Schnitzler)
  * 2016-01-30  0880fec  [TASK] Add dependency to typo3/cms >= 7.6.0 (Alexander Schnitzler)

1.0.2 / 15-11-21
==================

  * 2015-11-21  4bcd1ca  [BUGFIX] Set alt and title from merged properties (Alexander Schnitzler)

1.0.1 / 15-10-29
==================

  * 2015-10-29  4ce3c44  [!!!][TASK] Replace srcsetCandidate with srcset configuration (Alexander Schnitzler)
  * 2015-10-29  8db07a0  [!!!][TASK] Replace mediaQuery with sizes configuration (Alexander Schnitzler)
  * 2015-10-29  d5825fc  [!!!][TASK] Remove default sourceCollection configuration (Alexander Schnitzler)
  * 2015-10-29  c47de08  [TASK] Add basic documentation structure for changelogs (Alexander Schnitzler)
  * 2015-10-29  139eee4  [BUGFIX] Always create sizes attribute, even if it is empty (Alexander Schnitzler)
  * 2015-10-29  c40be71  [BUGFIX] Avoid creating large images if not possible (Alexander Schnitzler)
  * 2015-10-29  e426f7e  [BUGFIX] Ignore height of original image processing configuration (Alexander Schnitzler)

1.0.0 / 15-10-26
==================

  * 2015-10-26  ee9b5c4  Initial commit
