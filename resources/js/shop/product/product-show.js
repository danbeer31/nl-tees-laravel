// resources/js/product-show.js
document.addEventListener('DOMContentLoaded', () => {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    async function loadColor(colorId, productId){
        const res = await fetch('/product/color-data', {
            method: 'POST',
            headers: {
                'Content-Type':'application/json',
                'X-CSRF-TOKEN': csrf
            },
            body: JSON.stringify({ product_id: Number(productId), color_id: Number(colorId) })
        });
        const json = await res.json();
        if (!json.ok) return console.error(json.error || 'Failed color data');

        // TODO: update hero image, thumbs, size select, and price just like we outlined
    }

    // TODO: wire up your swatch click handlers to call loadColor(...)
});
