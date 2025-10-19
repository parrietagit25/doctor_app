# Instalación de Dependencias para Generación de PDFs

## 📋 Instrucciones de Instalación

### Opción 1: Instalación Automática (Recomendada)

1. **Ejecutar el script de instalación:**
   ```bash
   php install_dependencies.php
   ```

2. **El script automáticamente:**
   - Descarga e instala Composer
   - Instala DomPDF via Composer
   - Configura el autoloader

### Opción 2: Instalación Manual

1. **Instalar Composer:**
   ```bash
   # Descargar Composer
   curl -sS https://getcomposer.org/installer | php
   
   # O usar el instalador de Windows
   # https://getcomposer.org/Composer-Setup.exe
   ```

2. **Instalar dependencias:**
   ```bash
   php composer.phar install --no-dev --optimize-autoloader
   ```

### Opción 3: Si ya tienes Composer instalado globalmente

```bash
composer install --no-dev --optimize-autoloader
```

## 🔧 Verificación de la Instalación

Después de la instalación, deberías tener:

- ✅ `vendor/` - Directorio con las dependencias
- ✅ `vendor/autoload.php` - Autoloader de Composer
- ✅ `composer.phar` - Ejecutable de Composer (si se instaló localmente)

## 📚 Librerías Instaladas

### DomPDF v2.0
- **Descripción:** Librería para generar PDFs desde HTML/CSS
- **Características:**
  - Soporte completo para HTML5 y CSS3
  - Fuentes embebidas (DejaVu Sans)
  - Soporte para UTF-8
  - Optimizado para rendimiento

## 🚀 Uso

Una vez instaladas las dependencias, el sistema automáticamente:

1. **Detecta DomPDF** y lo usa para generar PDFs reales
2. **Fallback inteligente** si DomPDF no está disponible
3. **Genera PDFs profesionales** con diseño corporativo

## 🔍 Solución de Problemas

### Error: "Class 'Dompdf\Dompdf' not found"
- **Solución:** Ejecutar `php install_dependencies.php`
- **Verificar:** Que existe `vendor/autoload.php`

### Error: "Composer not found"
- **Solución:** El script descarga Composer automáticamente
- **Manual:** Descargar desde https://getcomposer.org/

### Error: "Permission denied"
- **Solución:** Dar permisos de escritura al directorio
- **Linux/Mac:** `chmod 755 .`
- **Windows:** Ejecutar como administrador

## 📁 Estructura de Archivos

```
doctor_app/
├── composer.json              # Configuración de Composer
├── composer.phar              # Ejecutable de Composer
├── vendor/                    # Dependencias instaladas
│   ├── autoload.php          # Autoloader
│   └── dompdf/               # Librería DomPDF
├── classes/
│   └── CotizacionPDFComposer.php  # Generador de PDFs
└── install_dependencies.php   # Script de instalación
```

## 🎯 Características del PDF Generado

- ✅ **PDF real** generado con DomPDF
- ✅ **Diseño profesional** con estilos corporativos
- ✅ **Fuentes embebidas** (DejaVu Sans)
- ✅ **Soporte UTF-8** para caracteres especiales
- ✅ **Optimizado para impresión** en formato A4
- ✅ **Información completa** de la cotización

## 🔄 Actualización

Para actualizar las dependencias:

```bash
php composer.phar update
```

## 🗑️ Desinstalación

Para eliminar las dependencias:

```bash
rm -rf vendor/
rm composer.phar
rm composer.json
rm composer.lock
```

## 📞 Soporte

Si tienes problemas con la instalación:

1. **Verificar PHP:** Versión 7.4 o superior
2. **Verificar permisos:** Escritura en el directorio
3. **Verificar conexión:** Internet para descargar dependencias
4. **Revisar logs:** Mensajes de error en la consola

---

**Nota:** Una vez instalado, el sistema generará PDFs reales automáticamente. No se requiere configuración adicional.
