// Theme management system
class ThemeManager {
  constructor() {
    this.buttons = document.querySelectorAll('.theme-btn');
    this.cssLink = document.getElementById('theme-css');
    this.init();
  }

  init() {
    const currentTheme = document.documentElement.getAttribute('data-theme') || 'cyberpunk';
    this.applyTheme(currentTheme, false);

    this.buttons.forEach(btn => {
      btn.addEventListener('click', () => {
        this.setTheme(btn.dataset.theme);
      });
    });
  }

  setTheme(themeName) {
    document.documentElement.setAttribute('data-theme', themeName);
    localStorage.setItem('theme', themeName);
    this.applyTheme(themeName, true);
  }

  applyTheme(themeName, swapFont) {
    // Swap stylesheet
    if (this.cssLink) {
      this.cssLink.href = `../css/${themeName}.css`;
    }

    // Mark active button
    this.buttons.forEach(btn => {
      btn.classList.toggle('active', btn.dataset.theme === themeName);
    });

    // Swap Google Fonts if links exist in <head>
    if (swapFont) {
      const fontDefault  = document.getElementById('font-default');
      const fontYotsuba  = document.getElementById('font-yotsuba');
      const fontCyberpunk = document.getElementById('font-cyberpunk');
      if (fontDefault && fontYotsuba && fontCyberpunk) {
        fontDefault.disabled = (themeName !== 'lainrocks');
        fontYotsuba.disabled = (themeName !== 'yotsuba');
        fontCyberpunk.disabled = (themeName !== 'cyberpunk');
      }
    }
  }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
  new ThemeManager();
});

// Respect system preference only if user hasn't chosen manually
if (window.matchMedia) {
  window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
    if (!localStorage.getItem('theme')) {
      const auto = e.matches ? 'cyberpunk' : 'yotsuba';
      document.documentElement.setAttribute('data-theme', auto);
      document.querySelectorAll('.theme-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.theme === auto);
      });
    }
  });
}