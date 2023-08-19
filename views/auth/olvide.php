<div class="contenedor olvide">
    <?php include_once __DIR__ . '/../templates/nombre-sitio.php';?>


    <div class="contenedor-sm">
        <p class="descripcion-pagina">Recupera tu acceso a tu cuenta</p>


        <?php include_once __DIR__ . '/../templates/alertas.php';?>
        <form action="/olvide" class="formulario" method="POST" novalidate>
            <div class="campo">
                <label for="email">Email </label>
                <input 
                type="email" 
                id="email"
                name="email"
                placeholder="Tu Email"
                >
            </div>

    

            <input type="submit" class="boton" value="Enviar">
        </form>
        <div class="acciones">
          <a href="/">¿Ya tienes cuenta? Inicia Sesión</a>
            <a href="/crear">¿Áun no tienes una cuenta? obtener una</a>
            
        </div>
    </div> <!-- contenedor-sm--> 
</div>