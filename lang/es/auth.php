<?php

return [

    'failed' => 'Estas credenciales no coinciden con nuestros registros.',
    'password' => 'La contraseña proporcionada es incorrecta.',
    'throttle' => 'Demasiados intentos de acceso. Inténtelo de nuevo en :seconds segundos.',

    'inactive_user' => 'Tu usuario está inactivo.',
    'sucursal_not_assigned' => 'La sucursal seleccionada no está asignada a tu usuario.',

    'login' => [
        'title' => 'Inicia sesión en tu cuenta',
        'description' => 'Introduce tu correo y contraseña para continuar',
        'email_label' => 'Correo electrónico',
        'password_label' => 'Contraseña',
        'placeholder_email' => 'correo@ejemplo.com',
        'forgot_password' => '¿Olvidaste tu contraseña?',
        'remember_me' => 'Recordarme',
        'submit' => 'Iniciar sesión',
        'no_account' => '¿No tienes una cuenta?',
        'sign_up' => 'Registrarse',
    ],

    'register' => [
        'title' => 'Crear una cuenta',
        'description' => 'Completa tus datos para registrarte',
        'name' => 'Nombre',
        'full_name_placeholder' => 'Nombre completo',
        'email_label' => 'Correo electrónico',
        'password_label' => 'Contraseña',
        'confirm_password_label' => 'Confirmar contraseña',
        'submit' => 'Crear cuenta',
        'have_account' => '¿Ya tienes una cuenta?',
        'log_in' => 'Iniciar sesión',
    ],

    'forgot_password' => [
        'title' => 'Recuperar contraseña',
        'description' => 'Introduce tu correo para recibir un enlace de restablecimiento',
        'email_label' => 'Correo electrónico',
        'submit' => 'Enviar enlace de restablecimiento',
        'or_return' => 'O volver a',
        'log_in' => 'iniciar sesión',
    ],

    'reset_password' => [
        'title' => 'Restablecer contraseña',
        'description' => 'Introduce tu nueva contraseña a continuación',
        'email_label' => 'Correo electrónico',
        'password_label' => 'Contraseña',
        'confirm_password_label' => 'Confirmar contraseña',
        'submit' => 'Restablecer contraseña',
    ],

    'confirm_password' => [
        'title' => 'Confirmar contraseña',
        'description' => 'Esta es un área segura. Confirma tu contraseña antes de continuar.',
        'password_label' => 'Contraseña',
        'submit' => 'Confirmar',
    ],

    'verify_email' => [
        'intro' => 'Verifica tu correo haciendo clic en el enlace que te acabamos de enviar.',
        'sent' => 'Se ha enviado un nuevo enlace de verificación al correo que indicaste al registrarte.',
        'resend' => 'Reenviar correo de verificación',
        'log_out' => 'Cerrar sesión',
    ],

    'two_factor' => [
        'auth_code_title' => 'Código de autenticación',
        'auth_code_description' => 'Introduce el código de tu aplicación de autenticación.',
        'recovery_title' => 'Código de recuperación',
        'recovery_description' => 'Confirma el acceso introduciendo uno de tus códigos de emergencia.',
        'otp_label' => 'Código de verificación',
        'continue' => 'Continuar',
        'or_you_can' => 'o puedes',
        'login_with_recovery' => 'iniciar sesión con un código de recuperación',
        'login_with_auth_code' => 'iniciar sesión con un código de autenticación',
    ],

];
