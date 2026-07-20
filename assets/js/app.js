document.addEventListener('DOMContentLoaded', () => {
  const passwordToggles = document.querySelectorAll('[data-toggle-password]');
  passwordToggles.forEach((button) => {
    button.addEventListener('click', () => {
      const targetId = button.getAttribute('data-toggle-password');
      const input = document.getElementById(targetId);
      if (!input) return;
      const type = input.type === 'password' ? 'text' : 'password';
      input.type = type;
      button.querySelector('i')?.classList.toggle('bi-eye');
      button.querySelector('i')?.classList.toggle('bi-eye-slash');
    });
  });

  const starContainers = document.querySelectorAll('.skillmap-stars');
  starContainers.forEach((container) => {
    const rating = Number(container.getAttribute('data-rating') || '0');
    const buttons = container.querySelectorAll('.star-btn');
    const hiddenField = container.closest('.d-flex, .border, .card')?.querySelector('.skill-rating-value');

    const paint = (value) => {
      buttons.forEach((button) => {
        const icon = button.querySelector('i');
        if (!icon) return;
        const buttonValue = Number(button.getAttribute('data-value'));
        const isFilled = buttonValue <= value;
        icon.className = isFilled ? 'bi bi-star-fill text-warning' : 'bi bi-star text-warning';
      });

      if (hiddenField) {
        hiddenField.value = String(value);
      }
    };

    paint(rating);
    buttons.forEach((button) => {
      button.addEventListener('click', () => {
        const value = Number(button.getAttribute('data-value'));
        container.setAttribute('data-rating', String(value));
        paint(value);
      });
    });
  });

  const tabButtons = document.querySelectorAll('[data-skillmap-tab]');
  tabButtons.forEach((button) => {
    button.addEventListener('click', () => {
      const target = button.getAttribute('data-skillmap-tab');
      document.querySelectorAll('[data-skillmap-tab-panel]').forEach((panel) => {
        panel.classList.toggle('d-none', panel.getAttribute('data-skillmap-tab-panel') !== target);
      });
      tabButtons.forEach((tab) => tab.classList.remove('active'));
      button.classList.add('active');
    });
  });

  document.querySelectorAll('[data-copy-text]').forEach((button) => {
    button.addEventListener('click', async () => {
      const text = button.getAttribute('data-copy-text') || '';
      try {
        await navigator.clipboard.writeText(text);
        const original = button.textContent;
        button.textContent = 'Copied';
        setTimeout(() => { button.textContent = original; }, 1200);
      } catch (error) {
        console.warn('Clipboard copy failed', error);
      }
    });
  });

  document.querySelectorAll('[data-href]').forEach((button) => {
    button.addEventListener('click', () => {
      const href = button.getAttribute('data-href');
      if (href) {
        window.location.href = href;
      }
    });
  });

  document.querySelectorAll('[data-toggle-panel]').forEach((button) => {
    button.addEventListener('click', () => {
      const targetId = button.getAttribute('data-toggle-panel');
      if (!targetId) return;
      const target = document.getElementById(targetId);
      if (!target) return;
      target.classList.toggle('d-none');
    });
  });

  document.querySelectorAll('[data-skill-status-filter]').forEach((button) => {
    button.addEventListener('click', () => {
      const filter = button.getAttribute('data-skill-status-filter') || 'all';
      document.querySelectorAll('[data-skill-status]').forEach((row) => {
        const rowStatus = row.getAttribute('data-skill-status') || 'all';
        row.classList.toggle('d-none', filter !== 'all' && rowStatus !== filter);
      });
      document.querySelectorAll('[data-skill-status-filter]').forEach((tab) => tab.classList.remove('active'));
      button.classList.add('active');
    });
  });

  document.querySelectorAll('[data-table-filter]').forEach((button) => {
    button.addEventListener('click', () => {
      const filter = button.getAttribute('data-table-filter') || 'all';
      const targetSelector = button.getAttribute('data-table-filter-target');
      if (!targetSelector) return;
      document.querySelectorAll(`${targetSelector} [data-filter-value]`).forEach((row) => {
        const rowValue = (row.getAttribute('data-filter-value') || '').toLowerCase();
        row.classList.toggle('d-none', filter !== 'all' && rowValue !== filter.toLowerCase());
      });
      const group = button.closest('[data-table-filter-group]');
      if (group) {
        group.querySelectorAll('[data-table-filter]').forEach((tab) => tab.classList.remove('active'));
      }
      button.classList.add('active');
    });
  });

  document.querySelectorAll('[data-search-input]').forEach((input) => {
    const targetSelector = input.getAttribute('data-search-target');
    const scope = targetSelector ? document.querySelector(targetSelector) : input.closest('[data-search-scope]');
    if (!scope) return;

    const emptyStateSelector = input.getAttribute('data-search-empty');
    const emptyState = emptyStateSelector ? document.querySelector(emptyStateSelector) : scope.querySelector('[data-search-empty]');
    const items = Array.from(scope.querySelectorAll('[data-search-item]'));

    const applySearch = () => {
      const query = input.value.trim().toLowerCase();
      let visibleCount = 0;

      items.forEach((item) => {
        const haystack = (item.getAttribute('data-search-text') || item.textContent || '').toLowerCase();
        const isVisible = query === '' || haystack.includes(query);
        item.classList.toggle('d-none', !isVisible);
        if (isVisible) visibleCount += 1;
      });

      if (emptyState) {
        emptyState.classList.toggle('d-none', visibleCount > 0 || query === '');
      }
    };

    input.addEventListener('input', applySearch);
    applySearch();
  });

  const profilePreview = document.querySelector('.profile-icon-preview');
  const iconRadios = document.querySelectorAll('input[name="profile_icon"]');
  const genderSelects = document.querySelectorAll('[data-profile-gender]');
  const genderDefaults = {
    male: 'profileicons/icons8-add-user-male-100.png',
    female: 'profileicons/icons8-add-user-female-skin-type-7-100.png',
  };

  iconRadios.forEach((radio) => {
    radio.addEventListener('change', () => {
      if (profilePreview && radio.checked) {
        profilePreview.src = `/fyp_skillmapsystem/${radio.value}`;
      }
    });
  });

  genderSelects.forEach((select) => {
    select.addEventListener('change', () => {
      const targetValue = genderDefaults[select.value] || genderDefaults.male;
      const targetRadio = document.querySelector(`input[name="profile_icon"][value="${targetValue}"]`);
      if (targetRadio) {
        targetRadio.checked = true;
        targetRadio.dispatchEvent(new Event('change'));
      }
    });
  });

  const adminSidebarToggle = document.querySelector('[data-admin-sidebar-toggle]');
  if (adminSidebarToggle) {
    const storageKey = 'skillmap-admin-sidebar-collapsed';
    const mobileMedia = window.matchMedia('(max-width: 991.98px)');
    const applySidebarState = (collapsed) => {
      document.body.classList.toggle('admin-sidebar-collapsed', collapsed);
      localStorage.setItem(storageKey, collapsed ? '1' : '0');
    };
    const syncSidebarMode = () => {
      if (mobileMedia.matches) {
        document.body.classList.remove('admin-sidebar-collapsed');
      } else {
        document.body.classList.remove('admin-sidebar-open');
        applySidebarState(localStorage.getItem(storageKey) === '1');
      }
    };

    syncSidebarMode();
    adminSidebarToggle.addEventListener('click', () => {
      if (mobileMedia.matches) {
        document.body.classList.toggle('admin-sidebar-open');
        return;
      }
      applySidebarState(!document.body.classList.contains('admin-sidebar-collapsed'));
    });
    mobileMedia.addEventListener?.('change', syncSidebarMode);
  }
});
