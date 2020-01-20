# TYPO3 CMS Extension "fluid_styled_responsive_images"

![Build Status](https://github.com/alexanderschnitzler/fluid-styled-responsive-images/workflows/CI/badge.svg?branch=master)
[![Codecov](https://img.shields.io/codecov/c/github/alexanderschnitzler/fluid-styled-responsive-images)](https://codecov.io/gh/alexanderschnitzler/fluid-styled-responsive-images)

[![Total Downloads](https://poser.pugx.org/schnitzler/fluid-styled-responsive-images/downloads)](https://packagist.org/packages/schnitzler/fluid-styled-responsive-images)
[![Latest Stable Version](https://poser.pugx.org/schnitzler/fluid-styled-responsive-images/v/stable)](https://packagist.org/packages/schnitzler/fluid-styled-responsive-images)
[![Latest Unstable Version](https://poser.pugx.org/schnitzler/fluid-styled-responsive-images/v/unstable)](https://packagist.org/packages/schnitzler/fluid-styled-responsive-images)
[![License](https://poser.pugx.org/schnitzler/fluid-styled-responsive-images/license)](https://packagist.org/packages/schnitzler/fluid-styled-responsive-images)

This project aims to provide an image-rendering process that will render
responsive images with fluid, where the assumption is made, that TYPO3 CMS
doesn't provide a mechanism out of the box for *fluid templates* while one
is able to do it with TypoScript.

* [Installation](#installation)
* [Configuration](#configuration)
  + [Mode `srcset`](#mode--srcset-)
    - [Configuration Example](#configuration-example)
* [Usage](#usage)
* [Inner workings](#inner-workings)
* [Extending `fluid_styled_responsive_images`](#extending--fluid-styled-responsive-images-)
* [License](#license)

## Installation

* Install via composer using the current stable release and track new stable releases: `composer require schnitzler/fluid-styled-responsive-images:"^10.2"` and enable the extension through the extension manager / your preferred mechanism
* Use the current development version by running `composer require schnitzler/fluid-styled-responsive-images:"dev-master"` in your `composer.json` file, run `composer update`
* Clone the current development version to your `typo3conf/ext` directory (ex. `cd typo3conf/ext && rm -Rf fluid_styled_responsive_images && git clone https://github.com/alexanderschnitzler/fluid-styled-responsive-images.git fluid_styled_responsive_images`)

## Configuration

The extension is configured through TypoScript, like most parts of your site are.
Include the static TypoScript of the extension and then begin with the Configuration
through your own TypoScript setup.

The ImageRenderer currently supports the [`srcset`](srcset specification) and
rendering as `data`-attributes, which is to make custom rendering with javascript
easier.

Minimal, empty configuration:

```
tt_content.textmedia.settings.responsive_image_rendering {
    layoutKey = srcset

    sourceCollection {
        # Please write your own sourceCollection configuration
    }
}
```

### Mode `srcset`

A `sourceCollection` entry is a TypoScript hash. It can contain the following indices:

| key              | description                                                                   | example                          |
|------------------|-------------------------------------------------------------------------------|----------------------------------|
| width            | The target size of the generated image. Supports modifications like `m` & `c` | 1200c (crops the image to 1200px)|
| srcset           | a string describing the condition under which the image is displayed          | `1200w` (1200px viewports)       |
| dataKey          | a name for the generated data-attribute                                       | `desktop`                        |
| sizes [optional] | a media query with custom styles to be applied                                | `(min-width: 1200px) 1170px`     |

For more precise descriptions, please check out the [html `img` element specification
on srcset](http://w3c.github.io/html/semantics-embedded-content.html#element-attrdef-img-srcset).

#### Configuration Example

```
tt_content.textmedia {
    settings {
        responsive_image_rendering {
            layoutKey = srcset

            sourceCollection {
                10 {
                    dataKey = desktop
                    width = 1260m
                    srcset = 1260w
                }

                20 {
                    dataKey = table
                    width = 960m
                    srcset = 960w
                }

                30 {
                    dataKey = tablet-small
                    width = 720m
                    srcset = 720w
                }

                40 {
                    dataKey = medium
                    width = 640m
                    srcset = 640w
                }

                50 {
                    dataKey = medium-phone
                    width = 360m
                    srcset = 360w
                }

                60 {
                    dataKey = small
                    width = 320m
                    srcset = 320w
                }
            }
        }
    }
}
```

## Usage

After installation and configuration, the output of the `<f:media>` viewhelper
uses the logic this extension supplies for images and renders the image.

## Inner workings

1. a custom image renderer is registered
2. when the `RendererRegistry` is asked for a renderer suitable for the current mimetype,
   the renderer proposes itself if one of the known image mimetypes is matched
3. the renderer reads the current TypoScript and merges it with global extension
   configuration like the `enableSmallDefaultImage` setting
4. the renderer then calculates the needed sizes and returns a ready-made `img`-tag

## Extending `fluid_styled_responsive_images`

Since fluid_styled_content is used, much of the output in TYPO3 CMS can be adjusted.

Example of registering custom templates and adjusting images in collaboration with
the `GalleryProcessor` in fluid_styled_content to provide precise rendering with
Bootstrap 3 based templates: [websightgmbh/ws-texmedia-bootstrap](https://github.com/websightgmbh/typo3-ws_textmedia_bootstrap).

## License

GPL-2.0+
