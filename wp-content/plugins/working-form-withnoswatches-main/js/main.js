(function($) {
    'use strict';

    function getWebsiteLanguage() {
        const htmlLang = document.documentElement.lang || '';
        if (htmlLang.startsWith('ar')) return 'ar';
        return 'fr';
    }

    function getTranslation(key, lang) {
        const translations = {
            'choose_wilaya_fr': 'Sélectionnez une province',
            'choose_commune_fr': 'Sélectionnez une commune',
            'free_fr': 'Gratuit',
            'processing_fr': 'Traitement en cours...',
            'error_name_fr': 'Veuillez entrer le nom complet',
            'error_phone_fr': 'Veuillez entrer le numéro de téléphone',
            'error_wilaya_fr': 'Veuillez sélectionner une province',
            'error_commune_fr': 'Veuillez sélectionner une commune',
            'error_connection_fr': 'Erreur de connexion. Réessayez.',
            'success_fr': 'Commande créée avec succès!',

            'choose_wilaya_ar': 'اختر ولاية',
            'choose_commune_ar': 'اختر البلدية',
            'free_ar': 'مجاني',
            'processing_ar': 'جاري المعالجة...',
            'error_name_ar': 'الرجاء إدخال الاسم الكامل',
            'error_phone_ar': 'الرجاء إدخال رقم الهاتف',
            'error_wilaya_ar': 'الرجاء اختيار الولاية',
            'error_commune_ar': 'الرجاء اختيار البلدية',
            'error_connection_ar': 'حدث خطأ في الاتصال. حاول مرة أخرى.',
            'success_ar': 'تم إنشاء الطلب بنجاح!'
        };

        const key_with_lang = key + '_' + lang;
        return translations[key_with_lang] || translations[key + '_fr'] || key;
    }

    function formatPrice(price, lang) {
        if (typeof price !== 'number' || isNaN(price) || price === 0) {
            return null;
        }

        let formatted;
        if (lang === 'ar') {
            formatted = price.toLocaleString('ar-SA', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
            return `د.ج ${formatted}`;
        } else {
            formatted = price.toLocaleString('fr-FR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
            return `${formatted} د.ج`;
        }
    }

    function initializeGodForm(formId, dataObject, suffix) {
        const lang = getWebsiteLanguage();

        const data = dataObject;
        const wilayas = data.wilayas || [];
        const communes = data.communes || {};
        const shippingCosts = data.shipping_costs || {};

        const $form = $(formId);
        if ($form.length === 0) return;

        const $wilayaSelect = $form.find(`#customer_wilaya${suffix}`);
        const $communeSelect = $form.find(`#customer_commune${suffix}`);
        const $shippingCost = $form.find(`#shipping-cost${suffix}`);
        const $totalCost = $form.find(`#total-cost${suffix}`);
        const $productPrice = $form.find(`#product_price${suffix}`);

        if ($wilayaSelect.length === 0 || $productPrice.length === 0) return;

        $wilayaSelect.find('option:not(:first)').remove();
        $wilayaSelect.find('option:first').text(getTranslation('choose_wilaya', lang));

        wilayas.forEach(function(wilaya, index) {
            if (wilaya && wilaya.name) {
                const id = wilaya.id || (index + 1);
                $wilayaSelect.append(
                    $('<option></option>')
                        .attr('value', wilaya.name)
                        .text(id + ' - ' + wilaya.name)
                );
            }
        });

        $wilayaSelect.on('change', function() {
            const selectedWilaya = $(this).val();
            $communeSelect.html(`<option value="">${getTranslation('choose_commune', lang)}</option>`);
            $communeSelect.prop('disabled', true);

            if (selectedWilaya && communes[selectedWilaya]) {
                const wilayaCommunes = communes[selectedWilaya];
                if (Array.isArray(wilayaCommunes) && wilayaCommunes.length > 0) {
                    wilayaCommunes.forEach(function(commune) {
                        if (commune) {
                            $communeSelect.append(
                                $('<option></option>')
                                    .attr('value', commune)
                                    .text(commune)
                            );
                        }
                    });
                    $communeSelect.prop('disabled', false);
                }
            }

            updatePrice();
        });

        $communeSelect.on('change', function() {
            updatePrice();
        });

        $form.find('input[name="shipping_method"]').on('change', function() {
            updatePrice();
        });

        function updatePrice() {
            const productPrice = parseFloat($productPrice.val()) || 0;
            const selectedWilaya = $wilayaSelect.val();
            const shippingMethod = $form.find('input[name="shipping_method"]:checked').val();

            let shippingPrice = 0;

            if (selectedWilaya && shippingMethod && shippingCosts[selectedWilaya]) {
                if (shippingCosts[selectedWilaya][shippingMethod] !== undefined) {
                    shippingPrice = parseFloat(shippingCosts[selectedWilaya][shippingMethod]) || 0;
                }
            }

            const total = productPrice + shippingPrice;

            if ($shippingCost.length) {
                if (shippingPrice > 0) {
                    $shippingCost.html(formatPrice(shippingPrice, lang));
                } else {
                    $shippingCost.html('اختر الولاية');
                }
            }

            if ($totalCost.length) {
                $totalCost.html(formatPrice(total, lang));
            }
        }

        $form.on('submit', function(e) {
            e.preventDefault();
            const $submitBtn = $(this).find('.god-submit');
            const originalText = $submitBtn.text();

            const name = $form.find(`#customer_name${suffix}`).val().trim();
            const phone = $form.find(`#customer_phone${suffix}`).val().trim();
            const wilaya = $wilayaSelect.val();
            const commune = $communeSelect.val();

            if (!name) {
                alert(getTranslation('error_name', lang));
                return false;
            }
            if (!phone) {
                alert(getTranslation('error_phone', lang));
                return false;
            }
            if (!wilaya) {
                alert(getTranslation('error_wilaya', lang));
                return false;
            }
            if (!commune) {
                alert(getTranslation('error_commune', lang));
                return false;
            }

            $submitBtn.prop('disabled', true).text(getTranslation('processing', lang));

            $.ajax({
                url: data.ajax_url,
                type: 'POST',
                data: $(this).serialize(),
                timeout: 30000,
                success: function(response) {
                    if (response.success) {
                        alert(getTranslation('success', lang));
                        window.location.href = response.data.redirect_url;
                    } else {
                        const message = response.data.message || getTranslation('error_connection', lang);
                        alert(message);
                        $submitBtn.prop('disabled', false).text(originalText);
                    }
                },
                error: function() {
                    alert(getTranslation('error_connection', lang));
                    $submitBtn.prop('disabled', false).text(originalText);
                }
            });

            return false;
        });

        updatePrice();
    }

    $(document).ready(function() {
        function tryInitialize(formId, dataVarName, suffix) {
            const data = window[dataVarName];
            if (typeof data !== 'undefined') {
                initializeGodForm(formId, data, suffix);
            } else {
                setTimeout(() => tryInitialize(formId, dataVarName, suffix), 100);
            }
        }

        tryInitialize('#god-order-form-desktop', 'custom_product_ajax', '_desktop');
        tryInitialize('#god-order-form-phone', 'custom_product_ajax_phone', '_phone');
    });

})(jQuery);
