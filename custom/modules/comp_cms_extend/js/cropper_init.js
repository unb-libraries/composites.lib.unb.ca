(function (Drupal, once) {
  Drupal.behaviors.compCmsCropper = {
    attach: function (context, settings) {
      // Find the image(s)
      once('compCmsCropper', '#subject-img', context).forEach(function(img) {
        // Confirm the element exists
        console.log('Initializing Cropper for:', img);

        // Initialize CropperJS
        var cropper = new Cropper(img, {
          viewMode: 1,
        });
      });
    }
  };
})(Drupal, once);