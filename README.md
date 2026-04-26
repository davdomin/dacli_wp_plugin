# dAcli Laboratorios - Plugin de WordPress

Plugin de WordPress que integra la API de dAcli para mostrar laboratorios activos mediante un shortcode. Incluye caché configurable y panel de administración.

## Características

- ✅ **Caché inteligente**: Configurable (mínimo 5 minutos, por defecto 6 horas)
- ✅ **Panel de administración**: Configurar URL de API y tiempo de caché
- ✅ **Botón para limpiar caché**: Actualiza los datos bajo demanda
- ✅ **Validación de datos**: Sanitización y escapado correcto
- ✅ **Diseño responsivo**: Grid de tarjetas adaptativo
- ✅ **Seguridad**: Verificación de nonce y validación de permisos
- ✅ **Manejo de errores**: Mensajes claros cuando algo falla

## Instalación

1. Descarga la carpeta del plugin a `wp-content/plugins/`
2. Activa el plugin desde el panel de WordPress (Plugins → Plugins Instalados)
3. Ve a **Configuración → dAcli Laboratorios** para configurar

## Configuración

### Panel de Opciones

Ve a **Configuración → dAcli Laboratorios** para:

- **URL de la API**: Ingresa la URL completa de tu API de dAcli
  - Por defecto: `https://dacli.net/2c55947a-ca26-4fbc-b4c8-01cb7deb1c7f/lista_web.php`
- **Tiempo de caché**: Establece en segundos cuánto tiempo se guardan los datos
  - Mínimo: 300 segundos (5 minutos)
  - Por defecto: 21600 segundos (6 horas)

### Limpiar Caché

Haz clic en el botón **Limpiar Caché** para forzar que el plugin obtenga datos frescos de la API de inmediato.

## Uso

Usa el shortcode en cualquier página o entrada:

```
[dacli_lista]
```

El plugin mostrará una cuadrícula de tarjetas con los laboratorios disponibles.

## Estructura de Datos Esperada

La API debe retornar un JSON con la siguiente estructura:

```json
[
  {
    "nombre_lab": "Laboratorio ABC",
    "direccion_lab": "Calle Principal 123",
    "testimonio": "Excelente servicio",
    "logo": "logo.png",
    "link_laboratorio": "https://ejemplo.com"
  }
]
```

### Campos Disponibles

- `nombre_lab` (requerido): Nombre del laboratorio
- `direccion_lab`: Dirección física
- `testimonio`: Descripción o testimonio
- `logo`: Ruta relativa del logo (se concatena con la URL base)
- `link_laboratorio`: URL del sitio web del laboratorio

## Características Técnicas

### Caché

El plugin utiliza WordPress Transients para almacenar datos en caché:
- **Almacenamiento**: Base de datos de WordPress
- **Expiración automática**: Según el tiempo configurado
- **Limpieza manual**: Disponible desde el panel de administración

### Seguridad

- **Sanitización**: Todas las URLs y textos son sanitizados
- **Escapado**: HTML correctamente escapado para prevenir XSS
- **Nonce verification**: Verificación de tokens en formularios
- **Permisos**: Solo administradores pueden acceder a la configuración
- **SSL verification**: Verificación de certificados en requests

### Rendimiento

- **Transients**: Caché nativa de WordPress
- **Timeout de API**: 10 segundos máximo
- **Grid responsivo**: CSS nativo sin librerías externas
- **Validación eficiente**: Evita procesamiento de datos incompletos

## Solución de Problemas

### El shortcode no muestra nada

1. Verifica que hayas ingresado la URL correcta en la configuración
2. Asegúrate de que la API sea accesible
3. Limpia el caché desde el panel de administración
4. Revisa los logs de WordPress en `/wp-content/debug.log`

### Error de conexión a la API

- Verifica la URL en **Configuración → dAcli Laboratorios**
- Comprueba que tu servidor tenga acceso a internet
- Asegúrate de que el certificado SSL sea válido (si usas HTTPS)

### El caché no se actualiza

- Usa el botón **Limpiar Caché** en el panel de administración
- O cambia el tiempo de caché a un valor menor

## Versión

**v1.1** - Incluye caché, panel de configuración y mejoras de seguridad

## Autor

David Domínguez

## Licencia

GPL v2 o superior