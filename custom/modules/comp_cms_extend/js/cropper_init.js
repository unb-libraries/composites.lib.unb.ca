(function (Drupal, once) {
  Drupal.behaviors.compCmsCropper = {
    attach: function (context, settings) {
      once('compCmsCropper', '#subject-img', context).forEach(function(img) {
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
            console.log('Crop coordinates:', data);
            // You can now store these coordinates in hidden fields, or send them via AJAX, etc.
          }
        });
      });
    }
  };
})(Drupal, once);