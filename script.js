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


// ===== Workshop: filtrado por categoría, búsqueda, orden y vista =====

document.addEventListener('DOMContentLoaded', () => {
    const grid = document.querySelector('.ws-grid');
    if (!grid) return; // no estamos en la workshop

    const links = Array.from(grid.querySelectorAll('.ws-item-link'));
    const countShow = document.getElementById('ws-count-show');
    const countTotal = document.getElementById('ws-count-total');
    const emptyEl = document.getElementById('ws-empty');
    const searchInput = document.getElementById('ws-search');

    const categoryItems = Array.from(document.querySelectorAll('.ws-category'));
    const sortItems = Array.from(document.querySelectorAll('.ws-sort'));
    const tagButtons = Array.from(document.querySelectorAll('.ws-tag'));
    const viewButtons = Array.from(document.querySelectorAll('.ws-view-btn'));
    const filtrosSeccion = document.getElementById('ws-filtros-seccion');

    const estado = {
        categoria: 'all',
        capitulo: '',
        busqueda: '',
        orden: 'popular'
    };

    function buscarTexto(item) {
        const q = estado.busqueda.trim().toLowerCase();
        return !q || (item.textContent || '').toLowerCase().includes(q);
    }

    // Sin capítulo elegido el filtro no descarta nada
    function capituloAplica(item) {
        if (estado.capitulo === '') return true;
        return item.dataset.capitulo === estado.capitulo;
    }

    function esVisible(item) {
        const enCategoria = estado.categoria === 'all' || item.dataset.category === estado.categoria;
        return enCategoria && capituloAplica(item) && buscarTexto(item);
    }

    // Al cambiar de categoría se suelta el capítulo: si el filtro queda puesto
    // y la nueva categoría no tiene saves, la lista quedaría vacía sin que se
    // entienda por qué.
    function soltarCapituloSiNoAplica() {
        if (estado.capitulo === '') return;

        const categoriaAcepta = estado.categoria === 'all' || categoriaEsDeCapitulo(estado.categoria);
        if (categoriaAcepta) return;

        estado.capitulo = '';
        tagButtons.forEach(t => t.classList.toggle('active', t.dataset.capitulo === ''));
    }

    // La categoría que usa capítulos la declara el propio bloque de filtros,
    // así el script no necesita saber que se llama "Saves".
    function categoriaEsDeCapitulo(slug) {
        return !!filtrosSeccion && filtrosSeccion.dataset.capituloCategoria === slug;
    }

    function actualizarFiltros() {
        if (!filtrosSeccion) return;

        // Sin ningún capítulo publicado el bloque no tiene nada que filtrar, así
        // que se queda oculto aunque se esté en "Todos".
        const hayCapitulos = tagButtons.some(t => t.dataset.capitulo !== '');

        // Y sólo tiene sentido verlo mientras no se esté en una categoría que no
        // los usa.
        const visible = hayCapitulos && (estado.categoria === 'all' || categoriaEsDeCapitulo(estado.categoria));

        filtrosSeccion.hidden = !visible;
    }

    function fechaOrden(link) {
        const item = link.querySelector('.ws-item');
        if (!item) return 0;
        return new Date((item.dataset.fecha || '').replace(' ', 'T')).getTime() || 0;
    }

    function puntajeOrden(link) {
        const item = link.querySelector('.ws-item');
        if (!item) return 0;
        switch (estado.orden) {
            case 'reciente':
                return fechaOrden(link);
            case 'valorado':
                return parseFloat(item.dataset.valoracion) || 0;
            case 'popular':
            case 'descargas':
            default:
                return parseInt(item.dataset.descargas, 10) || 0;
        }
    }

    function aplicarFiltros() {
        // Desempate por fecha: si nadie tiene descargas (o valoración) el
        // orden no queda al azar, muestra primero lo más reciente.
        links.slice()
            .sort((a, b) => (puntajeOrden(b) - puntajeOrden(a)) || (fechaOrden(b) - fechaOrden(a)))
            .forEach(link => grid.appendChild(link));

        let visibles = 0;
        links.forEach(link => {
            const item = link.querySelector('.ws-item');
            const mostrar = item && esVisible(item);
            link.classList.toggle('hidden', !mostrar);
            if (mostrar) visibles++;
        });

        if (countShow) countShow.textContent = visibles;
        if (emptyEl) emptyEl.hidden = visibles !== 0;
    }

    categoryItems.forEach(el => {
        el.addEventListener('click', () => {
            estado.categoria = el.dataset.category || 'all';
            categoryItems.forEach(c => c.classList.toggle('active', c === el));
            soltarCapituloSiNoAplica();
            actualizarFiltros();
            aplicarFiltros();
        });
    });

    sortItems.forEach(el => {
        el.addEventListener('click', () => {
            estado.orden = el.dataset.sort || 'popular';
            sortItems.forEach(s => s.classList.toggle('active', s === el));
            aplicarFiltros();
        });
    });

    tagButtons.forEach(el => {
        el.addEventListener('click', () => {
            estado.capitulo = el.dataset.capitulo || '';
            tagButtons.forEach(t => t.classList.toggle('active', t === el));
            aplicarFiltros();
        });
    });

    if (searchInput) {
        searchInput.addEventListener('input', () => {
            estado.busqueda = searchInput.value;
            aplicarFiltros();
        });
    }

    viewButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            viewButtons.forEach(b => b.classList.toggle('active', b === btn));
            grid.classList.toggle('list-view', btn.dataset.view === 'list');
        });
    });

    actualizarFiltros();
    aplicarFiltros();

    // El contenedor de la tarjeta es un div y no un <a> porque la tarjeta
    // lleva dos enlaces reales dentro (el contenido y el perfil del autor) y
    // los <a> anidados no son HTML válido. El clic en el resto de la tarjeta
    // lo lleva al item, igual que antes; el teclado usa el enlace del título.
    grid.addEventListener('click', (e) => {
        if (e.target.closest('a')) return; // los enlaces reales mandan

        const card = e.target.closest('.ws-item-link');
        if (!card || !card.dataset.href) return;

        window.location.href = card.dataset.href;
    });
});


