/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
/*global define*/
define(
    [
        'Magento_Payment/js/view/payment/cc-form',
        'jquery',
        'Magento_Payment/js/model/credit-card-validation/validator',
        'Magento_Checkout/js/model/quote',
        'mage/translate',
        'Magento_Catalog/js/price-utils'
    ],
    function (Component, $, validator, quote, $t, priceUtils) {

        'use strict';

        return Component.extend({

            /**
             * Default values
             */
            defaults: {
                template: 'Gabrielqs_Cielo/payment/cielo_webservice',
                ccOwner: null,
                installmentQuantity: 1,
                installments: []
            },

            /**
             * Init component
             */
            initialize: function () {
                this._super();
                this.initCieloInstallments();
                this.applyCcMasks();
            },

            /**
             * Initializing observables
             * @returns {exports}
             */
            initObservable: function() {
                this
                    ._super()
                    .observe({
                        'installmentQuantity': 1,
                        'installments': [],
                        'ccOwner': ''
                    });
                return this;
            },

            initCieloInstallments: function() {
                this.installments.removeAll();

                var installments = window.checkoutConfig.payment.cielo_webservice.installments;
                for (var i in installments) {
                    var installment = installments[i];
                    var installmentLabel = '';
                    if (installment.numberInstallments == 1) {
                        installmentLabel = this.getFormattedPrice(installment.installmentValue) +
                            ' ' + $t('in cash');
                    } else {
                        installmentLabel = installment.numberInstallments + ' ' + $t('times of') + ' ' +
                            this.getFormattedPrice(installment.installmentValue);
                        if (installment.interestsApplied == false) {
                            installmentLabel = installmentLabel + ' ' + $t('interest free');
                        }
                    }
                    var thisInstallment = {
                        'installmentAmount': installment.installmentValue,
                        'interestApplied': installment.interestsApplied,
                        'quantity': installment.numberInstallments,
                        'totalAmount': installment.installmentValue,
                        'label': installmentLabel
                    };
                    this.installments.push(thisInstallment);
                }
            },

            /**
             * Applies custom masks. This method is called by the template
             */
            applyCcMasks: function () {
                $('#cielo_cc_cid').inputmask({mask: '999'});
                $('#cielo_cc_number').inputmask({mask: '9999-9999-9999-9999'});
            },

            /**
             * Returns Cielo Payment method Code
             * @returns {string}
             */
            getCode: function() {
                return 'cielo_webservice';
            },

            /**
             * Prepares data prior to sending to payment method instance
             * @returns {*}
             */
            getData: function() {
                var data = this._super();
                data.additional_data['installment_quantity'] = this.installmentQuantity();
                data.additional_data['cc_owner'] = this.ccOwner();
                return data;
            },

            /**
             * Formats a float price using the current active currency
             * @param price
             * @returns {String|*}
             */
            getFormattedPrice: function (price) {
                return priceUtils.formatPrice(price, quote.getPriceFormat());
            },

            /**
             * Returns Cielo payment method CC Type icons
             * @param type
             * @returns {*}
             */
            getIcons: function(type) {
                return window.checkoutConfig.payment.cielo_webservice.icons.hasOwnProperty(type)
                    ? window.checkoutConfig.payment.cielo_webservice.icons[type]
                    : this._super(type);
            },

            /**
             * Is method active?
             * @returns {*}
             */
            isActive: function() {
                return window.checkoutConfig.payment.cielo_webservice.active;
            },

            /**
             * Validates the form
             * @returns {*}
             */
            validate: function() {
                var $form = $('#' + this.getCode() + '-form');
                return $form.validation() && $form.validation('isValid');
            }

        });
    }
);
