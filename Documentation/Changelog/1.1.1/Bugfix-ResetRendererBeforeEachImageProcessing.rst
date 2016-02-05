===================================================
Bugfix: Reset renderer before each image processing
===================================================

Description
===========

When using an image element with more than one file reference attached, the image renderer hasn't been properly reset, causing the generation of invalid ``img`` tags.
