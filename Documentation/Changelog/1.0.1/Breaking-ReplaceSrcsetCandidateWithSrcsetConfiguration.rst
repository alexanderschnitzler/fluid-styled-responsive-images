===================================================================
Breaking: Replace srcsetCandidate with srcset configuration
===================================================================

Description
===========

The ``sourceCollection`` configuration currently uses the ``srcsetCandidate`` key for the definition of srcset in combination with sizes. As part of an attempt to unify the configuration, ``srcsetCandidate`` will be replaced with ``srcset``


Impact
======

Existing configurations that rely on the ``srcsetCandidate`` setting need to replace the key with ``srcset``. If not, the srcset attribute of image tags will be missing.


Affected Installations
======================

All installations that use the ``srcsetCandidate`` setting are affected.


Migration
=========

Simply replace srcsetCandidate with srcset.

Old:

.. code-block:: typoscript

	tt_content.textmedia.settings.responsive_image_rendering.sourceCollection {
	  small {
	    width = 360m
	    srcsetCandidate = 360w
	    sizes = 100vw
	  }
	}

New:

.. code-block:: typoscript

	tt_content.textmedia.settings.responsive_image_rendering.sourceCollection {
	  small {
	    width = 360m
	    srcset = 360w
	    sizes = 100vw
	  }
	}
