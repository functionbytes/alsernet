$(document).ready(function(){


		$('#redirect-to-login').on('click', function () {
			$('a[href="#checkout-guest-form"]').removeClass('active');
			$('a[href="#checkout-login-form"]').addClass('active');
			$('#checkout-guest-form').removeClass('active');
			$('#checkout-login-form').addClass('active');
		});

		$('#redirect-to-register').on('click', function () {
			$('a[href="#checkout-guest-form"]').addClass('active');
			$('a[href="#checkout-login-form"]').removeClass('active');
			$('#checkout-guest-form').addClass('active');
			$('#checkout-login-form').removeClass('active');
		});

        $('.ano-boletines').click(function () {
            var nueva_url = '';
            var url = window.location.href;
            url = url.split('?year');
            window.history.pushState(null, null, url['0'] + '?year=' + $(this).val());

            $.ajax({
                url: url['0'] + '?year=' + $(this).val(),
                type: "post",
                async: true,
                data: {
                    ajax: true,
                    action: "updateBoletines",
                    year: $(this).val(),
                    id_deporte: $('#id_deporte').val(),
                },
                success: function (resp) {

                    //console.log(resp);
                    $("#boletines").replaceWith(resp.boletines);
                    //window.scrollTo(0, 0);
                    $("html, body").animate({scrollTop: 0}, 1500);

                },
            });
        });



	// Formulario 16 - Compromiso Álvarez mejor precio
	if ($('.inputs-hidden-referrer-source').length) {
		$(".inputs-hidden-product").html($(".inputs-hidden-referrer-source").html());
	}

	if ($('#blockcart-modal').length > 0) {
		$('#blockcart-modal').modal('show')
	}

	if (typeof mostrarmodalbloqueos !== 'undefined' && mostrarmodalbloqueos) {
		$("#bloqueos-modal").modal('show');
	}
	if (typeof showOrderInvoiceAddress !== 'undefined' && showOrderInvoiceAddress) {
		$('#need-invoice.addresses').click();
		$('[name=misma_difer]#diferente').prop('checked', true);
		$('input[name="misma_difer"]').click();
	}

	if ($('#amazzing_filter').length < 1 && $('.compact-toggle').length > 0) {
		$('.filter-btn').hide();
		$('.compact-toggle').remove();
		if ($(window).width() <= 575) {
			$('.sort-order-wrapper').removeClass('col-xs-6').removeClass('col-sp-6');
			$('.sort-order-wrapper').addClass('col-xs-12').addClass('col-sp-12');
		}
	}

	if ($('body#checkout').length > 0) {

		$('#need-invoice').click(function(e) {
			if ($(this).is(':checked')) {
				$('#use_same_address').attr('checked', false);
			}else{
				$('#use_same_address').attr('checked', true);
			}
		});

		if (!$('#need-invoice').is(':checked')) {
			$('#field-vat_number').attr('required',false);
			$('label[for="field-vat_number"]').removeClass('required');
			$('#use_same_address').attr('checked', true);
			$('.radios-direccion-factura').css('display', 'none');
			$('#invoice-addresses').css('display', 'none');
			$('#invoice-address').css('display', 'none');
		}else{
			$('#field-vat_number').attr('required',true);
			$('label[for="field-vat_number"]').addClass('required');
			$('#use_same_address').attr('checked', false);
			$('.radios-direccion-factura').css('display', 'block');
			$('#invoice-addresses').css('display', 'block');
			$('#invoice-address').css('display', 'none');
		}

		$('#need-invoice.addresses').click(function(e) {
			if (!$(this).is(':checked')) {
				$('#field-vat_number').attr('required',false);
				$('label[for="field-vat_number"]').removeClass('required');
				$('#use_same_address').attr('checked', true);
				$('.radios-direccion-factura').css('display', 'none');
				$('#invoice-addresses').css('display', 'none');
				$('#invoice-address').css('display', 'none');
			}else{
				$('#field-vat_number').attr('required',true);
				$('label[for="field-vat_number"]').addClass('required');
				$('#use_same_address').attr('checked', false);
				$('.radios-direccion-factura').css('display', 'block');
				$('#invoice-addresses').css('display', 'block');
				$('#invoice-address').css('display', 'none');
			}
		});

		$('input[name="misma_difer"]').click(function(e) {
			if ($(this).val() == 0) {//misma direccion
				$('#invoice-addresses.address-selector').css('display', 'block');
				$('.nueva-invoice').css('display', 'none');
				//$('button[name="confirm-addresses"]').css('display', 'block');
				$('button#buttonaddress').css('display', 'block');
			}else{ //dirección diferente
				$('#invoice-addresses.address-selector').css('display', 'none');
				$('.nueva-invoice').css('display', 'block');
				//$('button[name="confirm-addresses"]').css('display', 'none');
				$('button#buttonaddress').css('display', 'none');
			}
		});
	}

	$('#deportes').on('change', function(e) {
		if($('#manufacturerdeporte').length){
			window.location.href = this.value;
		}
	});

	$('#selector-orden').on('change', function(e) {
		$('#js-product-list').load(this.value + ' .products');
	});

	$("a.collapsed").parents('.panel-title').css("background-color", "#f5f6f8");
	$("a.collapsed").css("color", "#9B9B9B");

	$('.panel-title a').click(function(e) {
		if ($(this).hasClass('collapsed')) {
			$(this).parents('.panel-title').css("background-color", "#90BB13");
			$(this).css("color", "#FFFFFF");
		}else{
			$(this).parents('.panel-title').css("background-color", "#f5f6f8");
			$(this).css("color", "#9B9B9B");
		}
	});


	$('form').on('submit',function(e) {
		var id_parent_form = "#"+$(this).parent().attr('id');
		if (id_parent_form != '#undefined') {
			/* JLP - 03/12/2023 - comprobar que se han rellenado todos los campos requeridos antes de enviar */
			var required_data = true;
			$(id_parent_form + ' input[aria-required="true"]').each(function () {
				if ($(this).val() == '') {
					required_data = false;
				}
			});
			$(id_parent_form + ' textarea[aria-required="true"]').each(function () {
				if ($(this).val() == '') {
					required_data = false;
				}
			});
			if ($(id_parent_form + ' input[type="captcha"]').length) {
				if ($(id_parent_form + ' input[type="captcha"]').val() == '') {
					required_data = false;
				}
			}
			/* JLP - FIN */
			if (required_data && $('input[name="acceptance-0"]').length > 0) {
				var form_name = '';
				var nombre_form = '';
				if ($('body').attr('id') == 'product') {
					form_name += ' '+$('h1.product-detail-name').text();
				}else{
					nombre_form = $('body').attr('id');
				}
				if ($(this).data('formulario_nombre')) {
					form_name += ' '+$(this).data('formulario_nombre');
				}else{
					form_name += '';
				}
				var formulario_nombre = nombre_form+form_name;
				var nombre = '';
				var apellidos = '';
				var email = '';
				var fnacimiento = '';
				var check_rgpd = 0;
				var check_terceros = 0;
				var deportes = '';
				var necesita_verificacion = '';

				if ($(id_parent_form+' #nombre').length && $(id_parent_form+' #nombre').val()) {
					nombre = $(id_parent_form+' #nombre').val();
				}
				if ($(id_parent_form+' input[name="firstname"]').length && $(id_parent_form+' input[name="firstname"]').val()) {
					nombre = $(id_parent_form+' input[name="firstname"]').val();
				}
				if ($(id_parent_form+' #nombre_newsletter').length && $(id_parent_form+' #nombre_newsletter').val()) {
					nombre = $(id_parent_form+' #nombre_newsletter').val();
				}
				if ($(id_parent_form+' input[name="name"]').length && $(id_parent_form+' input[name="name"]').val()) {
					nombre = $(id_parent_form+' input[name="name"]').val();
				}
				if ($(id_parent_form+' input[name="txtNombre"]').length && $(id_parent_form+' input[name="txtNombre"]').val()) {
					nombre = $(id_parent_form+' input[name="txtNombre"]').val();
				}
				if ($(id_parent_form+' #apellidos').length && $(id_parent_form+' #apellidos').val()) {
					apellidos = $(id_parent_form+' #apellidos').val();
				}
				if ($(id_parent_form+' input[name="lastname"]').length && $(id_parent_form+' input[name="lastname"]').val()) {
					apellidos = $(id_parent_form+' input[name="lastname"]').val();
				}
				if ($(id_parent_form+' input[name="apellido_1"]').length && $(id_parent_form+' input[name="apellido_1"]').val()) {
					apellidos = $(id_parent_form+' input[name="apellido_1"]').val();
				}
				if ($(id_parent_form+' input[name="txtApellidos"]').length && $(id_parent_form+' input[name="txtApellidos"]').val()) {
					apellidos = $(id_parent_form+' input[name="txtApellidos"]').val();
				}
				if ($(id_parent_form+' #apellidos_newsletter').length && $(id_parent_form+' #apellidos_newsletter').val()) {
					apellidos = $(id_parent_form+' #apellidos_newsletter').val();
				}
				if ($(id_parent_form+' #email').length && $(id_parent_form+' #email').val()) {
					email = $(id_parent_form+' #email').val();
				}
				if ($(id_parent_form+' input[name="email"]').length && $(id_parent_form+' input[name="email"]').val()) {
					email = $(id_parent_form+' input[name="email"]').val();
				}
				if ($(id_parent_form+' input[name="email_tcn"]').length && $(id_parent_form+' input[name="email_tcn"]').val()) {
					email = $(id_parent_form+' input[name="email_tcn"]').val();
				}
				if ($(id_parent_form+' input[name="txtEmail"]').length && $(id_parent_form+' input[name="txtEmail"]').val()) {
					email = $(id_parent_form+' input[name="txtEmail"]').val();
				}
				if ($(id_parent_form+' #email_newsletter').length && $(id_parent_form+' #email_newsletter').val()) {
					email = $(id_parent_form+' #email_newsletter').val();
				}
				if ($(id_parent_form+' input[name="email_dg"]').length && $(id_parent_form+' input[name="email_dg"]').val()) {
					email = $(id_parent_form+' input[name="email_dg"]').val();
				}
				if ($(id_parent_form+' input[name="your-email"]').length && $(id_parent_form+' input[name="your-email"]').val()) {
					email = $(id_parent_form+' input[name="your-email"]').val();
				}
				if ($(id_parent_form+' input[name="email_newsletter_unsubscribe_form1"]').length && $(id_parent_form+' input[name="email_newsletter_unsubscribe_form1"]').val()) {
					email = $(id_parent_form+' input[name="email_newsletter_unsubscribe_form1"]').val();
				}
				if ($(id_parent_form+' input[name="email_newsletter_unsubscribe_form2"]').length && $(id_parent_form+' input[name="email_newsletter_unsubscribe_form2"]').val()) {
					email = $(id_parent_form+' input[name="email_newsletter_unsubscribe_form2"]').val();
				}
				if ($(id_parent_form+' input[name="email_newsletter_unsubscribe_form3"]').length && $(id_parent_form+' input[name="email_newsletter_unsubscribe_form3"]').val()) {
					email = $(id_parent_form+' input[name="email_newsletter_unsubscribe_form3"]').val();
				}
				if ($(id_parent_form+' input[name="birthday"]').length && $(id_parent_form+' input[name="birthday"]').val()) {
					fnacimiento = $(id_parent_form+' input[name="birthday"]').val();
				}
				if ($(id_parent_form+' input.psgdpr').length) {
					if ($(id_parent_form+' input.psgdpr').is(':checked')) {
						check_rgpd = 1;
					}else{
						check_rgpd = 0;
					}
				}
				if ($(id_parent_form+' input[name="psgdpr"]').length) {
					if ($(id_parent_form+' input[name="psgdpr"]').is(':checked')) {
						check_rgpd = 1;
					}else{
						check_rgpd = 0;
					}
				}
				if ($(id_parent_form+' input[name="psgdpr_consent_checkbox"]').length) {
					if ($(id_parent_form+' input[name="psgdpr_consent_checkbox"]').is(':checked')) {
						check_rgpd = 1;
					}else{
						check_rgpd = 0;
					}
				}
				if ($(id_parent_form+' input[name="check-lopd"]').length) {
					if ($(id_parent_form+' input[name="check-lopd"]').is(':checked')) {
						check_rgpd = 1;
					}else{
						check_rgpd = 0;
					}
				}
				if ($(id_parent_form+' input[name="acceptance-234"]').length) {
					if ($(id_parent_form+' input[name="acceptance-234"]').is(':checked')) {
						check_rgpd = 1;
					}else{
						check_rgpd = 0;
					}
				}
				if ($(id_parent_form+' input[name="acceptance-859"]').length) {
					if ($(id_parent_form+' input[name="acceptance-859"]').is(':checked')) {
						check_rgpd = 1;
					}else{
						check_rgpd = 0;
					}
				}
				if ($(id_parent_form+' input[name="acceptance-form1"]').length) {
					if ($(id_parent_form+' input[name="acceptance-form1"]').is(':checked')) {
						check_rgpd = 1;
					}else{
						check_rgpd = 0;
					}
				}
				if ($(id_parent_form+' input[name="acceptance-0"]').length) {
					if ($(id_parent_form+' input[name="acceptance-0"]').is(':checked')) {
						check_terceros = 1;
					}else{
						check_terceros = 0;
					}
				}

				if ($(id_parent_form+' input[name="necesita_verificacion"]').length) {
					necesita_verificacion = "1";
				}

				/*if ($('.deportes').length) {
					if ($('.deportes input[type="checkbox"]').is(':checked')) {
						$(this).each(function() {
					  		if ($(this).find("input[checked='checked'").size() == 1) {
									deportes += ','+$('.deportes input[type="checkbox"]').val();
					  		}
					  });
						deportes = $('.deportes input[type="checkbox"]').val();
					}else{
						deportes = 0;
					}
				}*/

				if ($(id_parent_form+' .deportes').length) {
					$(id_parent_form+' .deportes input[type="checkbox"]').each(function() {
						if ($(this).is(':checked')) {
							deportes += ','+$(this).val();
						}
					});
				}
				if ($(id_parent_form+' .deportes-alta-susc1').length) {
					$(id_parent_form+' .deportes-alta-susc1 input[type="checkbox"]').each(function() {
						if ($(this).is(':checked')) {
							deportes += ','+$(this).val();
						}
					});
				}
				if ($(id_parent_form+' .newsletter_unsubscribe_form2_deportes').length) {
					$(id_parent_form+' .newsletter_unsubscribe_form2_deportes input[type="checkbox"]').each(function() {
						if ($(this).is(':checked')) {
							deportes += ','+$(this).val();
						}
					});
				}


				//Aqui ajax para gestionar datos
				$.ajax({
					url: '/modules/addis_accep_prd/controllers/front/gestiona_rgpd.php',
					type: "post",
					async: true,
					data: {
						formulario_nombre: formulario_nombre,
						nombre: nombre,
						apellidos: apellidos,
						email: email,
						fnacimiento: fnacimiento,
						deportes: deportes,
						check_rgpd: check_rgpd,
						check_terceros: check_terceros,
						necesita_verificacion: necesita_verificacion,
					},
					success: function (resp) {

					},
				});
			}
		}
	});

	// if ($('.product-variants').size() > 0) {

	// 	toggleVisibilityVariantItem($('.product-variants-item').eq(0));

	// 	$('.product-variants').show();

		// if (!showbuttonaddbycomb()){
		// 	$('button.add-to-cart').attr('disabled', 'disabled');
		// }
	// }

	if ($(".inputs-formulario-devols").length > 0){
		var label = "";
		$("#motivo_devol").change(function(){
			if ($(this).prop('selectedIndex') == 1){
				if (prestashop.language.iso_code == "es"){
					label = '¿Otra talla?, ¿Otro color?... Por favor, indícanos el producto que deseas recibir a cambio.';
				}else if (prestashop.language.iso_code == "en"){
					label = 'Another size? Another color?... Please, tell us the product you want to receive in exchange.';
				}else if (prestashop.language.iso_code == "fr"){
					label = 'Autre taille ?Autre couleur ?... Merci de nous indiquer le produit que vous souhaitez recevoir en échange.';
				}else if (prestashop.language.iso_code == "pt"){
					label = 'Outro tamanho? Outra cor?... Por favor, diga-nos o produto que deseja receber em troca.';
				}else if (prestashop.language.iso_code == "de"){
					label = 'Eine andere Größe? Eine andere Farbe?... Bitte teilen Sie uns mit, welches Produkt Sie im Austausch erhalten möchten.';
				}else if (prestashop.language.iso_code == "it"){
					abel = '¿Otra talla?, ¿Otro color?... Por favor, indícanos el producto que deseas recibir a cambio.';
				}
				$("#label-comentarios span.etiqueta").html(label);
			}else if ($(this).prop('selectedIndex') == 2){
				if (prestashop.language.iso_code == "es"){
					label = 'Upsss, ¿Nos hemos equivocado?, Disculpa el error. Por favor indícanos aquí tus comentarios.';
				}else if (prestashop.language.iso_code == "en"){
					label = 'Whoops, were we wrong?, Sorry for the mistake. Please let us know your comments here.';
				}else if (prestashop.language.iso_code == "fr"){
					label = 'Oups, avions-nous tort?, Désolé pour l\'erreur. Veuillez nous faire part de vos commentaires ici.';
				}else if (prestashop.language.iso_code == "pt"){
					label = 'Opa, estávamos errados?, Desculpe pelo erro. Por favor, deixe-nos saber seus comentários aqui.';
				}else if (prestashop.language.iso_code == "de"){
					label = 'Hoppla, haben wir uns geirrt? Entschuldigung für den Fehler. Bitte teilen Sie uns hier Ihre Kommentare mit.';
				}else if (prestashop.language.iso_code == "it"){
					label = 'Upsss, ¿Nos hemos equivocado?, Disculpa el error. Por favor indícanos aquí tus comentarios.';
				}
				$("#label-comentarios span.etiqueta").html(label);
			}else if ($(this).prop('selectedIndex') == 3){
				if (prestashop.language.iso_code == "es"){
					label = '¿El producto ha sufrido algún desperfecto? Por favor indícanos aquí tus comentarios.';
				}else if (prestashop.language.iso_code == "en"){
					label = 'Has the product suffered any damage? Please let us know your comments here.';
				}else if (prestashop.language.iso_code == "fr"){
					label = 'Le produit a-t-il subi des dommages ? Veuillez nous faire part de vos commentaires ici.';
				}else if (prestashop.language.iso_code == "pt"){
					label = 'O produto sofreu algum dano? Por favor, deixe-nos saber seus comentários aqui.';
				}else if (prestashop.language.iso_code == "de"){
					label = 'Hat das Produkt Schäden erlitten? Bitte teilen Sie uns hier Ihre Kommentare mit.';
				}else if (prestashop.language.iso_code == "it"){
					label = '¿El producto ha sufrido algún desperfecto? Por favor indícanos aquí tus comentarios.';
				}
				$("#label-comentarios span.etiqueta").html(label);
			}else if ($(this).prop('selectedIndex') == 4){
				if (prestashop.language.iso_code == "es"){
					label = 'Por favor, indícanos aquí tus comentarios y lo que deseas recibir a cambio';
				}else if (prestashop.language.iso_code == "en"){
					label = 'Please, tell us here your comments and what you want to receive in return';
				}else if (prestashop.language.iso_code == "fr"){
					label = 'S\'il vous plaît, dites-nous ici vos commentaires et ce que vous voulez recevoir en retour';
				}else if (prestashop.language.iso_code == "pt"){
					label = 'Por favor, conte-nos aqui seus comentários e o que você deseja receber em troca';
				}else if (prestashop.language.iso_code == "de"){
					label = 'Bitte teilen Sie uns hier Ihre Kommentare und Ihre gewünschten Gegenleistungen mit';
				}else if (prestashop.language.iso_code == "it"){
					label = 'Por favor, indícanos aquí tus comentarios y lo que deseas recibir a cambio';
				}
				$("#label-comentarios span.etiqueta").html(label);
			}else{
				if (prestashop.language.iso_code == "es"){
					label = 'Comentarios';
				}else if (prestashop.language.iso_code == "en"){
					label = 'Comments';
				}else if (prestashop.language.iso_code == "fr"){
					label = 'Commentaires';
				}else if (prestashop.language.iso_code == "pt"){
					label = 'Comentários';
				}else if (prestashop.language.iso_code == "de"){
					label = 'Kommentare';
				}else if (prestashop.language.iso_code == "it"){
					label = 'Comentarios';
				}
				$("#label-comentarios span.etiqueta").html(label);
			}

		});
	}

	prestashop.on('updatedProduct', function (e) {
		if (e.mostrar_envio_48_horas === true){
			$("#entrega_48_horas").removeClass("dis-none");
		}else{
			$("#entrega_48_horas").addClass("dis-none");
		}
	});


	if(!document.cookie.includes("terminosAceptados=")) {
		document.cookie = "terminosAceptados=false; expires=FechaExpiracion; path=/";
	}else if(document.cookie.includes("terminosAceptados=")){
		if(document.cookie.includes("_ga=")){
			aceptarTerminos(true);
		}
	}


});


