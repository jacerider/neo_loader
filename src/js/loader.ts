(function (Drupal, once, drupalSettings) {

  type LoaderType =
    | 'fullscreen'
    | 'throbber';

  // The pending reveal timer belongs to the overlay it will activate rather
  // than to the behaviour. With more than one overlay on the page, clearing
  // "the" timer cancels a reveal belonging to an overlay nobody asked to tear
  // down. A WeakMap keeps the association off the DOM and lets an entry go
  // when its overlay does.
  const pendingReveals = new WeakMap<HTMLElement, ReturnType<typeof setTimeout>>();

  Drupal.behaviors.neoLoader = {

    attach: function (context:any) {
      once('neo-loader', '.use-neo-loader', context).forEach((element) => {
        element.addEventListener('click', (_e:Event) => {
          const message = element.getAttribute('data-neo-loader-message') || Drupal.t('Loading...');
          const type = parseInt(element.getAttribute('data-neo-loader-type') || 'fullscreen');
          const delay = parseInt(element.getAttribute('data-neo-loader-delay') || '0');
          this.show(message, type, 'body', delay);
        });
      });
    },

    show: (message:string, type:LoaderType, selector:HTMLElement|string, delay:number) => {
      type = type || 'fullscreen';
      selector = selector || 'body';
      delay = Math.max(typeof delay === 'undefined' ? 0 : delay, 10);
      if (typeof drupalSettings.neoLoader !== 'undefined' && typeof drupalSettings.neoLoader.markup !== 'undefined') {
        const loader = document.createElement('div');
        loader.classList.add('ajax-progress');
        loader.classList.add('ajax-progress-' + type);
        loader.innerHTML = drupalSettings.neoLoader.markup;
        if (message) {
          const messageElement = document.createElement('div');
          messageElement.classList.add('message');
          messageElement.textContent = message;
          loader.querySelector('.neo-loader')?.appendChild(messageElement);
        }
        if (selector) {
          const position = typeof selector === 'string' ? document.querySelector(selector) : selector;
          if (position) {
            if (type === 'fullscreen') {
              position.appendChild(loader);
            }
            else {
              let closest = position.closest('.js-form-item, form');
              if (closest) {
                closest.classList.add('ajax-progress-wrapper');
              }
              position.after(loader);
            }
            pendingReveals.set(loader, setTimeout(() => {
              pendingReveals.delete(loader);
              loader.classList.add('active');
            }, delay));
            return loader;
          }
        }
      }
      return null;
    },

    /**
     * Takes an overlay off the page.
     *
     * @param callback
     *   Invoked once the overlay has been taken down.
     * @param element
     *   The overlay to remove: the element a matching show() returned. A
     *   caller that built an overlay hands its own back, so that teardown
     *   removes the overlay belonging to that request rather than whichever
     *   one the document happens to hold first. Omitting it falls back to the
     *   document-wide lookup this behaviour has always used, which is what
     *   callers predating the argument rely on.
     *
     * @return
     *   The overlay that was taken down, or null when there was none to take
     *   down.
     */
    hide: (callback:Function, element?:HTMLElement|null) => {
      // An overlay already on its way out is not a target on either path: the
      // lookup filters it out, and an element handed over a second time is a
      // no-op rather than a second teardown.
      const loader = element
        ? (element.classList.contains('ajax-hiding') ? null : element)
        : document.querySelector<HTMLElement>('.ajax-progress:not(.ajax-hiding)');
      if (loader) {
        const pending = pendingReveals.get(loader);
        if (pending) {
          clearTimeout(pending);
          pendingReveals.delete(loader);
        }
        loader.classList.add('ajax-hiding');
        if (loader.classList.contains('active')) {
          loader.classList.remove('active');
          setTimeout(() => {
            loader.remove();
          }, 150);
        }
        else {
          loader.remove();
        }
        if (callback) {
          callback();
        }
        return loader;
      }
      return null;
    }
  };

})(Drupal, once, drupalSettings);
