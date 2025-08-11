<template>
    <div class="cart  pt-2 pb-2 mb-3">
        <div class="d-flex align-items-center pt-2 mw-500" >
            <div class="mw-500" v-if="Object.keys(this.color_options).length">
                <div class="fs-sm mb-4">
                    <span class="text-heading fw-medium me-1"><span class="text-danger">*</span> Boja:</span><span class="text-muted">{{ color_name }} <span class="text-warning">{{ extra_price }}</span></span>
                </div>
                <div class="position-relative me-n4 mb-3" id="select" >
                    <div v-for="(option, index) in color_options" class="form-check form-option form-check-inline mb-2" :data-target="option.option_id">
                        <input class="form-check-input" type="radio" :value="option.id" :id="option.id" :disabled="!option.active" v-model="color"/>
                        <label v-bind:class="{ opacity: !option.active }" class="form-option-label rounded-circle opacity-80" :for="option.id"><span class="form-option-color rounded-circle" :style="option.style"></span> </label>
                    </div>
                </div>
            </div>
            <div class="mw-500" v-if="Object.keys(this.size_options).length && size_disabled">
                <div class="mb-3" >
                    <div class="d-flex justify-content-between align-items-center pb-1 opac">
                        <label class="form-label" for="product-size"><span class="text-danger">*</span>Veličina: <span class="text-muted">{{ size_name }}</span></label>
                    </div>
                    <select class="form-select" required id="product-size" v-model="size">
                        <option value="0">Odaberite veličinu </option>
                        <option v-for="option in size_options" :disabled="!option.active" v-bind:value="option.id">{{ option.name }}</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="d-flex align-items-center pt-0 mw-500" >
            <input class="form-control me-3 mb-1" type="number" inputmode="numeric" pattern="[0-9]*" v-model="quantity" min="1" :max="available" style="width: 5rem;">
            <button class="btn btn-primary btn-shadow me-3 mb-1 " @click="add()" :disabled="disabled"><i class="ci-cart"></i> Dodaj u Košaricu</button>
        </div>
        <p style="width: 100%;" class="fs-md fw-light text-danger" v-if="has_in_cart">Imate {{ has_in_cart }} artikala u košarici.</p>
    </div>
</template>

