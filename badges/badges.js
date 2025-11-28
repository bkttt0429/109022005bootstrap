(function () {
  'use strict';

  const inputs = {
    text: document.getElementById('badge-text'),
    color: document.getElementById('badge-color'),
    subtle: document.getElementById('badge-subtle'),
    pill: document.getElementById('badge-pill'),
    withIcon: document.getElementById('badge-with-icon'),
    iconUrl: document.getElementById('badge-icon-url'),
  };

  const preview = document.getElementById('badge-preview');
  const samples = document.getElementById('badge-samples');
  const btnUpdate = document.getElementById('badge-update');
  const btnAdd = document.getElementById('badge-add');
  const btnReset = document.getElementById('badge-reset');

  function buildBadgeEl({ text, color, subtle, pill, withIcon, iconUrl }) {
    const span = document.createElement('span');
    span.classList.add('badge', 'd-flex', 'align-items-center', 'p-2');

    // Apply classes
    if (subtle) {
      span.classList.add(`bg-${color}-subtle`, `text-${color}-emphasis`);
    } else {
      // For light and dark we use slightly different defaults so text stays visible
      span.classList.add(`text-bg-${color}`);
    }

    if (pill) span.classList.add('rounded-pill');

    // Icon
    if (withIcon) {
      const img = document.createElement('img');
      img.className = 'rounded-circle me-1';
      img.width = 24;
      img.height = 24;
      img.alt = '';
      img.src = iconUrl && iconUrl.trim() ? iconUrl.trim() : 'https://github.com/mdo.png';
      span.appendChild(img);
    }

    const txt = document.createElement('span');
    txt.textContent = text || '示例';
    span.appendChild(txt);

    return span;
  }

  function updatePreview() {
    preview.innerHTML = '';
    const data = {
      text: inputs.text.value,
      color: inputs.color.value,
      subtle: inputs.subtle.checked,
      pill: inputs.pill.checked,
      withIcon: inputs.withIcon.checked,
      iconUrl: inputs.iconUrl.value,
    };

    const el = buildBadgeEl(data);
    preview.appendChild(el);
  }

  function addToSamples() {
    const data = {
      text: inputs.text.value,
      color: inputs.color.value,
      subtle: inputs.subtle.checked,
      pill: inputs.pill.checked,
      withIcon: inputs.withIcon.checked,
      iconUrl: inputs.iconUrl.value,
    };

    const el = buildBadgeEl(data);

    // Add delete control
    const wrapper = document.createElement('div');
    wrapper.className = 'd-inline-flex align-items-center';

    const del = document.createElement('a');
    del.href = '#';
    del.className = 'ms-2 text-decoration-none';
    del.setAttribute('aria-label', '刪除徽章');
    del.innerHTML = '<svg class="bi" width="16" height="16" aria-hidden="true"><use xlink:href="#x-circle-fill"></use></svg>';
    del.addEventListener('click', (e) => {
      e.preventDefault();
      wrapper.remove();
    });

    wrapper.appendChild(el);
    wrapper.appendChild(del);

    samples.appendChild(wrapper);
  }

  function resetControls() {
    inputs.text.value = '示例';
    inputs.color.value = 'primary';
    inputs.subtle.checked = false;
    inputs.pill.checked = false;
    inputs.withIcon.checked = false;
    inputs.iconUrl.value = '';
    updatePreview();
  }

  // attach events
  btnUpdate.addEventListener('click', (e) => {
    e.preventDefault();
    updatePreview();
  });

  btnAdd.addEventListener('click', (e) => {
    e.preventDefault();
    addToSamples();
  });

  btnReset.addEventListener('click', (e) => {
    e.preventDefault();
    resetControls();
  });

  // realtime update when changing important controls
  ['change', 'input'].forEach((ev) => {
    inputs.text.addEventListener(ev, updatePreview);
    inputs.color.addEventListener(ev, updatePreview);
    inputs.subtle.addEventListener(ev, updatePreview);
    inputs.pill.addEventListener(ev, updatePreview);
    inputs.withIcon.addEventListener(ev, updatePreview);
    inputs.iconUrl.addEventListener(ev, updatePreview);
  });

  // init
  document.addEventListener('DOMContentLoaded', () => {
    updatePreview();
  });
})();
