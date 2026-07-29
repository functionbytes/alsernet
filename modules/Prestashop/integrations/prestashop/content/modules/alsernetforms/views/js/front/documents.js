$(document).ready(function () {

    // Solo ejecutar si existe el contenedor
    if (!$('#alsernet-documents').length) {
        return; // Salir para evitar errores
    }

    // ✅ REFACTORIZADO: Obtener URL base dinámicamente del atributo data del form
    window.API_BASE_URL = $('#alsernet-documents').data('api-base-url');

    if (!window.API_BASE_URL) {
        console.error('❌ Error: API base URL not found in form data attribute');
        return;
    }

    console.log('✅ API Base URL initialized:', window.API_BASE_URL);

    // ✅ NUEVO: Variable global para almacenar documentos faltantes
    // Se actualiza cada vez que se carga el estado de documentos
    window.missingDocuments = {};

    // Cargar estado de documentos al inicio
    loadDocumentStatus();

    // ============================================
    // Controlar estado del botón de submit
    // El botón debe estar DISABLED hasta que:
    // 1. Se acepten los términos (checkbox condition)
    // 2. Se complete el reCAPTCHA
    // ============================================

    // Función para verificar si el formulario puede ser enviado
    function updateSubmitButtonState() {
        const $submitBtn = $('button[type="submit"]');
        const isConditionChecked = $('#condition').is(':checked');
        const isRecaptchaValid = grecaptcha && grecaptcha.getResponse() ? grecaptcha.getResponse().length > 0 : false;

        console.log('Checking button state:', {
            conditionChecked: isConditionChecked,
            recaptchaValid: isRecaptchaValid
        });

        // Habilitar solo si AMBAS condiciones se cumplen
        if (isConditionChecked && isRecaptchaValid) {
            $submitBtn.prop('disabled', false).removeAttr('style').removeAttr('aria-disabled');
            console.log('✅ Submit button ENABLED');
        } else {
            $submitBtn.prop('disabled', true).attr('style', 'opacity: 0.5; cursor: not-allowed;').attr('aria-disabled', 'true');
            console.log('❌ Submit button DISABLED');
        }
    }

    // Inicialmente, el botón debe estar DISABLED
    $('button[type="submit"]').prop('disabled', true).attr('style', 'opacity: 0.5; cursor: not-allowed;').attr('aria-disabled', 'true');

    // Escuchar cambios en el checkbox de condiciones
    $('#condition').on('change', function() {
        console.log('Condition checkbox changed:', $(this).is(':checked'));
        updateSubmitButtonState();
    });

    // Escuchar cambios en el reCAPTCHA
    // Se dispara después de que el usuario completa el captcha
    window.onRecaptchaChange = function() {
        console.log('✅ reCAPTCHA completed/verified');
        updateSubmitButtonState();
    };

    // Escuchar cuando el reCAPTCHA expira
    window.onRecaptchaExpire = function() {
        console.log('⚠️ reCAPTCHA expired - need to verify again');
        updateSubmitButtonState();
    };

    // También verificar cuando el reCAPTCHA carga (para casos donde ya está completo)
    // Hacer una verificación cada 500ms durante los primeros 5 segundos
    let checkCount = 0;
    const recaptchaCheckInterval = setInterval(function() {
        checkCount++;
        try {
            if (grecaptcha && grecaptcha.getResponse && grecaptcha.getResponse().length > 0) {
                console.log('reCAPTCHA detected as complete (periodic check)');
                updateSubmitButtonState();
                clearInterval(recaptchaCheckInterval);
            }
        } catch (e) {
            console.log('reCAPTCHA not ready yet');
        }

        if (checkCount > 10) {
            // Dejar de verificar después de 5 segundos
            clearInterval(recaptchaCheckInterval);
        }
    }, 500);

    // Manejar envío del formulario
    if ($.fn.validate) {
        $("#alsernet-documents").validate({
            rules: {
                condition: { required: true }
            },
            messages: {
                condition: { required: "You must accept the conditions." }
            },
            submitHandler: function (form) {
                // Obtener todos los archivos a subir
                const filesToUpload = [];

                // Formatos permitidos
                const allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
                const allowedMimeTypes = [
                    'application/pdf',
                    'image/jpeg',
                    'image/jpg',
                    'image/png',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                ];

                $(form).find('input[type="file"]').each(function() {
                    const input = this;
                    const files = input.files;
                    const docType = $(input).data('doc-type');

                    if (files && files.length > 0) {
                        // ✅ NUEVO: Validar que el docType es un documento faltante
                        if (!window.missingDocuments || !window.missingDocuments[docType]) {
                            showError(`The document "${docType}" is not in the list of missing documents or has already been uploaded. Please refresh the page and try again.`);
                            return false;
                        }

                        for (let i = 0; i < files.length; i++) {
                            const file = files[i];

                            // 1. Validar que el archivo existe
                            if (!file) {
                                showError('Please select a valid file.');
                                return false;
                            }

                            // 2. Validar tamaño (máximo 10MB)
                            const maxSize = 10 * 1024 * 1024; // 10MB en bytes
                            if (file.size > maxSize) {
                                const fileSizeMB = (file.size / (1024 * 1024)).toFixed(2);
                                showError(`File "${file.name}" is too large (${fileSizeMB}MB). Maximum size is 10MB.`);
                                return false;
                            }

                            // 3. Validar tamaño mínimo (no vacío)
                            if (file.size === 0) {
                                showError(`File "${file.name}" is empty. Please select a valid file.`);
                                return false;
                            }

                            // 4. Validar extensión del archivo
                            const fileName = file.name.toLowerCase();
                            const fileExtension = fileName.split('.').pop();
                            if (!allowedExtensions.includes(fileExtension)) {
                                showError(`File "${file.name}" has an invalid format. Allowed formats: PDF, JPG, PNG, DOC, DOCX.`);
                                return false;
                            }

                            // 5. Validar tipo MIME
                            if (!allowedMimeTypes.includes(file.type)) {
                                showError(`File "${file.name}" has an invalid type (${file.type}). Allowed formats: PDF, JPG, PNG, DOC, DOCX.`);
                                return false;
                            }

                            filesToUpload.push({
                                file: file,
                                docType: docType
                            });
                        }
                    }
                });

                // Si no hay archivos seleccionados, mostrar error
                if (filesToUpload.length === 0) {
                    if (window.toastr) {
                        toastr.warning('Please select at least one document to upload', 'Attention', {
                            closeButton: true,
                            progressBar: true,
                            positionClass: "toast-bottom-right"
                        });
                    } else {
                        alert('Please select at least one document to upload');
                    }
                    return false;
                }

                var $submitButton = $('button[type="submit"]');
                $submitButton.prop('disabled', true);

                // Mostrar progress bar
                $('#uploadProgress').show();
                $('#uploadStatus').text('0%');
                $('#uploadProgressBar').css('width', '0%');

                // Subir archivos uno por uno
                uploadFilesSequentially(filesToUpload, 0, form, $submitButton);

                return false;
            }
        });
    }

    // ✅ REFACTORIZADO: Eliminar documentos se maneja en el módulo de gestión de documentos de Laravel
    // No se permite eliminar documentos desde este formulario de Prestashop
});

