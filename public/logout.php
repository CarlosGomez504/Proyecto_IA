<?php
/**
 * Cerrar Sesión
 * 
 * Destruye la sesión del usuario y redirige al login.
 */

// Incluir archivos necesarios
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/funciones.php';

// Cerrar sesión
cerrarSesion();

// Redirigir al login
redirigir('index.php');
