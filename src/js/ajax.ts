(function (Drupal) {

  Drupal.behaviors.neoLoaderAjax = {

    attach: (_context: HTMLElement) => {

      once('neo-loader-ajax', '[data-loader-url]').forEach((element) => {
        var observer = new IntersectionObserver((entries, observer) => {
          entries.forEach(entry => {
            if (entry.intersectionRatio > 0) {
              observer.disconnect();
              const options = {
                url: element.dataset.loaderUrl,
                progress: false,
                wrapper: element.id,
                effect: 'fade',
                httpMethod: 'GET',
              } as any;
              const ajax = Drupal.ajax(options) as any;
              ajax.execute();
            }
          });
        });

        observer.observe(element);
      });
    }
  };

})(Drupal);

export {};
