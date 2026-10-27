import './bootstrap';

document.querySelectorAll('[data-pin-input]').forEach((input) => {
    const form = input.closest('form');

    form?.querySelectorAll('[data-pin-key]').forEach((button) => {
        button.addEventListener('click', () => {
            const key = button.dataset.pinKey;

            if (key === 'clear') {
                input.value = '';
            } else if (key === 'backspace') {
                input.value = input.value.slice(0, -1);
            } else if (/^\d$/.test(key) && input.value.length < 6) {
                input.value += key;
            }

            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.focus();
        });
    });
});

const storefront = document.querySelector('[data-storefront]');

if (storefront) {
    const products = JSON.parse(
        storefront.querySelector('[data-products]').textContent
    );

    const productGrid = storefront.querySelector('[data-product-grid]');
    const cartLines = document.querySelector('[data-cart-lines]');
    const cartTotal = document.querySelector('[data-cart-total]');
    const checkoutButton = document.querySelector('[data-checkout-button]');
    const itemFields = document.querySelector('[data-order-items]');
    const deliveryType = document.querySelector('[data-delivery-type]');
    const shippingAddress = document.querySelector(
        '[data-shipping-address] textarea'
    );

    const cartDrawer = document.getElementById('keranjang');
    const cartBackdrop = document.querySelector(
        '[data-cart-close].cart-backdrop'
    );
    const cartOpenButton = document.querySelector('[data-cart-open]');
    const cartKey = 'silaturizky-cart-v1';

    const money = new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    });

    const productById = new Map(
        products.map((product) => [String(product.id), product])
    );

    const quantityStep = (product) =>
        ['kg', 'liter'].includes(String(product.unit).toLowerCase())
            ? '0.001'
            : '1';

    const initialQuantity = (product) =>
        Math.min(1, Number(product.stock));

    let selectedCategory = 'all';
    let searchTerm = '';
    let cart = {};
    let returnFocusTo = null;

    const openCart = () => {
        if (!cartDrawer || !cartBackdrop) return;

        returnFocusTo = document.activeElement;
        cartDrawer.hidden = false;
        cartBackdrop.hidden = false;
        document.body.classList.add('cart-open');

        cartOpenButton?.setAttribute('aria-expanded', 'true');
        cartDrawer.querySelector('.cart-close')?.focus();
    };

    const closeCart = () => {
        if (!cartDrawer || !cartBackdrop) return;

        cartDrawer.hidden = true;
        cartBackdrop.hidden = true;
        document.body.classList.remove('cart-open');

        cartOpenButton?.setAttribute('aria-expanded', 'false');
        returnFocusTo?.focus();
    };

    cartOpenButton?.addEventListener('click', openCart);

    document.querySelectorAll('[data-cart-close]').forEach((button) => {
        button.addEventListener('click', closeCart);
    });

    document.addEventListener('keydown', (event) => {
        if (!cartDrawer || cartDrawer.hidden) return;

        if (event.key === 'Escape') {
            closeCart();
            return;
        }

        if (event.key === 'Tab') {
            const focusable = [
                ...cartDrawer.querySelectorAll(
                    'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled])'
                ),
            ].filter(
                (element) =>
                    !element.hidden
                    && element.getAttribute('type') !== 'hidden'
            );

            const first = focusable[0];
            const last = focusable[focusable.length - 1];

            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last?.focus();
            } else if (
                !event.shiftKey
                && document.activeElement === last
            ) {
                event.preventDefault();
                first?.focus();
            }
        }
    });

    if (window.location.hash === '#keranjang') {
        openCart();
    }

    const updateDeliveryFields = () => {
        if (!deliveryType || !shippingAddress) return;

        const isDelivery = deliveryType.value === 'delivery';

        deliveryType.closest('label')
            ?.parentElement
            ?.querySelector('[data-shipping-address]')
            ?.toggleAttribute('hidden', !isDelivery);

        shippingAddress.required = isDelivery;
    };

    deliveryType?.addEventListener('change', updateDeliveryFields);
    updateDeliveryFields();

    try {
        const saved = JSON.parse(localStorage.getItem(cartKey) || '{}');

        cart = Object.fromEntries(
            Object.entries(saved).filter(([id, quantity]) => {
                const product = productById.get(id);

                return product
                    && Number.isFinite(quantity)
                    && quantity > 0
                    && quantity <= product.stock;
            })
        );
    } catch {
        cart = {};
    }

    const escapeHtml = (value) =>
        String(value).replace(/[&<>"']/g, (character) => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;',
        })[character]);

    const renderProducts = () => {
        const visibleProducts = products.filter((product) => {
            const matchesCategory =
                selectedCategory === 'all'
                || product.category === selectedCategory;

            const matchesSearch =
                `${product.name} ${product.category} ${product.sku}`
                    .toLocaleLowerCase('id-ID')
                    .includes(searchTerm);

            return matchesCategory && matchesSearch;
        });

        storefront.querySelector('[data-empty-products]').hidden =
            visibleProducts.length > 0;

        productGrid.innerHTML = visibleProducts.map((product, index) => {
            const stock = Math.max(0, Number(product.stock));
            const isOut = stock <= 0;
            const isLow =
                !isOut && stock <= Number(product.min_stock || 0);

            const label = isOut
                ? 'Stok habis'
                : isLow
                    ? 'Stok menipis'
                    : 'Tersedia';

            return `
                <article class="product-card"
                    style="animation-delay:${Math.min(index * 35, 210)}ms">
                    <div class="product-image">
                        <img
                            src="${escapeHtml(product.image || 'https://images.unsplash.com/photo-1586201375761-83865001e31c?auto=format&fit=crop&w=720&q=85')}"
                            alt="${escapeHtml(product.name)}"
                            loading="lazy">
                        <span class="stock-tag ${
                            isOut ? 'is-out' : isLow ? 'is-low' : ''
                        }">${label}</span>
                    </div>

                    <div class="product-copy">
                        <span class="product-category">
                            ${escapeHtml(product.category)}
                        </span>

                        <h3 class="product-name">
                            ${escapeHtml(product.name)}
                        </h3>

                        <div class="product-bottom">
                            <strong class="product-price">
                                ${money.format(product.price)}
                                <span class="product-unit">
                                    / ${escapeHtml(product.unit)}
                                </span>
                            </strong>

                            <button
                                class="add-button"
                                type="button"
                                data-add="${escapeHtml(product.id)}"
                                aria-label="Tambah ${escapeHtml(product.name)} ke keranjang"
                                ${
                                    isOut
                                    || Number(cart[product.id] || 0) >= stock
                                        ? 'disabled'
                                        : ''
                                }>
                                +
                            </button>
                        </div>
                    </div>
                </article>
            `;
        }).join('');
    };

    const renderCart = () => {
        const entries = Object.entries(cart).filter(([id, quantity]) => {
            const product = productById.get(id);

            return product
                && Number.isFinite(Number(quantity))
                && Number(quantity) > 0
                && Number(quantity) <= Number(product.stock);
        });

        const count = entries.reduce(
            (total, [, quantity]) => total + quantity,
            0
        );

        const total = entries.reduce(
            (sum, [id, quantity]) =>
                sum + productById.get(id).price * quantity,
            0
        );

        document.querySelectorAll('[data-cart-count]').forEach((badge) => {
            badge.textContent = badge.classList.contains('cart-count')
                ? String(count)
                : `${count} item`;
        });

        cartTotal.textContent = money.format(total);
        checkoutButton.disabled = count === 0;

        cartLines.innerHTML = entries.length
            ? entries.map(([id, quantity]) => {
                const product = productById.get(id);

                return `
                    <div class="cart-line">
                        <div>
                            <div class="cart-line-name">
                                ${escapeHtml(product.name)}
                            </div>
                            <span class="cart-line-price">
                                ${money.format(product.price)}
                                / ${escapeHtml(product.unit)}
                            </span>
                        </div>

                        <strong class="cart-line-price">
                            ${money.format(product.price * quantity)}
                        </strong>

                        <div class="quantity-control">
                            <button
                                type="button"
                                data-quantity="${escapeHtml(id)}"
                                data-delta="-1"
                                aria-label="Kurangi ${escapeHtml(product.name)}">
                                −
                            </button>

                            <input
                                type="number"
                                data-quantity-input="${escapeHtml(id)}"
                                aria-label="Jumlah ${escapeHtml(product.name)}"
                                min="${quantityStep(product)}"
                                step="${quantityStep(product)}"
                                max="${Number(product.stock)}"
                                value="${Number(quantity)}">

                            <button
                                type="button"
                                data-quantity="${escapeHtml(id)}"
                                data-delta="1"
                                aria-label="Tambah ${escapeHtml(product.name)}"
                                ${
                                    quantity >= product.stock
                                        ? 'disabled'
                                        : ''
                                }>
                                +
                            </button>

                            <button
                                class="remove-line"
                                type="button"
                                data-remove="${escapeHtml(id)}">
                                Hapus
                            </button>
                        </div>
                    </div>
                `;
            }).join('')
            : `
                <div class="cart-empty">
                    <span aria-hidden="true">＋</span>
                    <p>Keranjangmu masih kosong.</p>
                    <small>Pilih kebutuhan yang ingin dibeli.</small>
                </div>
            `;

        itemFields.innerHTML = entries.map(
            ([id, quantity], index) => `
                <input type="hidden"
                    name="items[${index}][product_id]"
                    value="${escapeHtml(id)}">

                <input type="hidden"
                    name="items[${index}][quantity]"
                    value="${quantity}">
            `
        ).join('');

        try {
            localStorage.setItem(cartKey, JSON.stringify(cart));
        } catch {
            // Keranjang tetap aktif selama halaman ini terbuka.
        }

        renderProducts();
    };

    productGrid.addEventListener('click', (event) => {
        const button = event.target.closest('[data-add]');
        if (!button) return;

        const id = String(button.dataset.add);
        const product = productById.get(id);

        if (!product || Number(cart[id] || 0) >= product.stock) return;

        cart[id] = Number(cart[id] || 0) > 0
            ? Number(cart[id]) + 1
            : initialQuantity(product);

        renderCart();
    });

    cartLines.addEventListener('click', (event) => {
        const quantityButton = event.target.closest('[data-quantity]');
        const removeButton = event.target.closest('[data-remove]');

        if (removeButton) {
            delete cart[String(removeButton.dataset.remove)];
        }

        if (quantityButton) {
            const id = String(quantityButton.dataset.quantity);
            const product = productById.get(id);
            const next =
                Number(cart[id] || 0)
                + Number(quantityButton.dataset.delta);

            if (next <= 0) {
                delete cart[id];
            } else if (product && next <= product.stock) {
                cart[id] = next;
            }
        }

        renderCart();
    });

    cartLines.addEventListener('change', (event) => {
        const input = event.target.closest('[data-quantity-input]');
        if (!input) return;

        const id = String(input.dataset.quantityInput);
        const product = productById.get(id);
        const quantity = Number(input.value);

        if (
            !product
            || !Number.isFinite(quantity)
            || quantity <= 0
            || quantity > Number(product.stock)
            || (
                quantityStep(product) === '1'
                && !Number.isInteger(quantity)
            )
        ) {
            renderCart();
            return;
        }

        cart[id] = quantity;
        renderCart();
    });

    storefront.querySelector('[data-product-search]')
        .addEventListener('input', (event) => {
            searchTerm = event.target.value
                .trim()
                .toLocaleLowerCase('id-ID');

            renderProducts();
        });

    storefront.querySelector('[data-category-list]')
        .addEventListener('click', (event) => {
            const button = event.target.closest('[data-category]');
            if (!button) return;

            selectedCategory = button.dataset.category;

            storefront.querySelectorAll('[data-category]')
                .forEach((chip) => {
                    chip.classList.toggle('is-active', chip === button);
                });

            renderProducts();
        });

    document.querySelector('[data-checkout-form]')
        .addEventListener('submit', (event) => {
            if (Object.keys(cart).length === 0) {
                event.preventDefault();
                return;
            }

            checkoutButton.disabled = true;
            checkoutButton.textContent = 'Mengirim pesanan...';
        });

    renderCart();
}

