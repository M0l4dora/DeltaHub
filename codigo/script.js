const USERS_KEY = 'deltahub_users';
const SESSION_KEY = 'deltahub_session';

function getUsers() {
    return JSON.parse(localStorage.getItem(USERS_KEY)) || [];
}

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('auth-form');
    if (!form) return;

    const msg = document.getElementById('msg');
    const isRegister = form.dataset.mode === 'register';

    function show(text, type) {
        msg.textContent = text;
        msg.className = 'msg ' + type;
    }

    form.addEventListener('submit', (e) => {
        e.preventDefault();

        const user = form.username.value.trim();
        const pass = form.password.value;

        if (!user || !pass) {
            show('Completá todos los campos', 'error');
            return;
        }

        const users = getUsers();

        if (isRegister) {
            const confirm = form.confirm.value;

            if (pass !== confirm) {
                show('Las contraseñas no coinciden', 'error');
                return;
            }
            if (pass.length < 4) {
                show('La contraseña debe tener al menos 4 caracteres', 'error');
                return;
            }
            if (users.some(u => u.user.toLowerCase() === user.toLowerCase())) {
                show('Ese usuario ya existe', 'error');
                return;
            }

            users.push({ user, pass: btoa(pass) });
            localStorage.setItem(USERS_KEY, JSON.stringify(users));

            show('Cuenta creada con éxito. Redirigiendo...', 'ok');
            setTimeout(() => window.location.href = 'login.html', 1200);
        } else {
            const found = users.find(u =>
                u.user.toLowerCase() === user.toLowerCase() && atob(u.pass) === pass
            );

            if (!found) {
                show('Usuario o contraseña incorrectos', 'error');
                return;
            }

            sessionStorage.setItem(SESSION_KEY, found.user);
            show('Bienvenido, ' + found.user + '. Redirigiendo...', 'ok');
            setTimeout(() => window.location.href = 'index.html', 1200);
        }
    });
});
