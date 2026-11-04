(function () {
  const savedTheme = localStorage.getItem('theme') || 'cyberpunk';
  document.documentElement.setAttribute('data-theme', savedTheme);
})();
