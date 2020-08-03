CONTENTS OF THIS FILE
---------------------
   
 * Introduction
 * Requirements
 * Installation
 * Configuration

INTRODUCTION
------------

The PUG (stands for Password Suggestions) module provides
an user interface to configure how should the password
recommendations display on user add/edit form. As it makes
the recommendations labels configurable, it does not apply
any kind of password validations.

This module is useful in the following case:
  * When in-built password validations are working
    and you just need to show the recommendations
    while user type password in add/edit form.
  * When you are following the decouple architecture and
    want to expose the password recommendations to
    the middleware, PUG will be good choice for you.

REQUIREMENTS
------------

This module requires the following:
 * PHP 7.3 or greater
 * Drupal core 8.8.0 or greater

INSTALLATION
------------

 * Strongly recommend installing this module using composer:
   composer require drupal/pug

CONFIGURATION
-------------

 * Visit /admin/config/people/accounts
  (Administration > Configuration > People > Account settings)
 * Make sure the option "Enable password strength indicator" must be checked.

HOW TO USE
-------------
 * Follow the configuration.
 * Goto to user add or edit form
 * Enter you password in the Password field.
 * See the Recommendations under Confirm Password field. Here
  is what you have configured in the settings.

ABOUT PUG REST RESOURCE
-------------

 * If you have enabled REST UI module then:
   Goto, (/admin/config/services/rest)
   Find the API by name - Password Suggestions
   Just enable and use it.
