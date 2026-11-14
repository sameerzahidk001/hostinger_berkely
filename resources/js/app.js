// Public pages do not need Bootstrap JS or axios (those live in admin).
// Keep this bundle to layout-only helpers so PageSpeed unused-JS stays low.

// Make all tables horizontally scrollable on mobile
document.addEventListener('DOMContentLoaded', () => {
  const tables = Array.from(document.querySelectorAll('table'));
  for (const table of tables) {
    if (table.closest('.table-responsive')) continue;
    if (table.closest('.note-editor')) continue;

    const wrapper = document.createElement('div');
    wrapper.className = 'table-responsive';
    table.parentNode?.insertBefore(wrapper, table);
    wrapper.appendChild(table);
  }
});
