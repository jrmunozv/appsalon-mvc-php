<?php
namespace Controllers;

use Classes\Email;
use Model\Usuario;
use MVC\Router;

class LoginController{
    public static function login(Router $router){
        $alertas= [];

        //$auth= new Usuario; //Para que conserve datos en formulario

        if($_SERVER['REQUEST_METHOD'] === 'POST'){
            $auth= new Usuario($_POST);

            $alertas= $auth->validarLogin();

            if(empty($alertas)){
                //Comprobar que exista el usuario
                $usuario= Usuario::where('email', $auth->email);

                if($usuario){
                    //Verificar el password
                    if($usuario->comprobarPasswordAndVerificado($auth->password)){
                        //Autenticar el usuario
                        session_start();

                        $_SESSION['id'] = $usuario->id;
                        $_SESSION['nombre'] = $usuario->nombre . " " . $usuario->apellido;
                        $_SESSION['email'] = $usuario->email;
                        $_SESSION['login'] = true;

                        //Redireccionamiento
                        if($usuario->admin === "1"){
                            $_SESSION['admin'] = $usuario->admin ?? null;
                            header('Location: /admin');    
                        }else{
                            header('Location: /cita');
                        }
                        
                        
                    }

                } else {
                    Usuario::setAlerta('error', 'Usuario no encontrado');
                }
            }
        }

        $alertas= Usuario::getAlertas();

        $router->render('auth/login', [
            'alertas' => $alertas
            //'auth' => $auth //Para que conserve datos en formulario
        ]);

    }

    public static function logout(){
        session_start();
        //debuguear($_SESSION);
        $_SESSION= [];

        header('Location: /');

        
    }

    public static function olvide(Router $router){

        $alertas= [];

        if($_SERVER['REQUEST_METHOD']  === 'POST'){
            $auth= new Usuario($_POST);
            $alertas= $auth->validarEmail();

            if(empty($alertas)){
                $usuario= Usuario::where('email', $auth->email);
                
                if($usuario && $usuario->confirmado === "1"){
                    //Generar un token
                    $usuario->crearToken();
                    $usuario->guardar();
                    
                    //enviar email
                    $email= new Email($usuario->nombre, $usuario->email, $usuario->token);
                    $email->enviarInstrucciones();

                    //Alerta de exito
                    Usuario::SetAlerta('exito','Revisa tu mail');

                }else {
                    Usuario::setAlerta('error','El usuario no exste o no esta confirmado');
                }
            }
        }

        $alertas= Usuario::getAlertas();

        $router->render('auth/olvide-password', [
            'alertas' => $alertas    
        ]);
    }

    public static function recuperar(Router $router){
        $alertas= [];
        $error= false;

        $token= s($_GET['token']);

        //Buscar usuario por su token
        $usuario= Usuario::where('token', $token);

        if(empty($usuario)){
            Usuario::setAlerta('error', 'Token no valido');
            $error= true;
        }

        if($_SERVER['REQUEST_METHOD'] === 'POST'){
            //Leer el nuevo password y guardarlo

            $password= new Usuario($_POST);
            $alertas= $password->validarPassword();

            if(empty($alertas)){
                $usuario->password= null;

                $usuario->password= $password->password;
                $usuario->hashPassword();
                $usuario->token= null;

                $resultado= $usuario->guardar();
                if($resultado){
                    header('Location: /');
                }

                //debuguear($usuario);
            }
        }

        //debuguear($usuario);
        $alertas= Usuario::getAlertas();
        $router->render('auth/recuperar-password',[
            'alertas' => $alertas,
            'error' => $error
        ]);
    }

    public static function crear(Router $router){
        $usuario= new Usuario;

        //alertas vacias
        $alertas= [];
        if($_SERVER['REQUEST_METHOD']=== 'POST'){
            //echo "enviaste el form";
            
            //debuguear($usuario);
            $usuario->sincronizar($_POST);
            $alertas= $usuario->validarNuevaCuenta();

            //debuguear($alertas);
            //Revisar que alertas esté vacio
            if(empty($alertas)){
                //echo "pasaste la validacion";
                //Verificar que el usuario no este registrado
                $resultado= $usuario->existeUsuario();

                if($resultado->num_rows){
                    $alertas= Usuario::getAlertas();
                }else{
                    //hashear el pass
                    $usuario->hashPassword();
                    
                    //Generar un token unico
                    $usuario->crearToken();

                    //Enviar mail
                    $email= new Email($usuario->nombre, $usuario->email, $usuario->token);
                    
                    $email->enviarConfirmacion();

                    //Crear el usuario
                    $resultado= $usuario->guardar();
                    if($resultado){
                        header('Location: /mensaje');
                    }

                    //debuguear($usuario);

                }
            }

        }
        
        $router->render('auth/crear-cuenta', [
            'usuario' => $usuario,
            'alertas' => $alertas
        ]);
    }

    public static function mensaje(Router $router){
        $router->render('auth/mensaje');
    }

    public static function confirmar(Router $router){
        $alertas= [];

        $token= s($_GET['token']);

        $usuario= Usuario::where('token', $token);

        //debuguear($usuario);
        if(empty($usuario)){
            //Mostrar mensaje de error
            Usuario::setAlerta('error', 'Token no válido');
        }else{
            //Modificar a usuario confirmado
            $usuario->confirmado= "1";
            $usuario->token= '';
            $usuario->guardar();
            Usuario::setAlerta('exito', 'Cuenta confirmada correctamente');
        }

        $alertas= Usuario::getAlertas();
        $router->render('auth/confirmar-cuenta',[
            'alertas' => $alertas    
        ]);
    }
}