document.addEventListener('click', function(event) {
	if(event.target.classList.toString() == 'lgcookieslaw-button lgcookieslaw-accept-button'){
		aceptarTerminos(true);
	}

	if(event.target.classList.toString() == 'lgcookieslaw-button lgcookieslaw-partial-accept-button'){
		aceptarTerminos(true);
	}

	if(event.target.classList.toString() == 'lgcookieslaw-button lgcookieslaw-reject-button'){
		aceptarTerminos(false);
	}
});

function aceptarTerminos(accion) {
	if(accion){
		var fechaExpiracion = new Date();
		fechaExpiracion.setDate(fechaExpiracion.getDate() + 20);
		document.cookie = "terminosAceptados=true; expires=" + fechaExpiracion.toUTCString() + "; path=/";
        dataLayer.push({event:'ad_personalization_granted'});
	}else{
		document.cookie = "terminosAceptados=false; expires=FechaExpiracion; path=/";
	}
}

function ir_a_letra(letra) {
	$('html,body').animate({
			scrollTop: $("#"+letra).offset().top},
		'slow');
}


function toggleVisibilityVariantItem(obj) {

	if (obj.hasClass('product-variants-item')) {

		var count = obj.find('input').size();

		if (count > 0) {
			if (count == 1) {

				obj.find('input').prop('checked', true);
				obj.find('input').attr('checked', 'checked');

				objNext = obj.next();

				if (objNext) {
					toggleVisibilityVariantItem(objNext);
				}

			}

			obj.show();
		}
	}
}

