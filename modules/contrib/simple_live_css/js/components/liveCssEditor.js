(function ($) {

    'use strict';

    /**
     * The LiveCssEditor constructor.
     *
     * @constructor
     */
    let LiveCssEditor = function() {
      this.body = $('body');
      this.html = $('html');

      this.link = $("link[href*='" + drupalSettings.simple_live_css.css_file_path + "']");
      this.button = $("<button class='js--live-css-button live-css-button'>" + Drupal.t('LIVE CSS') + "</button>");

      this.ajaxSaveIsTriggered = false;
      this.liveCssService = new LiveCssService();

      this.initButton();
      this.initEventListeners();
    };

    /**
     * Initialize the live css button.
     */
    LiveCssEditor.prototype.initButton= function() {
      this.body.prepend(this.button);
    };

    /**
     * Initialize live css editor.
     */
    LiveCssEditor.prototype.initEditor = function(code) {

      // Set up the initial containers.
      this.body.after(this.buildEditorContainerMarkup());
      this.body.after(this.buildInjectContainerMarkup());

      this.editor = ace.edit('js--live-css-editor', {
        mode: 'ace/mode/css',
      });

      this.editor.setValue(code);
      this.editor.clearSelection();

      // Determine the height of the editor.
      let height = $(window).height() - $('.js--live-css-editor-controls').outerHeight();
      $('#js--live-css-editor').css('height', height); this.html.addClass('live-css-editor-open');

      let that = this;
      this.editor.on('change', function() {
        that.syncInjectContainer();
        that.setEditorStatusMessage(false);
      });

      // Sync the initial inject container to reflect loaded css code.
      this.syncInjectContainer();
    };

    /**
     * Close the editor session. This will destroy unsaved changes.
     */
    LiveCssEditor.prototype.closeEditor = function() {
      this.editor.destroy();
      this.editor.container.remove();

      this.html.removeClass('live-css-editor-open');

      $('.js--live-css-editor-container').remove();
      $('.js--live-css-inject-container').remove();
    };

    /**
     * Get the code entered in the editor.
     */
    LiveCssEditor.prototype.getEditorValue = function() {
      if (this.editor === undefined) {
        return false;
      }

      return this.editor.getValue();
    };

    /**
     * Make live preview work by injecting a <style> container into the page.
     */
    LiveCssEditor.prototype.syncInjectContainer = function() {
      let markup = this.buildInjectContainerMarkup();
      $('.js--live-css-inject-container').replaceWith(markup);
    };

    /**
     * Add a translatable message to the status span.
     *
     * Passing false or null will remove the previous status message.
     */
    LiveCssEditor.prototype.setEditorStatusMessage = function(message) {
      message = message ? Drupal.t(message) : '';
      $('.js--live-css-status').text(message);
    };

    /**
     * Disable the inject css file link.
     */
    LiveCssEditor.prototype.disableCssFile = function() {
      this.link.remove();
    };

    /**
     * Enable the inject css file link.
     */
    LiveCssEditor.prototype.enableCssFile = function() {
      $('head').append(this.link);
    };

    /**
     * Get the ajaxSaveIsTriggered property value.
     */
    LiveCssEditor.prototype.getAjaxSaveIsTriggered = function() {
      return this.ajaxSaveIsTriggered;
    };

  /**
   * Set the ajaxSaveIsTriggered property value.
   */
    LiveCssEditor.prototype.setAjaxSaveIsTriggered = function(value) {
      this.ajaxSaveIsTriggered = value;
    };

    /**
     * Build the editor container markup.
     */
    LiveCssEditor.prototype.buildEditorContainerMarkup = function(message) {

      return $([
        "  <div class='js--live-css-editor-container live-css-editor-container' style='width: 20%'>",
        "    <div class='js--live-css-editor-controls live-css-editor-controls'>",
        "      <button class='js--live-css-save live-css-control-btn live-css-save' onclick='this.blur();'>Save</button>",
        "      <span class='js--live-css-status live-css-status'></span>",
        "      <button class='js--live-css-close live-css-control-btn live-css-close'>Close</button>",
        "    </div>",
        "    <div id='js--live-css-editor' class='live-css-editor'></div>",
        "  </div>",
      ].join("\n"));
    };

    /**
     * Build the inject styles markup.
     */
    LiveCssEditor.prototype.buildInjectContainerMarkup = function() {
      let code = this.getEditorValue();
      if (!code) {
        return $([
          "<div class='js--live-css-inject-container'></div>",
        ].join("\n"));
      }

      return $([
        "<div class='js--live-css-inject-container'>",
        "  <style>" + code + "</style>",
        "</div>",
      ].join("\n"));
    };

    /**
     * Initialize event listeners.
     */
    LiveCssEditor.prototype.initEventListeners = function() {
      let that = this;
      $(this.button).click(function() {
        $.when(that.liveCssService.getCss()).done(function(code) {
          that.initEditor(code);
          that.disableCssFile();
        });
      });

      $(document).on('click', '.js--live-css-close', function() {

        // If an ajax save was triggered within the current session,
        // trigger a page reload on css editor close. This way we
        // avoid confusion, since all live css changes within this
        // session will be removed from the page again (even if they
        // were saved using the ajax method!).
        if (that.getAjaxSaveIsTriggered()) {
          location.reload();
          return;
        }

        that.closeEditor();
        that.enableCssFile();
      });

      $(document).on('click', '.js--live-css-save', function() {
        $('.js--live-css-editor-container').block({
          message: Drupal.t('Saving...'),
        });

        let code = that.getEditorValue();
        $.when(that.liveCssService.save(code)).done(function() {
          $('.js--live-css-editor-container').unblock();
          that.setEditorStatusMessage('All changes saved');
          that.setAjaxSaveIsTriggered(true);
        });
      });
    };

    window.LiveCssEditor = LiveCssEditor;

})(jQuery);
