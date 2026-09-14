<div class="documents mt-4">
    <div class="container">
        <div class="row">
            {if $document_type && $missing_documents}
                <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-12" id="documentsContainer">
                    <div class="mb-3" id="documentsUploadCard">
                        <div class="card-header p-3 bg-white border-bottom">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="mb-1 fw-bold">
                                        {l s='Upload delivery note for sale #' mod='alsernetforms'}{$label}
                                    </h5>
                                    <p class="small mb-0 text-muted">
                                        {l s='Please upload the required documentation for processing your order.' mod='alsernetforms'}
                                    </p>
                                </div>
                                <div>
                                    {assign var="uploadedCount" value=0}
                                    {if $uploaded_documents}
                                        {assign var="uploadedCount" value=$uploaded_documents|count}
                                    {/if}
                                    {assign var="totalDocs" value=$required_documents|count}
                                    <span id="documentCounter">
                                        {$uploadedCount}/{$totalDocs} cargados
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            {if $trans}
                                <div class="alert alert-info mb-3">
                                    {$trans nofilter}
                                    {if $trans_list}
                                        {$trans_list nofilter}
                                    {/if}
                                </div>
                            {/if}

                            {* Formulario de carga de documentos *}
                            <form id="alsernet-documents" enctype="multipart/form-data" method="post" onsubmit="return false" novalidate data-api-base-url="{$api_base_url}">
                                {* ✅ REFACTORIZADO: Usar endpoints RESTful en lugar de routes.php deprecated *}
                                <input type="hidden" name="uid" id="uid" value="{$uid}">
                                <input type="hidden" name="type" id="type" value="{$document_type}">
                                <input type="hidden" name="documents" id="documents_value" value="">
                                <input type="hidden" name="documents_status" id="documents_status" value="false">

                                {* Iterar solo por documentos FALTANTES (missing_documents) *}
                                {if $missing_documents}
                                    {foreach from=$missing_documents key=docKey item=docLabel}
                                        <div class="mb-3 document-upload-item" data-doc-type="{$docKey}">
                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                <label class="form-label mb-0 fw-semibold">
                                                    {$docLabel}
                                                </label>
                                                <span class="badge bg-danger-subtle text-danger">
                                                    {l s='Pendiente' mod='alsernetforms'}
                                                </span>
                                            </div>

                                            {* Input para cargar documento *}
                                            <input
                                                type="file"
                                                class="form-control document-file-input"
                                                name="documents[{$docKey}]"
                                                data-doc-type="{$docKey}"
                                                accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                                            >
                                            <small class="text-muted d-block mt-1">
                                                <i class="fas fa-circle-info"></i> {l s='PDF, JPG, PNG, DOC (max 10MB)' mod='alsernetforms'}
                                            </small>
                                        </div>
                                    {/foreach}
                                {else}
                                    {* Si no hay documentos faltantes, mostrar mensaje *}
                                    <div class="alert alert-success" role="alert">
                                        {l s='All documents have been uploaded successfully!' mod='alsernetforms'}
                                    </div>
                                {/if}

                                {* ✅ REFACTORIZADO: Documentos completados se muestran en el módulo de gestión de documentos de Laravel, no aquí *}

                                {* Progress Bar *}
                                <div id="uploadProgress" style="display: none;" class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <small class="fw-semibold">{l s='Uploading documents...' mod='alsernetforms'}</small>
                                        <small class="text-muted" id="uploadStatus">0%</small>
                                    </div>
                                    <div class="progress" style="height: 10px;">
                                        <div class="progress-bar bg-success progress-bar-striped progress-bar-animated" role="progressbar" id="uploadProgressBar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </div>

                                <div class="col-12 mt-3">
                                    <div class="errors mb-3 d-none"></div>
                                </div>

                                {* Mostrar checkboxes solo si hay documentos faltantes *}
                                {if $missing_documents}
                                    <div class="form-check mb-3">
                                        <div class="check">
                                            <input
                                                class="form-check-input fixed-size-input"
                                                type="checkbox"
                                                id="condition"
                                                name="condition"
                                                required
                                                aria-label="{l s='Accept terms and conditions' mod='alsernetforms'}">
                                            <label class="form-check-label small" for="condition">
                                                {l s='I have read and expressly accept the conditions' mod='alsernetforms'}
                                                <a href="/politica-de-privacidad" target="_blank" rel="noopener noreferrer">{l s='Data Protection' mod='alsernetauth'}</a>
                                            </label>
                                        </div>
                                    </div>

                                    <div class="form-check mb-3">
                                        <div class="check">
                                            <input class="form-check-input fixed-size-input" type="checkbox" id="services" name="services">
                                            <label class="form-check-label small" for="services">
                                                {l s='I agree to receive information about other inventaries and services' mod='alsernetforms'}
                                                <a href="/politica-de-privacidad" target="_blank">{l s='Data Protection' mod='alsernetauth'}</a>
                                            </label>
                                        </div>
                                    </div>
                                {/if}

                                {* Mostrar formulario de envío solo si hay documentos faltantes *}
                                {if $missing_documents}
                                    <div class="mb-3">
                                        <div class="g-recaptcha" id="g-recaptcha-response-compromise" data-sitekey="6LcRY40nAAAAAEFYHjowIjVbySvS7OBev7_mZsSh" data-callback="onRecaptchaChange" data-expired-callback="onRecaptchaExpire" aria-label="{l s='Complete the reCAPTCHA verification' mod='alsernetforms'}"></div>
                                    </div>

                                    {* Validación del botón *}
                                    <div class="mb-3">
                                        <button
                                            type="submit"
                                            class="btn btn-primary w-100"
                                            id="submitButton"
                                            disabled
                                            aria-disabled="true" >
                                            {l s='Upload Document' mod='alsernetforms'}
                                        </button>
                                        <small class="text-muted d-block mt-2" id="submitValidationHelp" role="alert">
                                            <i class="fas fa-circle-info"></i>
                                            {l s='Please accept the terms and complete the reCAPTCHA to proceed' mod='alsernetforms'}
                                        </small>
                                    </div>
                                {/if}
                            </form>
                        </div>
                    </div>
                </div>

                {* Pantalla de confirmación *}
                <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-12 d-none" id="documentsConfirmation">
                    <div class="success-documents-container">
                        <i class="fas fa-circle-check" ></i>
                        <h1>{l s='Document successfully submitted' mod='alsernetforms'}</h1>
                        <p>{l s='We will now review your documents and begin processing your order immediately. Thank you for your trust!' mod='alsernetforms'}</p>
                        <a href="/" class="btn btn-primary mt-3">{l s='Go back to homepage' mod='alsernetforms'}</a>
                    </div>
                </div>

                {* Pantalla de validación pendiente *}
                <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-12 d-none" id="validationPending">
                    <div class="success-documents-container">
                        <i class="fas fa-hourglass-end" ></i>
                        <h1>{l s='Documents under review' mod='alsernetforms'}</h1>
                        <p>{l s='Your documents are being reviewed. We will contact you shortly with the results.' mod='alsernetforms'}</p>
                        <a href="/" class="btn btn-primary mt-3">{l s='Go back to homepage' mod='alsernetforms'}</a>
                    </div>
                </div>

                {* Pantalla de error *}
                <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-12 d-none" id="validationError">
                    <div class="success-documents-container">
                        <i class="fas fa-circle-xmark  ></i>
                        <h1>{l s='Unable to upload documents' mod='alsernetforms'}</h1>
                        <p>{l s='This document request has been finalized and cannot accept new uploads. Please contact support for assistance.' mod='alsernetforms'}</p>
                        <a href="/" class="btn btn-primary mt-3">{l s='Go back to homepage' mod='alsernetforms'}</a>
                    </div>
                </div>

            {else}
                <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-12">
                    <div class="success-documents-container">
                        <i class="fas fa-file-check" ></i>
                        <h1>{l s='Document already uploaded' mod='alsernetforms'}</h1>
                        <p>{l s='We have already received the required documents for this request. No further action is needed.' mod='alsernetforms'}</p>
                        <a href="/" class="btn btn-primary mt-3">{l s='Go back' mod='alsernetforms'}</a>
                    </div>
                </div>
            {/if}
        </div>
    </div>
</div>