/**
 * Subir archivos secuencialmente (uno por uno)
 */
function uploadFilesSequentially(files, index, form, $submitButton) {
    // Si ya subimos todos los archivos
    if (index >= files.length) {
        // Recargar estado y resetear formulario
        loadDocumentStatus();
        $(form)[0].reset();

        // Ocultar progress bar
        $('#uploadProgress').hide();

        // Habilitar botón
        $submitButton.prop('disabled', false);

        if (window.toastr) {
            toastr.success("All documents uploaded successfully!", "Success", {
                closeButton: true,
                progressBar: true,
                positionClass: "toast-bottom-right"
            });
        } else {
            alert("All documents uploaded successfully!");
        }

        return;
    }

    const fileData = files[index];
    const uid = $('#uid').val();
    const formData = new FormData();

    // Agregar archivo y tipo
    formData.append('file[]', fileData.file);
    formData.append('document_types[]', fileData.docType);

    // Agregar tipo de documento (ya no se necesita uid ni action en el body)
    formData.append('type', $('#type').val());

    // Calcular progreso
    const progress = Math.round(((index + 1) / files.length) * 100);
    $('#uploadStatus').text(progress + '%');
    $('#uploadProgressBar').css('width', progress + '%');
    $submitButton.html('<i class="fas fa-spinner fa-spin"></i> Uploading ' + (index + 1) + '/' + files.length + '...');

    // ✅ REFACTORIZADO: Usar URL dinámica en lugar de hardcodeada
    // IMPORTANTE: Headers necesarios para evitar redirección 302 del middleware Sanctum
    $.ajax({
        url: window.API_BASE_URL + '/' + uid + '/files',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        success: function (response) {
            if (response.status === 'success') {
                // Continuar con el siguiente archivo
                uploadFilesSequentially(files, index + 1, form, $submitButton);
            } else {
                // Error en este archivo
                $('#uploadProgress').hide();
                $submitButton.prop('disabled', false).html('Upload Document');

                if (window.toastr) {
                    toastr.error('Error uploading "' + fileData.file.name + '": ' + (response.message || 'Unknown error'), 'Error', {
                        closeButton: true,
                        progressBar: true,
                        positionClass: "toast-bottom-right"
                    });
                } else {
                    alert('Error uploading "' + fileData.file.name + '": ' + (response.message || 'Unknown error'));
                }
            }
        },
        error: function (xhr, status, error) {
            // Error en la petición
            $('#uploadProgress').hide();
            $submitButton.prop('disabled', false).html('Upload Document');

            let errorMsg = "Error uploading document. Please try again.";
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMsg = xhr.responseJSON.message;
            }

            if (window.toastr) {
                toastr.error(errorMsg, "Error", {
                    closeButton: true,
                    progressBar: true,
                    positionClass: "toast-bottom-right"
                });
            } else {
                alert(errorMsg);
            }
        }
    });
}