// ===== Subir: el capítulo sólo aparece al elegir la categoría Saves =====

// El capítulo no es un campo más del formulario: sólo tiene sentido en los
// saves, así que se muestra cuando la categoría elegida es la que lo usa
// (data-slug del <option>) y se esconde en cualquier otra.

document.addEventListener('DOMContentLoaded', () => {
    const selectCategoria = document.getElementById('subir-categoria');
    const bloqueCapitulo = document.getElementById('subir-capitulo');
    if (!selectCategoria || !bloqueCapitulo) return; // no estamos en subir.php

    const selectCapitulo = bloqueCapitulo.querySelector('select[name="capitulo"]');

    function categoriaElegida() {
        return selectCategoria.options[selectCategoria.selectedIndex];
    }

    function actualizarCapitulo() {
        const opcion = categoriaElegida();
        const conCapitulo = !!opcion && opcion.dataset.cap === '1';

        bloqueCapitulo.hidden = !conCapitulo;

        // Escondido con hidden no alcanza: un select required escondido frenaría
        // el envío en las categorías que no lo necesitan.
        if (selectCapitulo) {
            selectCapitulo.disabled = !conCapitulo;
            if (!conCapitulo) selectCapitulo.value = '';
        }
    }

    selectCategoria.addEventListener('change', actualizarCapitulo);
    actualizarCapitulo(); // por si la página vuelve con la categoría ya elegida
});


// ===== Cuenta: elegir el avatar y subirlo con un botón =====

// En cuenta.php el <input type="file"> está escondido y es el <label> el que
// abre el explorador: la foto del avatar. Elegir el archivo NO sube nada por sí
// solo, hay que apretar el botón del formulario: acá sólo se avisa qué archivo
// quedó elegido. El banner tiene su propio bloque más abajo, porque antes de
// subirlo se abre la ventana de recorte.

