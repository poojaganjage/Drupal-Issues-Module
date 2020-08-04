## About

Simple live css is a D8 rewrite of https://www.drupal.org/project/live_css,
but with basic features only.

CSS can be added within the page, with live preview. The changes can then
be saved to a CSS file that will be added to every non-admin page. Its
primary usage purpose is (temporarily) overriding existing css.

## Installation

Add the block-ui and ace-builds libraries to your project's composer.json,
which are defined as a dependency in this module:

```
"repositories": [
    {
      "type": "package",
      "package": {
        "name": "library-blockui/blockui",
        "version": "2.70",
        "type": "drupal-library",
        "dist": {
          "url": "https://github.com/malsup/blockui/archive/2.70.zip",
          "type": "zip"
        }
      }
    },
    {
      "type": "package",
      "package": {
        "name": "ajaxorg/ace-builds",
        "version": "1.4",
        "type": "drupal-library",
        "dist": {
          "url": "https://github.com/ajaxorg/ace-builds/archive/v1.4.7.zip",
          "type": "zip"
        }
      }
    },
  ]
```

Install using composer: `composer require drupal/simple_live_css`

## Configuration

Enable the 'Edit live CSS' permission.
