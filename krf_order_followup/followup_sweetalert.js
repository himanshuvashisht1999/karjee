(function () {
 'use strict';
 let loading;
 window.krfFollowupSweetAlert = async function (options) {
  if (!window.Swal || typeof window.Swal.fire !== 'function') {
   if (!loading) loading = new Promise(function (resolve, reject) {
    const script = document.createElement('script');
    script.src = 'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js';
    script.onload = resolve;
    script.onerror = function () { loading = null; script.remove(); reject(new Error('Unable to load SweetAlert. Please check your connection and try again.')); };
    document.head.appendChild(script);
   });
   await loading;
  }
  return window.Swal.fire(Object.assign({heightAuto: false}, options));
 };
 const style = document.createElement('style');
 style.textContent = '.swal2-container{z-index:200000!important}';
 document.head.appendChild(style);
})();