document.addEventListener('DOMContentLoaded', () => {
    const avatar = document.getElementById('avatar');
    const banner = document.getElementById('banner');
    const bannerBtn = document.querySelector('.banner-btn');
    if (!avatar && !banner) return; // no estamos en la cuenta

    function avisarArchivo(input) {
        if (!input) return;

        const aviso = input.form.querySelector('[data-archivo-elegido]');

        input.addEventListener('change', () => {
            if (!aviso) return;

            // Si el usuario cancela el explorador no hay nada que mandar.
            aviso.textContent = (input.files && input.files.length > 0)
                ? 'Elegido: ' + input.files[0].name
                : '';
        });
    }

    avisarArchivo(avatar);

    // El <label> abre el explorador con el mouse, pero no con el teclado: se le
    // agrega la respuesta a Enter y Espacio de un botón de verdad.
    if (banner && bannerBtn) {
        bannerBtn.addEventListener('keydown', (e) => {
            if (e.key !== 'Enter' && e.key !== ' ') return;

            e.preventDefault();
            banner.click();
        });
    }
});


// ===== Intro: pantalla negra hasta que el gif llega a "Hub" =====

// La intro solo se reproduce la primera vez que se abre la página.
// Para verla de nuevo hay que borrar la clave en la consola:
// localStorage.removeItem('deltahub_intro_vista')
const INTRO_KEY = 'deltahub_intro_vista';

function introYaVista() {
    try { return localStorage.getItem(INTRO_KEY) !== null; }
    catch (e) { return false; }
}

function marcarIntroVista() {
    try { localStorage.setItem(INTRO_KEY, '1'); }
    catch (e) { /* sin storage la intro se repetirá, pero la página funciona */ }
}

document.addEventListener('DOMContentLoaded', () => {
    const overlay = document.getElementById('intro-overlay');
    const img = document.getElementById('intro-gif');
    if (!overlay || !img) return;

    // Ya se vio alguna vez: quitar el overlay sin transición ni parpadeo
    if (introYaVista()) {
        overlay.remove();
        return;
    }

    const GIF_SRC = 'imagenes/DeltaHubanim.gif';  // el gif del título
    const REVELAR_EN_MS = 3000;                    // tiempo hasta que aparece "Hub"
    const MAX_ESPERA_MS = 10000;                   // red de seguridad

    // Se marca antes de empezar, así que ni cerrando la pestaña a media
    // intro se vuelve a mostrar al volver a entrar.
    marcarIntroVista();

    function revelar() {
        overlay.classList.add('hidden');
        // Después de la transición, sacarla del todo para no bloquear la página
        overlay.addEventListener('transitionend', () => overlay.remove(), { once: true });
        // Respaldo por si el navegador no dispara transitionend
        setTimeout(() => overlay.remove(), 500);
    }

    let yaRevelo = false;
    const fin = () => { if (!yaRevelo) { yaRevelo = true; revelar(); } };

    // Se precarga aparte para que el gif siempre arranque desde el frame 0
    // recién cuando aparece en pantalla, y no a mitad de animación.
    const preload = new Image();
    preload.onload = () => {
        img.onload = () => setTimeout(fin, REVELAR_EN_MS);
        img.src = GIF_SRC;
    };
    preload.onerror = fin; // si la intro falla, se abre la página igual
    preload.src = GIF_SRC;

    // Por si algo se traba (red lenta, etc.), nunca dejar la página en negro
    setTimeout(fin, MAX_ESPERA_MS);
});


// ===== Banner: ventana emergente para centrar y acomodar la imagen =====

// Al elegir el archivo se abre un modal con la imagen en grande detrás de un
// marco fijo: lo que queda dentro del marco es exactamente lo que se sube. Se
// arrastra con el mouse y se ajusta el zoom con la rueda o con el control.
//
// El recorte se arma con <canvas>, y un canvas no sabe escribir un GIF: el
// resultado sería un fotograma fijo y se perdería justo lo que hizo útil
// admitir GIFs. Por eso la casilla "Conservar animación" sube el archivo entero
// y guarda el encuadre como dos porcentajes en usuarios.banner_posicion, que
// después se aplican como `object-position` (ver config/banner.php en PHP). Para
// los GIF viene marcada de entrada; para el resto, desmarcada, que es lo que se
// espera de un recorte.

