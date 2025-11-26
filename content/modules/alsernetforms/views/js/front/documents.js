Dropzone.autoDiscover = false; // Desactivar autoDiscover

var dropzoneInstance; // Instancia global

$(document).ready(function () {

    // Solo ejecutar si existe el contenedor
    if (!$('#documents').length || !$('#alsernet-documents').length) {
        return; // Salir para evitar errores
    }

    function generateCSRFToken() {
        return Math.random().toString(36).substring(2) + Math.random().toString(36).substring(2);
    }

    // Inicialización de Dropzone
    dropzoneInstance = new Dropzone("#documents", {
        paramName: "file",
        url: "#",
        autoQueue: false,
        autoProcessQueue: false,
        uploadMultiple: true,
        addRemoveLinks: true,
        acceptedFiles: ".png,.jpg,.jpeg,.pdf",
        parallelUploads: 3,
        maxFiles: 3,
        headers: {
            'X-CSRF-TOKEN': generateCSRFToken()
        },
        init: function () {
            var dz = this;

            var item = $("#documents_value").val();
            if (item) {
                const mockFile = { name: item, size: 123456, accepted: true };
                dz.emit("addedfile", mockFile);
                dz.emit("complete", mockFile);
                dz.files.push(mockFile);
            }

            dz.on("maxfilesexceeded", function (file) {
                dz.removeAllFiles();
                dz.addFile(file);
            });

            dz.on("addedfile", function (file) {
                $("#documents_value").val(file.name);
                $("#documents_status").val("true");
                if ($.fn.validate) {
                    $("#alsernet-documents").validate().element("#documents_value");
                }
            });

            dz.on("removedfile", function () {
                $("#documents_value").val("");
                $("#documents_status").val("false");
                if ($.fn.validate) {
                    $("#alsernet-documents").validate().element("#documents_value");
                }
            });

            dz.on("resetFiles", function () {
                dz.removeAllFiles(true);
                $("#documents_status").val("false");
            });
        }
    });

    // Validación y envío
    if ($.fn.validate) {
        $("#alsernet-documents").validate({
            ignore: ".ignore",
            rules: {
                documents: { required: true }
            },
            messages: {
                documents: { required: "El parámetro es necesario." }
            },
            submitHandler: function (form) {
                var formData = new FormData();
                const files = dropzoneInstance.getAcceptedFiles();

                files.forEach(file => {
                    formData.append('file[]', file);
                });

                formData.append('action', 'upload');
                formData.append('uid', $('#uid').val());
                formData.append('type', $('#type').val());

                var $submitButton = $('button[type="submit"]');
                $submitButton.prop('disabled', true);

                $.ajax({
                    url: 'https://webadminpruebas.a-alvarez.com/api/documents',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function (response) {
                        if (response.status === 'success') {
                            dropzoneInstance.removeAllFiles(true);
                            $('#documentsConfirmation').removeClass('d-none');
                            $('#documentsContainer').addClass('d-none');
                        }
                    },
                    error: function () {
                        $submitButton.prop('disabled', false);
                        if (window.toastr) {
                            toastr.error("Error al enviar el formulario", "Falló", {
                                closeButton: true,
                                progressBar: true,
                                positionClass: "toast-bottom-right"
                            });
                        } else {
                            alert("Error al enviar el formulario");
                        }
                    }
                });
            }
        });
    }
});