/**
 * Cargar estado de documentos desde el backend
 */
function loadDocumentStatus() {
    const uid = $('#uid').val();

    if (!uid) {
        console.error('UID not found');
        return;
    }

    // ✅ REFACTORIZADO: Usar URL dinámica en lugar de hardcodeada
    // IMPORTANTE: Headers necesarios para evitar redirección 302 del middleware Sanctum
    $.ajax({
        url: window.API_BASE_URL + '/' + uid + '/validation',
        type: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        success: function(response) {
            if (response.status === 'success' && response.data) {
                const validationStatus = response.data.validation_status;
                console.log('Validation status:', validationStatus);

                // ✅ NUEVO: Almacenar documentos faltantes globalmente para validación
                window.missingDocuments = response.data.missing_documents || {};
                console.log('✅ Missing documents stored:', window.missingDocuments);

                // Mostrar el panel apropiado según el estado de validación
                if (validationStatus === 'received') {
                    // Todos los documentos cargados - mostrar confirmación
                    $('#documentsConfirmation').removeClass('d-none');
                    $('#documentsContainer').addClass('d-none');
                    $('#validationPending').addClass('d-none');
                    $('#validationError').addClass('d-none');
                } else if (validationStatus === 'error') {
                    // Error: documento en estado final - mostrar error
                    $('#validationError').removeClass('d-none');
                    $('#documentsConfirmation').addClass('d-none');
                    $('#documentsContainer').addClass('d-none');
                    $('#validationPending').addClass('d-none');
                } else {
                    // Estado pendiente - mostrar formulario
                    updateDocumentUI(response.data);
                    updateDocumentCounter(response.data);
                    $('#documentsContainer').removeClass('d-none');
                    $('#documentsConfirmation').addClass('d-none');
                    $('#validationError').addClass('d-none');
                    $('#validationPending').addClass('d-none');
                }
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading document status:', error);
        }
    });
}

/**
 * Actualizar contador de documentos
 */
function updateDocumentCounter(data) {
    const uploadedCount = Object.keys(data.uploaded_documents || {}).length;
    const requiredCount = Object.keys(data.required_documents || {}).length;
    const missingCount = Object.keys(data.missing_documents || {}).length;

    console.log('Document counter:', { uploaded: uploadedCount, required: requiredCount, missing: missingCount });
    $('#documentCounter').text(uploadedCount + '/' + requiredCount + ' cargados');
}

/**
 * Actualizar UI con estado de documentos
 * Solo muestra los documentos faltantes (missing_documents)
 */
function updateDocumentUI(data) {
    console.log('=== updateDocumentUI ===');
    console.log('Required documents:', data.required_documents);
    console.log('Uploaded documents:', data.uploaded_documents);
    console.log('Missing documents:', data.missing_documents);

    // Ocultar todos los items primero
    $('.document-upload-item').hide();

    // Iterar solo por cada documento FALTANTE
    $.each(data.missing_documents || {}, function(docKey, docLabel) {
        const $item = $('[data-doc-type="' + docKey + '"]');

        if (!$item.length) {
            console.log('⚠️ Item no encontrado para docKey:', docKey);
            return;
        }

        console.log('✏️ Procesando documento faltante:', docKey, '→', docLabel);

        // Mostrar el item (estaba oculto)
        $item.show();

        // ✅ Mantener badge de "Pendiente" visible
        // El badge es un indicador visual importante del estado del documento

        // Asegurar que existe el input de archivo
        if ($item.find('input[type="file"]').length === 0) {
            const $input = $(`
                <input
                    type="file"
                    class="form-control document-file-input"
                    name="documents[${docKey}]"
                    data-doc-type="${docKey}"
                    accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                >
                <small class="text-muted d-block mt-1">
                    <i class="fas fa-circle-info"></i> PDF, JPG, PNG, DOC (max 10MB)
                </small>
            `);
            $item.append($input);
        }
    });

    // ✅ REFACTORIZADO: Documentos completados se muestran en el módulo de gestión de documentos de Laravel
    // No se muestra la sección de documentos completados en este formulario
}

/**
 * Mostrar mensaje de error
 */
function showError(message) {
    if (window.toastr) {
        toastr.error(message, "Error", {
            closeButton: true,
            progressBar: true,
            positionClass: "toast-bottom-right",
            timeOut: 5000
        });
    } else {
        alert(message);
    }
}
