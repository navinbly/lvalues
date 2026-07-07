(function () {
  var key = 'lvalues_admin_theme';
  var savedTheme = localStorage.getItem(key) || 'light';

  function applyTheme(theme) {
    document.body.setAttribute('data-admin-theme', theme);
    var toggle = document.querySelector('[data-admin-theme-toggle]');
    if (toggle) {
      toggle.setAttribute('aria-pressed', theme === 'dark' ? 'true' : 'false');
      toggle.setAttribute('title', theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode');
      var icon = toggle.querySelector('i');
      if (icon) {
        icon.className = theme === 'dark' ? 'mdi mdi-white-balance-sunny' : 'mdi mdi-moon-waning-crescent';
      }
    }
  }

  function labelResponsiveTables() {
    document.querySelectorAll('table').forEach(function (table) {
      var headers = Array.prototype.slice.call(table.querySelectorAll('thead th')).map(function (th) {
        return th.textContent.trim();
      });
      if (headers.length < 2) return;
      table.classList.add('admin-card-table');
      table.querySelectorAll('tbody tr').forEach(function (row) {
        Array.prototype.slice.call(row.children).forEach(function (cell, index) {
          if (!cell.getAttribute('data-label') && headers[index]) {
            cell.setAttribute('data-label', headers[index]);
          }
        });
      });
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    applyTheme(savedTheme);
    labelResponsiveTables();
    setTimeout(labelResponsiveTables, 800);

    var toggle = document.querySelector('[data-admin-theme-toggle]');
    if (toggle) {
      toggle.addEventListener('click', function () {
        var next = document.body.getAttribute('data-admin-theme') === 'dark' ? 'light' : 'dark';
        localStorage.setItem(key, next);
        applyTheme(next);
      });
    }
  });
})();
