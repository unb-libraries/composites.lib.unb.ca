(function ($, Drupal, once) {
  Drupal.behaviors.compCmsCropper = {
    attach: function (context, settings) {
      once('compCmsCropper', '#composite-img', context).forEach(function(img) {
        console.log('Initializing Cropper for:', img);

        var cropper = new Cropper(img, {
          viewMode: 1,
          // Freeform cropping (no aspect ratio)
          aspectRatio: NaN,
          // Listen for cropbox release
          cropend: function () {
            // Get cropping data
            var data = cropper.getData();
            // Coordinates: data.x, data.y, data.width, data.height
            var tx = data.x;
            var ty = data.y;
            var bx = tx + data.width;
            var by = ty + data.height;
            $('#edit-field-top-x-pixels-0-value', context).val(tx);
            $('#edit-field-top-y-pixels-0-value', context).val(ty);
            $('#edit-field-bottom-x-pixels-0-value', context).val(bx);
            $('#edit-field-bottom-y-pixels-0-value', context).val(by);
          }
        });
      });
    }
  };
})(jQuery, Drupal, once);