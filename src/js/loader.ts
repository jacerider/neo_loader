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

  // The element the throbber branch of show() marked with
  // `ajax-progress-wrapper`, remembered against the overlay that marked it.
  // The class positions that overlay over the element it covers, so it is the
  // overlay's own and has to leave when the overlay does; nothing used to
  // remove it, which was a cosmetic leak while the path lasted seventy
  // milliseconds and is a permanent one now the loader test holds it on
  // screen. Keeping the association here rather than on the DOM is the idiom
  // pendingReveals and dismissals already use, and it is what stops one
  // request's cleanup reaching for an element it did not mark.
  const wrappers = new WeakMap<HTMLElement, Element>();

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

  // How long an overlay's exit is given before the node leaves. The stylesheet
  // transitions the overlay and its badge at 0.2s and teardown has always cut
  // that slightly short; the number is here rather than at the two call sites
  // so the two ways out cannot drift apart again.
  const EXIT_MS = 150;

  /**
   * Gives back the element an overlay marked as its wrapper.
   *
   * Called once the overlay has left the document, not when its exit begins:
   * the class is what holds the overlay over the element it covers, so
   * removing it any earlier drops the overlay back into the line for the
   * length of its own exit.
   *
   * The class only goes when nothing is left inside that needs it. Two
   * requests in one form item resolve to the same wrapper -- the closest form
   * ancestor is one element for every trigger in a form -- and the first to
   * finish would otherwise pull the class out from under the second's overlay,
   * which is the cross-request reach remembering the element was meant to
   * prevent. The overlay asking has already been removed, so the lookup sees
   * only the others.
   *
   * @param element
   *   The overlay that has gone.
   */
  const releaseWrapper = (element:HTMLElement):void => {
    const wrapper = wrappers.get(element);
    wrappers.delete(element);
    if (wrapper && !wrapper.querySelector('.ajax-progress')) {
      wrapper.classList.remove('ajax-progress-wrapper');
    }
  };

  /**
   * Plays an overlay's exit and takes it off the page.
   *
   * Removing `active` is what runs the entrance transitions backwards, so an
   * overlay that never reached `active` — one dismissed or torn down inside
   * its own reveal delay — has nothing to play and goes at once. One that did
   * is left in the document for the length of the exit.
   *
   * `ajax-hiding` marks it as already on its way out for the length of that
   * wait, which is what stops either way out picking up an overlay whose exit
   * is still running and removing it a second time.
   *
   * Both ways out come through here, which is why the wrapper class an inline
   * overlay left on the element it covered is given back here too: dismissal
   * and ordinary teardown then clean up identically, and neither can forget.
   *
   * @param element
   *   The overlay to take down.
   */
  const runExit = (element:HTMLElement):void => {
    const drop = () => {
      element.remove();
      releaseWrapper(element);
    };
    element.classList.add('ajax-hiding');
    if (element.classList.contains('active')) {
      element.classList.remove('active');
      setTimeout(drop, EXIT_MS);
    }
    else {
      drop();
    }
  };

  /**
   * Takes a held overlay off the page and unbinds what was holding it.
   *
   * Leaves the page the way teardown would have left it, and by the same
   * route: the overlay plays the exit `hide()` plays, through the shared
   * runExit(), rather than being cut out of the document in the tick the
   * gesture arrives. Dismissal is the one way out a person actually watches —
   * every other overlay leaves while they are reading the response — so an
   * overlay that animated in and then vanished instantly is the one place the
   * difference shows.
   *
   * The body loading class goes at once rather than with the node, because it
   * is a single unrefcounted flag rather than a count of requests in flight:
   * an unrelated request completing during the hold has already removed it and
   * removing it again here is a no-op. Holding it for the length of the exit
   * would keep `cursor: wait` over a page that is no longer waiting.
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
    runExit(element);
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
                // Remembered against this overlay so the class leaves with it
                // and with no other overlay -- see releaseWrapper().
                wrappers.set(loader, closest);
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
        runExit(loader);
        if (callback) {
          callback();
        }
        return loader;
      }
      return null;
    }
  };

})(Drupal, once, drupalSettings);
