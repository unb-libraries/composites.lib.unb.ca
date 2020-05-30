(function($) {
  'use strict';

  // Set empty coordinate controls to zero if 'absent' is checked.
  Drupal.behaviors.absentOnClick = {
    attach: function (context, settings) {
      // On documeny ready.
      $(document).ready( function() {
        // When 'absent' checkbox is clicked.
        $('#edit-field-absent-value').click( function() {
          // If 'absent' has been checked.
          if ($(this).prop('checked')) {
            // Zero all empty coordinates.
            if (!$('#edit-field-top-x-0-value').val()) {
              $('#edit-field-top-x-0-value').val('0');
            }
            if (!$('#edit-field-top-y-0-value').val()) {
              $('#edit-field-top-y-0-value').val('0');
            }
            if (!$('#edit-field-bottom-x-0-value').val()) {
              $('#edit-field-bottom-x-0-value').val('0');
            }
            if (!$('#edit-field-bottom-y-0-value').val()) {
              $('#edit-field-bottom-y-0-value').val('0');
            }
          }
        });
      });
    },
  };
})(jQuery);
