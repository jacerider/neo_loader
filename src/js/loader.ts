(function (Drupal, once, drupalSettings) {

  type LoaderType =
    | 'fullscreen'
    | 'throbber';

  let waitTimer:ReturnType<typeof setTimeout>|null = null;

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
            waitTimer = setTimeout(() => {
              loader.classList.add('active');
            }, delay);
            return loader;
          }
        }
      }
      return null;
    },

    hide: (callback:Function) => {
      if (waitTimer) {
        clearTimeout(waitTimer);
      }
      const loader = document.querySelector<HTMLElement>('.ajax-progress:not(.ajax-hiding)');
      if (loader) {
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

export {};
