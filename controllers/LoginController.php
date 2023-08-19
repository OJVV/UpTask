<?php 

namespace Controllers;

use MVC\Router;
use Classes\Email;
use Model\Usuario;
use PHPMailer\PHPMailer\PHPMailer;
use SplSubject;

class LoginController{
    public static function login(Router $router){
        
        $alertas = [];

        if($_SERVER['REQUEST_METHOD'] === 'POST'){
            
            $usuario = new Usuario($_POST);


            $alertas = $usuario->validarLogin();
            if(empty($alertas)){
                //verificar que el usuario exista
                $usuario = Usuario::where('email', $usuario->email);

                if(!$usuario || !$usuario->confirmado){
                    Usuario::setAlerta('error', 'El Usuario No Existe O No Esta Confirmado');
                }else {
                    //el suaurio existe
                    if(password_verify($_POST['password'], $usuario->password)){

                        session_start();
                        $_SESSION['id'] = $usuario->id;
                        $_SESSION['nombre'] = $usuario->nombre;
                        $_SESSION['email'] = $usuario->email;
                        $_SESSION['login'] = true;

                        //redireccionar
                        header('Location: /dashboard');

                    }else {
                        Usuario::setAlerta('error', 'Password Incorrecto');
                    }
                }

            } 

        }

        $alertas = Usuario::getAlertas();

        $router->render('auth/login', [
            'titulo' => 'Iniciar Sesión',
            'alertas' => $alertas
        ]);
    }

    public static function logout(){
        session_start();
        $_SESSION= [];
        header('Location: /');

        
    }
    public static function crear(Router $router){
        $alertas = [];
        $usuario = new Usuario;
       

        if($_SERVER['REQUEST_METHOD'] === 'POST'){
            
            $usuario->sincronizar($_POST);
            $alertas = $usuario->validarNuevaCuenta();

            if(empty($alertas)){
                $existeUsuario = Usuario::where('email', $usuario->email);

            if($existeUsuario){
                Usuario::setAlerta('error', 'El Usuario ya esta Registrado');
                $alertas = Usuario::getAlertas();
            }else {
                //HASH PASSWORD
                $usuario->hashPassword();

                //eliminar password 2 

                unset($usuario->password2);

                //generar el token 
                $usuario->crearToken();

                $resultado = $usuario->guardar();

                //enviar email

                $email = new Email($usuario->email, $usuario->nombre, $usuario->token);
               $email->enviarConfirmacion();

                if($resultado){
                    header('Location: /mensaje');
                }
            }
            }

             

        }
        $router->render('auth/crear', [
            'titulo' => 'Crea tu Cuenta',
            'usuario' => $usuario,
            'alertas' => $alertas
        ]);
    }
    public static function olvide(Router $router){

        $alertas = [];
       

        if($_SERVER['REQUEST_METHOD'] === 'POST'){
            $usuario = new Usuario($_POST);
            $alertas = $usuario->validarEmail();

            if(empty($alertas)){
                //Search User
                $usuario = Usuario::where('email', $usuario->email);

                if($usuario && $usuario->confirmado){
                    //Generate New Token 

                    $usuario->crearToken();
                    unset($usuario->password2);


                    //actualizar el ususario 
                    $usuario->guardar();

                    //Send Email

                    $email = new Email($usuario->email, $usuario->nombre, $usuario->token);
                    $email->enviarInstrucciones();

                    //Print the Alert 
                    Usuario::setAlerta('exito', 'Hemos enviado las instrucciones a tu email');

                } else {
                    Usuario::setAlerta('error', 'El Usuario no existe o no esta confirmado ');
                  
                }
            }
        }
        $alertas = Usuario::getAlertas();
        $router->render('auth/olvide',[
            'titulo'=> 'Olvide mi Password',
            'alertas'=> $alertas
        ]);
    }
    public static function restablecer(Router $router){
        
        $token = s($_GET['token']);
        $mostrar = true;

        if(!$token) header('Location: /');

        //identificar el usuario con este token 

        $usuario = Usuario::where('token', $token);

        if(empty($usuario)){
            Usuario::setAlerta('error', 'Token No Válido');
            $mostrar = false;
        }

    
        if($_SERVER['REQUEST_METHOD'] === 'POST'){
            //añadir el nuevo password
            $usuario->sincronizar($_POST);

            //Password Validate

            $alertas = $usuario->validarPassword();

            if(empty($alertas)){
                //hasear el nuevo password

                $usuario->hashPassword();

                //Delete Token
                $usuario->token = null;


                // Save User 
               $resultado=  $usuario->guardar();

                

                //Redirecting 
                if($resultado){
                    header('Location: /');
                }

            }

        }

        $alertas = Usuario::getAlertas();
        $router->render('auth/restablecer', [
            'titulo' => 'Restablecer',
            'alertas' => $alertas,
            'mostrar' => $mostrar
        ]);
    }
    public static function mensaje(Router $router) {
        $router->render('auth/mensaje',[
            'titulo' => 'Cuenta Creada Exitosamente'
        ]);

        
    }
    public static function confirmar(Router $router){

        $token = s($_GET['token']);

        if(!$token) header('Location: /');

        //econtrar el ususario con este toke n

        $usuario = Usuario::where('token', $token);

        if(empty($usuario)){
            //no se encontro el ususario con ese token 
            Usuario::setAlerta('error', 'Token no Válido');
        } else {
            //confrimar cuenta 
            $usuario->confirmado = 1;
            $usuario->token = null;
            unset($usuario->password2);

            $usuario->guardar();
            Usuario::setAlerta('exito', 'Cuenta Comprobada Correctamente');
        }

        $alertas = Usuario::getAlertas();
       

        $router->render('auth/confirmar',[
            'titulo' => 'Confirmar Cuenta',
            'alertas' => $alertas
        ]);
 
    }

    

    
}