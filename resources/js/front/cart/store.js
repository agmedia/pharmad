/* */
let storage_cart = {
    name: 'ph_cart',
    cart: { count: 0, items: [] }
};

let messages = {
    error: 'Whoops!... Greška u vezi sa poslužiteljem!',
    cartAdd: 'Proizvod dodan u košaricu.',
    cartUpdate: 'Količina proizvoda je promjenjena',
    cartRemove: 'Proizvod maknut iz košarice.',
    couponSuccess: 'Kupon je uspješno dodan u košaricu.',
    couponError: 'Nažalost nema kupona pod tim kodom.',
};

class AgService {
    getCart() {
        return axios.get('cart/get')
            .then(response => response.data)
            .catch(() => this.returnError(messages.error));
    }

    checkCart(ids) {
        return axios.post('cart/check', { ids })
            .then(response => response.data)
            .catch(() => this.returnError(messages.error));
    }

    addToCart(item) {
        return axios.post('cart/add', { item })
            .then(response => {
                if (response.data.error) {
                    this.returnError(response.data.error);
                    return false;
                }

                let product = response.data.items[item.id].associatedModel;

                window.dataLayer = window.dataLayer || [];
                window.dataLayer.push({ ecommerce: null });
                window.dataLayer.push({
                    event: 'add_to_cart',
                    ecommerce: { items: [product.dataLayer] }
                });

                this.returnSuccess(messages.cartAdd);
                return response.data;
            })
            .catch(() => this.returnError(messages.error));
    }

    updateCart(item) {
        return axios.post('cart/update/' + item.id, { item })
            .then(response => {
                if (response.data.error) {
                    this.returnError(response.data.error);
                    return false;
                }
                this.returnSuccess(messages.cartUpdate);
                return response.data;
            })
            .catch(() => this.returnError(messages.error));
    }

    removeItem(item) {
        return axios.get('cart/remove/' + item.id)
            .then(response => {
                this.returnSuccess(messages.cartRemove);
                return response.data;
            })
            .catch(() => this.returnError(messages.error));
    }

    checkCoupon(coupon) {
        if (!coupon) coupon = null;
        return axios.get('cart/coupon/' + coupon)
            .then(response => {
                this.returnSuccess(messages.couponSuccess);
                return response.data;
            })
            .catch(() => this.returnError(messages.error));
    }

    checkOptions(option, is_parent) {
        return axios.get('products/options/' + option + '?is_parent=' + is_parent)
            .then(response => response.data)
            .catch(() => this.returnError(messages.error));
    }

    updateLoyalty(loyalty) {
        if (!loyalty) loyalty = null;
        return axios.get('cart/loyalty/' + loyalty)
            .then(response => {
                this.returnSuccess(messages.couponSuccess);
                return response.data;
            })
            .catch(() => this.returnError(messages.error));
    }

    getSettings() {
        return axios.get('settings/get')
            .then(response => response.data)
            .catch(() => this.returnError(messages.error));
    }

    returnSettings(settings) {
        window.AGSettings = settings;
    }

    returnError(msg) { window.ToastWarning.fire(msg); }
    returnSuccess(msg) { window.ToastSuccess.fire(msg); }

    formatPrice(price) {
        return Number(price).toLocaleString('hr-HR', {
            style: 'currency',
            currencyDisplay: 'symbol',
            currency: 'HRK'
        });
    }

    formatMainPrice(price) {
        if (!store.state.settings) {
            this.getSettings().then((response) => {
                return this.resolvePrice(response['currency.list'], price);
            });
        } else {
            return this.resolvePrice(store.state.settings['currency.list'], price);
        }
    }

    resolvePrice(currency_list, price, main = true) {
        let list = currency_list;
        let main_currency = {};

        list.forEach((item) => {
            if (main) {
                if (item.main) main_currency = item;
            } else {
                if (!item.main) { main_currency = item; return; }
            }
        });

        let left = main_currency.symbol_left ? main_currency.symbol_left + '' : '';
        let right = main_currency.symbol_right ? '' + main_currency.symbol_right : '';
        return left + Number(price * main_currency.value).toFixed(main_currency.decimal_places) + right;
    }

    formatSecondaryPrice(price) {
        if (!store.state.settings) {
            this.getSettings().then((response) => {
                return this.resolvePrice(response['currency.list'], price, false);
            });
        } else {
            return this.resolvePrice(store.state.settings['currency.list'], price, false);
        }
    }

    getDiscountAmount(price, special) {
        let discount = ((price - special) / price) * 100;
        return Math.round(discount).toFixed(0);
    }

    calculateItemsTax(items) {
        let tax = 0;
        if (isNaN(items)) {
            for (const key in items) {
                tax += items[key].price - (items[key].price / (Number(items[key].attributes.tax.rate) / 100 + 1));
            }
        } else {
            tax = items - (items / 1.25);
        }
        return tax;
    }
}

class AgStorage {
    getCart() {
        let item = localStorage.getItem(storage_cart.name);
        return (item && item != 'undefined') ? JSON.parse(item) : null;
    }
    setCart(value) {
        return localStorage.setItem(storage_cart.name, JSON.stringify(value));
    }
}

