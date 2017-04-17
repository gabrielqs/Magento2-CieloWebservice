/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
/*global define*/
define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'cielo_webservice',
                component: 'Gabrielqs_Cielo/js/view/payment/method-renderer/cielo-webservice-method'
            }
        );
        return Component.extend({});
    }
);