<script>
export default {
    props: {
        product: String,
        available: String,
        options: String,
        action: String,
    },

    data() {
        return {
            id: '',
            quantity: 1,
            has_in_cart: 0,
            disabled: false,
            is_available: 0,
            size_options: {},
            color_options: {},
            selected_size: {},
            selected_color: {},
            trans: window.trans,
            size: 0,
            color: '',
            parent: '',
            color_name: '',
            size_name: '',
            extra_price: '',
            context_product: {},
            price: 0,
            size_disabled: false,
            context_action: {},
            shown_price: 0,
        }
    },
    //
    watch: {
        size(value) {
            if (value) {
                this.checkAvailableOptions(value, 'size');
            }
        },
        color(value) {
            this.checkAvailableOptions(value, 'color');
        }
    },
    //
    beforeMount() {
        this.context_product = JSON.parse(this.product);
        this.id = this.context_product.id;
        this.price = this.context_product.main_price;
        this.shown_price = this.price;

        this.context_action = JSON.parse(this.action);
    },
    //
    mounted() {
        let cart = this.$store.state.storage.getCart();

        if (cart) {
            for (const key in cart.items) {
                if (this.id == cart.items[key].id) {
                    this.has_in_cart = cart.items[key].quantity;
                }
            }
        }

        console.log(this.price, this.shown_price)

        this.is_available = this.available;

        this.setOptionsSelection();
        this.checkAvailability();
        this.setPrice();
    },

    methods: {
        /**
         * HELPERI ZA PRIKAZ OPCIJA
         */
        optionGroupLabel(key) {
            return key === 'color' ? 'Boja' : key === 'size' ? 'Veličina' : 'Opcija';
        },
        toNum(v) {
            return Number(v || 0);
        },
        priceText(num) {
            const n = this.toNum(num);
            if (!n || n === 0) return null;
            // koristi postojeći formatter (valuta/format)
            return this.$store.state.service.formatMainPrice(n);
        },

        /**
         * Sastavi prikazne atribute za košaricu (ne mijenja backend payload)
         */
        buildCartAttributes() {
            const parts = [];
            if (Object.keys(this.selected_color).length) {
                parts.push({
                    group: this.optionGroupLabel('color'),
                    id: this.selected_color.id,
                    name: this.selected_color.name,
                    price: this.toNum(this.selected_color.price),
                    price_text: this.priceText(this.selected_color.price),
                });
            }
            if (Object.keys(this.selected_size).length) {
                parts.push({
                    group: this.optionGroupLabel('size'),
                    id: this.selected_size.id,
                    name: this.selected_size.name,
                    price: this.toNum(this.selected_size.price),
                    price_text: this.priceText(this.selected_size.price),
                });
            }

            // istaknuta (brzi prikaz) — ona koja NIJE parent, ako postoji parent/child
            let highlighted = null;
            if (this.parent === 'color' && Object.keys(this.selected_size).length) {
                highlighted = {
                    group: this.optionGroupLabel('size'),
                    id: this.selected_size.id,
                    name: this.selected_size.name,
                    price: this.toNum(this.selected_size.price),
                    price_text: this.priceText(this.selected_size.price),
                };
            } else if (this.parent === 'size' && Object.keys(this.selected_color).length) {
                highlighted = {
                    group: this.optionGroupLabel('color'),
                    id: this.selected_color.id,
                    name: this.selected_color.name,
                    price: this.toNum(this.selected_color.price),
                    price_text: this.priceText(this.selected_color.price),
                };
            } else if (parts.length) {
                highlighted = parts[0];
            }

            return {
                option: highlighted,   // jedna "glavna" opcija
                options: parts         // sve odabrane (npr. i Boja i Veličina)
            };
        },

        /**
         *
         */
        setOptionsSelection() {
            let res = JSON.parse(this.options);

            this.parent = res.parent ? res.parent : null;

            if (!this.parent) {
                this.size_disabled = true;
            }

            this.size_options = res.size ? res.size.options : {};
            this.color_options = res.color ? res.color.options : {};
        },

        setPrice() {
            if (Number(this.context_product.main_price) > Number(this.context_product.main_special)) {
                this.price = this.context_product.main_price;
                this.shown_price = this.context_product.main_special;

                console.log(this.shown_price)
            }
        },

        /**
         *
         */
        add() {
            this.checkAvailability(true);

            if (this.has_in_cart) {
                this.updateCart();
            } else {
                this.addToCart();
            }
        },

        /**
         *
         */
        addToCart() {

            let item = {
                id: this.id,
                quantity: this.quantity,
                options: this.setRequestOptions(),
                // ➜ novo: meta za prikaz u košarici
                attributes: this.buildCartAttributes()
            }

            this.$store.dispatch('addToCart', item);
        },

        /**
         *
         */
        updateCart() {
            /*if (parseFloat(this.quantity) > parseFloat(this.is_available)) {
                this.quantity = this.is_available;
            }*/

            let item = {
                id: this.id,
                quantity: this.quantity,
                options: this.setRequestOptions(),
                relative: true,
                // ➜ novo: meta za prikaz u košarici
                attributes: this.buildCartAttributes()
            }

            this.$store.dispatch('updateCart', item);
        },

        /**
         *
         * @param add
         */
        checkAvailability(add = false) {
            this.disabled = false;

            if (this.available == undefined) {
                this.is_available = 0;
            }

            if (add) {
                this.has_in_cart = parseFloat(this.has_in_cart) + parseFloat(this.quantity);
            }

            if (this.is_available <= this.has_in_cart) {
                this.disabled = true;
                this.has_in_cart = this.is_available;
            }

            if (Object.keys(this.color_options).length && !Object.keys(this.selected_color).length) {
                this.disabled = true;
            }
            if (Object.keys(this.size_options).length && !Object.keys(this.selected_size).length) {
                this.disabled = true;
            }
        },

        /**
         *
         * @param option
         * @param type
         */
        checkAvailableOptions(option, type) {
            let is_parent = (type == this.parent) ? 1 : 0;

            if (option != 0) {
                if (Object.keys(this.color_options).length && Object.keys(this.size_options).length) {
                    this.$store.state.service.checkOptions(option, is_parent).then((response) => {
                        if (type == 'color') {
                            this.size_options = response.size.options;
                            this.setSelectedColor(option);
                            this.size_disabled = true;

                        } else {
                            this.color_options = response.color.options;
                            this.setSelectedSize(option);
                        }

                        this.checkAvailability();
                    });

                } else {
                    if (Object.keys(this.color_options).length) {
                        this.setSelectedColor(option);
                    }

                    if (Object.keys(this.size_options).length) {
                        this.setSelectedSize(option);
                    }

                    this.checkAvailability();
                }

            } else {
                if (type == 'color') {
                    for (let item in this.size_options) {
                        this.size_options[item].active = 1;
                    }

                } else {
                    for (let item in this.color_options) {
                        this.color_options[item].active = 1;
                    }
                }
            }

        },

        /**
         *
         * @returns {{}}
         */
        setRequestOptions() {
            let response = {};

            response.id = this.id;
            response.parent_id = (this.parent && this.parent == 'color') ? this.selected_color.option_id : (this.parent ? this.selected_size.option_id : undefined);
            response.option_id = this.parent == 'color' ? this.selected_size.option_id : (this.selected_color.option_id ? this.selected_color.option_id : this.selected_size.option_id);

            return response;
        },

        /**
         *
         * @param id
         */
        setSelectedColor(id) {
            for (let item in this.color_options) {
                if (id == this.color_options[item].id) {
                    this.selected_color = this.color_options[item];
                    this.color_name = this.selected_color.name;

                    if (this.selected_color.price != '0.0000') {
                        this.price = Math.round(Number(this.context_product.main_price + this.selected_color.price)).toFixed(2)
                        let price = Number(this.selected_color.price);
                        this.extra_price = (price < 0 ? '' : '+') + this.$store.state.service.formatMainPrice(price);

                    } else {
                        this.price = this.context_product.main_price;
                        this.extra_price = '';
                    }

                    this.shown_price = this.setShowPrice();
                }
            }

            this.size = 0;
        },

        /**
         *
         * @param id
         */
        setSelectedSize(id) {
            for (let item in this.size_options) {
                if (id == this.size_options[item].id) {
                    this.selected_size = this.size_options[item];
                    this.size_name = this.selected_size.name;

                    console.log(this.selected_size.price)

                    if (this.selected_size.price != '0.0000') {
                        this.price = Number(this.context_product.main_price) + Number(this.selected_size.price);
                        let price = Number(this.selected_size.price);
                        this.extra_price = (price < 0 ? '' : '+') + this.$store.state.service.formatMainPrice(price);

                        console.log(1111)

                    } else {
                        this.price = this.context_product.main_price;
                        this.extra_price = '';

                        console.log(2222)
                    }

                    this.shown_price = this.setShowPrice();
                }

            }
        },


        setShowPrice() {
            if (this.context_action.discount) {
                let price = Number(this.$store.state.service.setDiscount(this.context_action.discount, this.price)).toFixed(2);

                return price;

            } else {
                return this.price;
            }
        }
    }
};
</script>
