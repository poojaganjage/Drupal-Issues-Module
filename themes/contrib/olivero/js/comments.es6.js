/**
 * @file
 * Comments.
 */

(Drupal => {
  const indentedComments = document.querySelectorAll('.js-comments .js-indented');

  document.querySelectorAll('.js-comment').forEach(comment => {
    if (
      comment.nextElementSibling != null &&
      comment.nextElementSibling.matches('.js-indented')
    ) {
      comment.classList.add('has-children');
    }
  });

  indentedComments.forEach(commentGroup => {
    const showHideWrapper = document.createElement('div');
    showHideWrapper.setAttribute('class', 'show-hide-wrapper');

    const toggleCommentsBtn = document.createElement('button');
    toggleCommentsBtn.setAttribute('type', 'button');
    toggleCommentsBtn.setAttribute('aria-expanded', 'true');
    toggleCommentsBtn.setAttribute('class', 'show-hide-btn');
    toggleCommentsBtn.innerText = Drupal.t('Replies');

    commentGroup.parentNode.insertBefore(showHideWrapper, commentGroup);
    showHideWrapper.appendChild(toggleCommentsBtn);

    toggleCommentsBtn.addEventListener('click', e => {
      commentGroup.classList.toggle('hidden');
      e.currentTarget.setAttribute(
        'aria-expanded',
        commentGroup.classList.contains('hidden') ? 'false' : 'true',
      );
    });
  });
})(Drupal);