/* ---------- META ZA OPCIJE (trajna) ---------- */
class AgCartMeta {
    constructor() { this.name = 'ph_cart_meta'; }
    getMeta() {
        const raw = localStorage.getItem(this.name);
        return raw && raw !== 'undefined' ? JSON.parse(raw) : {};
    }
    setMeta(obj) { localStorage.setItem(this.name, JSON.stringify(obj || {})); }
    setLine(key, attrs) { const m = this.getMeta(); m[key] = attrs || null; this.setMeta(m); }
    removeLine(key) { const m = this.getMeta(); delete m[key]; this.setMeta(m); }
    clear() { this.setMeta({}); }
}

/* ---------- Helperi za overlay meta -> preko server carta ---------- */
function lineKey(item) {
    const opt = (item && item.options) ? item.options : {};
    const optionId = (opt.option_id != null) ? String(opt.option_id) : '';
    return String(item && item.id) + '|' + optionId;
}

function findMetaForItem(serverItem, meta) {
    const exact = meta[lineKey(serverItem)];
    if (exact) return exact;
    const prefix = String(serverItem && serverItem.id) + '|';
    const candidates = Object.keys(meta).filter(k => k.startsWith(prefix) && meta[k]);
    if (candidates.length === 1) return meta[candidates[0]];
    return candidates.length ? meta[candidates[0]] : null;
}

function overlayCartWithMeta(serverCart, metaObj) {
    const cart = serverCart || { count: 0, items: [] };
    const meta = metaObj || {};

    const apply = (it) => {
        const m = findMetaForItem(it, meta);
        if (m) it.attributes = { ...(it.attributes || {}), ...m };
        return it;
    };

    if (Array.isArray(cart.items)) {
        cart.items = cart.items.map(apply);
    } else if (cart.items && typeof cart.items === 'object') {
        Object.keys(cart.items).forEach(k => { cart.items[k] = apply(cart.items[k]); });
    }
    return cart;
}

/* ---------- STORE ---------- */
let store = {
    state: {
        storage: new AgStorage(),
        service: new AgService(),
        cartMeta: new AgCartMeta(),       // ⬅️ trajna meta za opcije
        cart: storage_cart.cart,
        messages: messages,
        settings: null
    },

    actions: {
        /* Rehidratacija iz localStorage-a + meta (zove se na bootu aplikacije) */
        hydrateFromStorage(context) {
            const s = context.state;
            const cached = s.storage.getCart() || { count: 0, items: [] };
            const merged = overlayCartWithMeta(cached, s.cartMeta.getMeta());
            s.cart = merged;
            s.storage.setCart(merged);
        },

        getCart(context) {
            context.commit('setCart');
        },

        addToCart(context, item) {
            const s = context.state;
            // spremi meta odmah (da preživi refresh)
            s.cartMeta.setLine(lineKey(item), item.attributes);

            s.service.addToCart(item).then(cart => {
                if (cart) {
                    const merged = overlayCartWithMeta(cart, s.cartMeta.getMeta());
                    s.storage.setCart(merged);
                    s.cart = merged;
                }
            });
        },

        updateCart(context, item) {
            const s = context.state;
            if (item.attributes) s.cartMeta.setLine(lineKey(item), item.attributes);

            s.service.updateCart(item).then(cart => {
                if (cart) {
                    const merged = overlayCartWithMeta(cart, s.cartMeta.getMeta());
                    s.storage.setCart(merged);
                    s.cart = merged;
                }
            });
        },

        removeFromCart(context, item) {
            const s = context.state;
            s.cartMeta.removeLine(lineKey(item));

            s.service.removeItem(item).then(cart => {
                const merged = overlayCartWithMeta(cart, s.cartMeta.getMeta());
                s.storage.setCart(merged);
                s.cart = merged;
            });
        },

        checkCart(context, ids) {
            const s = context.state;
            s.service.checkCart(ids).then(response => {
                const serverCart = response.cart || response;
                const merged = overlayCartWithMeta(serverCart, s.cartMeta.getMeta());

                s.storage.setCart(merged);
                s.cart = merged;

                if (response.message && window.location.pathname != '/uspjeh') {
                    window.ToastWarningLong.fire(response.message);
                    if (window.location.pathname != '/kosarica') {
                        window.setTimeout(() => { window.location.href = '/kosarica'; }, 5000);
                    }
                }
            });
        },

        checkCoupon(context, coupon) {
            let s = context.state;

            s.cart.coupon = coupon;
            s.storage.setCart(s.cart);

            s.service.checkCoupon(coupon).then(response => {
                if (response) {
                    s.service.returnSuccess(messages.couponSuccess);
                } else {
                    s.service.returnError(messages.couponError);
                }
                context.commit('setCart');
            });
        },

        updateLoyalty(context, loyalty) {
            let s = context.state;

            s.cart.loyalty = loyalty;
            s.storage.setCart(s.cart);

            s.service.updateLoyalty(loyalty).then(response => {
                if (response) {
                    s.service.returnSuccess(messages.couponSuccess);
                } else {
                    s.service.returnError(messages.couponError);
                }
                context.commit('setCart');
            });
        },

        flushCart(context) {
            context.state.cartMeta.clear();                         // ⬅️ očisti i metu
            context.state.storage.setCart(storage_cart.cart);
            context.state.cart = storage_cart.cart;
        },

        getSettings(context, item) {
            let s = context.state;
            s.service.getSettings(item).then(settings => {
                if (settings) s.settings = settings;
            });
        },
    },

    mutations: {
        setCart(state) {
            return state.service.getCart().then(cart => {
                const merged = overlayCartWithMeta(cart, state.cartMeta.getMeta());
                state.cart = merged;
                return state.storage.setCart(merged);
            });
        }
    },
};

export default store;
