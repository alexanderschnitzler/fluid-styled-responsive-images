===================================================================
Breaking: Set 360 pixels as default image width
===================================================================

Description
===========

The width of the default image, the one that is set as ``src`` of any responsive image, is now set to a sensible default of 360 pixels. Browsers, that are not yet able to render responsive images without a polyfill, do load the initial image first. Currently, that image can be huge and therefore can have a decent impact on the website speed.


Impact
======

Actually most people will perceive this change as some kind of bugfix because the default image is much smaller now, resulting in faster website loading speed. In case one relied on the bug, that the default image didn't have a maximum size, one might need to write an additional sourceCollection rule for big images.

Current sourceCollection configurations should be adapted. If an own rule is close to the default of 360 pixels, I do recommend to drop that rule.


Affected Installations
======================

All installations.


Migration
=========

None, adapt configuration to your needs.