(function () {
  var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  function addLoader() {
    if (document.querySelector('.site-page-loader')) return;
    var loader = document.createElement('div');
    loader.className = 'site-page-loader';
    loader.setAttribute('role', 'status');
    loader.setAttribute('aria-live', 'polite');
    loader.innerHTML = '<span class="site-page-loader__spinner" aria-hidden="true"></span><span class="site-page-loader__label">Memuat halaman...</span>';
    document.body.appendChild(loader);
  }

  function revealElement(element, observer) {
    if (!(element instanceof Element) || element.matches('.site-page-loader,.site-feature-loader')) return;
    if (element.matches('.hero__content,.mini-hero__content,.greet__inner,.info-card,.reviews__head,.review-card,.menu-section__title,.faq-item,.faq-cta__box,.contact-grid,.location-section,.support-form-section')) {
      element.classList.add('motion-reveal');
      if (observer) observer.observe(element);
      else element.classList.add('is-visible');
    }
    element.querySelectorAll('.hero__content,.mini-hero__content,.greet__inner,.info-card,.reviews__head,.review-card,.menu-section__title,.faq-item,.faq-cta__box,.contact-grid,.location-section,.support-form-section').forEach(function (child) {
      child.classList.add('motion-reveal');
      if (observer) observer.observe(child);
      else child.classList.add('is-visible');
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    if (reducedMotion) return;

    document.body.classList.add('motion-entering');
    window.setTimeout(function () { document.body.classList.remove('motion-entering'); }, 420);

    var revealObserver = 'IntersectionObserver' in window
      ? new IntersectionObserver(function (entries, observer) {
          entries.forEach(function (entry) {
            if (entry.isIntersecting) {
              entry.target.classList.add('is-visible');
              observer.unobserve(entry.target);
            }
          });
        }, { threshold: 0.08 })
      : null;

    revealElement(document.body, revealObserver);
    addLoader();

    document.addEventListener('click', function (event) {
      var link = event.target.closest('a[href]');
      if (!link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
      if (link.target || link.hasAttribute('download') || link.getAttribute('rel') === 'external') return;

      var destination;
      try { destination = new URL(link.href, window.location.href); }
      catch (error) { return; }

      if (destination.origin !== window.location.origin || destination.protocol !== 'http:' && destination.protocol !== 'https:') return;
      if (destination.pathname === window.location.pathname && destination.search === window.location.search) return;

      event.preventDefault();
      document.body.classList.add('is-navigating');
      window.setTimeout(function () { window.location.href = destination.href; }, 180);
    });

    var featureSelector = '.cart-drawer,.checkout-modal';
    var observedFeatures = new WeakSet();

    function animateFeature(feature) {
      if (feature.classList.contains('is-open')) {
        feature.classList.add('motion-visible');
        if (!observedFeatures.has(feature)) {
          observedFeatures.add(feature);
          var featureLoader = document.createElement('div');
          featureLoader.className = 'site-feature-loader';
          featureLoader.innerHTML = '<span class="site-feature-loader__spinner" aria-hidden="true"></span><span>Memuat...</span>';
          feature.appendChild(featureLoader);
          window.setTimeout(function () { featureLoader.remove(); }, 200);
        }
      } else {
        feature.classList.remove('motion-visible');
        observedFeatures.delete(feature);
      }
    }

    document.querySelectorAll(featureSelector).forEach(function (feature) {
      new MutationObserver(function () { animateFeature(feature); }).observe(feature, { attributes: true, attributeFilter: ['class'] });
    });

    new MutationObserver(function (records) {
      records.forEach(function (record) {
        record.addedNodes.forEach(function (node) {
          if (!(node instanceof Element)) return;
          revealElement(node, revealObserver);
          if (node.matches(featureSelector)) {
            new MutationObserver(function () { animateFeature(node); }).observe(node, { attributes: true, attributeFilter: ['class'] });
          }
        });
      });
    }).observe(document.body, { childList: true, subtree: true });
  });
})();