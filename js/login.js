// js/login.js
// API simulada para validar usuario por email y clave md5

/**
 * Valida el usuario contra una "base de datos" simulada.
 * @param {string} email
 * @param {string} passwordMd5 - Clave ya en md5
 * @returns {Promise<{success: boolean, user?: object}>}
 */
export async function validarUsuario(email, passwordMd5) {
    // Llama a la API PHP real
    try {
        const res = await fetch('api/login.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email, passwordMd5 })
        });
        return await res.json();
    } catch (e) {
        return { success: false, error: 'Error de red' };
    }
}

// Importar función md5 universal
import { md5 } from '/js/md5.js';
