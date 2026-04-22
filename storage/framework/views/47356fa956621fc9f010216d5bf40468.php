<div class="ctf-section">
    <div class="ctf-map-bg">
        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3077.750340444402!2d-9.0202247!3d39.520124100000004!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0xd18a5e735f923a7%3A0x3646712fc13b5572!2sCaixilharia%20PVC%20Blanco!5e0!3m2!1ses!2ses!4v1774563950052!5m2!1ses!2ses" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
    </div>
    <div class="container ctf-overlay-container">
        <div class="row justify-content-end">
            <div class="col-lg-6 col-xl-6">
                <div class="ctf-card wow fadeInUp" data-wow-delay="0.1s">
                    <div class="ctf-badge wow fadeInUp" data-wow-delay="0.2s">
                        <span class="ctf-dot"></span>CONTÁCTENOS
                    </div>
                    <h2 class="ctf-title wow fadeInUp" data-wow-delay="0.3s">Envíenos su consulta</h2>
                    <p class="ctf-subtitle wow fadeInUp" data-wow-delay="0.35s">Respondemos en menos de 24h &middot; Visita técnica gratuita &middot; Sin compromiso</p>
                    <form id="formContacts">
                        <?php echo csrf_field(); ?>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="ctf-label" for="names">Nombre <span class="ctf-required">*</span></label>
                                <input type="text" name="names" id="names" class="ctf-input" placeholder="Ej. João Silva">
                            </div>
                            <div class="col-md-6">
                                <label class="ctf-label" for="cellphone">Teléfono <span class="ctf-required">*</span></label>
                                <input type="text" name="cellphone" id="cellphone" class="ctf-input" placeholder="Ej. 913 893 833">
                            </div>
                            <div class="col-12">
                                <label class="ctf-label" for="email">Correo electrónico <span class="ctf-required">*</span></label>
                                <input type="email" name="email" id="email" class="ctf-input" placeholder="Ej. info@caixilhariablanco.pt">
                            </div>
                            <div class="col-12">
                                <label class="ctf-label" for="message">¿En qué podemos ayudarle?</label>
                                <textarea name="message" id="message" class="ctf-input ctf-textarea" placeholder="Cuéntenos su proyecto: tipo de producto, localidad, dimensiones aproximadas..."></textarea>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="ctf-btn">Enviar mensaje</button>
                            </div>
                            <div class="col-12 text-center">
                                <p class="ctf-trust"><i class="fa-solid fa-lock ctf-trust-icon me-1"></i> Sus datos están protegidos &middot; No compartimos información</p>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div><?php /**PATH /Users/developerts/Herd/system/storage/framework/views/762d7b74a61e9b55f6903d0be7b84756.blade.php ENDPATH**/ ?>