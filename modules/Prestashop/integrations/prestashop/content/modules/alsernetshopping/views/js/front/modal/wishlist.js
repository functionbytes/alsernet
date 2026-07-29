
$(document).ready(function(){

	$(document).on('click', '.shopping-modal .type-add-to-cart', function() {

		var id_wishlist_product = $(this).data('id-product');
		var id_wishlist = $(this).data('id-wishlist');
		var id_product = $(this).data('id-product');
		var id_product_attribute = $(this).data('id-product-attribute');

		var link = "/modules/alsernetshopping/controllers/routes.php?modalitie=wishlist&action=add";

		var self = this;

		$.ajax({
			cache: true,
			url: link,
			data: {
				id_product: id_product,
				id_wishlist_product: id_wishlist_product,
				id_wishlist: id_wishlist,
				id_product_attribute: id_product_attribute
			}
		}).done(function(result) {

			if(result.status == "success") {

				$('#shopping-modal').modal('hide');
				window.reloadCarts();
			}

		}).fail(function() {
			console.log("Error en la carga de datos.");
		});

	});

});