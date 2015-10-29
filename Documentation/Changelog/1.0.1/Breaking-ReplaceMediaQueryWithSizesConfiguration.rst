===================================================================
Breaking: Replace mediaQuery with sizes configuration
===================================================================

Description
===========

The ``sourceCollection`` configuration currently uses the ``mediaQuery`` key for the definition of sizes in combination with srcset. Unfortunately ``mediaQuery`` is misleading and not the right term, so instead it will be replaced with ``sizes``


Impact
======

Existing configurations that rely on the ``mediaQuery`` setting need to replace the key with ``sizes``. If not, the sizes attribute of image tags will remain empty.


Affected Installations
======================

All installations that use the ``mediaQuery`` setting are affected.


Migration
=========

Simply replace mediaQuery with sizes.

Old:

.. code-block:: typoscript

	tt_content.textmedia.settings.responsive_image_rendering.sourceCollection {
	  small {
	    width = 360m
	    srcsetCandidate = 360w
	    mediaQuery = 100vw
	  }
	}

New:

.. code-block:: typoscript

	tt_content.textmedia.settings.responsive_image_rendering.sourceCollection {
	  small {
	    width = 360m
	    srcsetCandidate = 360w
	    sizes = 100vw
	  }
	}
