/**
 * Template Preview Page - Print and Device Switching
 */

document.addEventListener('DOMContentLoaded', function() {
    // Device view switcher
    document.querySelectorAll('#btnDesktopView, #btnMobileView').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const width = this.getAttribute('data-width');
            const container = document.getElementById('previewContainer');

            if (!container) {
                if (typeof toastr !== 'undefined') {
                    toastr.error('Error: Contenedor de vista previa no encontrado', 'Error');
                }
                return;
            }

            // Update active button
            document.querySelectorAll('.btn-group .btn').forEach(b => {
                b.classList.remove('active');
            });
            this.classList.add('active');

            // Remove previous view classes
            container.classList.remove('preview-desktop-view', 'preview-mobile-view');

            // Apply appropriate view class
            if (width === '375px') {
                container.classList.add('preview-mobile-view');
            } else {
                container.classList.add('preview-desktop-view');
            }

            // Visual feedback
            if (typeof toastr !== 'undefined') {
                if (width === '375px') {
                    toastr.info('Vista móvil activada (375px)', 'Vista Previa', {
                        timeOut: 1500,
                        progressBar: true
                    });
                } else {
                    toastr.info('Vista desktop activada (100%)', 'Vista Previa', {
                        timeOut: 1500,
                        progressBar: true
                    });
                }
            }
        });
    });

    // Print email preview
    const printBtn = document.getElementById('btnPrintTemplate');
    if (printBtn) {
        printBtn.addEventListener('click', function(e) {
            e.preventDefault();
            handlePrint();
        });
    }
});

function handlePrint() {
    const container = document.getElementById('previewContainer');

    if (!container) {
        if (typeof toastr !== 'undefined') {
            toastr.error('No se encontró el contenedor de vista previa', 'Error');
        }
        return;
    }

    // Get the inner HTML and clean it
    let emailContent = container.innerHTML;

    // Remove any scripts from content
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = emailContent;
    const scripts = tempDiv.querySelectorAll('script');
    scripts.forEach(script => script.remove());
    emailContent = tempDiv.innerHTML;

    if (!emailContent.trim()) {
        if (typeof toastr !== 'undefined') {
            toastr.error('El contenido del email está vacío', 'Error');
        }
        return;
    }

    const printWindow = window.open('', '', 'width=1200,height=800');

    if (!printWindow) {
        if (typeof toastr !== 'undefined') {
            toastr.error('No se pudo abrir la ventana de impresión. Verifica que no hayas bloqueado las ventanas emergentes.', 'Error');
        }
        return;
    }

    try {
        // Clone the container to preserve styles
        const containerClone = container.cloneNode(true);

        // Get all stylesheets from the document
        let stylesheets = '';
        for (let i = 0; i < document.styleSheets.length; i++) {
            try {
                const sheet = document.styleSheets[i];
                const rules = sheet.cssRules || sheet.rules;
                for (let j = 0; j < rules.length; j++) {
                    stylesheets += rules[j].cssText;
                }
            } catch (e) {
                // Skip stylesheets we can't access (cross-origin)
                console.warn('Could not access stylesheet:', e);
            }
        }

        // Build the print document
        let html = '<!DOCTYPE html><html lang="es"><head>';
        html += '<meta charset="UTF-8">';
        html += '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
        html += '<title>Imprimir Plantilla de Email</title>';
        html += '<style>';
        html += 'body { margin: 0; padding: 20px; background: white; font-family: Arial, sans-serif; }';
        html += '.print-container { width: 100%; max-width: 900px; margin: 0 auto; background: white; }';
        html += '.preview-email-container { max-width: none !important; margin: 0 !important; padding: 0 !important; }';
        html += stylesheets;
        html += '@media print {';
        html += '  body { padding: 0; margin: 0; }';
        html += '  .print-container { max-width: 100%; margin: 0; padding: 0; }';
        html += '  * { margin: 0 !important; padding: 0 !important; }';
        html += '  img { max-width: 100% !important; height: auto !important; }';
        html += '}';
        html += '</style>';
        html += '</head><body>';
        html += '<div class="print-container">';
        html += '<div class="preview-email-container">' + containerClone.innerHTML + '</div>';
        html += '</div>';
        html += '</body></html>';

        printWindow.document.open();
        printWindow.document.write(html);
        printWindow.document.close();

        // Trigger print after content and styles load
        setTimeout(function() {
            printWindow.focus();
            printWindow.print();
        }, 1000);

        // Close on print finish
        if (printWindow.addEventListener) {
            printWindow.addEventListener('afterprint', function() {
                printWindow.close();
            });
        }

        if (typeof toastr !== 'undefined') {
            toastr.info('Preparando impresión...', 'Impresión', {
                timeOut: 2000,
                progressBar: true
            });
        }
    } catch (error) {
        console.error('Error al imprimir:', error);
        if (typeof toastr !== 'undefined') {
            toastr.error('Error al procesar la impresión. Intenta de nuevo.', 'Error');
        }
        if (printWindow && !printWindow.closed) {
            printWindow.close();
        }
    }
}
