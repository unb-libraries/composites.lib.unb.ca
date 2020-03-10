Drupal.behaviors.comp_cms_extend = {
  attach: function (context, settings) {

    // Attach a click listener to the refresh_subject_img button.
    var clearBtn = document.getElementById('refresh-subject-img');
    clearBtn.addEventListener('click', function() {

        // Do something!
        var subjectImg = document.getElementById('subject-img');
        var newSrc = drupalSettings.comp_cms_extend.comp_url;

        subjectImg.setAttribute('src', newSrc);
    }, false);
  }
};
