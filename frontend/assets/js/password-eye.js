document.querySelectorAll('.eye-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const input = document.getElementById(btn.dataset.target);
        const img = btn.querySelector('img');

        if (input.type === 'password') {
        input.type = 'text';
        img.src = 'assets/images/Icons/eye-open.svg';
        } else {
        input.type = 'password';
        img.src = 'assets/images/Icons/eye-closed.svg';
        }
    });
});
