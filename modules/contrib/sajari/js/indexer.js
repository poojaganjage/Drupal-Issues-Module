/**
 * @file
 * Indexing script for sajari.
 */

var _sj = _sj || [];
_sj.push(['project', drupalSettings.sajariIndex.project]);
_sj.push(['collection', drupalSettings.sajariIndex.collection]);
(function () {
  var sj = document.createElement('script');
  sj.type = 'text/javascript';
  sj.async = true;
  sj.src = '//cdn.sajari.com/js/sj.js';
  var s = document.getElementsByTagName('script')[0];
  s.parentNode.insertBefore(sj, s);
})();
