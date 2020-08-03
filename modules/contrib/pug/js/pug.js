/**
 * PUG js file.
**/

(function ($, Drupal, drupalSettings) {

  Drupal.evaluatePasswordStrength = function (password, translate) {
    password = password.trim();
    var indicatorText = void 0;
    var indicatorClass = void 0;
    var weaknesses = 0;
    var strength = 100;
    var msg = [];

    var hasLowercase = /[a-z]/.test(password);
    var hasUppercase = /[A-Z]/.test(password);
    var hasNumbers = /[0-9]/.test(password);
    var hasPunctuation = /[^a-zA-Z0-9]/.test(password);

    var $usernameBox = $('input.username');
    var username = $usernameBox.length > 0 ? $usernameBox.val() : translate.username;

    if (password.length < translate.passwordLength) {
      msg.push(translate.tooShort);
      strength -= (translate.passwordLength - password.length) * 5 + 30;
    }

    if (!hasLowercase) {
      msg.push(translate.addLowerCase);
      weaknesses++;
    }
    if (!hasUppercase) {
      msg.push(translate.addUpperCase);
      weaknesses++;
    }
    if (!hasNumbers) {
      msg.push(translate.addNumbers);
      weaknesses++;
    }
    if (!hasPunctuation) {
      msg.push(translate.addPunctuation);
      weaknesses++;
    }

    switch (weaknesses) {
      case 1:
        strength -= translate.passwordLength + .5;
        break;

      case 2:
        strength -= 25;
        break;

      case 3:
        strength -= 40;
        break;

      case 4:
        strength -= 40;
        break;
    }

    if (password !== '' && password.toLowerCase() === username.toLowerCase()) {
      msg.push(translate.sameAsUsername);

      strength = 5;
    }

    if (strength < 60) {
      indicatorText = translate.labelWeak;
      indicatorClass = 'is-weak';
    } else if (strength < 70) {
      indicatorText = translate.labelFair;
      indicatorClass = 'is-fair';
    } else if (strength < 80) {
      indicatorText = translate.labelGood;
      indicatorClass = 'is-good';
    } else if (strength <= 100) {
      indicatorText = translate.labelStrong;
      indicatorClass = 'is-strong';
    }

    msg = translate.hasWeaknesses + '<ol><li>' + msg.join('</li><li>') + '</li></ol>';

    return {
      strength: strength,
      message: msg,
      indicatorText: indicatorText,
      indicatorClass: indicatorClass
    };
  };
})(jQuery, Drupal, drupalSettings);
