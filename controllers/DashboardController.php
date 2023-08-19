<?php 

namespace Controllers;

use Model\Proyecto;
use Model\Usuario;
use MVC\Router;


class DashboardController{
    public static function index(Router $router){
        
        session_start();
        isAuth();

        $id = $_SESSION['id'];

        $proyectos = Proyecto::belongsTo('propietarioId', $id);

        $router->render('dashboard/index', [
            'titulo' => 'Proyectos',
            'proyectos' => $proyectos
        ]);
        
    }

    public static function crear_proyecto(Router $router){
        session_start();

        isAuth();
        $alertas = [];

        if($_SERVER['REQUEST_METHOD'] === 'POST'){
            $proyecto = new Proyecto($_POST);

            //Validate 
            $alertas = $proyecto->validarProyecto();

            if(empty($alertas)){
                //generate unique Token 
                $hash = md5(uniqid());
                $proyecto->url=$hash;

                //Save Project Creator 
                

                $proyecto->propietarioId = $_SESSION['id'];

                //Save Project 

                $proyecto->guardar();


                header('Location: /proyecto?id=' .  $proyecto->url);
            }
        }


        $router->render('/dashboard/crear-proyecto', [
            'titulo' => 'Crear Proyecto',
            'alertas' => $alertas
        ]);
    }

    public static function proyecto(Router $router){
        session_start();
        isAuth();
        $token= $_GET['id'];
        if(!$token) header('Location: /dashboard');

        //revisar que la persona que revisa el proyecto es quien la creo
        
       $proyecto = Proyecto::where('url', $token);

       if($proyecto->propietarioId !== $_SESSION['id']){
            header('Location: /dashboard');
       }

        $router->render('/dashboard/proyecto', [
            'titulo' => $proyecto->proyecto
        ]);
    }

    public static function perfil(Router $router){

        session_start();
        isAuth();

       $usuario = Usuario::find($_SESSION['id']);

        $alertas = [];

        if($_SERVER['REQUEST_METHOD'] === 'POST'){
            $usuario->sincronizar($_POST);

            $alertas = $usuario->validar_perfil();

            if(empty($alertas)){

                $existeUsuario = Usuario::where('email', $usuario->email);

                if($existeUsuario && $existeUsuario->id !== $usuario->id){
                    //mensaje de eeror
                    Usuario::setAlerta('error', 'Email no Valido, ya pertenece a otra cuenta');
                    $alertas = $usuario->getAlertas();
                }else{
                     //guardar el ususario 
                $usuario->guardar();

                Usuario::setAlerta('exito', 'Guardado Correctamente');
                $alertas = $usuario->getAlertas();

                // asignar el nombre uevo a la barra
                $_SESSION['nombre'] = $usuario->nombre;
                }
               
            }
        }
       


        $router->render('/dashboard/perfil', [
            'titulo' => 'Perfil',
            'usuario' => $usuario,
            'alertas' => $alertas
        ]);
    }

    public static function cambiar_password(Router $router){

        session_start();
        isAuth();

        $alertas = [];

        if($_SERVER['REQUEST_METHOD'] === 'POST'){
            $usuario = Usuario::find($_SESSION['id']);

            //soncronizar con los datos del ususario 

            $usuario->sincronizar($_POST);

            $alertas = $usuario->nuevo_password();

            if(empty($alertas)){
              $resultado = $usuario->comprobar_password(); 

              if($resultado) {
                $usuario->password = $usuario->password_nuevo;

                //eliminar propiedades no necesarias
                unset($usuario->password_actual);
                unset($usuario->password_nuevo);
 
                //hasear el password
                $usuario->hashPassword();
                //actualizar
                $resultado = $usuario->guardar();


                if($resultado){
                    Usuario::setAlerta('exito', 'Password Guardado Correctamente');
                    $alertas = $usuario->getAlertas();

                }
              }else {
                Usuario::setAlerta('error', 'Password Incorrecto');
                $alertas = $usuario->getAlertas();
              }
              
            }
        }


        $router->render('dashboard/cambiar-password', [
            'titulo' => 'Cambiar Password',
            'alertas' => $alertas
        ]);
    }
}