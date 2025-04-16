(function (Drupal, once) {
  'use strict';

  // Set empty coordinate controls to zero if 'absent' is checked.
  Drupal.behaviors.absentOnClick = {
    attach: function (context, settings) {
      // Select the 'absent' checkbox and attach the behavior only once.
      const absentCheckbox = once('absent-on-click', '#edit-field-absent-value', context);
      absentCheckbox.forEach((checkbox) => {
        checkbox.addEventListener('click', function () {
          // If 'absent' has been checked.
          if (checkbox.checked) {
            // Zero all empty coordinates.
            const topX = document.querySelector('#edit-field-top-x-0-value');
            const topY = document.querySelector('#edit-field-top-y-0-value');
            const bottomX = document.querySelector('#edit-field-bottom-x-0-value');
            const bottomY = document.querySelector('#edit-field-bottom-y-0-value');

            if (topX && !topX.value) {
              topX.value = '0';
            }
            if (topY && !topY.value) {
              topY.value = '0';
            }
            if (bottomX && !bottomX.value) {
              bottomX.value = '0';
            }
            if (bottomY && !bottomY.value) {
              bottomY.value = '0';
            }
          }
        });
      });
    },
  };
})(Drupal, once);