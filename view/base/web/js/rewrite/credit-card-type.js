/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*jshint browser:true jquery:true*/
/*global alert*/
define(
    [
        'jquery',
        'mageUtils'
    ],
    function ($, utils) {
        'use strict';
        var types = [

            /* Changes by Gabrielqs - Brazilian CC Types Validation - Beginning */
            {
                title: 'Elo',
                type: 'EL',
                pattern: '^(40117(8|9)|431274|438935|636297|451416|45763(1|2)|504175|5067((17)|(18)|(22)|(25)|' +
                        '(26)|(27)|(28)|(29)|(30)|(33)|(39)|(40)|(41)|(42)|(44)|(45)|(46)|(47)|(48))|627780|636297|' +
                        '636368)[0-9]{10}$',
                gaps: [4, 8, 12],
                lengths: [16],
                code: {
                    name: 'CVC',
                    size: 3
                }
            },
            {
                title: 'Aura',
                type: 'AU',
                pattern: '^(50[0-9]{17})$',
                gaps: [4, 8, 12, 16],
                lengths: [19],
                code: {
                    name: 'CVC',
                    size: 3
                }
            },
            {
                title: 'Hipercard',
                type: 'HI',
                pattern: '^(384100|384140|384160|606282)([0-9]{10}|[0-9]{13})$',
                gaps: [4, 8, 12],
                lengths: [13,16,19],
                code: {
                    name: 'CVC',
                    size: 3
                }
            },
            /* Changes by Gabrielqs - Brazilian CC Types Validation - End */

            {
                title: 'Visa',
                type: 'VI',
                pattern: '^4\\d*$',
                gaps: [4, 8, 12],
                lengths: [16],
                code: {
                    name: 'CVV',
                    size: 3
                }
            },
            {
                title: 'MasterCard',
                type: 'MC',
                pattern: '^5([1-5]\\d*)?$',
                gaps: [4, 8, 12],
                lengths: [16],
                code: {
                    name: 'CVC',
                    size: 3
                }
            },
            {
                title: 'American Express',
                type: 'AE',
                pattern: '^3([47]\\d*)?$',
                isAmex: true,
                gaps: [4, 10],
                lengths: [15],
                code: {
                    name: 'CID',
                    size: 4
                }
            },
            {
                title: 'Diners',
                type: 'DN',
                pattern: '^3((0([0-5]\\d*)?)|[689]\\d*)?$',
                gaps: [4, 10],
                lengths: [14],
                code: {
                    name: 'CVV',
                    size: 3
                }
            },
            {
                title: 'Discover',
                type: 'DI',
                pattern: '^6(0|01|011\\d*|5\\d*|4|4[4-9]\\d*)?$',
                gaps: [4, 8, 12],
                lengths: [16],
                code: {
                    name: 'CID',
                    size: 3
                }
            },
            {
                title: 'JCB',
                type: 'JCB',
                pattern: '^((2|21|213|2131\\d*)|(1|18|180|1800\\d*)|(3|35\\d*))$',
                gaps: [4, 8, 12],
                lengths: [16],
                code: {
                    name: 'CVV',
                    size: 3
                }
            },
            {
                title: 'UnionPay',
                type: 'UN',
                pattern: '^6(2\\d*)?$',
                gaps: [4, 8, 12],
                lengths: [16, 17, 18, 19],
                code: {
                    name: 'CVN',
                    size: 3
                }
            },
            {
                title: 'Maestro International',
                type: 'MI',
                pattern: '^(5(0|[6-9])|63|67(?!59|6770|6774))\\d*$',
                gaps: [4, 8, 12],
                lengths: [12, 13, 14, 15, 16, 17, 18, 19],
                code: {
                    name: 'CVC',
                    size: 3
                }
            },
            {
                title: 'Maestro Domestic',
                type: 'MD',
                pattern: '^6759(?!24|38|40|6[3-9]|70|76)|676770|676774\\d*$',
                gaps: [4, 8, 12],
                lengths: [12, 13, 14, 15, 16, 17, 18, 19],
                code: {
                    name: 'CVC',
                    size: 3
                }
            }
        ];
        return {
            getCardTypes: function (cardNumber) {
                var i, value,
                    result = [];

                if (utils.isEmpty(cardNumber)) {
                    return result;
                }

                if (cardNumber === '') {
                    return $.extend(true, {}, types);
                }

                for (i = 0; i < types.length; i++) {
                    value = types[i];

                    if (new RegExp(value.pattern).test(cardNumber)) {
                        result.push($.extend(true, {}, value));
                    }
                }
                return result;
            }
        }
    }
);