function showbuttonaddbycomb(){

	if (!$('.fitting').length) {

		var ngroups = $('.product-variants-item').size();
		var ic = 0;

		$('.product-variants-item').each(function() {
			if ($(this).find("input[checked='checked'").size() == 1) {
				ic+=1;
			}
		});

		return (ngroups==ic);

	}else{
		return true;
	}

}


if (typeof prestashop !== 'undefined') {
	var that;

	prestashop.on('updatedProduct', function (event) {


		if ($("#referenciavariante").size()>0){
			if ($("#referenciavariante").val()!=""){
				$(".product-reference span").html($("#referenciavariante").val());
			}
		}

		if (that) {


			toggleVisibilityVariantItem($(".product-variants input[value='"+that+"']").parent().parent().parent().parent().next());

			$('.product-variants').show();
		}
	});

	prestashop.on('updateProduct', function (event) {
		if (event && event.reason) {
			if (event.eventType == 'updatedProductCombination') {
				that=$(event.event.target).val();
			}
		}
	});


}


function cartCompleteOrder() {
	$([document.documentElement, document.body]).animate({
		scrollTop: $('.cart-bottom-similar').offset().top - 75
	}, 500);
}



function scrollToSection(selector, seg) {
	var segWait = seg || 500;
	$([document.documentElement, document.body]).animate({
		scrollTop: $(selector).offset().top
	}, segWait);
}

