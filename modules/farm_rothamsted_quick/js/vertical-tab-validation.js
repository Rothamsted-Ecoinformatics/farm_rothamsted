(function ($, Drupal) {

  /**
   * Javascript behavior that opens the first vertical tab containing invalid form fields.
   */
  Drupal.behaviors.rothamstedVerticalTabs = {
    attach(context) {

      // Find the first invalid form field nested in a vertical tab.
      const invalidField = context.querySelector('form .js-vertical-tabs .form-element.error:invalid');
      if (!invalidField) {
        return;
      }

      // If the vertical tab is found show the tab.
      const invalidTab = invalidField.closest('.js-vertical-tabs-pane');
      if (invalidTab) {

        // Use jQuery to load the verticalTab data object.
        // This is how core/drupal.vertical-tab implements tab methods.
        const tab = $(invalidTab).data('verticalTab');
        if (tab) {
          tab.tabShow();
        }
      }
    },
  };
}(jQuery, Drupal))
