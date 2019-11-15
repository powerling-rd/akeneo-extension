'use strict';
/**
 * Mass edit operation: send to Powerling
 *
 * @author    Arnaud Lejosne <a.lejosne@powerling.com>
 * @copyright 2019 Powerling (https://powerling.com)
*/
define(
    [
        'underscore',
        'pim/mass-edit-form/product/operation',
        'powerling/template/mass_edit',
        'pim/user-context',
        'pim/fetcher-registry',
        'pim/formatter/choices/base',
        'pim/initselect2'
    ],
    function (
        _,
        BaseOperation,
        template,
        UserContext,
        FetcherRegistry,
        ChoicesFormatter,
        initSelect2
    ) {
        return BaseOperation.extend({
            template: _.template(template),

            events: {
                'change .powerling-field': 'updateModel'
            },

            render() {
                const data = Object.assign(
                    {},
                    {
                        name: '',
                        langAssociations: []
                    },
                    this.getFormData().actions[0]
                );

                this.$el.html(this.template(data));
                this.initLangAssociations();

                return this;
            },

            /**
             * {@inheritDoc}
             */
            updateModel(event) {
                const target = event.target;
                this.setValue(target.name, target.value);
            },

            /**
             * Replace actions[0] with an updated version.
             * #immutability
             *
             * @param {String} field Name of the input field
             * @param {string} value Value of the input field
             */
            setValue(field, value) {
                const data = this.getFormData();
                data.actions[0] = Object.assign(
                    {},
                    {
                        name: '',
                        langAssociations: [],
                        username: UserContext.get('username')
                    },
                    data.actions[0],
                    {[field]: value}
                );
                this.setData(data);
            },

            initLangAssociations: function () {
                const fetcher = FetcherRegistry.getFetcher('powerling-lang-associations');

                fetcher.fetchAll()
                    .then(langAssociations => {
                        const choices = _.chain(langAssociations)
                            .map(langAssociation => {
                                return {
                                    id: langAssociation.id,
                                    text: `[${langAssociation.language_from} to ${langAssociation.language_to}]`
                                };
                            })
                            .value();
                        initSelect2.init(this.$('#powerling-lang-associations'), {
                            data: choices,
                            multiple: true,
                            containerCssClass: 'input-xxlarge'
                        });
                    });
            }
        });
    }
);
