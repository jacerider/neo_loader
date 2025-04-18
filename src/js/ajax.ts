(function (Drupal) {

  Drupal.behaviors.neoLoaderAjax = {

    attach: (_context: HTMLElement) => {

      once('neo-loader-ajax', '[data-loader-url]').forEach((element) => {
        var observer = new IntersectionObserver((entries, observer) => {
          entries.forEach(entry => {
            if (entry.intersectionRatio > 0) {
              observer.disconnect();
              let url = element.dataset.loaderUrl;
              if (url) {
                url = addDestination(url);
                const options = {
                  url: url,
                  progress: false,
                  wrapper: element.id,
                  effect: 'fade',
                  httpMethod: 'GET',
                } as any;
                const ajax = Drupal.ajax(options) as any;
                ajax.execute();
              }
            }
          });
        });

        observer.observe(element);
      });
    }
  };

  /**
   * Adds a destination parameter to the URL if it doesn't already exist.
   *
   * @param {string} url - The URL to which the destination parameter will be added.
   * @returns {string} - The modified URL with the destination parameter.
   */
  const addDestination = (url:string):string => {
    const parts = url.split('?');
    let currentDestination = null;
    if (parts[1]) {
      currentDestination = new URLSearchParams('?' + parts[1]).get('destination');
    }
    if (!currentDestination) {
      let key = 'destination';
      let value = window.location.pathname;
      key = encodeURI(key);
      value = encodeURI(value);
      var isQuestionMarkPresent = url && url.indexOf('?') !== -1,
        separator = isQuestionMarkPresent ? '&' : '?';
      url += separator + key + '=' + value;
    }
    return url;
  }

})(Drupal);

export {};
