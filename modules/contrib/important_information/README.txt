README.txt for Important Information module
------------

CONTENTS OF THIS FILE
------------

 * Introduction
 * Permissions
 * Installation
 * Dependencies
 * Configuration
 * Maintainers

INTRODUCTION
------------

The Important Information module allows for the constant display of vital
content to the site user.
It provides 5 different ways for this vital content to always be in view for the
user.
The module can be configured to use any combination of the following 5 displays:

 - Important Information Embedded Bottom: A block meant for use embedded on the
   page, usually at the bottom
 - Important Information Floating: A block meant for use in a footing footer,
   using CSS and JS to ensure its always at the bottom floating over the site
 - Important Information Sidebar: A block meant for use in a sidebar, using CSS
   and JS to ensure its always at the right side fixed over the site
 - Important Information Acknowledge: A block meant for use as a modal for all
   first time visitors, a cookie is set to skip this modal for 7 days
 - Important Information Leaving Interstitial: A block meant for use as a modal
   when the user attempts to navigate away from the page


PERMISSIONS
------------
The module allows different permissions:

 - Administer content entities
 - Create, edit and delete each content entity
 - Delete, revert and view revisions for each content entity


INSTALLATION
------------

 - Install the Important Information module as you would normally install a
   contributed Drupal module.
 - Extract the 'important_information' folder from the tar ball to the
   modules directory from your website (typically sites/all/modules).
 - Go to /admin/modules and enable the 'Important Information' module.


DEPENDENCIES
------------

- The Important Information is not dependent of other modules, but it is
  recommended to use it alongside a CKEditor module.
- If content moderation is required, it is recommended to enable Workflows
  and Content Moderation core modules.


CONFIGURATION
------------

1. CREATE CONTENT

Embedded Bottom Container
  - Go to /structure and select Important Information Content List
  - Click on II Add Content, select II Embedded Bottom Container
  - Name. Admin name
  - Content Title. Title shown first in the content
  - Important Information Content. Set the Important Information markup,
    this content also will be shown on Sidebar and Floating containers
  - Collapsible. Embedded Bottom collapses on mobile view
  - Full-size Modal. Enable to provide a button to present the Important
    Information in a full screen modal
  - Modal Size. Percent of the screen modal will cover

  For most sites, the II should always be embedded on the bottom of the
  page, regardless of whether it uses the Sidebar or the Floating container.
  This is the only block that will print

Sidebar Container
  - Go to /structure and select Important Information Content List
  - Click on II Add Content, select II Sidebar Container
  - Name. Admin name
  - Vertical Offset. Set the placement of the Sidebar Container. Also
    serves as the Y position at which absolute switches to fixed if
    that is the behavior option set
  - Container Width. Width of the container
  - Hide Sidebar Container. Sidebar container will disappear once the
    user scrolls to the Embedded Bottom container section
  - Offset. Adjust the calibration of the Embedded Bottom detection.
    Lower than zero makes it happen slower
  - Full-size Modal. Enable to provide a button to present the Important
    Information in a full screen modal
  - Modal Open. On click opens modal window
  - Modal Size. Percent of the screen modal will cover

Floating Container
  - Go to /structure and select Important Information Content List
  - Click on II Add Content, select II Floating Container
  - Name. Admin name
  - Hide Floating Container. Floating container will disappear once the
    user scrolls to the Embedded Bottom container section
  - Offset. Adjust the calibration of the Embedded Bottom detection.
    Lower than zero makes it happen slower
  - Collapsed Height. Percentage of the screen the collapsed Floating
    Container occupies
  - Expand Button. Allows the user to expand the footer to see more
    of the content
  - Expanded Button Text. Markup for the expanded button
  - Un-expanded Button Text. Markup for the un-expanded button
  - Expanded Height. Percentage of the screen the expanded Floating
    Container will cover
  - Full-size Modal. Enable to provide a button to present the Important
    Information in a full screen modal
  - Modal Size. Percent of the screen modal will cover

Acknowledgement Modal
  - Go to /structure and select Important Information Content List
  - Click on II Add Content, select II Acknowledgement Container
  - Name. Admin name
  - Acknowledgement Content. Content for modal box
  - Modal Size. Percent of the screen modal will cover
  - Continue Button. Enable to show a continue button

Leaving Interstitial
  - Go to /structure and select Important Information Content List
  - Click on II Add Content, select II Leaving Interstitial Container
  - Name. Admin name
  - Leaving Interstitial Content. Content for modal box
  - Modal Size. Percent of the screen modal will cover

2. CUSTOM CSS
  If it is necessary to add custom CSS, you can add the content entity
  Custom CSS, it has an example of hiding Floating container for mobile view

  - Go to /structure and select Important Information Content List
  - Click on II Add Content, select II Custom CSS
  - Name. Admin name
  - Custom CSS Content. Type custom CSS code for this module.

3. ASSIGNING THE BLOCKS

- Assign the blocks as per the design and functionality needed. A common
  way to assign blocks is to use the Block Configuration page and placing
  them into a Drupal theme’s region.
- Pages that use Layout Builder for placing blocks and content should use
  the Block Configuration page and placing them into any Drupal theme’s
  region too, except for the Embedded Bottom Block.
- Pages that use Layout Builder for placing blocks and content may need
  to assign specific element ID via their theme’s *.theme file.


AUTHOR / MAINTAINERS
------------

 - Antonio Estevez - https://www.drupal.org/u/tonytosta
 - Gretel Gutierrez - https://github.com/gretel-gutierrez
