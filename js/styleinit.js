(function () {
  const savedTheme = localStorage.getItem('theme') || 'lainrocks';
  document.documentElement.setAttribute('data-theme', savedTheme);
})();