jQuery(document).ready(function($) {
    'use strict';

    const form = $('#cod-order-form');
    if (form.length === 0) {
        return;
    }

    const variations = form.data('variations');
    const quantityInput = $('#quantity');
    let selectedAttributes = {};

    function updatePrice() {
        const matchingVariation = findMatchingVariation();
        
        if (matchingVariation) {
            const price = parseFloat(matchingVariation.display_price);
            $('.product-price').text(price.toLocaleString('fr-DZ') + ' DA');
            $('#variation_id').val(matchingVariation.variation_id);
        } else {
            $('.product-price').text('');
            $('#variation_id').val('');
        }
        updateTotals();
    }

    function findMatchingVariation() {
        const numSelected = Object.keys(selectedAttributes).length;
        const numAttributes = $('.swatch-group').length;
        if (numSelected < numAttributes) {
            return null;
        }

        return variations.find(variation => {
            const attributes = variation.attributes;
            return Object.keys(selectedAttributes).every(key => {
                const formattedKey = 'attribute_' + key.toLowerCase();
                return attributes[formattedKey] === '' || attributes[formattedKey] === selectedAttributes[key];
            });
        });
    }

    $('.swatch-btn').on('click', function() {
        const $this = $(this);
        const attribute = $this.data('attribute');
        const value = $this.data('value');

        selectedAttributes[attribute] = value;
        $this.siblings().removeClass('active');
        $this.addClass('active');

        updatePrice();
    });

    function updateTotals() {
        const matchingVariation = findMatchingVariation();
        if (!matchingVariation) {
            $('#product-total').text('0 DA');
            $('#grand-total').text('0 DA');
            return;
        }

        const quantity = parseInt(quantityInput.val());
        const productTotal = parseFloat(matchingVariation.display_price) * quantity;
        const deliveryFeeText = $('#delivery-fee').text();
        const deliveryFee = deliveryFeeText ? parseFloat(deliveryFeeText.replace(/[^\d.-]/g, '')) : 0;
        const grandTotal = productTotal + deliveryFee;

        $('#product-total').text(productTotal.toLocaleString('fr-DZ') + ' DA');
        $('#grand-total').text(grandTotal.toLocaleString('fr-DZ') + ' DA');
    }

    $('input[name="delivery_type"]').on('change', function() {
        const deliveryType = $(this).val();
        let fee = 0;
        if (deliveryType === 'home') {
            fee = parseFloat(codDzAjax.home_delivery_cost) || 700;
        } else if (deliveryType === 'office') {
            fee = parseFloat(codDzAjax.office_delivery_cost) || 400;
        }
        $('#delivery-fee').text(fee.toLocaleString('fr-DZ') + ' DA');
        updateTotals();
    }).trigger('change');

    $('#qty-increase').on('click', function() {
        quantityInput.val(parseInt(quantityInput.val()) + 1);
        updateTotals();
    });

    $('#qty-decrease').on('click', function() {
        const currentVal = parseInt(quantityInput.val());
        if (currentVal > 1) {
            quantityInput.val(currentVal - 1);
        }
        updateTotals();
    });

    form.on('submit', function(e) {
        e.preventDefault();

        if (!$('#variation_id').val()) {
            alert('Veuillez sélectionner les options du produit (taille, couleur, etc.).');
            return;
        }

        const submitBtn = $(this).find('.submit-btn');
        submitBtn.prop('disabled', true).text('Traitement...');

        const formData = $(this).serialize() + '&action=cod_dz_create_order&nonce=' + codDzAjax.nonce;

        $.ajax({
            url: codDzAjax.ajaxUrl,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    if(response.data.redirect) {
                        window.location.href = response.data.redirect;
                    } else {
                        alert('Commande passée avec succès!');
                    }
                } else {
                    alert('Erreur: ' + response.data.message);
                }
            },
            error: function() {
                alert('Une erreur de connexion est survenue. Veuillez réessayer.');
            },
            complete: function() {
                submitBtn.prop('disabled', false).text('Commandez Maintenant');
            }
        });
    });

    // Set initial price on load if a variation is pre-selected
    if($('.swatch-btn.active').length > 0) {
       $('.swatch-btn.active').first().trigger('click');
    } else {
       // Attempt to select the first option for each attribute
       $('.swatch-group').each(function() {
          $(this).find('.swatch-btn').first().trigger('click');
       });
    }

    // Load Wilayas and Communes from JSON
    const stateSelect = $('select[name="customer_state"]');
    const citySelect = $('select[name="customer_city"]');

    if (stateSelect.length > 0) {
        $.getJSON('https://raw.githubusercontent.com/sellami-mohamed/algeria-cities/master/wilayas.json', function(data) {
            const sortedWilayas = data.sort((a, b) => a.name.localeCompare(b.name));
            $.each(sortedWilayas, function(key, entry) {
                stateSelect.append($('<option></option>').attr('value', entry.name).text(entry.name));
            });
        }).fail(function() {
            console.error("Could not load wilayas data.");
        });
    }

    stateSelect.on('change', function() {
        const selectedWilaya = $(this).val();
        citySelect.html('<option value="">votre commune</option>'); // Reset

        if (selectedWilaya) {
            $.getJSON('https://raw.githubusercontent.com/sellami-mohamed/algeria-cities/master/communes.json', function(data) {
                const filteredCommunes = data.filter(commune => commune.wilaya_name === selectedWilaya);
                const sortedCommunes = filteredCommunes.sort((a, b) => a.name.localeCompare(b.name));
                $.each(sortedCommunes, function(key, entry) {
                    citySelect.append($('<option></option>').attr('value', entry.name).text(entry.name));
                });
            }).fail(function() {
                console.error("Could not load communes data.");
            });
        }
    });

    function showToast(message, isError = false) {
        const toast = $('<div></div>').addClass('cod-toast').text(message);
        if (isError) {
            toast.addClass('error');
        }
        $('body').append(toast);
        setTimeout(() => toast.fadeIn(500), 100);
        setTimeout(() => {
            toast.fadeOut(500, () => toast.remove());
        }, 3000);
    }
});
