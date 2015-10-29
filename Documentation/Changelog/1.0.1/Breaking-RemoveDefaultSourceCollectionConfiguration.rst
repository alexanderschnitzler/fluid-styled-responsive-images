=======================================================
Breaking: Remove default sourceCollection configuration
=======================================================

Description
===========

The initial version of the extension provided a default ``sourceCollection`` configuration. It had simply been copied from the ``css_styled_content`` extension without further thinking about it. Unfortunately this configuration doesn't make any sense as each and every website is different and the srcset and sizes attributes of images heavily rely on the given layout. Therefore the whole default configuration will be removed.


Impact
======

When entirely relying on the default configuration, image tags will not be rendered as desired any more.


Affected Installations
======================

All installations that rely on the default configuration.


Migration
=========

Write your very own configuration that suits your website.