function $(s, r=document){ return r.querySelector(s); }
function $all(s, r=document){ return Array.from(r.querySelectorAll(s)); }

function bootProductGallery(){
    // JSON blob: <script type="application/json" id="images-by-color">{...}</script>
    const blob = $('#images-by-color');
    const map  = blob ? JSON.parse(blob.textContent || '{}') : {};

    const main     = $('#hero-image');                        // was #main-img
    const thumbs   = $('#thumbs');
    const typeSel  = $('#img-type');                          // optional
    const swatches = $all('#color-swatch-grid .color-swatch'); // was #color-swatches .swatch
    const hiddenId = $('#active-color-id');

    let currentColor = hiddenId?.value
        || swatches[0]?.dataset.colorId
        || Object.keys(map)[0]
        || null;

    let currentType = typeSel?.value || 'default';

    function filtered(color, type){
        const arr = map[color] || [];
        const byType = arr.filter(i => (i.type || 'default') === type);
        return byType.length ? byType : arr;
    }

    function render(color=currentColor, type=currentType){
        const imgs = filtered(color, type);
        if(!imgs.length){ if(main){ main.src=''; main.alt=''; } if(thumbs){ thumbs.innerHTML=''; } return; }
        if(main){ main.src = imgs[0].url; main.alt = imgs[0].alt || ''; }
        if(thumbs){
            thumbs.innerHTML = imgs.map((i,idx)=>`
        <button type="button" class="thumb btn p-0 border-0">
          <img src="${i.url}" alt="${i.alt||''}" data-idx="${idx}"
               class="img-thumbnail" style="width:64px;height:64px;object-fit:cover">
        </button>`).join('');
        }
    }

    swatches.forEach(btn=>{
        btn.addEventListener('click', ()=>{
            currentColor = btn.dataset.colorId;                   // was dataset.color
            swatches.forEach(b=>b.classList.toggle('ring-2', b===btn));
            if(hiddenId) hiddenId.value = currentColor;
            render(currentColor, currentType);
        });
    });

    typeSel?.addEventListener('change', ()=>{
        currentType = typeSel.value;
        render(currentColor, currentType);
    });

    thumbs?.addEventListener('click', (e)=>{
        const img = e.target.closest('img');
        if(!img || !main) return;
        main.src = img.src;
        main.alt = img.alt || '';
    });

    render(currentColor, currentType);
}

document.addEventListener('DOMContentLoaded', bootProductGallery);
