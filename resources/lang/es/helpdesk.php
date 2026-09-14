<?php

return [
    'livechat' => [
        'title' => 'Configuración de LiveChat - Helpdesk',
        'page_title' => 'Configuración del widget de chat en vivo',
        'page_description' => 'Personaliza la apariencia y comportamiento del widget de chat para tu sitio web',

        'tabs' => [
            'widget' => 'Widget',
            'timeouts' => 'Timeouts',
            'install' => 'Instalación',
            'security' => 'Seguridad',
        ],

        'sections' => [
            'home_screen' => 'Pantalla de Inicio',
            'chat_screen' => 'Pantalla de Chat',
            'launcher' => 'Launcher',
            'style' => 'Estilos y Colores',
            'additional_options' => 'Opciones Adicionales',
            'feature_toggles' => 'Habilitar/Deshabilitar Funcionalidades',
            'installation' => 'Instalación del Widget',
            'timeouts_config' => 'Configuración de Tiempos',
        ],

        'fields' => [
            'show_avatars' => 'Mostrar Avatares',
            'show_avatars_help' => 'Las fotos de perfil de agentes activos serán visibles en la pantalla de inicio',

            'show_help_center' => 'Mostrar Centro de Ayuda',
            'show_help_center_help' => 'Muestra un enlace directo al centro de ayuda en la pantalla de inicio',

            'hide_suggested_articles' => 'Ocultar Artículos Sugeridos',
            'hide_suggested_articles_help' => 'No mostrar artículos recomendados automáticamente en la pantalla de inicio',

            'show_tickets_section' => 'Mostrar Sección de Tickets',
            'show_tickets_section_help' => 'Permite a los clientes ver sus tickets activos desde la pantalla de inicio',

            'enable_send_message' => 'Enviar Mensaje',
            'enable_send_message_help' => 'Permitir que los clientes envíen mensajes al soporte',

            'enable_create_ticket' => 'Crear Ticket',
            'enable_create_ticket_help' => 'Permitir que los clientes creen tickets de soporte',

            'enable_search_help' => 'Buscar Centro de Ayuda',
            'enable_search_help_help' => 'Permitir que los clientes busquen en el centro de ayuda',

            'welcome_message' => 'Mensaje de Bienvenida',
            'welcome_message_help' => 'Primer mensaje que ve el cliente al iniciar el chat',

            'input_placeholder' => 'Placeholder del Input',
            'input_placeholder_help' => 'Texto de ayuda que aparece en el campo de entrada de mensajes',

            'no_agents_message' => 'Mensaje: No hay agentes disponibles',
            'no_agents_message_help' => 'Mensaje cuando todos los agentes están desconectados',

            'queue_message' => 'Mensaje: Cliente en Cola',
            'queue_message_help' => 'Mensaje cuando el cliente está esperando en la cola (usa :number y :minutes como variables)',

            'position' => 'Posición del Widget',
            'position_help' => 'Esquina de la pantalla donde aparece el botón del chat',

            'side_spacing' => 'Espaciado Lateral (px)',
            'side_spacing_help' => 'Distancia desde el borde lateral de la pantalla',

            'bottom_spacing' => 'Espaciado Inferior (px)',
            'bottom_spacing_help' => 'Distancia desde el borde inferior de la pantalla',

            'hide_launcher' => 'Ocultar Launcher por Defecto',
            'hide_launcher_help' => 'El botón del chat estará oculto por defecto y deberá mostrarse manualmente via API',

            'primary_color' => 'Color Principal',
            'primary_color_help' => 'Color del encabezado y botones principales (se usa en light y dark mode)',

            'secondary_color' => 'Color Secundario',
            'secondary_color_help' => 'Color del texto y elementos secundarios (se adapta a dark:bg-gray-800)',
            'secondary_color_note' => 'Nota de Colores: El color secundario debe contrastar bien en modo oscuro (dark:bg-gray-800). Se recomienda usar colores claros como blanco (#ffffff) o tonos claros para mejor legibilidad.',
            'secondary_color_preview' => 'Vista previa en modo oscuro',
            'secondary_color_preview_help' => 'Activa o desactiva la vista previa del modo oscuro con tu configuración de colores actual',

            'header_title' => 'Título del Header',
            'header_title_help' => 'Título que aparece en la parte superior del widget',

            'show_timestamps' => 'Mostrar Timestamps',
            'show_timestamps_help' => 'Muestra la hora de envío en cada mensaje del chat',

            'typing_indicator' => 'Indicador de Escritura',
            'typing_indicator_help' => 'Muestra "agente está escribiendo..." cuando el agente responde',

            'sound_notifications' => 'Notificaciones de Sonido',
            'sound_notifications_help' => 'Reproduce un sonido cuando llegan nuevos mensajes',

            'enable_email_transcripts' => 'Permitir Descargar Transcripción',
            'enable_email_transcripts_help' => 'Los clientes pueden recibir por email el historial completo del chat',

            'enable_auto_transfer' => 'Cuando el agente no responde por',
            'auto_transfer_minutes' => 'minutos',
            'auto_transfer_help' => 'Transferir el cliente a otro agente disponible. Si el chat está en un grupo con asignación manual, el chat se pondrá en cola en su lugar.',

            'enable_auto_inactive' => 'Cuando no hay mensajes por',
            'auto_inactive_minutes' => 'minutos',
            'auto_inactive_help' => 'Marcar el chat como inactivo. Los chats inactivos no se incluyen en el límite de chats concurrentes de los agentes.',

            'enable_auto_close' => 'Cerrar automáticamente después de',
            'auto_close_minutes' => 'minutos',
            'auto_close_help' => 'Cerrar el chat automáticamente. Los clientes pueden reabrir chats cerrados enviando un nuevo mensaje a ese chat.',

            'trusted_domains' => 'Dominios de Confianza',
            'trusted_domains_help' => 'Lista de dominios permitidos separados por comas donde se puede incrustar el widget',

            'enforce_identity_verification' => 'Requerir Verificación de Identidad',
            'enforce_identity_verification_help' => 'Los clientes deben verificar su identidad antes de enviar mensajes',
        ],

        'buttons' => [
            'save_changes' => 'Guardar Cambios',
            'back' => 'Volver',
        ],

        'messages' => [
            'success' => 'Configuración actualizada correctamente',
            'error' => 'Error al actualizar la configuración',
        ],

        'install' => [
            'title' => 'Instalación del Widget',
            'instructions' => 'Instrucciones: Copia y pega este código antes de la etiqueta &lt;/body&gt; en cada página donde quieras que aparezca el widget.',
            'basic_code' => 'Código básico',
        ],

        'positions' => [
            'bottom-right' => 'Abajo Derecha',
            'bottom-left' => 'Abajo Izquierda',
            'top-right' => 'Arriba Derecha',
            'top-left' => 'Arriba Izquierda',
        ],
    ],
];
