==========================================
Feature: Make small default image optional
==========================================

Description
===========

Since version 1.1.0, the ``src`` attribute of responsive images contains a 360px wide image which is quite useful when
working with polyfills, so the browser doesn't initially load a huge image before loading the desired image. I still
consider this a good idea but from this version on, one can enable/disable that feature in the extension configurations.
