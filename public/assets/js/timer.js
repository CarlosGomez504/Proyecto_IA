/**
 * Temporizador para el control de tiempo en proyectos
 * 
 * Este script muestra un contador en tiempo real del tiempo
 * transcurrido desde que se inició el temporizador.
 */

/**
 * Inicia un contador que actualiza el tiempo transcurrido
 * 
 * @param {string} fechaInicio - Fecha de inicio en formato 'Y-m-d H:i:s'
 * @param {string} elementoId - ID del elemento HTML donde mostrar el tiempo
 */
function iniciarContador(fechaInicio, elementoId) {
    const elemento = document.getElementById(elementoId);
    
    if (!elemento) {
        console.error('Elemento no encontrado:', elementoId);
        return;
    }
    
    // Convertir la fecha de inicio a timestamp
    const inicio = new Date(fechaInicio.replace(' ', 'T')).getTime();
    
    /**
     * Actualiza el display del temporizador
     */
    function actualizarTimer() {
        const ahora = new Date().getTime();
        const diferencia = ahora - inicio;
        
        if (diferencia < 0) {
            elemento.textContent = '00:00:00';
            return;
        }
        
        // Calcular horas, minutos y segundos
        const horas = Math.floor(diferencia / (1000 * 60 * 60));
        const minutos = Math.floor((diferencia % (1000 * 60 * 60)) / (1000 * 60));
        const segundos = Math.floor((diferencia % (1000 * 60)) / 1000);
        
        // Formatear con ceros a la izquierda
        const horasStr = horas.toString().padStart(2, '0');
        const minutosStr = minutos.toString().padStart(2, '0');
        const segundosStr = segundos.toString().padStart(2, '0');
        
        elemento.textContent = `${horasStr}:${minutosStr}:${segundosStr}`;
    }
    
    // Actualizar inmediatamente
    actualizarTimer();
    
    // Actualizar cada segundo
    setInterval(actualizarTimer, 1000);
}

/**
 * Formatea minutos a formato HH:MM
 * 
 * @param {number} minutos - Total de minutos
 * @returns {string} Formato HH:MM
 */
function minutosAHoras(minutos) {
    const horas = Math.floor(minutos / 60);
    const mins = minutos % 60;
    return `${horas.toString().padStart(2, '0')}:${mins.toString().padStart(2, '0')}`;
}

// Exportar funciones para uso global
window.iniciarContador = iniciarContador;
window.minutosAHoras = minutosAHoras;