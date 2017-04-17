define(
    [
        'knockout',
        'jquery',
        'inputmask.dependencyLib',
        'inputmask.jQuery',
        'inputmask.date'
    ],function (ko, $) {
        ko.bindingHandlers.inputmask = {
            init: function (element, valueAccessor, allBindings, viewModel, bindingContext) {
                maskvalue = valueAccessor();
                $(element).inputmask({mask: maskvalue});
            }
        }
    });