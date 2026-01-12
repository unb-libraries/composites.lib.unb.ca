(function (Drupal, window, document) {
  'use strict';

  Drupal.behaviors.compSlideshowSwiper = {
    attach: function (context, settings) {
      // Add swiper-slider class to view-content if needed.
      var viewContent = context.querySelector('#block-views-block-slideshow-block-1 .view-content');
      if (viewContent && !viewContent.classList.contains('swiper-slider')) {
        viewContent.classList.add('swiper-slider');
      }

      var containers = context.querySelectorAll('.swiper-slider:not(.swiper-initialized)');
      Array.prototype.forEach.call(containers, function (container) {
        container.classList.add('swiper-initialized');

        // Ensure or build wrapper/slide structure
        var wrapper = container.querySelector('.swiper-wrapper');
        if (!wrapper) {
          wrapper = document.createElement('div');
          wrapper.className = 'swiper-wrapper';
          while (container.firstChild) {
            var child = container.firstChild;
            if (child === wrapper) break;
            container.removeChild(child);
            if (child.nodeType === 3 && !/\S/.test(child.nodeValue)) continue;
            if (child.nodeType === 1) child.classList.add('swiper-slide');
            wrapper.appendChild(child);
          }
          container.appendChild(wrapper);
        } else {
          var kids = wrapper.children;
          for (var i = 0; i < kids.length; i++) {
            kids[i].classList.add('swiper-slide');
          }
        }

        // read JSON options safely from the container or nearest ancestor that has the attribute
        var options = {};
        var dataElem = container;
        var dataAttr = container.getAttribute('data-swiper-options');
        if (!dataAttr) {
          var ancestorWithAttr = container.closest('[data-swiper-options]');
          if (ancestorWithAttr) {
            dataElem = ancestorWithAttr;
            dataAttr = ancestorWithAttr.getAttribute('data-swiper-options');
          }
        }
        if (dataAttr) {
          try { options = JSON.parse(dataAttr); } catch (e) {
            console.warn('Invalid JSON in data-swiper-options on', dataElem, dataAttr, e);
            options = {};
          }
        }

        // Ensure navigation/pagination DOM exists (create if missing)
        var paginationEl = container.querySelector('.swiper-pagination');
        if (!paginationEl && options.pagination !== false) {
          paginationEl = document.createElement('div');
          paginationEl.className = 'swiper-pagination';
          container.appendChild(paginationEl);
        }

        var nextEl = container.querySelector('.swiper-button-next');
        if (!nextEl && options.navigation !== false) {
          nextEl = document.createElement('div');
          nextEl.className = 'swiper-button-next';
          container.appendChild(nextEl);
        }

        var prevEl = container.querySelector('.swiper-button-prev');
        if (!prevEl && options.navigation !== false) {
          prevEl = document.createElement('div');
          prevEl.className = 'swiper-button-prev';
          container.appendChild(prevEl);
        }

        // Determine effect
        var effect = (options.effect && String(options.effect).toLowerCase() === 'fade') ? 'fade' : 'slide';

        // Normalize autoplay (kept from your version)
        function normalizeAutoplay(opts) {
          var result = false;
          if (opts.autoplay === false) {
            result = false;
          } else if (typeof opts.autoplay === 'number') {
            result = { delay: Number(opts.autoplay) || 5000 };
          } else if (typeof opts.autoplay === 'object' && opts.autoplay !== null) {
            result = {};
            if (opts.autoplay.delay !== undefined) {
              result.delay = Number(opts.autoplay.delay) || 5000;
            }
            if (opts.autoplay.disableOnInteraction !== undefined) {
              result.disableOnInteraction = !!opts.autoplay.disableOnInteraction;
            }
            if (opts.autoplay.pauseOnMouseEnter !== undefined) {
              result.pauseOnMouseEnter = !!opts.autoplay.pauseOnMouseEnter;
            }
            if (result.delay === undefined && opts.autoplaySpeed !== undefined) {
              result.delay = Number(opts.autoplaySpeed) || 5000;
            }
            if (Object.keys(result).length === 0) {
              result = { delay: 5000 };
            }
          } else if (opts.autoplay === true) {
            result = { delay: Number(opts.autoplaySpeed) || 5000 };
          } else if (opts.autoplaySpeed !== undefined) {
            result = { delay: Number(opts.autoplaySpeed) || 5000 };
          } else {
            result = { delay: 5000 };
          }
          return result;
        }

        // base cfg
        var cfg = {
          loop: options.loop !== undefined ? options.loop : true,
          effect: effect,
          slidesPerView: options.slidesPerView || 1,
          pagination: paginationEl ? { el: paginationEl, clickable: true } : false,
          navigation: (nextEl && prevEl) ? { nextEl: nextEl, prevEl: prevEl } : false,
          fadeEffect: { crossFade: true },
          a11y: true,
          allowTouchMove: options.allowTouchMove === undefined ? true : !!options.allowTouchMove
        };

        // Shallow merge other options (except autoplay/speed handled explicitly)
        for (var k in options) {
          if (!options.hasOwnProperty(k)) continue;
          if (k === 'autoplay') continue;
          if (k === 'autoplaySpeed') continue;
          if (k === 'speed') continue;
          cfg[k] = options[k];
        }

        cfg.autoplay = normalizeAutoplay(options);
        if (options.autoplay === false) {
          cfg.autoplay = false;
        }

        // Set transition speed
        var resolvedSpeed = undefined;
        if (options.speed !== undefined) {
          resolvedSpeed = Number(options.speed) || undefined;
        } else if (options.transitionSpeed !== undefined) {
          resolvedSpeed = Number(options.transitionSpeed) || undefined;
        }
        cfg.speed = resolvedSpeed !== undefined ? resolvedSpeed : 600;

        if (String(cfg.effect).toLowerCase() === 'fade') {
          cfg.slidesPerView = 1;
          cfg.fadeEffect = cfg.fadeEffect || { crossFade: true };
          if (options.allowTouchMove === undefined) {
            cfg.allowTouchMove = false;
          }
        }

        // Add hooks so we reveal the slider only when ready.
        cfg.on = cfg.on || {};
        // Add init hook (fires when Swiper initialized)
        var existingInit = cfg.on.init;
        cfg.on.init = function () {
          // call any existing handler
          if (typeof existingInit === 'function') {
            try { existingInit.apply(this, arguments); } catch (e) { console.warn(e); }
          }
          // add class to reveal slider
          container.classList.add('swiper-ready');
        };
        // Also prefer imagesReady event to reveal after images loaded
        var existingImagesReady = cfg.on.imagesReady;
        cfg.on.imagesReady = function () {
          if (typeof existingImagesReady === 'function') {
            try { existingImagesReady.apply(this, arguments); } catch (e) { console.warn(e); }
          }
          container.classList.add('swiper-ready');
        };

        if (typeof Swiper === 'undefined') {
          console.error('Swiper not found. Make sure swiper-bundle.min.js is loaded in your library.');
          return;
        }

        try {
          // eslint-disable-next-line no-new
          new Swiper(container, cfg);
        } catch (err) {
          console.error('Swiper init error for', container, err);
          // In case of error, reveal the content so users are not stuck with hidden content.
          container.classList.add('swiper-ready');
        }
      });
    }
  };
})(Drupal, window, document);