document.querySelectorAll('[data-buy-again]').forEach((button) => {
    button.addEventListener('click', () => {
        const key = 'silaturizky-cart-v1';
        let canRestoreCart = false;

        try {
            let cart = {};

            try {
                const saved = JSON.parse(
                    localStorage.getItem(key) || '{}'
                );

                if (
                    saved
                    && typeof saved === 'object'
                    && !Array.isArray(saved)
                ) {
                    cart = saved;
                }
            } catch {
                cart = {};
            }

            const items = JSON.parse(
                button.dataset.buyAgain || '[]'
            );

            if (!Array.isArray(items)) {
                throw new Error(
                    'Format pembelian ulang tidak valid.'
                );
            }

            items.forEach((item) => {
                const id = String(item.product_id ?? '');
                const quantity = Number(item.quantity);

                if (
                    id
                    && Number.isFinite(quantity)
                    && quantity > 0
                ) {
                    cart[id] =
                        Number(cart[id] || 0) + quantity;
                }
            });

            localStorage.setItem(key, JSON.stringify(cart));
            canRestoreCart = true;
        } catch {
            // Tetap arahkan pengguna ke toko jika penyimpanan gagal.
        }

        window.location.assign(
            `${button.dataset.storeUrl}${
                canRestoreCart ? '#keranjang' : '#produk'
            }`
        );
    });
});

