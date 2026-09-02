<?php
session_start();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Deltahub</title>
    <link rel="icon" type="image/png" href="favicon\favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="favicon\favicon.svg" />
    <link rel="shortcut icon" href="favicon\favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="favicon\apple-touch-icon.png" />
    <meta name="apple-mobile-web-app-title" content="Deltahub" />
    <link rel="manifest" href="favicon\site.webmanifest" />
</head>
<body>
    <header>

        <a href="index.php" class="header-logo" id="header-logo">
            <img src="imagenes\DeltahubLogo3px.png" alt="logo deltahub">
        </a>

        <a href="" id="shadow">Principal</a>
        <a href="workshop.html" id="shadow">Workshop</a>
        <a href="community.html" id="shadow">Comunidad</a>

        <div class="auth">

            <?php if (isset($_SESSION["usuario_id"])): ?>

            <span id="shadow">
            <?php echo htmlspecialchars($_SESSION["nombre_usuario"]); ?>
            </span>

            <a href="logout.php" id="shadow">
                Logout
            </a>

            <?php else: ?>

            <a href="login.html" id="shadow">
                Login
            </a>

            <a href="register.html" id="shadow">
                Signup
            </a>

            <?php endif; ?>

        </div>

        

    </header>

    <main>
        <div class="logohome"><img src="imagenes\DeltahubLogo3px.png" alt="logo deltahub"></div>

        <div class="slogan"><h2>todo el fandom de deltarune, en un solo lugar.</h2></div>

        <div class="info_main">

            <h2>
                EL CENTRO DEFINITIVO PARA EL FANDOM DE DELTARUNE
            </h2>
            <p>-----------------------------------------------------------------------------------</p>
            <p>
                ¡Deltahub es un centro comunitario dedicado a todo lo relacionado con el juego Deltarune,
                donde podrás encontrar y publicar tus mods, arte, UST, participar en debates y
                muchas otras cosas mas!
            </p>

        </div>

        <div class="main_buttons">
            <a href="workshop.html" class="boton">Explorar la workshop</a>
            <a href="community.html" class="boton2">Visita la comunidad</a>
        </div>

        <section class="features">

            <article class="feature-card">
                <div class="feature-img">IMAGEN ACÁ</div>
                <div class="feature-text">
                    <h3><a href="workshop.html" style="color:inherit">WORKSHOP</a></h3>
                    <p>
                        Navega a travez de un mar de Mods, Fangames, Soundtracks, Traducciones y más contenido customizado creado
                        por la comunidad o libera tu potencial compartiendo tu propio contenido con el fandom.
                    </p>
                </div>
            </article>

            <article class="feature-card reverse">
                <div class="feature-img">IMAGEN ACÁ</div>
                <div class="feature-text">
                    <h3>COMUNIDAD</h3>
                    <p>
                    Comparte tus teorias e ideas con otros fans y construye relaciones duraderas en nuestro foro comunitario,
                    repleto de actividades y discusiones.
                    </p>
                </div>
            </article>

        </section>

    </main>

    <footer>
        <p>&copy; 2026 Deltahub. Todos los derechos reservados.</p>
    </footer>
    <script src="script.js"></script>
</body>
</html>