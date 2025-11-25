/**
 * @file
 * Drupal behavior to initialize DMF configuration editor.
 *
 * The configuration editor JS listens for 'dmf-configuration-editor-init' events
 * to find and initialize new textareas. This behavior dispatches that event
 * after Drupal attaches behaviors to AJAX-loaded content.
 */

(function (Drupal) {
  'use strict';

  /**
   * Initialize DMF configuration editor textareas.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.dmfConfigurationEditorInit = {
    attach: function (context) {
      // Check if there are any uninitialized DMF configuration textareas in the context.
      var textareas = context.querySelectorAll
        ? context.querySelectorAll('textarea.dmf-configuration-document:not([data-init])')
        : [];

      if (textareas.length > 0) {
        // Dispatch the init event to trigger the configuration editor to initialize
        // any new textareas. The editor listens for this event and will find all
        // textareas with class 'dmf-configuration-document' that don't have
        // data-init set yet.
        document.dispatchEvent(new Event('dmf-configuration-editor-init'));
      }
    }
  };

})(Drupal);