if (document.querySelector('[data-order-confirmation]')) {
    try {
        localStorage.removeItem('silaturizky-cart-v1');
    } catch {
        // Tidak mengganggu halaman konfirmasi.
    }
}

const pos = document.querySelector('[data-pos]');

if (pos) {
    const products = JSON.parse(
        pos.querySelector('[data-pos-products]').textContent
    );

    pos.querySelector('[data-pos-tablist]')
        ?.addEventListener('click', (event) => {
            const tab = event.target.closest('[data-pos-tab]');
            if (!tab) return;

            pos.querySelectorAll('[data-pos-tab]').forEach((button) => {
                const selected = button === tab;
                button.classList.toggle('is-active', selected);
                button.setAttribute(
                    'aria-selected',
                    selected ? 'true' : 'false'
                );
            });

            pos.querySelectorAll('[data-pos-tab-panel]')
                .forEach((panel) => {
                    panel.hidden =
                        panel.dataset.posTabPanel !== tab.dataset.posTab;
                });
        });

    const productById = new Map(
        products.map((product) => [String(product.id), product])
    );

    const quantityStep = (product) =>
        ['kg', 'liter'].includes(String(product.unit).toLowerCase())
            ? '0.001'
            : '1';

    const initialQuantity = (product) =>
        Math.min(1, Number(product.stock));

    const grid = pos.querySelector('[data-pos-grid]');
    const lines = pos.querySelector('[data-pos-lines]');
    const totalOutput = pos.querySelector('[data-pos-total]');
    const changeOutput = pos.querySelector('[data-pos-change]');
    const paidInput = pos.querySelector('[data-pos-paid]');
    const paymentMethod = pos.querySelector(
        '[data-pos-payment-method]'
    );
    const submitButton = pos.querySelector('[data-pos-submit]');
    const errorOutput = pos.querySelector('[data-pos-payment-error]');
    const qrisConfirmation = pos.querySelector(
        '[data-qris-confirmation]'
    );
    const qrisVerified = pos.querySelector('[data-qris-verified]');
    const qrisError = pos.querySelector('[data-qris-error]');
    const itemFields = pos.querySelector('[data-pos-item-fields]');

    const money = new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    });

    let category = 'all';
    let search = '';
    const cart = {};

    const escapeHtml = (value) =>
        String(value).replace(/[&<>"']/g, (character) => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;',
        })[character]);

    const imageByCategory = {
        Beras: 'photo-1586201375761-83865001e31c',
        'Minyak Goreng': 'photo-1474979266404-7eaacbcd87c5',
        Telur: 'photo-1518569656558-1f25e69d93d7',
        Gula: 'photo-1558642452-9d2a7deb7f62',
        'Gas LPG': 'photo-1603398938378-e54eab446dde',
    };

    const imageFor = (product) =>
        product.image
        || `https://images.unsplash.com/${
            imageByCategory[product.category] || imageByCategory.Beras
        }?auto=format&fit=crop&w=520&q=75`;

    const renderGrid = () => {
        const visible = products.filter((product) => {
            const categoryMatch =
                category === 'all' || product.category === category;

            const searchMatch =
                `${product.name} ${product.sku}`
                    .toLocaleLowerCase('id-ID')
                    .includes(search);

            return categoryMatch && searchMatch;
        });

        pos.querySelector('[data-pos-empty]').hidden =
            visible.length > 0;

        grid.innerHTML = visible.map((product) => {
            const stock = Number(product.stock);
            const low =
                stock > 0
                && stock <= Number(product.min_stock || 0);

            return `
                <button type="button" class="pos-product"
                    data-pos-add="${escapeHtml(product.id)}"
                    ${stock <= 0 ? 'disabled' : ''}>
                    <span class="pos-product-image">
                        <img src="${escapeHtml(imageFor(product))}"
                            alt="" loading="lazy">
                        <i class="${
                            stock <= 0
                                ? 'is-out'
                                : low
                                    ? 'is-low'
                                    : ''
                        }"></i>
                    </span>

                    <span class="pos-product-text">
                        <strong>${escapeHtml(product.name)}</strong>
                        <small>
                            ${escapeHtml(product.sku)}
                            · Stok ${stock} ${escapeHtml(product.unit)}
                        </small>
                        <b>
                            ${money.format(product.price)}
                            <em>/ ${escapeHtml(product.unit)}</em>
                        </b>
                    </span>

                    <span class="pos-add-mark" aria-hidden="true">+</span>
                </button>
            `;
        }).join('');
    };

    const getCartItem = (id) => {
        const item = cart[id];
        if (!item) return null;
        if (typeof item === 'number') {
            return { quantity: item, unitId: null };
        }
        return item;
    };

    const renderCart = () => {
        const entries = Object.keys(cart)
            .map((id) => [id, getCartItem(id)])
            .filter(([, item]) => item && item.quantity > 0);

        const count = entries.reduce(
            (sum, [, item]) => sum + item.quantity,
            0
        );

        const total = entries.reduce((sum, [id, item]) => {
            const product = productById.get(id);
            if (!product) return sum;
            const unitObj = item.unitId && product.units
                ? product.units.find((u) => String(u.id) === String(item.unitId))
                : null;
            const price = unitObj ? unitObj.price : product.price;
            return sum + price * item.quantity;
        }, 0);

        if (paymentMethod.value === 'qris') {
            paidInput.value = total;
        }

        const paid = Number(paidInput.value || 0);
        const isQris = paymentMethod.value === 'qris';

        const verifiedQris =
            !isQris || Boolean(qrisVerified?.checked);

        const validPayment =
            count > 0 && paid >= total && verifiedQris;

        if (qrisConfirmation) {
            qrisConfirmation.hidden = !isQris;
        }

        if (qrisError) {
            qrisError.hidden =
                !isQris || Boolean(qrisVerified?.checked) || count === 0;
        }

        pos.querySelector('[data-pos-count]').textContent =
            `${count} item`;

        totalOutput.textContent = money.format(total);

        changeOutput.textContent = money.format(
            Math.max(0, paid - total)
        );

        errorOutput.hidden = count === 0 || paid >= total;
        submitButton.disabled = !validPayment;

        itemFields.innerHTML = entries.map(
            ([id, item], index) => `
                <input type="hidden"
                    name="items[${index}][product_id]"
                    value="${escapeHtml(id)}">

                <input type="hidden"
                    name="items[${index}][product_unit_id]"
                    value="${item.unitId ? escapeHtml(item.unitId) : ''}">

                <input type="hidden"
                    name="items[${index}][quantity]"
                    value="${item.quantity}">
            `
        ).join('');

        lines.innerHTML = entries.length
            ? entries.map(([id, item]) => {
                const product = productById.get(id);
                const hasUnits = product.units && product.units.length > 0;
                const unitObj = item.unitId && hasUnits
                    ? product.units.find((u) => String(u.id) === String(item.unitId))
                    : null;
                const activePrice = unitObj ? unitObj.price : product.price;
                const activeUnitName = unitObj ? unitObj.unit_name : product.unit;
                const factor = unitObj ? unitObj.conversion_factor : 1;
                const maxUnitQty = factor > 1
                    ? Math.floor(product.stock / factor)
                    : Number(product.stock);

                return `
                    <div class="pos-line">
                        <div>
                            <strong>${escapeHtml(product.name)}</strong>
                            <small>
                                ${money.format(activePrice)}
                                / ${escapeHtml(activeUnitName)}
                            </small>
                            ${hasUnits ? `
                                <div style="margin-top: 4px;">
                                    <select data-pos-unit-select="${escapeHtml(id)}"
                                        aria-label="Pilih satuan ${escapeHtml(product.name)}"
                                        style="font-size: 11px; padding: 2px 6px; border: 1px solid #cbd5e1; border-radius: 6px; background: #f8fafc; color: #1e293b; cursor: pointer;">
                                        <option value="" ${!item.unitId ? 'selected' : ''}>
                                            ${escapeHtml(product.unit)} (${money.format(product.price)})
                                        </option>
                                        ${product.units.map((u) => `
                                            <option value="${escapeHtml(u.id)}" ${String(item.unitId) === String(u.id) ? 'selected' : ''}>
                                                ${escapeHtml(u.unit_name)} (${money.format(u.price)}) [isi ${u.conversion_factor} ${escapeHtml(product.unit)}]
                                            </option>
                                        `).join('')}
                                    </select>
                                </div>
                            ` : ''}
                        </div>

                        <div class="pos-line-quantity">
                            <button type="button"
                                data-pos-quantity="${escapeHtml(id)}"
                                data-pos-delta="-1"
                                aria-label="Kurangi">−</button>

                            <input type="number"
                                data-pos-quantity-input="${escapeHtml(id)}"
                                aria-label="Jumlah ${escapeHtml(product.name)}"
                                min="${unitObj ? '1' : quantityStep(product)}"
                                step="${unitObj ? '1' : quantityStep(product)}"
                                max="${maxUnitQty}"
                                value="${Number(item.quantity)}">

                            <button type="button"
                                data-pos-quantity="${escapeHtml(id)}"
                                data-pos-delta="1"
                                aria-label="Tambah"
                                ${item.quantity >= maxUnitQty ? 'disabled' : ''}>
                                +
                            </button>
                        </div>

                        <b>${money.format(activePrice * item.quantity)}</b>
                    </div>
                `;
            }).join('')
            : `
                <div class="cart-empty">
                    <span aria-hidden="true">＋</span>
                    <p>Belum ada produk</p>
                    <small>Pilih produk untuk menambahkan</small>
                </div>
            `;
    };

    grid.addEventListener('click', (event) => {
        const button = event.target.closest('[data-pos-add]');
        if (!button) return;

        const id = String(button.dataset.posAdd);
        const product = productById.get(id);

        if (product && product.stock > 0) {
            const current = getCartItem(id);
            if (current) {
                const unitObj = current.unitId && product.units
                    ? product.units.find((u) => String(u.id) === String(current.unitId))
                    : null;
                const factor = unitObj ? unitObj.conversion_factor : 1;
                const maxQty = factor > 1 ? Math.floor(product.stock / factor) : product.stock;

                if (current.quantity < maxQty) {
                    current.quantity += 1;
                    cart[id] = current;
                }
            } else {
                cart[id] = { quantity: initialQuantity(product), unitId: null };
            }
        }

        renderCart();
    });

    lines.addEventListener('click', (event) => {
        const button = event.target.closest('[data-pos-quantity]');
        if (!button) return;

        const id = String(button.dataset.posQuantity);
        const product = productById.get(id);
        const current = getCartItem(id);
        if (!current || !product) return;

        const delta = Number(button.dataset.posDelta);
        const unitObj = current.unitId && product.units
            ? product.units.find((u) => String(u.id) === String(current.unitId))
            : null;
        const factor = unitObj ? unitObj.conversion_factor : 1;
        const maxQty = factor > 1 ? Math.floor(product.stock / factor) : product.stock;

        const newQty = current.quantity + delta;

        if (newQty <= 0) {
            delete cart[id];
        } else if (newQty <= maxQty) {
            current.quantity = newQty;
            cart[id] = current;
        }

        renderCart();
    });

    lines.addEventListener('change', (event) => {
        const unitSelect = event.target.closest('[data-pos-unit-select]');
        if (unitSelect) {
            const id = String(unitSelect.dataset.posUnitSelect);
            const current = getCartItem(id);
            if (current) {
                current.unitId = unitSelect.value ? unitSelect.value : null;
                current.quantity = 1;
                cart[id] = current;
                renderCart();
            }
            return;
        }

        const input = event.target.closest('[data-pos-quantity-input]');
        if (!input) return;

        const id = String(input.dataset.posQuantityInput);
        const product = productById.get(id);
        const current = getCartItem(id);
        const quantity = Number(input.value);

        if (!product || !current || !Number.isFinite(quantity) || quantity <= 0) {
            renderCart();
            return;
        }

        const unitObj = current.unitId && product.units
            ? product.units.find((u) => String(u.id) === String(current.unitId))
            : null;
        const factor = unitObj ? unitObj.conversion_factor : 1;
        const maxQty = factor > 1 ? Math.floor(product.stock / factor) : product.stock;

        if (quantity > maxQty) {
            renderCart();
            return;
        }

        current.quantity = quantity;
        cart[id] = current;
        renderCart();
    });

    const searchInput = pos.querySelector('[data-pos-search]');
    if (searchInput) {
        searchInput.addEventListener('input', (event) => {
            search = event.target.value
                .trim()
                .toLocaleLowerCase('id-ID');

            renderGrid();
        });

        // Dukungan Barcode Scanner: Tekan Enter untuk langsung memasukkan barang ke keranjang
        searchInput.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                const term = searchInput.value.trim().toLocaleLowerCase('id-ID');
                if (!term) return;

                const matched = products.find((p) =>
                    p.sku.toLocaleLowerCase('id-ID') === term
                    || p.name.toLocaleLowerCase('id-ID') === term
                ) || products.find((p) =>
                    p.sku.toLocaleLowerCase('id-ID').includes(term)
                    || p.name.toLocaleLowerCase('id-ID').includes(term)
                );

                if (matched && Number(matched.stock) > 0) {
                    const current = getCartItem(matched.id);
                    if (current) {
                        current.quantity += 1;
                        cart[matched.id] = current;
                    } else {
                        cart[matched.id] = { quantity: initialQuantity(matched), unitId: null };
                    }
                    searchInput.value = '';
                    search = '';
                    renderGrid();
                    renderCart();
                }
            }
        });
    }

    pos.querySelector('[data-pos-categories]')
        .addEventListener('click', (event) => {
            const button = event.target.closest('[data-pos-category]');
            if (!button) return;

            category = button.dataset.posCategory;

            pos.querySelectorAll('[data-pos-category]')
                .forEach((chip) => {
                    chip.classList.toggle('is-active', chip === button);
                });

            renderGrid();
        });

    paidInput.addEventListener('input', renderCart);
    qrisVerified?.addEventListener('change', renderCart);

    pos.querySelectorAll('[data-pos-payment]').forEach((button) => {
        button.addEventListener('click', () => {
            paymentMethod.value = button.dataset.posPayment;

            pos.querySelectorAll('[data-pos-payment]')
                .forEach((option) => {
                    option.classList.toggle(
                        'is-active',
                        option === button
                    );
                });

            paidInput.readOnly = paymentMethod.value === 'qris';

            if (paymentMethod.value === 'qris') {
                if (qrisVerified) {
                    qrisVerified.checked = false;
                }

                const total = Object.keys(cart).reduce((sum, id) => {
                    const item = getCartItem(id);
                    if (!item || item.quantity <= 0) return sum;
                    const product = productById.get(id);
                    if (!product) return sum;
                    const unitObj = item.unitId && product.units
                        ? product.units.find((u) => String(u.id) === String(item.unitId))
                        : null;
                    const price = unitObj ? unitObj.price : product.price;
                    return sum + price * item.quantity;
                }, 0);

                paidInput.value = total;
            } else {
                paidInput.value = '';
            }

            renderCart();
        });
    });

    pos.querySelectorAll('[data-cash-value]').forEach((button) => {
        button.addEventListener('click', () => {
            const currentTotal = Object.keys(cart).reduce((sum, id) => {
                const item = getCartItem(id);
                if (!item || item.quantity <= 0) return sum;
                const product = productById.get(id);
                if (!product) return sum;
                const unitObj = item.unitId && product.units
                    ? product.units.find((u) => String(u.id) === String(item.unitId))
                    : null;
                const price = unitObj ? unitObj.price : product.price;
                return sum + price * item.quantity;
            }, 0);

            paidInput.value =
                button.dataset.cashValue === 'exact'
                    ? currentTotal
                    : button.dataset.cashValue;

            renderCart();
        });
    });

    pos.querySelector('[data-pos-form]')
        .addEventListener('submit', (event) => {
            if (submitButton.disabled) {
                event.preventDefault();
                return;
            }

            submitButton.disabled = true;
            submitButton.textContent = 'Menyimpan transaksi...';
        });

    renderGrid();
    renderCart();
}