(function ventanaRecorte() {

    const overlay = document.querySelector('[data-recorte]');

    if (!overlay) return; // no estamos en la cuenta, no hay nada que recortar

    const input = document.getElementById('banner');
    const lienzo = overlay.querySelector('[data-recorte-lienzo]');
    const img = overlay.querySelector('[data-recorte-imagen]');
    const marco = overlay.querySelector('[data-recorte-marco]');
    const zoom = overlay.querySelector('[data-recorte-zoom]');
    const conservar = overlay.querySelector('[data-recorte-conservar]');
    const textoConservar = overlay.querySelector('[data-recorte-conservar-texto]');
    const nota = overlay.querySelector('[data-recorte-nota]');
    const error = overlay.querySelector('[data-recorte-error]');
    const btnCancelar = overlay.querySelector('[data-recorte-cancelar]');
    const btnAceptar = overlay.querySelector('[data-recorte-aceptar]');
    const campoPosicion = input ? input.form.querySelector('[data-banner-posicion]') : null;
    const avisoElegido = input ? input.form.querySelector('[data-archivo-elegido]') : null;

    if (!input || !lienzo || !img || !marco) return;

    // El mismo límite que pone cambiar_banner.php del lado del servidor. Sirve
    // para avisar en la ventana en vez de mandar un archivo que el servidor va
    // a rechazar después de todo el viaje.
    const MAXIMO = 4 * 1024 * 1024;
    const ZOOM_MINIMO = 1;
    const ZOOM_MAXIMO = 4;

    const archivo = {
        original: null,   // el File que eligió el usuario
        esGif: false,
        url: '',          // object URL de la imagen en pantalla
        naturalAncho: 0,
        naturalAlto: 0,
        escala: 1,       // escala para cubrir el marco con zoom 1
        nivelZoom: 1,    // 1 a ZOOM_MAXIMO
        marcoX: 0,       // del marco dentro del lienzo
        marcoY: 0,
        marcoAncho: 0,
        marcoAlto: 0,
        panX: 0,         // de la imagen respecto del marco (nunca positiva)
        panY: 0,
    };

    let urlPrevia = '';   // object URL de la vista previa, para revocarla
    let elementoAnterior = null; // qué tenía el foco antes de abrir

    // --- Medidas -------------------------------------------------------

    function medir() {
        // Los rectángulos sólo sirven con la ventana abierta: si el modal está
        // en `hidden` todo mide cero. Por eso se llama después de mostrarlo.
        const delLienzo = lienzo.getBoundingClientRect();
        const delMarco = marco.getBoundingClientRect();

        archivo.marcoX = delMarco.left - delLienzo.left;
        archivo.marcoY = delMarco.top - delLienzo.top;
        archivo.marcoAncho = delMarco.width;
        archivo.marcoAlto = delMarco.height;
    }

    // El tamaño con el que se dibuja la imagen: la escala justa para tapar el
    // marco (como un object-fit: cover) multiplicada por el zoom.
    function anchoActual() {
        return archivo.naturalAncho * archivo.escala * archivo.nivelZoom;
    }

    function altoActual() {
        return archivo.naturalAlto * archivo.escala * archivo.nivelZoom;
    }

    // La imagen nunca se puede dejar de cubrir el marco, así que el desplazamiento
    // va entre -(lo que sobra) y 0.
    function limitarPan() {
        archivo.panX = Math.min(0, Math.max(archivo.marcoAncho - anchoActual(), archivo.panX));
        archivo.panY = Math.min(0, Math.max(archivo.marcoAlto - altoActual(), archivo.panY));
    }

    function centrar() {
        archivo.panX = (archivo.marcoAncho - anchoActual()) / 2;
        archivo.panY = (archivo.marcoAlto - altoActual()) / 2;
        limitarPan();
    }

    function pintar() {
        const ancho = anchoActual();
        const alto = altoActual();

        limitarPan();

        img.style.width = ancho + 'px';
        img.style.height = alto + 'px';
        img.style.left = (archivo.marcoX + archivo.panX) + 'px';
        img.style.top = (archivo.marcoY + archivo.panY) + 'px';

        zoom.value = String(Math.round(archivo.nivelZoom * 100));
    }

    // --- Posición ------------------------------------------------------

    // Traduce el desplazamiento a los dos porcentajes que guarda
    // usuarios.banner_posicion.
    //
    // La fórmula es la inversa de un `object-position: P%`, que alinea el punto
    // P% de la imagen con el punto P% de la caja: 0% deja la imagen pegada a la
    // izquierda, 100% a la derecha y 50% al centro. Como acá el desplazamiento
    // va al revés (0 = pegado a la izquierda, negativo = corrido a la derecha),
    // sale el signo cambiado.
    function posicionEnPorcentaje() {
        const ancho = anchoActual();
        const alto = altoActual();

        const x = ancho > archivo.marcoAncho
            ? (-archivo.panX / (ancho - archivo.marcoAncho)) * 100
            : 50;

        const y = alto > archivo.marcoAlto
            ? (-archivo.panY / (alto - archivo.marcoAlto)) * 100
            : 50;

        return x.toFixed(1) + '% ' + y.toFixed(1) + '%';
    }

    // --- Zoom ----------------------------------------------------------

    function cambiarZoom(nuevo) {
        const destino = Math.min(ZOOM_MAXIMO, Math.max(ZOOM_MINIMO, nuevo));

        if (destino === archivo.nivelZoom) return;

        // El zoom se ancla en lo que hay bajo el centro del marco: si no, cada
        // paso de la rueda haría un salto y sería imposible acercarse a un
        // detalle sin empezar de nuevo.
        const centroX = (archivo.panX + archivo.marcoAncho / 2) / anchoActual();
        const centroY = (archivo.panY + archivo.marcoAlto / 2) / altoActual();

        archivo.nivelZoom = destino;

        archivo.panX = centroX * anchoActual() - archivo.marcoAncho / 2;
        archivo.panY = centroY * altoActual() - archivo.marcoAlto / 2;

        pintar();
    }

    // --- Arrastrar -----------------------------------------------------

    let arrastrando = false;
    let inicioX = 0;
    let inicioY = 0;
    let panXInicial = 0;
    let panYInicial = 0;

    lienzo.addEventListener('pointerdown', (e) => {
        // Botón izquierdo solamente: con el derecho se abriría el menú del
        // navegador y no tiene sentido para arrastrar.
        if (e.button !== 0) return;

        arrastrando = true;
        inicioX = e.clientX;
        inicioY = e.clientY;
        panXInicial = archivo.panX;
        panYInicial = archivo.panY;

        lienzo.classList.add('arrastrando');
        lienzo.setPointerCapture(e.pointerId);
        e.preventDefault();
    });

    lienzo.addEventListener('pointermove', (e) => {
        if (!arrastrando) return;

        archivo.panX = panXInicial + (e.clientX - inicioX);
        archivo.panY = panYInicial + (e.clientY - inicioY);

        pintar();
    });

    function soltar(e) {
        if (!arrastrando) return;

        arrastrando = false;
        lienzo.classList.remove('arrastrando');

        // El puntero puede haberse liberado fuera del lienzo (terminar el
        // arrastre afuera, cambiar de pestaña).
        if (e && e.pointerId !== undefined && lienzo.hasPointerCapture(e.pointerId)) {
            lienzo.releasePointerCapture(e.pointerId);
        }
    }

    lienzo.addEventListener('pointerup', soltar);
    lienzo.addEventListener('pointercancel', soltar);

    lienzo.addEventListener('wheel', (e) => {
        e.preventDefault();
        cambiarZoom(archivo.nivelZoom - e.deltaY * 0.002);
    }, { passive: false });

    zoom.addEventListener('input', () => cambiarZoom(Number(zoom.value) / 100));

    // Con teclado también se puede encuadrar: las flechas mueven y el + / -
    // cambian el zoom. El input del zoom ya anda con las flechas solo.
    overlay.addEventListener('keydown', (e) => {
        const paso = e.shiftKey ? 20 : 4;

        switch (e.key) {
            case 'ArrowLeft':  archivo.panX += paso; break;
            case 'ArrowRight': archivo.panX -= paso; break;
            case 'ArrowUp':    archivo.panY += paso; break;
            case 'ArrowDown':  archivo.panY -= paso; break;
            case '+':
            case '=':
                cambiarZoom(archivo.nivelZoom + 0.1);
                break;
            case '-':
            case '_':
                cambiarZoom(archivo.nivelZoom - 0.1);
                break;
            default:
                return;
        }

        e.preventDefault();
        pintar();
    });

    // --- Mensajes ------------------------------------------------------

    function mostrarError(mensaje) {
        error.textContent = mensaje;
        error.hidden = false;
    }

    function limpiarError() {
        error.textContent = '';
        error.hidden = true;
    }

    function actualizarNota() {
        if (archivo.esGif) {
            textoConservar.textContent = 'Conservar la animación del GIF';

            nota.textContent = conservar.checked
                ? 'Se sube el GIF entero. El encuadre se guarda y el perfil lo muestra sin perderse ningún fotograma.'
                : 'Al recortar, el GIF se queda en un solo fotograma: la animación se pierde.';
        } else {
            textoConservar.textContent = 'Subir sin recortar';

            nota.textContent = conservar.checked
                ? 'Se sube la imagen entera, sin recortar. El encuadre elegido se guarda y el perfil muestra esa parte.'
                : 'Se recorta al marco y se sube como imagen fija.';
        }
    }

    conservar.addEventListener('change', actualizarNota);

    // --- Abrir y cerrar -------------------------------------------------

    function abrir(file) {
        if (typeof DataTransfer === 'undefined') {
            //Sin DataTransfer no hay forma de poner un archivo recortado en el
            // input, así que se avisa en vez de fallar en silencio.
            alert('Este navegador no permite preparar la imagen antes de subirla. Probá con Chrome, Firefox o Edge.');
            return;
        }

        elementoAnterior = document.activeElement;

        archivo.original = file;
        // El tipo lo dice el navegador, pero algunos lo mandan vacío: el
        // nombre del archivo es el plan B.
        archivo.esGif = file.type === 'image/gif' || /\.gif$/i.test(file.name);
        archivo.nivelZoom = ZOOM_MINIMO;

        limpiarError();

        conservar.checked = archivo.esGif;
        zoom.value = String(ZOOM_MINIMO * 100);
        actualizarNota();

        // La vista previa del modal es la imagen elegida, sin recortar todavía.
        img.removeAttribute('src');

        const reader = new Image();

        reader.onload = () => {
            archivo.naturalAncho = reader.naturalWidth;
            archivo.naturalAlto = reader.naturalHeight;

            if (!reader.naturalWidth || !reader.naturalHeight) {
                mostrarError('No se pudo leer la imagen.');
                return;
            }

            // Con zoom 1 la imagen apenas tapa el marco, como un cover.
            archivo.escala = Math.max(
                archivo.marcoAncho / reader.naturalWidth,
                archivo.marcoAlto / reader.naturalHeight
            );

            img.src = archivo.url;
            centrar();
            pintar();

            btnAceptar.focus();
        };

        reader.onerror = () => mostrarError('Ese archivo no se pudo abrir como imagen.');

        if (archivo.url) URL.revokeObjectURL(archivo.url);
        archivo.url = URL.createObjectURL(file);
        reader.src = archivo.url;

        overlay.hidden = false;

        // Recién con la ventana visible el marco tiene medidas reales.
        medir();

        document.body.style.overflow = 'hidden';
    }

    function cerrar() {
        overlay.hidden = true;
        document.body.style.overflow = '';
        soltar(null);

        if (archivo.url) {
            URL.revokeObjectURL(archivo.url);
            archivo.url = '';
        }

        img.removeAttribute('src');
        archivo.original = null;

        if (elementoAnterior && elementoAnterior.focus) {
            elementoAnterior.focus();
        }
    }

    // --- Mandar el resultado -------------------------------------------

    // El canvas no escribe GIF, así que un GIF recortado sale como PNG. Para el
    // resto se mantiene el formato de origen.
    function tipoDeSalida(tipo) {
        if (tipo === 'image/jpeg' || tipo === 'image/pjpeg') return 'image/jpeg';
        if (tipo === 'image/png') return 'image/png';
        if (tipo === 'image/webp') return 'image/webp';
        return 'image/png';
    }

    function blobDelCanvas(canvas, tipo, calidad) {
        return new Promise((resolver) => {
            if (typeof canvas.toBlob !== 'function') {
                resolver(null);
                return;
            }

            canvas.toBlob((blob) => resolver(blob), tipo, calidad);
        });
    }

    function dibujarRecorte(canvas, factor) {
        // El recorte se toma del tamaño natural de la imagen: el marco es
        // siempre más chico que eso, así que sale con detalle de sobra.
        const escalaX = archivo.naturalAncho / anchoActual();
        const escalaY = archivo.naturalAlto / altoActual();

        const origenX = -archivo.panX * escalaX;
        const origenY = -archivo.panY * escalaY;
        const anchoOrigen = archivo.marcoAncho * escalaX;
        const altoOrigen = archivo.marcoAlto * escalaY;

        // `factor` achica el resultado si el archivo recortado igual queda
        // pesado: se vuelve a dibujar en un canvas más chico.
        canvas.width = Math.max(1, Math.round(anchoOrigen * factor));
        canvas.height = Math.max(1, Math.round(altoOrigen * factor));

        const ctx = canvas.getContext('2d');

        if (!ctx) return false;

        ctx.drawImage(
            img,
            origenX, origenY, anchoOrigen, altoOrigen,
            0, 0, canvas.width, canvas.height
        );

        return true;
    }

    async function recortar() {
        const tipo = tipoDeSalida(archivo.original.type);
        const canvas = document.createElement('canvas');

        if (!dibujarRecorte(canvas, 1)) {
            mostrarError('Tu navegador no pudo preparar el recorte.');
            return null;
        }

        // Un PNG de una foto grande se pasa de 4 MB con facilidad, así que se
        // prueba de a poco: primero bajando la calidad y, si sigue, achicando.
        for (const calidad of [0.92, 0.8, 0.65]) {
            const blob = await blobDelCanvas(canvas, tipo, calidad);

            if (blob && blob.size <= MAXIMO) return blob;
        }

        for (const factor of [0.75, 0.5, 0.35]) {
            if (!dibujarRecorte(canvas, factor)) break;

            const blob = await blobDelCanvas(canvas, tipo, 0.8);

            if (blob && blob.size <= MAXIMO) return blob;
        }

        mostrarError(
            'El banner recortado sigue pesando más de 4 MB. Probá con una imagen más chica.'
        );

        return null;
    }

    function ponerEnElInput(file) {
        const portapapeles = new DataTransfer();

        portapapeles.items.add(file);
        input.files = portapapeles.files;
    }

    function actualizarVistaPrevia(url, posicion, nombre) {
        const caja = document.querySelector('[data-banner-preview]');

        if (caja) {
            caja.classList.remove('is-empty');

            const vacio = caja.querySelector('.banner-preview-empty');
            if (vacio) vacio.remove();

            let previa = caja.querySelector('.banner-preview-img');

            if (!previa) {
                previa = document.createElement('img');
                previa.className = 'banner-preview-img';
                previa.alt = 'Tu banner de perfil';
                caja.appendChild(previa);
            }

            previa.src = url;
            previa.style.objectPosition = posicion;
        }

        if (urlPrevia) URL.revokeObjectURL(urlPrevia);
        urlPrevia = url;

        if (avisoElegido) {
            avisoElegido.textContent = 'Listo para subir: ' + nombre;
        }

        if (campoPosicion) {
            campoPosicion.value = posicion;
        }
    }

    btnAceptar.addEventListener('click', async () => {
        const elegido = archivo.original;

        if (!elegido) {
            cerrar();
            return;
        }

        limpiarError();
        btnAceptar.disabled = true;
        btnCancelar.disabled = true;

        const posicion = posicionEnPorcentaje();

        if (conservar.checked) {
            // Sin recortar: sube el archivo tal cual lo eligió el usuario y el
            // encuadre viaja en la columna banner_posicion. El perfil lo aplica
            // como object-position, así que el GIF sigue animándose.
            if (elegido.size > MAXIMO) {
                mostrarError('Ese archivo pesa más de 4 MB. Recortalo, o usá uno más liviano.');
                btnAceptar.disabled = false;
                btnCancelar.disabled = false;
                return;
            }

            ponerEnElInput(elegido);
            actualizarVistaPrevia(archivo.url, posicion, elegido.name);
        } else {
            const blob = await recortar();

            if (!blob) {
                btnAceptar.disabled = false;
                btnCancelar.disabled = false;
                return;
            }

            const extension = blob.type === 'image/jpeg' ? 'jpg'
                : blob.type === 'image/webp' ? 'webp'
                : 'png';

            const archivoRecortado = new File(
                [blob],
                'banner.' + extension,
                { type: blob.type, lastModified: Date.now() }
            );

            ponerEnElInput(archivoRecortado);

            // El recorte ya trae el encuadre cocido, así que la posición se
            // deja en el centro.
            actualizarVistaPrevia(
                URL.createObjectURL(blob),
                '50% 50%',
                elegido.name + ' (recortado)'
            );
        }

        cerrar();

        btnAceptar.disabled = false;
        btnCancelar.disabled = false;
    });

    btnCancelar.addEventListener('click', cerrar);

    // Cancelar con Escape o clicking afuera del recuadro blanco.
    overlay.addEventListener('mousedown', (e) => {
        if (e.target === overlay) cerrar();
    });

    document.addEventListener('keydown', (e) => {
        if (overlay.hidden) return;

        if (e.key === 'Escape') {
            e.preventDefault();
            cerrar();
        }
    });

    // Al abrir se arrastra y al cambiar el tamaño de la ventana el marco cambia
    // de medidas: hay que volver a medir y a encuadrar.
    window.addEventListener('resize', () => {
        if (overlay.hidden) return;

        const zoomPrevio = archivo.nivelZoom;

        medir();
        cambiarZoom(zoomPrevio); // reencuadra y vuelve a medir
        centrar();
        pintar();
    });

    // --- Arrancar el flujo ---------------------------------------------

    input.addEventListener('change', () => {
        // El File se guarda antes de vaciar el input: `files` queda vacío
        // recién después, aunque el objeto siga siendo válido.
        const elegido = input.files && input.files[0];

        // El input se vacía a propósito: el `required` del formulario tiene que
        // seguir siendo cierto hasta que el modal confirme el recorte, así que
        // no se puede mandar el archivo crudo si el usuario cancela.
        input.value = '';

        if (!elegido) return;

        if (!/^image\//.test(elegido.type) && !/\.(png|jpe?g|webp|gif)$/i.test(elegido.name)) {
            if (avisoElegido) {
                avisoElegido.textContent = 'Ese archivo no es una imagen.';
            }
            return;
        }

        if (elegido.size > MAXIMO) {
            if (avisoElegido) {
                avisoElegido.textContent = 'La imagen pesa más de 4 MB.';
            }
            return;
        }

        if (avisoElegido) {
            avisoElegido.textContent = 'Elegido: ' + elegido.name;
        }

        abrir(elegido);
    });

})();


