<?php

return [
    'module_name' => 'Autenticación',
    'breadcrumb' => 'Autenticación',

    'actions' => [
        'create' => 'Crear',
        'edit' => 'Editar',
        'update' => 'Actualizar',
        'delete' => 'Eliminar',
        'view' => 'Ver',
        'save' => 'Guardar',
        'cancel' => 'Cancelar',
        'back' => 'Volver',
        'export' => 'Exportar',
        'search' => 'Buscar',
        'filter' => 'Filtrar',
        'reset' => 'Restablecer',
    ],

    'messages' => [
        'created' => 'Creado correctamente.',
        'updated' => 'Actualizado correctamente.',
        'deleted' => 'Eliminado correctamente.',
        'error' => 'Ha ocurrido un error. Por favor, inténtalo de nuevo.',
        'not_found' => 'No encontrado.',
        'unauthorized' => 'No tienes permiso para realizar esta acción.',
    ],

    'login' => [
        'title' => 'Iniciar sesión',
        'email' => 'Correo electrónico',
        'password' => 'Contraseña',
        'remember_me' => 'Recordarme',
        'submit' => 'Iniciar sesión',
        'failed' => 'Las credenciales no coinciden con nuestros registros.',
        'throttle' => 'Demasiados intentos de inicio de sesión. Por favor, espera :seconds segundos.',
    ],

    'logout' => [
        'title' => 'Cerrar sesión',
        'success' => 'Has cerrado sesión correctamente.',
    ],

    'password' => [
        'reset' => 'Restablecer contraseña',
        'request' => 'Solicitar restablecimiento',
        'email_sent' => 'Se ha enviado un enlace de restablecimiento a tu correo.',
        'reset_success' => 'Contraseña restablecida correctamente.',
        'confirm' => 'Confirmar contraseña',
        'update' => 'Actualizar contraseña',
        'current' => 'Contraseña actual',
        'new' => 'Nueva contraseña',
        'confirm_new' => 'Confirmar nueva contraseña',
        'incorrect' => 'La contraseña actual es incorrecta.',
        'changed' => 'Contraseña actualizada correctamente.',
    ],

    'two_factor' => [
        'title' => 'Verificación en dos pasos',
        'code' => 'Código de verificación',
        'submit' => 'Verificar',
        'recovery' => 'Usar código de recuperación',
        'enabled' => 'Autenticación en dos pasos activada.',
        'disabled' => 'Autenticación en dos pasos desactivada.',
        'invalid_code' => 'El código ingresado no es válido.',
    ],

    'magic_link' => [
        'title' => 'Enlace mágico',
        'send' => 'Enviar enlace mágico',
        'sent' => 'Enlace mágico enviado a tu correo.',
        'expired' => 'El enlace ha expirado. Por favor, solicita uno nuevo.',
        'used' => 'Este enlace ya fue utilizado.',
    ],

    'sessions' => [
        'title' => 'Sesiones activas',
        'current' => 'Sesión actual',
        'revoke' => 'Revocar sesión',
        'revoke_all' => 'Revocar todas las sesiones',
        'revoked' => 'Sesión revocada correctamente.',
        'last_active' => 'Último acceso',
    ],

    'devices' => [
        'title' => 'Dispositivos',
        'this_device' => 'Este dispositivo',
        'unknown' => 'Dispositivo desconocido',
    ],

    'lockscreen' => [
        'title' => 'Pantalla bloqueada',
        'message' => 'Tu sesión está bloqueada. Ingresa tu contraseña para continuar.',
        'unlock' => 'Desbloquear',
    ],
];
