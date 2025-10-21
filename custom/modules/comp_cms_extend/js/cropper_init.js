(function ($, Drupal, once) {
  Drupal.behaviors.compCmsCropper = {
    attach: function (context, settings) {
      once('compCmsCropper', '#composite-img', context).forEach(function (imgEl) {
        var $img = $(imgEl);
        var $tx = $('#edit-field-top-x-pixels-0-value', context);
        var $ty = $('#edit-field-top-y-pixels-0-value', context);
        var $bx = $('#edit-field-bottom-x-pixels-0-value', context);
        var $by = $('#edit-field-bottom-y-pixels-0-value', context);

        function parseVal($el) {
          var v = parseFloat($el.val());
          return Number.isFinite(v) ? v : null;
        }

        var topX = parseVal($tx);
        var topY = parseVal($ty);
        var bottomX = parseVal($bx);
        var bottomY = parseVal($by);

        // sensible defaults
        if (topX === null) topX = 0;
        if (topY === null) topY = 0;

        var initialWidth = (bottomX !== null) ? (bottomX - topX) : 100;
        var initialHeight = (bottomY !== null) ? (bottomY - topY) : 100;

        // Ensure positive defaults
        if (!Number.isFinite(initialWidth) || initialWidth <= 0) {
          initialWidth = 100;
        }
        if (!Number.isFinite(initialHeight) || initialHeight <= 0) {
          initialHeight = 100;
        }

        var cropper;

        function initCropper() {
          if (cropper) {
            return;
          }

          cropper = new Cropper(imgEl, {
            viewMode: 1,
            aspectRatio: NaN,
            // disable built-in wheel zoom (we implement Shift+wheel)
            zoomOnWheel: false,
            data: {
              x: topX,
              y: topY,
              width: initialWidth,
              height: initialHeight
            },
            cropend: function () {
              var data = cropper.getData(true);
              var tx = Math.round(data.x || 0);
              var ty = Math.round(data.y || 0);
              var bxv = Math.round((data.x || 0) + (data.width || 0));
              var byv = Math.round((data.y || 0) + (data.height || 0));

              $tx.val(tx);
              $ty.val(ty);
              $bx.val(bxv);
              $by.val(byv);
            }
          });

          // Choose container to listen on. Prefer cropper.cropper (wrapper),
          // fall back to image parent or document.
          var container = (cropper && cropper.cropper) || imgEl.parentElement || document;

          // Zoom sensitivity: smaller = slower/finer, bigger = faster/coarser
          var SENSITIVITY = 800; // tweak this number if zoom is too fast/slow
          var MAX_STEP = 0.9;
          var MIN_STEP = -0.9;

          // This handler keeps the center at the center of the current crop box.
          function wheelHandler(e) {
            // Only act when Shift key is pressed.
            if (!e.shiftKey) {
              // Do not block page scrolling or other handlers when Shift isn't down.
              return;
            }

            e.preventDefault();
            e.stopPropagation();

            if (!cropper) return;

            try {
              // Normalize step (positive -> zoom in)
              var raw = -e.deltaY;
              var step = raw / SENSITIVITY;
              step = Math.max(Math.min(step, MAX_STEP), MIN_STEP);

              // Compute multiplicative factor for canvas (factor > 1 => zoom in)
              var factor = 1 + step;
              if (factor <= 0.01) factor = 0.01;

              // Obtain current data
              var data = cropper.getData(); // crop box in image coordinates
              var imageData = cropper.getImageData(); // natural sizes etc.
              var canvasData = cropper.getCanvasData(); // displayed canvas position/size

              var naturalWidth = imageData.naturalWidth || imageData.width;
              var naturalHeight = imageData.naturalHeight || imageData.height;
              if (!naturalWidth || !naturalHeight) {
                // Can't compute without natural sizes; fallback to API zoom
                cropper.zoom(step);
                return;
              }

              // crop box center in image coordinates
              var cxImage = (data.x || 0) + (data.width || 0) / 2;
              var cyImage = (data.y || 0) + (data.height || 0) / 2;

              // current scale from image coords -> container coords
              var oldScaleX = canvasData.width / naturalWidth;
              var oldScaleY = canvasData.height / naturalHeight;
              // use X scale (they should match), but guard
              var oldScale = (oldScaleX + oldScaleY) / 2 || oldScaleX || oldScaleY || 1;

              // center in container coordinates
              var centerContainerX = canvasData.left + cxImage * oldScale;
              var centerContainerY = canvasData.top + cyImage * oldScale;

              // new canvas size
              var newCanvasWidth = canvasData.width * factor;
              var newCanvasHeight = canvasData.height * factor;

              // new scale
              var newScale = newCanvasWidth / naturalWidth;

              // compute new top-left so the crop-box center remains at the same container point
              var newLeft = centerContainerX - cxImage * newScale;
              var newTop = centerContainerY - cyImage * newScale;

              // Clamp new canvas so image isn't completely moved out of view.
              // Ensure some minimum canvas size (so zoom doesn't go to zero).
              var MIN_CANVAS_DIM = 20;
              if (newCanvasWidth < MIN_CANVAS_DIM) newCanvasWidth = MIN_CANVAS_DIM;
              if (newCanvasHeight < MIN_CANVAS_DIM) newCanvasHeight = MIN_CANVAS_DIM;

              // Also optionally clamp to avoid showing huge image (optional)
              var MAX_CANVAS_MULTIPLIER = 50; // relative to initial canvas size
              var maxW = (canvasData.width || naturalWidth) * MAX_CANVAS_MULTIPLIER;
              var maxH = (canvasData.height || naturalHeight) * MAX_CANVAS_MULTIPLIER;
              if (newCanvasWidth > maxW) newCanvasWidth = maxW;
              if (newCanvasHeight > maxH) newCanvasHeight = maxH;

              // Recompute newScale if sizes changed by clamps
              newScale = newCanvasWidth / naturalWidth;
              newLeft = centerContainerX - cxImage * newScale;
              newTop = centerContainerY - cyImage * newScale;

              // Apply canvas transform — this effectively "zooms" the image content.
              cropper.setCanvasData({
                left: newLeft,
                top: newTop,
                width: newCanvasWidth,
                height: newCanvasHeight
              });

              // No change to crop box image coordinates is necessary; inputs remain valid.
              // But ensure UI shows exact values in inputs (rounded)
              var updatedData = cropper.getData(true);
              $tx.val(Math.round(updatedData.x || 0));
              $ty.val(Math.round(updatedData.y || 0));
              $bx.val(Math.round((updatedData.x || 0) + (updatedData.width || 0)));
              $by.val(Math.round((updatedData.y || 0) + (updatedData.height || 0)));
            } catch (err) {
              if (window && window.console) {
                console.debug('centered canvas zoom error', err);
              }
            }
          }

          // Add wheel listener. passive:false so we can preventDefault when intercepting.
          container.addEventListener('wheel', wheelHandler, { passive: false });

          // ---- Keyboard handler: move crop box by 1px when Shift + Arrow keys ----
          var isPointerOver = false;
          var hasFocusInside = false;

          try {
            container.addEventListener('mouseenter', function () {
              isPointerOver = true;
            }, true);
            container.addEventListener('mouseleave', function () {
              isPointerOver = false;
            }, true);
          } catch (err) {
            // ignore if not supported
          }

          document.addEventListener('focusin', function (ev) {
            if (container && container.contains(ev.target)) {
              hasFocusInside = true;
            }
          }, true);
          document.addEventListener('focusout', function () {
            if (container && !container.contains(document.activeElement)) {
              hasFocusInside = false;
            }
          }, true);

          function isTextInput(el) {
            if (!el) return false;
            var tag = el.tagName && el.tagName.toLowerCase();
            if (tag === 'input' || tag === 'textarea' || tag === 'select') return true;
            if (el.isContentEditable) return true;
            return false;
          }

          function keyHandler(e) {
            // Only care about Arrow keys with Shift pressed.
            if (!e.shiftKey) return;

            var isArrow =
              e.key === 'ArrowLeft' ||
              e.key === 'ArrowRight' ||
              e.key === 'ArrowUp' ||
              e.key === 'ArrowDown';
            if (!isArrow) return;

            // Avoid interfering when typing in form controls anywhere.
            if (isTextInput(document.activeElement)) return;

            // Only proceed if pointer is over the cropper or focus is inside.
            if (!isPointerOver && !hasFocusInside) return;

            // Intercept the key event and move the crop box by 1 pixel.
            e.preventDefault();
            e.stopPropagation();

            if (!cropper) return;

            try {
              var data = cropper.getData(); // x, y, width, height (floats)
              var imageData = cropper.getImageData();
              var imgWidth = imageData.naturalWidth || imageData.width || Infinity;
              var imgHeight = imageData.naturalHeight || imageData.height || Infinity;

              var dx = 0, dy = 0;
              if (e.key === 'ArrowLeft') dx = -1;
              if (e.key === 'ArrowRight') dx = 1;
              if (e.key === 'ArrowUp') dy = -1;
              if (e.key === 'ArrowDown') dy = 1;

              var newX = (data.x || 0) + dx;
              var newY = (data.y || 0) + dy;

              // Clamp within image bounds so crop box cannot move outside image.
              var w = data.width || 0;
              var h = data.height || 0;
              newX = Math.max(0, Math.min(newX, imgWidth - w));
              newY = Math.max(0, Math.min(newY, imgHeight - h));

              // Apply new position, preserving size.
              cropper.setData({ x: newX, y: newY, width: w, height: h });

              // Also update the input fields to reflect the new coordinates immediately.
              // Use rounded integers like cropend does.
              $tx.val(Math.round(newX));
              $ty.val(Math.round(newY));
              $bx.val(Math.round(newX + w));
              $by.val(Math.round(newY + h));
            } catch (err) {
              if (window && window.console) {
                console.debug('cropper keyboard move error', err);
              }
            }
          }

          // Attach key listener in capture phase to get events early.
          document.addEventListener('keydown', keyHandler, true);
        }

        // Initialize after image is loaded (or immediately if already loaded)
        if (imgEl.complete && imgEl.naturalWidth) {
          initCropper();
        } else {
          $img.on('load', initCropper);
        }
      });
    }
  };
})(jQuery, Drupal, once);