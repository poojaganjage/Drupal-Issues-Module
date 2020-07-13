/**
 * @file
 * Messages.
 */

(Drupal => {
  const messages = document.querySelectorAll('.js-messages');

  messages.forEach(el => {
    const messageContainer = el.querySelector('.js-messages__container');

    const closeBtnWrapper = document.createElement('div');
    closeBtnWrapper.setAttribute('class', 'js-messages__button');

    const closeBtn = document.createElement('button');
    closeBtn.setAttribute('type', 'button');
    closeBtn.setAttribute('class', 'js-messages__close');

    const closeBtnText = document.createElement('span');
    closeBtnText.setAttribute('class', 'js-visually-hidden');
    closeBtnText.innerText = Drupal.t('Close message');

    messageContainer.appendChild(closeBtnWrapper);
    closeBtnWrapper.appendChild(closeBtn);
    closeBtn.appendChild(closeBtnText);

    closeBtn.addEventListener('click', () => {
      el.classList.add('hidden');
    });
  });
})(Drupal);
