<?php
// ============================================================
// VETERINARIA ALEM - CONFIGURACIÓN GENERAL
// Todo lo que puede cambiar (teléfono, correos, horarios, IVA)
// está en este único archivo.
// ============================================================

// Carpeta del proyecto dentro de htdocs (sin barra al final).
define("BASE_URL", "/VeterinariaAlem_Proyecto");

// Hora de Uruguay para todo el sitio.
date_default_timezone_set("America/Montevideo");

// ---------- Datos de contacto de la veterinaria ----------
define("VET_NOMBRE", "Veterinaria Alem");
define("VET_TELEFONO", "+598 92 297 210");          // como se muestra en pantalla
define("VET_TELEFONO_LINK", "+59892297210");        // para llamar (tel:) y WhatsApp
define("VET_EMAIL_SOPORTE", "nahufontao14@gmail.com");

// ---------- Correos (verificación de cuenta, facturas, citas) ----------
// Remitente: el correo desde el que "salen" los mensajes.
define("MAIL_FROM", "nahufontao14@gmail.com");
define("MAIL_FROM_NOMBRE", "Veterinaria Alem");

// Opcional: recibir una copia de cada correo que envía el sistema.
// Déjalo vacío ("") para no recibir copias.
define("MAIL_COPIA_A", "");

// Servidor SMTP. Con Gmail hay que usar una "contraseña de aplicación"
// (Cuenta de Google > Seguridad > Verificación en 2 pasos > Contraseñas de aplicaciones).
// NO se usa la contraseña normal de Gmail.
define("SMTP_HOST", "smtp.gmail.com");
define("SMTP_PORT", 587);                           // 587 = STARTTLS, 465 = SSL
define("SMTP_USUARIO", "egeresoverificacion@gmail.com");
define("SMTP_CLAVE", "vsxqazufzbjuxqub");                           // <-- pegar aquí la contraseña de aplicación

// Si SMTP_CLAVE está vacía, el sistema funciona en MODO DEMOSTRACIÓN:
// los correos se guardan en la carpeta /correos_enviados y el enlace de
// verificación se muestra en pantalla para poder probar sin internet.
// En cuanto se completa SMTP_CLAVE, se envían correos reales y el enlace
// deja de mostrarse en pantalla.

// ---------- Tienda y facturación ----------
define("IVA_PORCENTAJE", 22);   // Los precios de productos YA incluyen IVA.
define("FACTURA_SERIE", "A");

// ---------- Agenda de citas ----------
define("HORA_APERTURA", "08:00");
define("HORA_CIERRE", "19:00");        // el último turno termina a esta hora
define("MINUTOS_POR_TURNO", 30);
define("DIAS_ATENCION", [1, 2, 3, 4, 5, 6]); // 1=lunes ... 6=sábado, 7=domingo
define("DIAS_BUSQUEDA", 14);           // cuántos días hacia adelante se busca horario libre
