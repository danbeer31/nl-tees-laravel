(function(){
    const csrf   = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const pidEl  = document.getElementById('product-id');
    const baseEl = document.getElementById('base-price');

    const colorGrid = document.getElementById('color-swatch-grid');
    const sizeSelect = document.getElementById('size-select');
    const priceDisplay = document.getElementById('price-display');
    const activeColorName = document.getElementById('active-color-name');

    const activeColorIdEl = document.getElementById('active-color-id');
    const activeColorDeltaEl = document.getElementById('active-color-delta');

    const heroImg = document.getElementById('hero-image');
    const thumbs = document.getElementById('thumbs');

    function formatMoney(num){
        return '$' + (Number(num || 0).toFixed(2));
    }

    function recomputePrice(){
        const base = Number(baseEl.value || 0);
        const colorDelta = Number(activeColorDeltaEl.value || 0);
        const sizeDelta = Number(sizeSelect?.selectedOptions?.[0]?.dataset?.priceDelta || 0);
        const qty = Number(document.getElementById('qty')?.value || 1);
        const unit = base + colorDelta + sizeDelta;
        priceDisplay.textContent = formatMoney(unit * qty);
    }

    function wireThumbClicks(){
        if (!thumbs) return;
        thumbs.querySelectorAll('img').forEach(img => {
            img.addEventListener('click', () => { if (heroImg) heroImg.src = img.src; });
        });
    }

    async function loadColor(colorId){
        const productId = pidEl?.value;
        if (!productId || !colorId) return;

        try {
            const res = await fetch('/product/color-data', {
                method: 'POST',
                headers: {
                    'Content-Type':'application/json',
                    'X-CSRF-TOKEN': csrf
                },
                body: JSON.stringify({ product_id: Number(productId), color_id: Number(colorId) })
            });
            const json = await res.json();
            if (!json.ok) {
                console.error(json.error || 'Failed loading color data');
                return;
            }

            const { color, images, sizes } = json.data;

            // Update state
            activeColorIdEl.value = color.id;
            activeColorDeltaEl.value = Number(color.price_delta || 0);
            if (activeColorName) activeColorName.textContent = color.name || '';

            // Update gallery
            if (thumbs) thumbs.innerHTML = '';
            if (images && images.length){
                if (heroImg) heroImg.src = images[0].url || images[0].path || heroImg.src;
                images.forEach(img => {
                    const t = document.createElement('img');
                    t.src = img.url || img.path || '';
                    t.alt = img.alt || '';
                    t.className = 'border rounded';
                    t.style.width = '72px';
                    t.style.height = '72px';
                    t.style.objectFit = 'cover';
                    t.style.cursor = 'pointer';
                    thumbs?.appendChild(t);
                });
            }
            wireThumbClicks();

            // Update sizes
            if (sizeSelect){
                sizeSelect.innerHTML = '';
                if (sizes && sizes.length){
                    sizes.forEach(s => {
                        const opt = document.createElement('option');
                        opt.value = s.id;
                        opt.textContent = s.name + (s.price_delta ? ` (${s.price_delta > 0 ? '+' : ''}${formatMoney(Math.abs(s.price_delta))})` : '');
                        opt.dataset.priceDelta = String(s.price_delta || 0);
                        if (s.in_stock === false) { opt.textContent += ' — Out of stock'; opt.disabled = true; }
                        sizeSelect.appendChild(opt);
                    });
                    sizeSelect.disabled = false;
                } else {
                    const opt = document.createElement('option');
                    opt.value = '';
                    opt.textContent = 'No sizes for this color';
                    sizeSelect.appendChild(opt);
                    sizeSelect.disabled = true;
                }
            }

            recomputePrice();
        } catch (e) {
            console.error(e);
        }
    }

    function setActiveSwatch(btn){
        colorGrid.querySelectorAll('.color-swatch').forEach(b => {
            b.classList.remove('ring-2');
            b.style.boxShadow = '';
        });
        btn.classList.add('ring-2');
        btn.style.boxShadow = '0 0 0 3px rgba(13,110,253,.5)'; // bootstrap-ish ring
    }

    colorGrid?.addEventListener('click', (e) => {
        const b = e.target.closest('.color-swatch');
        if (!b) return;
        setActiveSwatch(b);
        const id = b.getAttribute('data-color-id');
        loadColor(id);
    });

    sizeSelect?.addEventListener('change', recomputePrice);
    document.getElementById('qty')?.addEventListener('input', recomputePrice);

    wireThumbClicks();
    recomputePrice();
})();