$(window).load(function () {
	if ($('#checkout-payment-step.-current').length > 0 && $(window).width() <= 780) {
		scrollToSection('#checkout-payment-step', 1500);
	}
});


/*

if ($.fn.rating) $('input.star').rating();
if ($.fn.rating) $('.auto-submit-star').rating();
if ($.fn.fancybox) $('.open-comment-form').fancybox({'hideOnContentClick': false});

// lgcomments
if (typeof $.fancybox !== 'undefined') {
	$(".lgcomment_button")
		.fancybox({
			'href': '#form_review_popup'
			, 'width': 400
			, 'height': 'auto'
			, 'autoSize': false
			, 'tpl': {
				closeBtn: ''
			}
		});
	$("select#lg_score")
		.on("change", function () {
			changeStars($(this)
				.val());
		});
	$(document).off('click', '#product #submit_review');
	$('#submit_review')
		.on('click', function () {
			if (checkFields()) {
				sendProductReview(review_controller_link);
			}
		});
}

if (typeof init_comments === 'function' && typeof lgcomments_products_default_display !== 'undefined') {
	init_comments(lgcomments_products_default_display);
}


if (typeof ets_captcha_load == 'function') {
	ets_captcha_load(document.getElementsByTagName('form'));
}

$('.hi-cmb-popup-opener').on('click', function() {
	var cmb_position = $(this).attr('data-position');
	$.magnificPopup.open({
		items: {
			src: '.hi-cmb-popup-' + cmb_position
		},
		type: 'inline',
		midClick: true,
		removalDelay: 0,
		mainClass: 'mfp-fade',
		closeOnBgClick: true,
		showCloseBtn: true,
		enableEscapeKey: true,
		modal: false
	}, 0);

	return false;
});

$('.cmb-call-me .cmb-button').click(function(){
	var $this = $(this);
	var $form = $this.closest('form');
	$.ajax({
		type:'POST',
		dataType: 'JSON',
		url: cmb.ajax_url,
		data:{
			ajax: true,
			action: 'callRequest',
			secure_key: cmb.secure_key,
			first_name: $form.find('[name="cmb_fname"]').val(),
			last_name: $form.find('[name="cmb_lname"]').val(),
			phone: $form.find('[name="cmb_phone"]').val(),
			from: $form.find('[name="cmb_from"]').val(),
			to: $form.find('[name="cmb_to"]').val(),
			message: $form.find('[name="cmb_message"]').val(),
			recaptcha: $form.find('[name="g-recaptcha-response"]').val()

		},
		beforeSend: function(){
			$form.find('.cmb-button span').hide();
			$form.find('.cmb-button img').show();
		},
		success: function(response){
			$form.find('.cmb-errors').hide();
			$form.find('.cmb-button span').show();
			$form.find('.cmb-button img').hide();
			if (response.hasError){
				if (cmb.enable_recaptcha) {
					grecaptcha.reset();
				}
				if(response.error.recaptcha){
					$form.find('.cmb_recaptcha_info > span').html(response.error.recaptcha);
					$form.find('.cmb_recaptcha_info').show();
				}
				if(response.error.phone){
					$form.find('.cmb_phone_info > span').html(response.error.phone);
					$form.find('.cmb_phone_info').show();
				}
				if(response.error.first_name){
					$form.find('.cmb_fname_info > span').html(response.error.first_name);
					$form.find('.cmb_fname_info').show();
				}
				if(response.error.last_name){
					$form.find('.cmb_lname_info > span').html(response.error.last_name);
					$form.find('.cmb_lname_info').show();
				}
				if(response.error.time){
					$form.find('.cmb_time_info > span').html(response.error.time);
					$form.find('.cmb_time_info').show();
				}
				if(response.error.message){
					$form.find('.cmb_message_info > span').html(response.error.message);
					$form.find('.cmb_message_info').show();
				}
			} else {
				$form.find('.cmb-errors').hide();

				var $html = ''+response.success+'';
				$form.find('.cmb-footer').html($html);
				$('[name="cmb_fname"], [name="cmb_lname"], [name="cmb_phone"], [name="cmb_message"], [name="cmb_from"], [name="cmb_to"]').val('');
				setTimeout(function(){
					$.magnificPopup.close();
				}, 3000);
			}
		}
	});
	return false;
});

// retail rocket
// retailrocket.markup.render();

*/