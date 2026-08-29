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

  // Marks an overlay whose request asked for it to stay on screen after the
  // response landed. It is the whole of the hold: teardown skips an overlay
  // carrying it, and only dismissal takes one off the page.
  const HELD = 'ajax-progress-held';

  // A held overlay's own unbind, kept off the DOM so that the listeners
  // dismissal depends on can be removed by identity when the overlay goes.
  // Binding and unbinding are a pair — an overlay that outlived its listeners
  // would strand a full-page layer over the page with no way out — so hold()
  // writes the entry and dismiss() is the only thing that clears it.
  const dismissals = new WeakMap<HTMLElement, () => void>();

  /**
   * Takes a held overlay off the page and unbinds what was holding it.
   *
   * Leaves the page as teardown would have left it: no overlay node, and no
   * body loading class. That class is a single unrefcounted flag rather than a
   * count of requests in flight, so an unrelated request completing during the
   * hold has already removed it and removing it again here is a no-op; the
   * overlay's own `cursor: wait` is what it costs to keep this simple.
   *
   * @param element
   *   The held overlay.
   *
   * @return
   *   The overlay that was dismissed, or null when the element was not a held
   *   overlay and so had nothing to dismiss.
   */
  const dismissOverlay = (element?:HTMLElement|null):HTMLElement|null => {
    if (!element || !element.classList.contains(HELD)) {
      return null;
    }
    const unbind = dismissals.get(element);
    if (unbind) {
      unbind();
      dismissals.delete(element);
    }
    const pending = pendingReveals.get(element);
    if (pending) {
      clearTimeout(pending);
      pendingReveals.delete(element);
    }
    element.classList.remove(HELD);
    element.remove();
    document.body.classList.remove('ajax-loading');
    return element;
  };

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
     * Keeps an overlay on screen until somebody dismisses it.
     *
     * The overlay is the one show() already built, from the same markup and
     * through the same progress-indicator path; the hold governs teardown and
     * nothing else, so what is on screen is exactly what an unheld request
     * would have shown had its reveal been given time to finish.
     *
     * Two gestures dismiss it, bound here and unbound the moment it goes: Esc,
     * and a click anywhere on the overlay. Both are needed — a keyboard-only
     * way out for a full-page layer, and the obvious gesture for a pointer or
     * a touch.
     *
     * The dismissal hint goes inside the throbber badge rather than on the
     * scrim, because the badge docks in 0.3 s while the scrim does not begin
     * to appear for a full second, and a hint nobody can read for the first
     * seconds of the state it explains is not a hint. It is separate from the
     * ajax message and is shown whether or not that message is suppressed,
     * since the case that most needs a way out is the one showing no message.
     *
     * @param element
     *   The overlay to hold: the element a matching show() returned.
     *
     * @return
     *   The overlay now being held, or null when there was none to hold.
     */
    hold: (element?:HTMLElement|null) => {
      if (!element || element.classList.contains(HELD)) {
        return element || null;
      }
      element.classList.add(HELD);

      const hint = document.createElement('div');
      hint.classList.add('neo-loader-hint');
      hint.textContent = Drupal.t('Press Esc or click to dismiss');
      element.querySelector('.neo-loader')?.appendChild(hint);

      const onKeyDown = (event:KeyboardEvent) => {
        if (event.key === 'Escape' || event.key === 'Esc') {
          dismissOverlay(element);
        }
      };
      const onClick = () => {
        dismissOverlay(element);
      };
      document.addEventListener('keydown', onKeyDown);
      element.addEventListener('click', onClick);
      dismissals.set(element, () => {
        document.removeEventListener('keydown', onKeyDown);
        element.removeEventListener('click', onClick);
      });

      return element;
    },

    /**
     * Whether an overlay is being held.
     *
     * A held overlay is an overlay that exists and is being kept, not an
     * absent one, so a caller deciding what to do about teardown asks this
     * before concluding from a null teardown result that there was no overlay
     * at all.
     *
     * @param element
     *   The overlay to test.
     *
     * @return
     *   True when the overlay is being held.
     */
    isHeld: (element?:HTMLElement|null) => {
      return !!element && element.classList.contains(HELD);
    },

    /**
     * Dismisses a held overlay.
     *
     * @param element
     *   The held overlay.
     *
     * @return
     *   The overlay that was dismissed, or null when there was none.
     */
    dismiss: (element?:HTMLElement|null) => {
      return dismissOverlay(element);
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
      // no-op rather than a second teardown. A held overlay is filtered out of
      // both for the same reason from the other direction — it is not on its
      // way out and is not going to be, so teardown skips it entirely and an
      // unrelated request's document-wide lookup cannot resolve to it.
      const loader = element
        ? (element.classList.contains('ajax-hiding') || element.classList.contains(HELD) ? null : element)
        : document.querySelector<HTMLElement>('.ajax-progress:not(.ajax-hiding):not(.' + HELD + ')');
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
