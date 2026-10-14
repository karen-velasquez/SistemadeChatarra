<?php
function isActiveRoute($routeName){
    return request()->routeIs($routeName) ? 'menu-activo' : 'collapsed';
}
function isActiveSubMenu($routeName){
    return request()->routeIs($routeName) ? 'active' : '';
}

function mostrar($routeName){
    return request()->routeIs($routeName) ? 'show' : '';
}
function mes_texto($numero)
{
    $meses = array("Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre");
    return $meses[$numero -1 ];
}
function mes_texto_corto($numero)
{
    $meses = array("ENE","FEB","MAR","ABR","MAY","JUN","JUL","AGO","SEP","OCT","NOV","DIC");
    return $meses[$numero -1 ];
}

// Para el botón "Imprimir" de archivos adjuntos: un PDF ya se puede imprimir
// con el visor nativo del navegador, solo las imágenes necesitan convertirse
// primero (ver SirveArchivoAdjunto::responderArchivo con ?print=1).
function es_imagen_archivo(?string $nombreArchivo): bool
{
    if (!$nombreArchivo) return false;
    $ext = strtolower(pathinfo($nombreArchivo, PATHINFO_EXTENSION));
    return in_array($ext, ['jpg', 'jpeg', 'png']);
}