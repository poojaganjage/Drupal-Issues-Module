
CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Included Modules and Features
 * Recommended Modules
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

This module allows content creators to embed code snippets from github
repositories by using "github_embed" token inside the text editor.


REQUIREMENTS
------------

This module has no additional requirements outside the Drupal core.


INCLUDED MODULES AND FEATURES
-----------------------------

This module does not include any additional modules / features.


RECOMMENDED MODULES
-------------------

This module does not need recommend any additional module.


INSTALLATION
------------

 - Install the module as you would normally install a contributed Drupal
   module. Visit https://www.drupal.org/node/1897420 for further information.


CONFIGURATION
-------------

1. Enable the module.

2. Go to: "Configuration" > "Content authoring" > "Text formats and editors" or
go to admin page URL "admin/config/content/formats".

3. Enable "Github embed" filter for your desired format and configure
the Github filter settings (base URL, username, repository).

4. Use "github_embed" token inside your text editor to embed code from
github.com

There are two token variants available:

  - [github_embed:BRANCH-NAME/FILENAME]
  This token uses default repository settings set by filter, so you only
  need to specify branch name and a file name.

  E.g.: [github_embed:master/src/Plugin/Block/MyContentBlock.php]

  - [github_embed:file:FULL-FILEPATH]
  In this case we need to explicitly specify full repository details in the file
  path (username, repository name, branch and file name).

  E.g.: [github_embed:file:my-username/my-repository-name/master/src/MyCode.php]


MAINTAINERS
-----------

  - Borut Piletic (borutpiletic) - https://www.drupal.org/u/borutpiletic
