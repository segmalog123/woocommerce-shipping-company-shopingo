(function ($) {
    'use strict';

    /**
     * All of the code for your admin-facing JavaScript source
     * should reside in this file.
     *
     * Note: It has been assumed you will write jQuery code here, so the
     * $ function reference has been prepared for usage within the scope
     * of this function.
     *
     * This enables you to define handlers, for when the DOM is ready:
     */
    $(function () {




        $('.woocommerce-layout__activity-panel-tabs button:first-child').before('<button data-title="Commandes Programmées"  data-panel="programmed"  class="custom-panel-tab components-button woocommerce-layout__activity-panel-tab" type="button">\n\
<i class="dashicons dashicons-calendar-alt"></i>Commandes<br/>Programmées</button> ')


        $(".bulkactions   option").each(function (i) {
            if ($(this).val().indexOf("mark_") >= 0 /*&&   $(this).val() != 'mark_manifest'*/) {
                $(this).hide()
            }
        });

        if (jspopupdata.post_status && jspopupdata.post_status != '') {
            if (jspopupdata.post_status == 'wc-manifest') {
                //$('.bulkactions option[value="mark_pick-up"]').show()
            }

            var arr_status = ['wc-en-cours','wc-processing','wc-injoignable','wc-attente-stock','wc-programmee','wc-on-hold','wc-cancelled']


            if (arr_status.includes(jspopupdata.post_status)) {
                $('.bulkactions option[value="mark_en-cours"]').show()
            }

        }

        var ajaxConfig = {
            dataType: 'json',
            method: 'POST',
            beforeSend: function (xhr) {
                xhr.setRequestHeader('X-WP-Nonce', wpApiSettings.nonce);
            },
        }


        if (jspopupdata.post_type && jspopupdata.post_type == 'shop_order') {




            $.each($('.page-title-action'), function (k, vv) {
                if (typeof ($(this).attr('href')) != "undefined" &&
                    $(this).attr('href').indexOf('post_type=shop_order') !== -1) {
                    $(this).remove()
                }
            })
            $('.wp-heading-inline').after(
                '<span id="neworderapp" data-app class="v-application v-application--is-ltr theme--light" >\n\
            <v-btn color="primary" outlined tile @click="newOrder" small class="  btn_action_order mr-2" >Ajouter une commande</v-btn>\n\
              <order-component ref="newOrder"></order-component>\n\
             <snack-component ref="snack"></snack-component><v-overlay :value="overlayloading"  z-index="99999999999999999999999" > <v-progress-circular indeterminate size="64" ></v-progress-circular> </v-overlay>\n\
            </span>')
        }



        if($('#paneltabapp').length>=1){
            var panelObj =  new Vue({
                el: '#paneltabapp',
                vuetify: new Vuetify(),
                data: function () {
                    return {
                        panel:{
                            type:'',
                            title:'',},
                        overlayloading: false
                    }
                },

            })

            var panel = $('#slide-out-panel').SlideOutPanel({ offsetTop: '30px',screenOpacity: '0',});
            $('.custom-panel-tab').on('click', function () {
                panelObj.panel.title = $(this).data('title')
                panelObj.panel.type = $(this).data('panel')
                panel.open()
            })

        }

        const ConfirmComp = Vue.component('confirm-component', {
            template: '<v-dialog v-model="show" :max-width="options.width" :style="{ zIndex: options.zIndex }" @keydown.esc="cancel"><v-card><v-toolbar :color="options.color" dark dense flat><v-toolbar-title class="white--text">{{ title }}</v-toolbar-title></v-toolbar><v-card-text v-show="!!message" class="pa-4">{{ message }}</v-card-text><v-card-actions class="pt-0"><v-spacer></v-spacer><v-btn   @click.native="agree" color="success darken-1" >Confirmer</v-btn><v-btn @click.native="cancel" color="grey" text>Annuler</v-btn></v-card-actions></v-card></v-dialog>',
            data() {
                return {

                    dialog: false,
                    resolve: null,
                    reject: null,
                    message: null,
                    title: null,
                    options: {
                        color: 'primary',
                        width: 290,
                        zIndex: 200
                    }
                }
            },
            computed: {
                show: {
                    get() {
                        return this.dialog
                    },
                    set(value) {
                        this.dialog = value
                        if (value === false) {
                            this.cancel()
                        }
                    }
                }
            },
            methods: {
                open(title, message, options) {
                    this.dialog = true
                    this.title = title
                    this.message = message
                    this.options = Object.assign(this.options, options)
                    return new Promise((resolve, reject) => {
                        this.resolve = resolve
                        this.reject = reject
                    })
                },
                agree() {
                    this.resolve(true)
                    this.dialog = false
                },
                cancel() {
                    this.resolve(false)
                    this.dialog = false
                }
            }
        })
        const snackComp = Vue.component('snack-component', {
            template: '<v-snackbar style="z-index:99999999999999999" v-model="visiblity" :timeout="options.timeout" :color="options.color"   top :vertical="options.vertical" > <v-icon v-if="options.icon">{{ options.icon }}</v-icon> {{ message }} <template v-slot:action="{ attrs }"> <v-btn icon dark @click="visiblity = false" > <v-icon >mdi-close</v-icon> </v-btn> </template> <v-progress-linear :active="options.loading" :indeterminate="options.loading" absolute bottom color="red"></v-progress-linear></v-snackbar>',
            data() {
                return {

                    visiblity: false,
                    message: null,
                    options: {
                        timeout: 5000,
                        color: '',
                        icon: '',
                        vertical: false,
                        loading: false,
                    }
                }
            },
            methods: {
                show(message, options) {
                    this.visiblity = true
                    this.message = message
                    this.options = Object.assign(this.options, options)

                },
            }
        })

        const orderComp = Vue.component('order-component', {
            template: '#ordertpl',
            vuetify: new Vuetify(),
            data() {
                return {
                    nowDate: new Date().toISOString().slice(0, 10),
                    dateProgrammed: '',
                    menu2: false,
                    shipping_methods: jspopupdata.shipping_methods,
                    currentStatus: {},
                    listeStatus: jspopupdata.listeStatus,
                    overlayloading: false,
                    array_city: Object.keys(jspopupdata.array_city),
                    varit: [],
                    totalOrder: 0,
                    selectedVariation: [],
                    variation: [],
                    selectedProducts: [],
                    cat: '',
                    productSearch: '',
                    status_intitial: ['en-cours', 'on-hold', 'programmee', 'attente-stock', 'injoignable', 'cancelled', "processing"],
                    headers: [
                        {text: 'Image', value: 'image'},
                        {text: 'UGS', value: 'sku'},
                        {text: 'Titre', value: 'name'},
                        {text: 'Qty', value: 'qty'},
                        {text: 'Prix', value: 'price'},
                        {text: 'Actions', value: 'actions'},
                    ],
                    products: [],
                    loading: false,
                    categoryTree: jspopupdata.categoryTree,
                    dialog: false,
                    orderData: {id: '', },
                    shipping_cost: {},
                    list_user: [],
                    userData: {},
                    action: '',
                    btn: 'update'
                }
            },
            computed: {
                getListStatus() {

                    let filterStatus = []
                    if (this.orderData && this.orderData.statusData && this.orderData.statusData.value) {
                        if (this.status_intitial.includes(this.orderData.statusData.value)) {
                            filterStatus = this.listeStatus.filter(el => {
                                return this.status_intitial.includes(el.value)
                            })
                        } else {
                            if (this.orderData.statusData.value == 'completed') {
                                filterStatus = this.listeStatus.filter(el => {
                                    return el.value == this.orderData.statusData.value || el.value == 'echange-recu' || el.value == 'paiement-recu'
                                })
                            }
                            if (this.orderData.statusData.value == 'retour') {
                                filterStatus = this.listeStatus.filter(el => {
                                    return el.value == this.orderData.statusData.value || el.value == 'failed'
                                })
                            }
                            if (this.orderData.statusData.value == 'manifest') {
                                filterStatus = this.listeStatus.filter(el => {
                                    return el.value == this.orderData.statusData.value || el.value == 'pick-up'
                                })
                            }

                            if (this.orderData.statusData.value == 'pick-up') {
                                filterStatus = this.listeStatus.filter(el => {
                                    return  el.value == this.orderData.statusData.value || el.value == 'en-livraison'
                                })
                            }

                            if (this.orderData.statusData.value == 'anomalie' || this.orderData.statusData.value == 'en-livraison') {
                                filterStatus = this.listeStatus.filter(el => {
                                    return el.value == this.orderData.statusData.value || el.value == 'retour' || el.value == 'failed'
                                })
                            }
                        }
                    } else {
                        filterStatus = this.listeStatus.filter(el => {
                            return this.status_intitial.includes(el.value)
                        })
                    }



                    return filterStatus
                },
                requiredRule() {
                    return [v => !!v || "Champ Obligatoire"]
                },
                requiredCustomRule(e) {

                    return [v => !!v || "Champ Obligatoire"]


                },
                emailRule() {
                    return [

                        v => (!v || /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,24}))$/.test(v)) || "Email invalide!"]
                },
                zeroRules() {
                    const rules = []
                    const ruleMin = v => v >= 0 || 'Valuer supérieure à zéro svp!'
                    rules.push(ruleMin)
                    return rules
                },
                qtyRules() {
                    const rules = []
                    const ruleMin = v => v >= 1 || 'Valuer supérieure à zéro svp!'
                    rules.push(ruleMin)
                    return rules
                },

            },
            mounted() {

                this.currentStatus = this.listeStatus.find(el => {
                    return el.value == 'processing'
                })



            },
            methods: {
                disableManifest(data) {
                    if (data.statusData && data.statusData.value) {
                        if (data.statusData.value == 'manifest') {
                            return true
                        }
                    }
                    return false
                },
                disableEdit(data) {

                    if (data.status && this.status_intitial.includes(data.status)) {
                        return true;
                    } else {
                        return false;
                    }
                },
                onChangVariation(item) {
                    let selectedVariation = this.variation.find((el, k) => {
                        return k == item.product_id && el != ''
                    })

                    item.price = selectedVariation.price
                },
                changeCat() {

                    this.getDataFromApi()
                },

                removeProduct(item) {
                    this.selectedProducts.splice(this.selectedProducts.indexOf(item), 1)
                    this.calculatetotal()
                },
                addProduct(item) {
                    let itemData = {}
                    let duplicateItem = []
                    this.selectedVariation = this.variation.find((el, k) => {
                        return k == item.product_id && el != ''
                    })

                    if (item.type == 'variable' && (item.variation.length <= 0 || !this.selectedVariation)) {
                        this.$refs.snack.show('Séléctionner une variation svp!', {color: 'error', icon: 'mdi-close-octagon'})
                        return;
                    }
                    if (item.type == 'variable') {
                        itemData.selectedVariation = this.selectedVariation
                        itemData.product_id = this.selectedVariation.id
                        itemData.product_parent_id = item.product_id

                    } else {
                        itemData.product_id = item.product_id
                    }

                    itemData.newPrice = item.price
                    itemData.newQty = item.qty
                    itemData.image = item.image
                    itemData.name = item.name
                    itemData.type = item.type
                    itemData.sku = item.sku



                    duplicateItem = this.selectedProducts.filter(el => {
                        return el.product_id == itemData.product_id
                    })
                    if (duplicateItem && duplicateItem.length >= 1) {
                        this.$refs.snack.show('Produit existe déja!', {color: 'error', icon: 'mdi-close-octagon'})
                        this.selectedProducts = removeDuplicates(this.selectedProducts, 'product_id')
                    } else {
                        this.selectedProducts.push(itemData)
                        // this.selectedProducts = removeDuplicates(this.selectedProducts,'product_id')

                    }



                    this.calculatetotal()
                },
                calculatetotal() {
                    let total = 0
                    this.selectedProducts.forEach(el => {
                        total += Number(el.newPrice) * Number(el.newQty)
                    })
                    if (this.shipping_cost && this.shipping_cost.cost) {
                        this.totalOrder = Number(total) + Number(this.shipping_cost.cost)
                    } else {
                        this.totalOrder = Number(total)
                    }
                },
                loadUser() {

                    if (this.orderData && this.orderData.user) {
                        this.userData = this.orderData.user
                    }
                },
                showDialog(order_id = '') {


                    let self = this


                    if (order_id && order_id != '') {
                        this.action = 'edit'
                        this.overlayloading = true


                        ajaxConfig.url = wpApiSettings.root + "globalapi/v2/get_order_data"
                        ajaxConfig.data = {order_ids: [order_id]}
                        $.ajax(ajaxConfig).done(function (data) {
                            console.log(data)
                            self.orderData = data.order_data
                            if (self.orderData.user) {
                                self.userData = self.orderData.user
                            }
                            self.overlayloading = false
                            self.dialog = true
                            self.selectedProducts = self.orderData.products
                            self.totalOrder = self.orderData.total.replace('.000', '')
                            self.currentStatus = self.orderData.statusData

                            self.dateProgrammed = self.orderData.date_programmed

                            self.shipping_cost = jspopupdata.shipping_methods.find(el => {
                                return el.cost == self.orderData.shipping_method.cost
                            })

                            self.$nextTick(() => {
                                self.$refs.refShipComp.formData.echange_contenu = self.orderData.companyData.echange_contenu
                                self.$refs.refShipComp.formData.shipping_nbr_pcs = Object.values(self.$refs.refShipComp.arrNbrPcs).find(el => {
                                    return el == self.orderData.companyData.shipping_nbr_pcs
                                })
                            })

                        });

                    } else {

                        this.shipping_cost = jspopupdata.shipping_methods.reduce(function (prev, curr) {
                            return prev.method_order < curr.method_order ? prev : curr;
                        }),
                            this.action = 'new'
                        this.userData = {}
                        this.orderData = {}
                        this.selectedProducts = []
                        this.dialog = true
                        self.$nextTick(() => {
                            self.$refs.refShipComp.formData = {shipping_nbr_pcs: 1}
                            self.$refs.orderform.resetValidation()
                            self.$refs.addressform.resetValidation()
                            self.$refs.cityform.resetValidation()

                        })

                    }
                    // this.getUserData()
                    this.getDataFromApi()

                },
                validateArticle() {
                    let self = this

                    this.btn = 'update'

//                   console.log(this.$refs.refShipComp.formData)
//                   return

                    let validate = true
                    if (!this.userData.first_name || !this.userData.phone || this.userData.first_name == '' || this.userData.phone == '') {
                        validate = this.$refs.orderform.validate()
                    }

                    if (this.selectedProducts.length <= 0) {
                        this.$refs.snack.show('Ajouter des produits svp!', {color: 'error', icon: 'mdi-close-octagon'})
                        validate = false
                    }

                    if (validate) {

                        if (this.$refs.refShipComp) {
                            this.orderData.formData = this.$refs.refShipComp.formData
                        }

                        this.overlayloading = true


                        this.orderData.shipping_cost = this.shipping_cost
                        this.orderData.dateProgrammed = this.dateProgrammed

                        ajaxConfig.url = wpApiSettings.root + "globalapi/v2/save_admin_new_order"
                        ajaxConfig.data = {total_order: this.totalOrder, products: this.selectedProducts, currentStatus: this.currentStatus,
                            orderData: this.orderData, userData: this.userData}

                        ajaxConfig.success = function (data) {
                            self.overlayloading = false
                            console.log(data)
                            self.dialog = false
                            self.$refs.snack.show('Commande enregistrée avec succées!', {color: 'success', icon: 'mdi-check'})
                            window.location.reload();


                        }

                        $.ajax(ajaxConfig)

                    }
                },

                getDataFromApi() {

                    this.loading = true


                    let self = this
                    ajaxConfig.url = wpApiSettings.root + "globalapi/v2/get_list_products"
                    ajaxConfig.data = {cat: this.cat, productSearch: this.productSearch}
                    $.ajax(ajaxConfig).done(function (data) {
                        self.products = data.products
                        self.loading = false
                    })

                }
            }
        })


        if ($('#neworderapp').length >= 1) {

            const vueObj = new Vue({
                el: '#neworderapp',
                vuetify: new Vuetify(),
                data: function () {
                    return {
                        overlayloading: false
                    }
                },

                methods: {
                    newOrder() {
                        this.$refs.newOrder.showDialog()
                    },

                }
            })


            if (jspopupdata.post_type && jspopupdata.post_type == 'shop_order') {
                $(".wp-list-table ").on("click", ".type-shop_order", function (e) {
                    if (!$(e.target).is("a,input,a strong,select,span,button")) {
                        var order_id = $(this).attr('id').replace(/[^0-9\.]/g, '')
                        vueObj.$refs.newOrder.showDialog(order_id)
                    }

                })

                $(".wp-list-table").on("click", ".custom-order-preview", function (e) {
                    e.preventDefault()
                    var order_id = $(this).attr('data-order-id').replace(/[^0-9\.]/g, '')
                    vueObj.$refs.newOrder.showDialog(order_id)
                })





            }
        }

        function removeDuplicates(originalArray, prop) {
            var newArray = [];
            var lookupObject = {};

            for (var i in originalArray) {
                lookupObject[originalArray[i][prop]] = originalArray[i];
            }

            for (i in lookupObject) {
                newArray.push(lookupObject[i]);
            }
            return newArray;
        }


    });

    /* When the window is loaded:
     *
     * $( window ).load(function() {
     *
     * });
     *
     * ...and/or other possibilities.
     *
     * Ideally, it is not considered best practise to attach more than a
     * single DOM-ready or window-load handler for a particular page.
     * Although scripts in the WordPress core, Plugins and Themes may be
     * practising this, we should strive to set a better example in our own work.
     */

})(jQuery);
