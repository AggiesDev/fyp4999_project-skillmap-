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
    const hiddenField = container.closest('.skillmap-rating-row, .skillmap-review-rating, .d-flex, .border, .card')?.querySelector('.skill-rating-value');

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

  const recipientModeInputs = document.querySelectorAll('[data-recipient-mode]');
  if (recipientModeInputs.length > 0) {
    const recipientRoleSelect = document.querySelector('[data-recipient-role-select]');
    const recipientUserSelect = document.querySelector('[data-recipient-user-select]');
    const syncRecipientMode = () => {
      const checkedMode = document.querySelector('[data-recipient-mode]:checked')?.value || 'role';
      if (recipientRoleSelect) {
        recipientRoleSelect.disabled = checkedMode !== 'role';
      }
      if (recipientUserSelect) {
        recipientUserSelect.disabled = checkedMode !== 'user';
      }
    };

    recipientModeInputs.forEach((input) => {
      input.addEventListener('change', syncRecipientMode);
    });
    syncRecipientMode();
  }

  const syncAdminFormLayouts = () => {
    document.querySelectorAll('.skillmap-admin-form-side').forEach((side) => {
      const visiblePanel = side.querySelector('.card:not(.d-none)');
      side.classList.toggle('skillmap-form-hidden', !visiblePanel);
    });
  };

  document.querySelectorAll('[data-toggle-panel]').forEach((button) => {
    button.addEventListener('click', () => {
      const targetId = button.getAttribute('data-toggle-panel');
      if (!targetId) return;
      const target = document.getElementById(targetId);
      if (!target) return;
      target.classList.toggle('d-none');
      syncAdminFormLayouts();
    });
  });

  syncAdminFormLayouts();

  document.querySelectorAll('[data-skill-status-filter]').forEach((button) => {
    button.addEventListener('click', () => {
      const filter = button.getAttribute('data-skill-status-filter') || 'all';
      document.querySelectorAll('[data-skill-status]').forEach((row) => {
        const rowStatus = row.getAttribute('data-skill-status') || 'all';
        row.classList.toggle('d-none', filter !== 'all' && rowStatus !== filter);
      });
      document.dispatchEvent(new CustomEvent('skillmap:table-visibility-change'));
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
      document.dispatchEvent(new CustomEvent('skillmap:table-visibility-change'));
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

      document.dispatchEvent(new CustomEvent('skillmap:table-visibility-change'));
    };

    input.addEventListener('input', applySearch);
    applySearch();
  });

  document.querySelectorAll('[data-select-search]').forEach((input) => {
    const select = document.getElementById(input.getAttribute('data-select-search') || '');
    if (!select) return;

    const options = Array.from(select.options);
    const applySelectSearch = () => {
      const query = input.value.trim().toLowerCase();
      let firstVisible = null;

      options.forEach((option) => {
        const matches = query === '' || option.textContent.toLowerCase().includes(query);
        option.hidden = !matches;
        option.disabled = !matches;
        if (matches && firstVisible === null) {
          firstVisible = option;
        }
      });

      if (select.selectedOptions[0]?.disabled && firstVisible) {
        select.value = firstVisible.value;
      }
    };

    input.addEventListener('input', applySelectSearch);
    applySelectSearch();
  });

  const initAdminTablePagination = () => {
    if (!document.querySelector('.skillmap-admin-sidebar')) return;

    document.querySelectorAll('.table-responsive > table').forEach((table) => {
      if (table.dataset.skillmapPaginated === '1') return;

      const tbody = table.tBodies[0];
      if (!tbody) return;

      const headerRow = table.tHead?.rows[0] || null;
      const firstHeader = headerRow?.cells[0] || null;
      const hasNumberColumn = firstHeader && ['#', 'no.', 'no'].includes((firstHeader.textContent || '').trim().toLowerCase());
      if (headerRow && !hasNumberColumn) {
        const numberHeader = document.createElement('th');
        numberHeader.className = 'skillmap-table-number-col';
        numberHeader.scope = 'col';
        numberHeader.textContent = 'No.';
        headerRow.prepend(numberHeader);
      } else if (firstHeader) {
        firstHeader.classList.add('skillmap-table-number-col');
        firstHeader.textContent = 'No.';
      }

      Array.from(tbody.rows).forEach((row) => {
        const currentColspan = Number(row.cells[0]?.getAttribute('colspan') || '0');
        if (row.matches('[data-search-empty]') || currentColspan > 1) {
          if (currentColspan > 0 && !row.dataset.skillmapColspanAdjusted) {
            row.cells[0].setAttribute('colspan', String(currentColspan + (hasNumberColumn ? 0 : 1)));
            row.dataset.skillmapColspanAdjusted = '1';
          }
          return;
        }

        const firstCell = row.cells[0] || null;
        const rowHasNumberCell = firstCell?.classList.contains('skillmap-table-number-col') || hasNumberColumn;
        if (!rowHasNumberCell) {
          const numberCell = document.createElement('td');
          numberCell.className = 'skillmap-table-number-col';
          row.prepend(numberCell);
        } else if (firstCell) {
          firstCell.classList.add('skillmap-table-number-col');
        }
      });

      const rows = Array.from(tbody.rows).filter((row) => !row.matches('[data-search-empty]'));
      if (rows.length === 0) return;

      table.dataset.skillmapPaginated = '1';

      const pager = document.createElement('div');
      pager.className = 'skillmap-table-pager';
      pager.setAttribute('aria-label', 'Table pagination');
      table.closest('.table-responsive')?.after(pager);

      let currentPage = 1;
      const rowsPerPage = 10;

      const visibleRows = () => rows.filter((row) => !row.classList.contains('d-none'));

      const refreshRowNumbers = (activeRows) => {
        activeRows.forEach((row, index) => {
          const numberCell = row.querySelector('.skillmap-table-number-col');
          if (numberCell) {
            numberCell.textContent = String(index + 1);
          }
        });
      };

      const render = () => {
        const activeRows = visibleRows();
        const totalPages = Math.max(1, Math.ceil(activeRows.length / rowsPerPage));
        currentPage = Math.min(currentPage, totalPages);
        currentPage = Math.max(currentPage, 1);
        refreshRowNumbers(activeRows);

        rows.forEach((row) => {
          row.hidden = true;
        });

        activeRows.slice((currentPage - 1) * rowsPerPage, currentPage * rowsPerPage).forEach((row) => {
          row.hidden = false;
        });

        pager.innerHTML = '';
        if (activeRows.length <= rowsPerPage) {
          pager.classList.add('d-none');
          return;
        }

        pager.classList.remove('d-none');

        const summary = document.createElement('div');
        summary.className = 'skillmap-table-pager-summary';
        summary.textContent = `Page ${currentPage} of ${totalPages}`;
        pager.append(summary);

        const pageList = document.createElement('div');
        pageList.className = 'skillmap-table-pager-pages';

        for (let page = 1; page <= totalPages; page += 1) {
          const button = document.createElement('button');
          button.type = 'button';
          button.className = `btn btn-sm ${page === currentPage ? 'btn-primary' : 'btn-outline-secondary'}`;
          button.textContent = String(page);
          button.setAttribute('aria-label', `Go to page ${page}`);
          button.setAttribute('aria-current', page === currentPage ? 'page' : 'false');
          button.addEventListener('click', () => {
            currentPage = page;
            render();
          });
          pageList.append(button);
        }

        pager.append(pageList);
      };

      document.addEventListener('skillmap:table-visibility-change', () => {
        currentPage = 1;
        render();
      });
      render();
    });
  };

  initAdminTablePagination();

  document.querySelectorAll('[data-paginated-list]').forEach((list) => {
    const itemsPerPage = Math.max(1, Number(list.getAttribute('data-paginated-list') || '5'));
    const items = Array.from(list.querySelectorAll(':scope > [data-list-item]'));
    if (items.length === 0) return;

    const pager = document.createElement('div');
    pager.className = 'skillmap-table-pager skillmap-list-pager';
    pager.setAttribute('aria-label', 'Message pagination');
    list.after(pager);

    let currentPage = 1;

    const render = () => {
      const totalPages = Math.max(1, Math.ceil(items.length / itemsPerPage));
      currentPage = Math.min(Math.max(currentPage, 1), totalPages);

      items.forEach((item) => {
        item.hidden = true;
      });

      items.slice((currentPage - 1) * itemsPerPage, currentPage * itemsPerPage).forEach((item) => {
        item.hidden = false;
      });

      pager.innerHTML = '';
      if (items.length <= itemsPerPage) {
        pager.classList.add('d-none');
        return;
      }

      pager.classList.remove('d-none');

      const summary = document.createElement('div');
      summary.className = 'skillmap-table-pager-summary';
      summary.textContent = `Page ${currentPage} of ${totalPages}`;
      pager.append(summary);

      const pageList = document.createElement('div');
      pageList.className = 'skillmap-table-pager-pages';

      for (let page = 1; page <= totalPages; page += 1) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = `btn btn-sm ${page === currentPage ? 'btn-primary' : 'btn-outline-secondary'}`;
        button.textContent = String(page);
        button.setAttribute('aria-label', `Go to page ${page}`);
        button.setAttribute('aria-current', page === currentPage ? 'page' : 'false');
        button.addEventListener('click', () => {
          currentPage = page;
          render();
        });
        pageList.append(button);
      }

      pager.append(pageList);
    };

    render();
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
    const setStoredSidebarState = (collapsed) => {
      document.body.classList.toggle('admin-sidebar-collapsed', collapsed);
      try {
        localStorage.setItem(storageKey, collapsed ? '1' : '0');
      } catch (error) {
        // Browsers can block storage in private modes; the button should still work.
      }
    };
    const storedSidebarCollapsed = () => {
      try {
        return localStorage.getItem(storageKey) === '1';
      } catch (error) {
        return false;
      }
    };
    const syncSidebarMode = () => {
      if (mobileMedia.matches) {
        document.body.classList.remove('admin-sidebar-collapsed');
      } else {
        document.body.classList.remove('admin-sidebar-open');
        document.body.classList.toggle('admin-sidebar-collapsed', storedSidebarCollapsed());
      }
    };

    syncSidebarMode();
    adminSidebarToggle.addEventListener('click', () => {
      if (mobileMedia.matches) {
        document.body.classList.toggle('admin-sidebar-open');
        return;
      }
      setStoredSidebarState(!document.body.classList.contains('admin-sidebar-collapsed'));
    });
    document.querySelectorAll('.skillmap-admin-menu-link').forEach((link) => {
      link.addEventListener('click', () => {
        if (mobileMedia.matches) {
          document.body.classList.remove('admin-sidebar-open');
        }
      });
    });
    mobileMedia.addEventListener?.('change', syncSidebarMode);
  }
});
