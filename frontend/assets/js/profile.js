document.addEventListener('DOMContentLoaded', () => {
  const editBtn = document.getElementById('editBtn');
  const saveBtn = document.getElementById('saveBtn');
  const cancelBtn = document.getElementById('cancelBtn');
  const form = document.getElementById('profileForm');

  const changePasswordBtn = document.getElementById('changePasswordBtn');
  const changePasswordModal = document.getElementById('changePasswordModal');
  const closeModalBtn = document.querySelector('.close-btn');
  const changePasswordForm = document.getElementById('changePasswordForm');
  const successMessage = document.getElementById('successMessage');
  
  if (!editBtn || !saveBtn || !cancelBtn || !form || !changePasswordBtn || !changePasswordModal || !closeModalBtn || !changePasswordForm) return;

  const getInputs = () => {
    const inside = Array.from(form.querySelectorAll('input.field-input'));
    const linked = Array.from(document.querySelectorAll('input.field-input[form="profileForm"]'));
    return Array.from(new Set([...inside, ...linked]));
  };

  const inputs = getInputs();

  const setEditMode = (on) => {
    document.body.classList.toggle('profile-edit', on);
    inputs.forEach(i => (i.disabled = !on));

    editBtn.style.display = on ? 'none' : 'inline-flex';
    saveBtn.style.display = on ? 'inline-flex' : 'none';
    cancelBtn.style.display = on ? 'inline-flex' : 'none';
  };

  editBtn.addEventListener('click', () => setEditMode(true));

  cancelBtn.addEventListener('click', () => {
    inputs.forEach(i => {
      if (i.dataset.original !== undefined) i.value = i.dataset.original;
    });
    setEditMode(false);
  });

  form.addEventListener('submit', () => {
    saveBtn.disabled = true;
  });

  changePasswordBtn.addEventListener("click", () => {
    changePasswordModal.style.display = "block";
    successMessage.style.display = 'none';  
  });

  closeModalBtn.addEventListener("click", () => {
    changePasswordModal.style.display = "none";
  });

  window.onclick = (event) => {
    if (event.target === changePasswordModal) {
      changePasswordModal.style.display = "none";
    }
  };

  changePasswordForm.addEventListener('submit', function (event) {
    event.preventDefault();

    const oldPassword = document.getElementById('oldPassword').value;
    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;

    const errorMessage = document.getElementById('errorMessage');


    if (errorMessage) {
      errorMessage.textContent = '';
      errorMessage.style.display = 'none';
    }

    if (newPassword !== confirmPassword) {
      const errorMessage = document.getElementById('errorMessage');
      errorMessage.textContent = 'Passwords do not match!';
      errorMessage.style.display = 'block';
      return;
    }

    const formData = {
      oldPassword: oldPassword,
      newPassword: newPassword,
      newPassword_confirmation: confirmPassword
    };

    fetch('change_password.php', {
      method: 'POST',
      body: JSON.stringify(formData),
      headers: {
        'Content-Type': 'application/json',
      },
      })
      .then(response => response.json())
      .then(data => {
        if (data.message === 'Password updated successfully') {
          successMessage.style.display = 'block';
          changePasswordModal.style.display = "none";

          document.getElementById('oldPassword').value = '';
          document.getElementById('newPassword').value = '';
          document.getElementById('confirmPassword').value = '';
        } else if (data.error) {
          const errorMessage = document.getElementById('errorMessage');
          errorMessage.textContent = data.error;
          errorMessage.style.display = 'block';

          changePasswordModal.style.display = "none";
          
          document.getElementById('oldPassword').value = '';
          document.getElementById('newPassword').value = '';
          document.getElementById('confirmPassword').value = '';
        } else {
          const errorMessage = document.getElementById('errorMessage');
          errorMessage.textContent = 'Something went wrong! Please try again.';
          errorMessage.style.display = 'block';
        }
      })
      .catch(error => {
        console.error('Error:', error);
        const errorMessage = document.getElementById('errorMessage');
        errorMessage.textContent = 'There was an error with the request.';
        errorMessage.style.display = 'block';
      });
  });
});