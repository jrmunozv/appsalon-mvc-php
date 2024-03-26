<h1 class="nombre-pagina">Login</h1>
<p class="descripcion-pagina">Inicia sesión con tus datos</p>
<?php
    include_once __DIR__ . "/../templates/alertas.php";
?>

<form class="formulario" method="POST" action="/">
    <div class="campo">
        <label for="email">Email</label>
        <input
            type="email"
            id="email"
            placeholder="Tu mail"
            name="email"
            
        />
    </div>

    <div class="campo">
        <label for="password">Password</label>
        <input
            type="password"
            id="password"
            placeholder="Tu password"
            name="password"
        />
    </div>

    <input type="submit" class="boton" value="Iniciar sesión">

</form>

<div class="acciones">
    <a href="/crear-cuenta">¿Aun no tienes una cuenta? Crea una.</a>
    <a href="/olvide">¿Olvidaste tu password?</a>
</div>