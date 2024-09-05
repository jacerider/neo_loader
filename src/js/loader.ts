(function (Drupal, drupalSettings) {

  type LoaderType =
    | 'fullscreen'
    | 'throbber';

  let fullyLoaded = false;
  let waitTimer:ReturnType<typeof setTimeout>|null = null;

  Drupal.behaviors.neoLoader = {

    show: (message:string, type:LoaderType, selector:HTMLElement|string, delay:number) => {
      type = type || 'fullscreen';
      selector = selector || 'body';
      delay = delay || 1000;
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
              fullyLoaded = false;
              const transitionCallback = (e:Event) => {
                const target = e.target as HTMLElement;
                if (target.classList.contains('ajax-progress')) {
                  fullyLoaded = true;
                }
              };
              loader.addEventListener('transitionend', transitionCallback);
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
      const loader = document.querySelector<HTMLElement>('.ajax-progress');
      if (loader) {
        if (loader.classList.contains('active')) {
          const checkFullyLoaded = setInterval(() => {
            if (fullyLoaded) {
              clearInterval(checkFullyLoaded);
              const transitionCallback = (e:Event) => {
                const target = e.target as HTMLElement;
                if (target.classList.contains('ajax-progress')) {
                  loader.remove();
                  if (callback) {
                    callback();
                  }
                }
              };
              loader.addEventListener('transitionend', transitionCallback);
              loader.classList.remove('active');
            }
          }, 100);
        }
        else {
          loader.remove();
          if (callback) {
            callback();
          }
        }
        return loader;
      }
      return null;
    }
  };

})(Drupal, drupalSettings);

export {};
