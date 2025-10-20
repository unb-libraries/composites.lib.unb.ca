(function ($, Drupal, once) {
  Drupal.behaviors.compCmsCropper = {
    attach: function (context, settings) {
      once('compCmsCropper', '#composite-img', context).forEach(function(img) {
        // Get initial dimensions from your input fields
        var initialX = parseFloat($('#edit-field-top-x-pixels-0-value', context).val()) || 0;
        var initialY = parseFloat($('#edit-field-top-y-pixels-0-value', context).val()) || 0;
        var initialWidth = parseFloat($('#edit-field-bottom-x-pixels-0-value', context).val()) || 100; // default width if not set
        var initialHeight = parseFloat($('#edit-field-bottom-y-pixels-0-value', context).val()) || 100; // default height if not set
        
        // Calculate width/height if bottom coordinates represent endpoints
        if (
          $('#edit-field-bottom-x-pixels-0-value', context).val() &&
          $('#edit-field-top-x-pixels-0-value', context).val()
        ) {
          initialWidth = initialWidth - initialX;
        }
        if (
          $('#edit-field-bottom-y-pixels-0-value', context).val() &&
          $('#edit-field-top-y-pixels-0-value', context).val()
        ) {
          initialHeight = initialHeight - initialY;
        }

        var cropper = new Cropper(img, {
          viewMode: 1,
          aspectRatio: NaN,
          data: {
            x: initialX,
            y: initialY,
            width: initialWidth,
            height: initialHeight
          },
          cropend: function () {
            // Get cropping data
            var data = cropper.getData();
            // Coordinates: data.x, data.y, data.width, data.height
            var tx = data.x;
            var ty = data.y;
            var bx = tx + data.width;
            var by = ty + data.height;
            $('#edit-field-top-x-pixels-0-value', context).val(Math.round(tx));
            $('#edit-field-top-y-pixels-0-value', context).val(Math.round(ty));
            $('#edit-field-bottom-x-pixels-0-value', context).val(Math.round(bx));
            $('#edit-field-bottom-y-pixels-0-value', context).val(Math.round(by));
          }
        });
      });
    }
  };
})(jQuery, Drupal, once);