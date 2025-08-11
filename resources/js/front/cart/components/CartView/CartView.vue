<template>
    <div>
        <div class="d-block pt-3 pb-2 mt-1 text-center text-sm-start">
            <h2 class="h6 text-primary mb-0">Artikli</h2>
        </div>

        <div class="d-flex pt-3 pb-2 mt-1" v-if="!$store.state.cart.count">
            <p class="text-dark mb-0">Vaša košarica je prazna!</p>
        </div>

        <div
            class="d-sm-flex justify-content-between align-items-center my-2 pb-3 border-bottom"
            v-for="item in cartItems"
            :key="item.id + '-' + (item.options && item.options.option_id ? item.options.option_id : 'noopt') + '-' + item.quantity"
        >
            <div class="d-block d-sm-flex align-items-center text-center text-sm-start">
                <a class="d-inline-block flex-shrink-0 mx-auto me-sm-4" :href="base_path + getItemUrl(item)">
                    <img :src="item.associatedModel.image" width="120" :alt="item.name" :title="item.name">
                </a>

                <div class="pt-2">
                    <h3 class="product-title fs-base mb-2">
                        <a :href="base_path + getItemUrl(item)">{{ item.name }}</a>
                    </h3>

                    <!-- ODABRANA OPCIJA (koristi merged attributes: server + meta iz localStorage-a) -->
                    <div class="fs-sm text-muted" v-if="getDisplayOption(item)">
                        {{ getDisplayOption(item).group || 'Opcija' }}:
                        <strong>{{ getDisplayOption(item).name }}</strong>
                        <span v-if="Number(getDisplayOption(item).price) > 0">
              — {{ getDisplayOption(item).price_text || (Number(getDisplayOption(item).price).toFixed(2) + ' €') }}
            </span>
                    </div>

                    <div class="fs-lg text-primary pt-2">
                        {{ hasConditions(item) ? item.associatedModel.main_special_text : item.associatedModel.main_price_text }}
                        <span class="text-primary fs-md fw-light" style="margin-left: 20px;"
                              v-if="hasActionBadge(item)">
              {{ item.associatedModel.action.title }} ({{ Math.round(item.associatedModel.action.discount).toFixed(0) }}
              {{ item.associatedModel.action.type == 'F' ? '€' : '%' }})
            </span>
                    </div>

                    <div class="fs-sm text-dark pt-2" v-if="item.associatedModel.secondary_price">
                        {{ hasConditions(item) ? item.associatedModel.secondary_special_text : item.associatedModel.secondary_price_text }}
                    </div>
                </div>
            </div>

            <div class="pt-2 pt-sm-0 ps-sm-3 mx-auto mx-sm-0 text-center text-sm-start" style="max-width: 9rem;">
                <label class="form-label">Količina: {{ item.quantity }}</label>
                <input class="form-control" type="number" v-model.number="item.quantity"
                       min="1" :max="item.associatedModel.quantity"
                       @click.prevent="updateCart(item)" @change.prevent="updateCart(item)">
                <button class="btn btn-link px-0 text-danger" type="button" @click.prevent="removeFromCart(item)">
                    <i class="ci-close-circle me-2"></i><span class="fs-sm">Ukloni</span>
                </button>
            </div>
        </div>

        <div class="d-block pt-3 pb-3 mt-1 text-center text-sm-start" v-if="show_buttons">
            <a class="btn btn-outline-dark btn-sm ps-2" :href="continueurl"><i class="ci-arrow-left me-2"></i>Natrag na trgovinu</a>
        </div>
    </div>
</template>

<script>
export default {
    props: {
        continueurl: String,
        checkouturl: String,
        freeship: String,
        buttons: { type: String, default: 'true' },
    },
    data() {
        return {
            base_path: window.location.origin + '/',
            mobile: false,
            show_delete_btn: true,
            coupon: '',
            show_buttons: true,
        }
    },
    computed: {
        cartItems() {
            const items = this.$store.state.cart?.items || [];
            return Array.isArray(items) ? items : Object.values(items);
        }
    },
    mounted() {
        if (window.innerWidth < 800) this.mobile = true;
        this.show_buttons = this.buttons !== 'false';
        this.checkIfEmpty();
        this.setCoupon();
    },
    methods: {
        getItemUrl(item) {
            return (item.attributes && item.attributes.path)
                ? item.attributes.path
                : (item.associatedModel && item.associatedModel.url ? item.associatedModel.url : '');
        },

        // --- META helpers (čitanje ph_cart_meta iz localStorage-a) ---
        _getMeta() {
            try { return JSON.parse(localStorage.getItem('ph_cart_meta') || '{}'); }
            catch(e) { return {}; }
        },
        _lineKeyFromItem(item) {
            const optionId = item && item.options && item.options.option_id != null
                ? String(item.options.option_id)
                : '';
            return String(item && item.id) + '|' + optionId;
        },
        _findMetaForItem(item) {
            const meta = this._getMeta();
            // 1) probaj pun ključ (id|option_id)
            const fullKey = this._lineKeyFromItem(item);
            if (meta[fullKey]) return meta[fullKey];
            // 2) fallback: nema option_id u itemu nakon refresha → uzmi prvi meta ključ koji počinje s id|
            const idPrefix = String(item && item.id) + '|';
            const k = Object.keys(meta).find(x => x.startsWith(idPrefix));
            return k ? meta[k] : null;
        },

        // Vrati "merged" attributes za prikaz (server attributes + meta fallback)
        getDisplayAttributes(item) {
            const a = (item && item.attributes) ? item.attributes : {};
            const m = this._findMetaForItem(item) || {};
            return { ...a, ...m };
        },

        // Jedna opcija za prikaz (option ili prva iz options) iz merged atributa
        getDisplayOption(item) {
            const a = this.getDisplayAttributes(item);
            if (a.option && (a.option.name || a.option.group)) return a.option;
            if (Array.isArray(a.options) && a.options.length) return a.options[0];
            return null;
        },

        hasConditions(item) {
            return item && item.conditions && Object.keys(item.conditions).length > 0;
        },
        hasActionBadge(item) {
            return this.hasConditions(item) &&
                item.associatedModel &&
                item.associatedModel.action &&
                item.associatedModel.action.coupon == this.$store.state.cart.coupon;
        },

        updateCart(item) { this.$store.dispatch('updateCart', item); },
        removeFromCart(item) { this.$store.dispatch('removeFromCart', item); },

        CheckQuantity(qty) { return qty < 1 ? 1 : qty; },
        checkIfEmpty() {
            let cart = this.$store.state.storage.getCart();
            if (cart && !cart.count && window.location.pathname != '/kosarica') {
                window.location.href = '/kosarica';
            }
        },
        setCoupon() {
            let cart = this.$store.state.storage.getCart();
            if (cart) this.coupon = cart.coupon;
        },
        checkCoupon() { this.$store.dispatch('checkCoupon', this.coupon); }
    }
};
</script>

<style>
.table th, .table td { padding: 0.75rem 0.45rem !important; vertical-align: top; border-top: 1px solid #dee2e6; }
.empty th, .empty td { padding: 1rem !important; vertical-align: top; border-top: 1px solid #dee2e6; }
.mobile-prices { font-size: .66rem; color: #999999; }
</style>
