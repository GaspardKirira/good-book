document.addEventListener("DOMContentLoaded", () => {
  // THEME TOGGLE
  const themeToggle = document.getElementById("theme-toggle");

  // read saved theme
  const savedTheme = localStorage.getItem("theme"); // 'dark' or 'light'
  const prefersDark =
    window.matchMedia &&
    window.matchMedia("(prefers-color-scheme: dark)").matches;

  const applyTheme = (theme) => {
    if (theme === "dark") {
      document.body.classList.add("dark-theme");
      if (themeToggle) themeToggle.textContent = "☀️";
    } else {
      document.body.classList.remove("dark-theme");
      if (themeToggle) themeToggle.textContent = "🌙";
    }
  };

  // initial
  if (savedTheme) {
    applyTheme(savedTheme);
  } else {
    applyTheme(prefersDark ? "dark" : "light");
  }

  if (themeToggle) {
    themeToggle.addEventListener("click", () => {
      const isDark = document.body.classList.toggle("dark-theme");
      localStorage.setItem("theme", isDark ? "dark" : "light");
      themeToggle.textContent = isDark ? "☀️" : "🌙";
    });
  }

  // MOBILE MENU TOGGLE
  const menuToggle = document.getElementById("menu-toggle");
  const mainNav = document.getElementById("main-nav");

  if (menuToggle && mainNav) {
    menuToggle.addEventListener("click", () => {
      mainNav.classList.toggle("active");
    });
  }
});
