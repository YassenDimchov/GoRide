(() => {
  const editBtn = document.getElementById('editBtn');
  const saveBtn = document.getElementById('saveBtn');
  const cancelBtn = document.getElementById('cancelBtn');
  const form = document.getElementById('profileForm');

  if (!editBtn || !saveBtn || !cancelBtn || !form) return;

  const inputs = Array.from(form.querySelectorAll('input.field-input'));

  const setEditMode = (on) => {
    document.body.classList.toggle('profile-edit', on);
    inputs.forEach(i => (i.disabled = !on));

    editBtn.style.display = on ? 'none' : 'inline-flex';
    saveBtn.style.display = on ? 'inline-flex' : 'none';
    cancelBtn.style.display = on ? 'inline-flex' : 'none';
  };

  editBtn.addEventListener('click', () => setEditMode(true));

  cancelBtn.addEventListener('click', () => {
    inputs.forEach(i => (i.value = i.dataset.original ?? i.value));
    setEditMode(false);
  });

  form.addEventListener('submit', () => {
    saveBtn.disabled = true;
  });
})();
