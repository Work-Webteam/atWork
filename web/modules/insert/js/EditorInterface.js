(function($, Drupal) {
  'use strict';

  /**
   * @constructor
   */
  Drupal.insert.EditorInterface = Drupal.insert.EditorInterface || (function() {

    /**
     * @constructor
     */
    function EditorInterface() {
      throw new Error('EditorInterface cannot be instantiated directly.');
    }

    $.extend(EditorInterface.prototype, {

      /**
       * @type {Function}
       */
      editorConstructor: undefined,

      /**
       * Checks whether this editor interface is to be used.
       *
       * @return {boolean}
       */
      check: function() {
        throw new Error('Method not overridden.');
      },

      /**
       * @param {*} editor
       * @return {string}
       */
      getId: function(editor) {
        throw new Error('Method not overridden.');
      },

      /**
       * Checks whether the editor is fully initialized.
       *
       * @param {*} editor
       * @return {boolean}
       */
      isReady: function(editor) {
        throw new Error('Method not overridden.');
      },

      /**
       *
       * @return {*[]}
       */
      getInstances: function() {
        throw new Error('Method not overridden.');
      },

      /**
       * @return {*|undefined}
       */
      getCurrentInstance: function() {
        throw new Error('Method not overridden.');
      },

      /**
       * @param {*} editor
       * @return {HTMLElement|undefined}
       */
      getElement: function(editor) {
        throw new Error('Method not overridden.');
      },

      /**
       * @param {*} editor
       * @return {jQuery}
       */
      getDom: function(editor) {
        throw new Error('Method not overridden.');
      },

      /**
       * @param {*} editor
       * @return {string}
       */
      getData: function(editor) {
        throw new Error('Method not overridden.');
      },

      /**
       * @param {*} editor
       * @param {HTMLElement} element
       * @return {string|null}
       */
      getUUID: function(editor, element) {
        throw new Error('Method not overridden.');
      },

      /**
       * @param {*} editor
       * @param {HTMLElement} element
       * @param {string} value
       */
      setAltAttribute: function(editor, element, value) {
        throw new Error('Method not overridden.');
      },

      /**
       * @param {*} editor
       * @param {HTMLElement} element
       * @param {string} value
       */
      setTitleAttribute: function(editor, element, value) {
        throw new Error('Method not overridden.');
      }

    });

    return EditorInterface;

  })();

})(jQuery, Drupal);
