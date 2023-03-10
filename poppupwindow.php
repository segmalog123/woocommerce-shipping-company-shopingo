<script type="text/x-template" id="ordertpl">
    <div>

        <v-dialog
                style="z-index:9999999"
                id="newordercomp"
                width="1300px"
                v-model="dialog"
                transition="dialog-bottom-transition">

            <v-form ref="orderform" @submit.prevent="">
                <v-card>
                    <v-toolbar
                            height="50px"
                            dark
                            color="primary"

                    >


                        <v-toolbar-title>Commande {{orderData.id}}</v-toolbar-title>
                        <v-spacer></v-spacer>
                        <v-toolbar-items>
                            <p v-if="orderData.id">
                                <mark :class="'order-status status-'+orderData.statusData.value+' tips'"><span>{{orderData.statusData.label}}</span>
                                </mark>

                            </p>
                            <v-btn
                                    icon
                                    dark
                                    @click="dialog = false"
                            >
                                <v-icon>mdi-close</v-icon>
                            </v-btn>
                        </v-toolbar-items>
                    </v-toolbar>


                    <v-card-text>
                        <v-row class="mt-3">

                            <v-col cols="12" sm="3">
                                <v-text-field
                                        ref="refFirstname"
                                        outlined
                                        hide-details="auto"
                                        label="Nom/Prénom*"
                                        dense
                                        v-model.trim="userData.first_name"
                                        :rules="requiredRule"
                                        :readonly="disableManifest(orderData)"
                                ></v-text-field>
                            </v-col>
                            <v-col cols="12" sm="3">
                                <v-text-field
                                        ref="refPhone"
                                        hide-details="auto"
                                        outlined
                                        label="Téléphone*"
                                        dense
                                        v-model.trim="userData.phone"
                                        type="number"
                                        :rules="requiredRule"
                                        :readonly="disableManifest(orderData)"
                                ></v-text-field>
                            </v-col>


                            <v-col cols="12" sm="3">
                                <v-form ref="addressform" @submit.prevent="">
                                    <v-text-field

                                            hide-details="auto"
                                            outlined
                                            label="Addresse"
                                            dense
                                            v-model.trim="userData.address"
                                            :rules="requiredCustomRule"
                                            :readonly="disableManifest(orderData)"
                                    ></v-text-field>
                                </v-form>
                            </v-col>


                            <v-col cols="12" sm="3">
                                <v-form ref="cityform" @submit.prevent="">
                                    <v-select
                                            hide-details="auto"
                                            outlined
                                            label="Gouvernerat"
                                            dense

                                            :items="array_city"
                                            v-model.trim="userData.city"
                                            :rules="requiredCustomRule"
                                            :readonly="disableManifest(orderData)"

                                    ></v-select>
                                </v-form>
                            </v-col>


                            <v-col cols="12" sm="3">
                                <v-text-field
                                        hide-details="auto"
                                        outlined
                                        label="Ville"
                                        dense
                                        :readonly="disableManifest(orderData)"
                                        v-model.trim="userData.state"

                                ></v-text-field>
                            </v-col>
                            <v-col cols="12" sm="3">
                                <v-text-field
                                        hide-details="auto"
                                        outlined
                                        label="Email"
                                        dense
                                        v-model.trim="userData.user_email"
                                        :rules=" emailRule "
                                        :readonly="disableManifest(orderData)"
                                ></v-text-field>
                            </v-col>
                            <v-col cols="12" sm="3">
                                <v-textarea
                                        hide-details="auto"
                                        rows="1"
                                        auto-grow
                                        outlined
                                        label="Note"
                                        dense
                                        v-model.trim="orderData.note"

                                ></v-textarea>
                            </v-col>


                        </v-row>


                      <?php if (is_plugin_active('woocommerce-shipping-company/woocommerce-shipping-company.php')) { ?>
                            <shipping-company ref="refShipComp" :orderinfo="orderData"
                                              :refdata="$root"></shipping-company>
                        <?php } ?>


                        <v-row>
                            <v-col v-if=" action == 'new' || (disableEdit(orderData) &&   !disableManifest(orderData)) "
                                   cols="12" sm="6">
                                <div class="d-flex">
                                    <v-select
                                            style="width:40%"
                                            clearable
                                            @change="changeCat"
                                            v-model="cat"
                                            class="vselect mr-3"
                                            :items="categoryTree"
                                            item-value="id"
                                            :item-text="item => item.child!=0?`---- ${item.name}`:item.name"
                                            label="Catégories"
                                            outlined
                                            dense

                                    >

                                    </v-select>
                                    <v-text-field
                                            style="width:50%"
                                            hide-details="auto"
                                            outlined
                                            label="Recherche par produit"
                                            dense
                                            v-model.trim="productSearch"
                                            append-icon="mdi-file-search"
                                            @input="changeCat"
                                    ></v-text-field>
                                </div>
                                <v-data-table
                                        hide-default-footer
                                        height="300"
                                        :footer-props="{ 'items-per-page-options': [100, 500, 1000,-1] }"
                                        :items-per-page="-1"
                                        :loading="loading"
                                        hide-default-header
                                        :headers="headers"
                                        :items="products"
                                        class="elevation-1"
                                >

                                    <template v-slot:item.price="{ item }">

                                        {{item.price}} <small>TND</small>
                                    </template>
                                    <template v-slot:item.image="{ item }">
                                        <img width="50" :src="item.image"/>
                                    </template>

                                    <template v-slot:item.qty="{ item }">

                                    </template>

                                    <template v-slot:item.name="{ item }">
                                        {{item.name}}


                                        <v-select
                                                label="Variation"
                                                @change="onChangVariation(item)"
                                                return-object
                                                v-model="variation[item.product_id]"
                                                outlined
                                                dense
                                                v-if="item.type=='variable'"
                                                :items="item.variation"
                                                item-value="id"
                                        >
                                            <template v-slot:selection="{ item, index }">
                                                <v-chip
                                                        label
                                                        color="primary"
                                                        small
                                                >
                                                    {{item.label}}
                                                </v-chip>
                                            </template>
                                            <template v-slot:item="{ active, item, attrs, on }">
                                                <v-chip
                                                        label
                                                        color="primary"
                                                        small
                                                >
                                                    {{item.label}}
                                                </v-chip>
                                            </template>
                                        </v-select>


                                    </template>


                                    <template v-slot:item.actions="{ item }">
                                        <v-btn icon class="ma-2 elevation-1"
                                               color="green" @click.stop="addProduct(item)">
                                            <v-icon>
                                                mdi-plus
                                            </v-icon>
                                        </v-btn>
                                    </template>


                                </v-data-table>
                            </v-col>
                            <v-col cols="12"
                                   :sm=" (action == 'new' || (disableEdit(orderData) &&   !disableManifest(orderData)))?'6':'12'">

                                <div class="d-flex">
                                    <v-select
                                            style="width:60%"
                                            v-model="currentStatus"
                                            class="vselect mr-3"
                                            :items="getListStatus"
                                            item-value="value"
                                            item-text="label"
                                            label="Etat"
                                            outlined
                                            dense
                                            return-object
                                    >
                                    </v-select>

                                    <v-menu
                                            v-if="currentStatus.value == 'programmee'"
                                            style="width:35%"
                                            v-model="menu2"
                                            :close-on-content-click="false"
                                            :nudge-right="40"
                                            transition="scale-transition"
                                            offset-y
                                            min-width="auto"
                                    >
                                        <template v-slot:activator="{ on, attrs }">
                                            <v-text-field
                                                    outlined
                                                    dense
                                                    v-model="dateProgrammed"
                                                    label="Date Programmée"
                                                    prepend-icon="mdi-calendar"
                                                    readonly
                                                    v-bind="attrs"
                                                    v-on="on"
                                            ></v-text-field>
                                        </template>
                                        <v-date-picker
                                                :min="nowDate"
                                                no-title
                                                v-model="dateProgrammed"
                                                @input="menu2 = false"
                                        ></v-date-picker>
                                    </v-menu>
                                </div>
                                <v-data-table
                                        hide-default-footer
                                        height="300"
                                        :footer-props="{ 'items-per-page-options': [100, 500, 1000,-1] }"
                                        :items-per-page="-1"

                                        hide-default-header
                                        :headers="headers"
                                        :items="selectedProducts"
                                        class="elevation-1"
                                >
                                    <template v-slot:item.image="{ item }">
                                        <img width="50" :src="item.image"/>
                                    </template>

                                    <template v-slot:item.qty="{ item }">
                                        <div style="width: 80px">
                                            <v-text-field

                                                    :rules="qtyRules.concat(requiredRule)"
                                                    @input="calculatetotal"
                                                    dense
                                                    class="mt-5"
                                                    outlined
                                                    v-model="item.newQty"
                                                    type="number"
                                            >
                                            </v-text-field>
                                        </div>
                                    </template>
                                    <template v-slot:item.price="{ item }">
                                        <div style="width: 120px">
                                            <v-text-field
                                                    :rules="zeroRules.concat(requiredRule)"
                                                    @input="calculatetotal"
                                                    dense
                                                    outlined
                                                    v-model="item.newPrice"
                                                    class="mt-5"
                                                    type="number"
                                                    suffix="TND"
                                            >
                                            </v-text-field>
                                        </div>
                                    </template>
                                    <template v-slot:item.name="{ item }">
                                        {{item.name}}
                                        <v-chip
                                                v-if="item.type=='variable'"
                                                label
                                                color="primary"
                                                small
                                        >
                                            {{item.selectedVariation.label}}
                                        </v-chip>

                                    </template>

                                    <template v-slot:item.actions="{ item }">
                                        <v-btn v-if=" action == 'new' || (disableEdit(orderData) &&   !disableManifest(orderData))"
                                               icon class="ma-2 elevation-1"
                                               color="green" @click.stop="removeProduct(item)">
                                            <v-icon>
                                                mdi-delete
                                            </v-icon>
                                        </v-btn>
                                    </template>


                                </v-data-table>


                            </v-col>


                        </v-row>


                    </v-card-text>
                    <v-divider></v-divider>
                    <v-card-actions>

                        <v-container fluid>
                            <v-row>
                                <v-spacer></v-spacer>

                                <v-col cols="12" sm="4" class="text-center">
                                    <v-btn
                                            v-if=" action == 'new' ||  disableEdit(orderData)  ||    disableManifest(orderData) "
                                            x-large
                                            width="50%"
                                            color="success"
                                            @click="validateArticle"
                                    >
                                        <v-icon left>
                                            mdi-check-bold
                                        </v-icon>
                                        Valider
                                    </v-btn>
                                </v-col>


                                <v-col cols="12" sm="4" class="text-right">
                                    <div class="d-flex flex-column">
                                        <div class="align-self-end">
                                            <v-select
                                                    :readonly="disableManifest(orderData)"
                                                    style="max-width:100px"
                                                    :rules="requiredRule"
                                                    @change="calculatetotal"
                                                    dense
                                                    outlined
                                                    v-model="shipping_cost"
                                                    :items="shipping_methods"
                                                    label="Livraison"
                                                    hide-details="auto"
                                                    item-value="cost"
                                                    item-text="cost"
                                                    return-object
                                            >
                                            </v-select>
                                        </div>
                                        <div>
                                            <h1>
                                                Total:{{totalOrder}} <small>TND</small>
                                            </h1>
                                        </div>
                                    </div>
                                </v-col>

                            </v-row>
                        </v-container>


                    </v-card-actions>


                </v-card>
            </v-form>

        </v-dialog>
        <v-overlay :value="overlayloading" z-index="99999999999999999999999">
            <v-progress-circular
                    indeterminate
                    size="64"
            ></v-progress-circular>
        </v-overlay>
        <snack-component ref="snack"></snack-component>

    </div>


</script>


<div id="slide-out-panel" class="slide-out-panel">
    <div id="paneltabapp">
        <header>{{panel.title}}</header>
        <section>


        </section>
    </div>
</div>
