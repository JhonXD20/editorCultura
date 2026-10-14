const passwordToggle = document.querySelector('[data-password-toggle]');
const passwordInput = document.getElementById('password');

if (passwordToggle && passwordInput) {
    passwordToggle.addEventListener('click', () => {
        const isVisible = passwordInput.type === 'text';

        passwordInput.type = isVisible ? 'password' : 'text';
        passwordToggle.setAttribute('aria-pressed', String(!isVisible));
        passwordToggle.setAttribute(
            'aria-label',
            isVisible ? 'Mostrar senha' : 'Ocultar senha',
        );

        const icon = passwordToggle.querySelector('i');
        icon?.classList.toggle('fa-eye', isVisible);
        icon?.classList.toggle('fa-eye-slash', !isVisible);
    });
}
