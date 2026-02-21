document.addEventListener('DOMContentLoaded', () => {
  const editBtn = document.getElementById('editBtn');
  const saveBtn = document.getElementById('saveBtn');
  const cancelBtn = document.getElementById('cancelBtn');
  const form = document.getElementById('profileForm');

  const changePasswordBtn = document.getElementById('changePasswordBtn');
  const changePasswordModal = document.getElementById('changePasswordModal');
  const changePasswordForm = document.getElementById('changePasswordForm');
  const successMessage = document.getElementById('successMessage');
  const errorMessage = document.getElementById('errorMessage');

  const viewSessionsBtn = document.getElementById('viewSessionsBtn');
  const sessionsModal = document.getElementById('sessionsModal');
  const closeSessionsModalBtn = document.getElementById('closeSessionsModal');
  const sessionsList = document.getElementById('sessionsList');
  const sessionsError = document.getElementById('sessionsError');
  const sessionsSuccess = document.getElementById('sessionsSuccess');
  const logoutOtherSessionsBtn = document.getElementById('logoutOtherSessionsBtn');

  const becomeDriverBtn = document.getElementById('becomeDriverBtn');
  const becomeDriverModal = document.getElementById('becomeDriverModal');
  const closeBecomeDriverModalBtn = document.getElementById('closeBecomeDriverModal');
  const becomeDriverForm = document.getElementById('becomeDriverForm');
  const becomeDriverError = document.getElementById('becomeDriverError');
  const becomeDriverSuccess = document.getElementById('becomeDriverSuccess');

  if (!editBtn || !saveBtn || !cancelBtn || !form || !changePasswordBtn || !changePasswordModal || !changePasswordForm) return;

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

  const closeModal = (modal) => {
    if (!modal) return;
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
  };

  const openModal = (modal) => {
    if (!modal) return;
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
  };

  changePasswordBtn.addEventListener('click', () => {
    if (successMessage) successMessage.style.display = 'none';
    if (errorMessage) {
      errorMessage.textContent = '';
      errorMessage.style.display = 'none';
    }

    document.getElementById('oldPassword').value = '';
    document.getElementById('newPassword').value = '';
    document.getElementById('confirmPassword').value = '';

    openModal(changePasswordModal);
  });

  const changePasswordCloseBtn = changePasswordModal.querySelector('.close-btn');
  if (changePasswordCloseBtn) {
    changePasswordCloseBtn.addEventListener('click', () => closeModal(changePasswordModal));
  }

  changePasswordForm.addEventListener('submit', function (event) {
    event.preventDefault();

    const oldPassword = document.getElementById('oldPassword').value;
    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;

    if (errorMessage) {
      errorMessage.textContent = '';
      errorMessage.style.display = 'none';
    }

    if (newPassword !== confirmPassword) {
      if (errorMessage) {
        errorMessage.textContent = 'Passwords do not match!';
        errorMessage.style.display = 'block';
      }
      return;
    }

    const formData = {
      oldPassword,
      newPassword,
      newPassword_confirmation: confirmPassword,
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
          if (successMessage) successMessage.style.display = 'block';
          closeModal(changePasswordModal);

          document.getElementById('oldPassword').value = '';
          document.getElementById('newPassword').value = '';
          document.getElementById('confirmPassword').value = '';
        } else {
          if (errorMessage) {
            errorMessage.textContent = data.error || data.message || 'Could not update the password.';
            errorMessage.style.display = 'block';
          }
          closeModal(changePasswordModal);
        }
      })
      .catch(() => {
        if (errorMessage) {
          errorMessage.textContent = 'There was an error with the request.';
          errorMessage.style.display = 'block';
        }
      });

    setTimeout(() => {
      if (successMessage) successMessage.style.display = 'none';
      if (errorMessage) errorMessage.style.display = 'none';
    }, 10000);
  });

  async function loadSessions() {
    if (!sessionsList) return;

    sessionsList.innerHTML = '<div class="sessions-empty">Loading sessions...</div>';
    if (sessionsError) {
      sessionsError.textContent = '';
      sessionsError.style.display = 'none';
    }
    if (sessionsSuccess) {
      sessionsSuccess.textContent = '';
      sessionsSuccess.style.display = 'none';
    }

    try {
      const url = new URL('/GoRide/frontend/api/sessions.php', window.location.origin);
      const res = await fetch(url.toString(), { headers: { Accept: 'application/json' } });
      const json = await res.json().catch(() => ({}));

      if (!res.ok) {
        throw new Error(json.message || `Failed to load sessions (${res.status})`);
      }

      const sessions = Array.isArray(json.sessions) ? json.sessions : [];
      if (sessions.length === 0) {
        sessionsList.innerHTML = '<div class="sessions-empty">No active sessions found.</div>';
        return;
      }

      const rows = sessions.map((s) => {
        const createdAt = s.created_at ? new Date(s.created_at).toLocaleString() : '-';
        const lastUsedAt = s.last_used_at ? new Date(s.last_used_at).toLocaleString() : 'Never';
        const currentBadge = s.is_current ? '<span class="session-current">Current</span>' : '';

        return `
          <div class="session-item">
            <div class="session-title">${(s.name || 'Session')} ${currentBadge}</div>
            <div class="session-sub">Created: ${createdAt}</div>
            <div class="session-sub">Last used: ${lastUsedAt}</div>
          </div>
        `;
      });

      sessionsList.innerHTML = rows.join('');
    } catch (e) {
      sessionsList.innerHTML = '<div class="sessions-empty">Could not load sessions.</div>';
      if (sessionsError) {
        sessionsError.textContent = e.message || 'Could not load sessions.';
        sessionsError.style.display = 'block';
      }
    }
  }

  if (viewSessionsBtn && sessionsModal) {
    viewSessionsBtn.addEventListener('click', async () => {
      openModal(sessionsModal);
      await loadSessions();
    });
  }

  if (closeSessionsModalBtn && sessionsModal) {
    closeSessionsModalBtn.addEventListener('click', () => closeModal(sessionsModal));
  }

  if (logoutOtherSessionsBtn) {
    logoutOtherSessionsBtn.addEventListener('click', async () => {
      logoutOtherSessionsBtn.disabled = true;

      if (sessionsError) {
        sessionsError.textContent = '';
        sessionsError.style.display = 'none';
      }
      if (sessionsSuccess) {
        sessionsSuccess.textContent = '';
        sessionsSuccess.style.display = 'none';
      }

      try {
        const url = new URL('/GoRide/frontend/api/logout_other_sessions.php', window.location.origin);
        const res = await fetch(url.toString(), {
          method: 'POST',
          headers: { Accept: 'application/json' },
        });

        const json = await res.json().catch(() => ({}));
        if (!res.ok) {
          throw new Error(json.message || `Failed to log out other sessions (${res.status})`);
        }

        if (sessionsSuccess) {
          sessionsSuccess.textContent = json.message || 'Logged out from all other sessions.';
          sessionsSuccess.style.display = 'block';
        }

        await loadSessions();
      } catch (e) {
        if (sessionsError) {
          sessionsError.textContent = e.message || 'Could not log out other sessions.';
          sessionsError.style.display = 'block';
        }
      } finally {
        logoutOtherSessionsBtn.disabled = false;
      }
    });
  }

  if (becomeDriverBtn && becomeDriverModal) {
    becomeDriverBtn.addEventListener('click', () => {
      if (becomeDriverError) {
        becomeDriverError.textContent = '';
        becomeDriverError.style.display = 'none';
      }
      if (becomeDriverSuccess) {
        becomeDriverSuccess.textContent = '';
        becomeDriverSuccess.style.display = 'none';
      }
      if (becomeDriverForm) becomeDriverForm.reset();
      openModal(becomeDriverModal);
    });
  }

  if (closeBecomeDriverModalBtn && becomeDriverModal) {
    closeBecomeDriverModalBtn.addEventListener('click', () => closeModal(becomeDriverModal));
  }

  if (becomeDriverForm) {
    becomeDriverForm.addEventListener('submit', async (event) => {
      event.preventDefault();

      const vehicleMake = document.getElementById('applyVehicleMake')?.value?.trim() || '';
      const vehicleModel = document.getElementById('applyVehicleModel')?.value?.trim() || '';
      const vehicleColor = document.getElementById('applyVehicleColor')?.value?.trim() || '';
      const licensePlate = document.getElementById('applyLicensePlate')?.value?.trim() || '';
      const passengerCapacity = Number(document.getElementById('applyPassengerCapacity')?.value || 0);

      if (becomeDriverError) {
        becomeDriverError.textContent = '';
        becomeDriverError.style.display = 'none';
      }
      if (becomeDriverSuccess) {
        becomeDriverSuccess.textContent = '';
        becomeDriverSuccess.style.display = 'none';
      }

      if (!vehicleMake || !vehicleModel || !vehicleColor || !licensePlate || !Number.isInteger(passengerCapacity) || passengerCapacity < 1 || passengerCapacity > 8) {
        if (becomeDriverError) {
          becomeDriverError.textContent = 'All fields are required and capacity must be 1-8.';
          becomeDriverError.style.display = 'block';
        }
        return;
      }

      try {
        const url = new URL('/GoRide/frontend/api/driver_apply.php', window.location.origin);
        const res = await fetch(url.toString(), {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
          body: JSON.stringify({
            vehicle_make: vehicleMake,
            vehicle_model: vehicleModel,
            vehicle_color: vehicleColor,
            license_plate: licensePlate,
            passenger_capacity: passengerCapacity,
          }),
        });

        const json = await res.json().catch(() => ({}));
        if (!res.ok) {
          throw new Error(json.message || `Application failed (${res.status})`);
        }

        if (becomeDriverSuccess) {
          becomeDriverSuccess.textContent = json.message || 'Driver application sent successfully.';
          becomeDriverSuccess.style.display = 'block';
        }
      } catch (e) {
        if (becomeDriverError) {
          becomeDriverError.textContent = e.message || 'Failed to send driver application.';
          becomeDriverError.style.display = 'block';
        }
      }
    });
  }

  window.addEventListener('click', (event) => {
    if (event.target === changePasswordModal) closeModal(changePasswordModal);
    if (sessionsModal && event.target === sessionsModal) closeModal(sessionsModal);
    if (becomeDriverModal && event.target === becomeDriverModal) closeModal(becomeDriverModal);
  });
});
