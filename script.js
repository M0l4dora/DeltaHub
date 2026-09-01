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

// ===== Animación: el logo grande viaja al header al scrollear =====

document.addEventListener('DOMContentLoaded', () => {
    const heroImg = document.querySelector('.logohome img');
    const slot = document.getElementById('header-logo');
    if (!heroImg || !slot) return;

    const slotImg = slot.querySelector('img');
    const header = slot.closest('header');
    const DURACION = 550;
    let ghost = null;
    let docked = false;
    let volando = null; // 'ida' o 'vuelta'

    // Crea el clon animado desde "from" hacia "to" y avisa cuando aterriza
    function createGhost(from, to, alAterrizar) {
        const g = heroImg.cloneNode(true);
        g.classList.add('flying-logo');
        Object.assign(g.style, {
            visibility: 'visible', // el clon siempre se ve, aunque el original esté oculto
            position: 'fixed',
            left: from.left + 'px',
            top: from.top + 'px',
            width: from.width + 'px',
            height: from.height + 'px',
            margin: '0',
            zIndex: '1000',
            pointerEvents: 'none',
            transition: `left ${DURACION}ms ease, top ${DURACION}ms ease, width ${DURACION}ms ease, height ${DURACION}ms ease`
        });
        document.body.appendChild(g);

        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                Object.assign(g.style, {
                    left: to.left + 'px',
                    top: to.top + 'px',
                    width: to.width + 'px',
                    height: to.height + 'px'
                });
                g.enMarcha = true; // recién desde acá tiene sentido retargetear
            });
        });

        g.addEventListener('transitionend', () => {
            g.remove();
            alAterrizar();
        }, { once: true });

        return g;
    }

    function flyToHeader() {
        if (ghost || docked) return;

        volando = 'ida';
        ghost = createGhost(
            heroImg.getBoundingClientRect(),   // arranca donde está el logo grande
            slotImg.getBoundingClientRect(),    // aterriza en su lugar del header
            () => {
                ghost = null;
                volando = null;
                docked = true;
                slot.classList.add('visible'); // recién acá aparece el real
            }
        );
    }

    function cancelarVuelta() {
        if (ghost) {
            ghost.remove();
            ghost = null;
        }
        volando = null;
        heroImg.style.visibility = '';
    }

    function undock() {
        if (docked) {
            // Vuelo de vuelta: el chico despega del header y vuela hacia
            // donde quedó el logo grande, agrandándose
            docked = false;
            volando = 'vuelta';
            slot.classList.remove('visible');
            heroImg.style.visibility = 'hidden'; // el real se oculta para que no se vean dos
            ghost = createGhost(
                slotImg.getBoundingClientRect(),
                heroImg.getBoundingClientRect(),
                () => {
                    ghost = null;
                    volando = null;
                    heroImg.style.visibility = ''; // recién acá reaparece el real
                }
            );
        } else if (ghost && volando === 'ida') {
            // Si agarró el scroll en medio del viaje de ida, se corta nomás
            ghost.remove();
            ghost = null;
            volando = null;
        }
        // Si está volviendo ('vuelta'), se deja terminar tranquilo
    }

    function onScroll() {
        const limite = header.offsetHeight;
        const fuera = heroImg.getBoundingClientRect().bottom <= limite;

        if (fuera) {
            // Si venía volviendo y volvió a bajar, se corta la vuelta y arranca la ida
            if (volando === 'vuelta') cancelarVuelta();
            flyToHeader();
        } else {
            undock();
        }

        // En la vuelta el destino se actualiza en vivo, así el clon
        // aterrice justo donde quedó el logo grande y no más arriba
        if (volando === 'vuelta' && ghost && ghost.enMarcha) {
            const destino = heroImg.getBoundingClientRect();
            ghost.style.left = destino.left + 'px';
            ghost.style.top = destino.top + 'px';
        }
    }

    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll(); // por si la página ya carga scrolleada
});
