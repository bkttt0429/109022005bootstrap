(function(){
  'use strict';

  const PRODUCTS = window.SHOP_PRODUCTS || [];

  // util: get cart from localStorage (sync) — keep for fast UI updates and fallback
  function getCart(){
    try { return JSON.parse(localStorage.getItem('shop_cart')||'{}'); } catch(e){ return {}; }
  }
  function saveCart(cart){ localStorage.setItem('shop_cart', JSON.stringify(cart)); }

  const API_CART = 'api/cart.php';
  const API_AUTH = 'api/auth.php';
  let csrfToken = null;
  let currentUser = null;
  async function serverGetCart(){
    const res = await fetch(API_CART, { method: 'GET', credentials: 'same-origin' });
    if(!res.ok) throw new Error('server error');
    const body = await res.json();
    if(!body.success) throw new Error(body.error || 'unknown');
    return body.cart || {};
  }
  async function serverModify(action, payload){
    payload = payload || {};
    payload.action = action;
    const headers = { 'Content-Type':'application/json' };
    if(csrfToken) headers['X-CSRF-Token'] = csrfToken;

    const res = await fetch(API_CART, {
      method: 'POST',
      credentials: 'same-origin',
      headers,
      body: JSON.stringify(payload)
    });
    if(!res.ok) throw new Error('server error');
    const body = await res.json();
    if(!body.success) throw new Error(body.error || 'unknown');
    return body.cart || {};
  }

  // header cart count update
  function updateCartCount(){
    // immediate local update
    try{ const cart = getCart(); const count = Object.values(cart).reduce((s,v)=>s+v,0); const el = document.getElementById('cart-count'); if(el) el.textContent = count; }catch(e){}
    // try server authoritative value and resync
    serverGetCart().then(serverCart=>{ try{ const el = document.getElementById('cart-count'); if(el) el.textContent = Object.values(serverCart).reduce((s,v)=>s+v,0); saveCart(serverCart); }catch(e){} }).catch(()=>{});
  }

  // render product cards for home / products
  function renderProductsGrid(containerId, items){
    const container = document.getElementById(containerId);
    if(!container) return;
    container.innerHTML = '';
    items.forEach(p=>{
      const col = document.createElement('div'); col.className='col';
      col.innerHTML = `
        <div class="card h-100 shadow-sm">
          <img src="${p.image}" class="card-img-top" alt="${p.title}" />
          <div class="card-body d-flex flex-column">
            <h5 class="card-title">${p.title}</h5>
            <p class="card-text text-muted small">${p.description}</p>
            <div class="mt-auto d-flex justify-content-between align-items-center">
              <div class="text-primary fw-semibold">$${p.price.toFixed(2)}</div>
              <div class="btn-group">
                <a class="btn btn-sm btn-outline-secondary" href="product.html?id=${p.id}">詳情</a>
                <button class="btn btn-sm btn-primary add-to-cart" data-id="${p.id}">加入購物車</button>
              </div>
            </div>
          </div>
        </div>`;
      container.appendChild(col);
    });

    // attach add handlers
    container.querySelectorAll('.add-to-cart').forEach(btn=>{
      btn.addEventListener('click', (e)=>{
        const id = btn.getAttribute('data-id');
        addToCart(Number(id),1);
      });
    });
  }

  // add cart
  function addToCart(id, qty){
    // update local immediately for snappy UI
    const cart = getCart(); cart[id] = (cart[id]||0)+qty; saveCart(cart); updateCartCount(); alert('已加入購物車');
    // attempt to persist server-side, but don't block UI
    serverModify('add', { product_id: id, quantity: qty }).then(serverCart=>{ try{ saveCart(serverCart); updateCartCount(); }catch(e){} }).catch(()=>{});
  }

  // render home samples (3 items)
  function renderHomeSamples(){
    const samples = document.getElementById('home-samples');
    if(!samples) return;
    const items = PRODUCTS.slice(0,3);
    renderProductsGrid('home-samples', items);
  }

  // product listing page
  function initProductsPage(){
    renderProductsGrid('product-grid', PRODUCTS);
  }

  // parse query param
  function getQueryParam(name){
    const params = new URLSearchParams(location.search);
    return params.get(name);
  }

  // product detail
  function initProductDetail(){
    const id = parseInt(getQueryParam('id'))||0;
    const p = PRODUCTS.find(x=>x.id===id);
    const el = document.getElementById('product-detail');
    if(!el) return;
    if(!p){ el.innerHTML = '<div class="col-12"><div class="alert alert-warning">找不到商品</div></div>'; return; }

    el.innerHTML = `
      <div class="col-md-6">
        <img src="${p.image}" alt="${p.title}" class="img-fluid rounded" />
      </div>
      <div class="col-md-6">
        <h2>${p.title}</h2>
        <p class="text-muted">${p.description}</p>
        <div class="mb-3"><strong>$${p.price.toFixed(2)}</strong></div>
        <div class="d-flex gap-2 align-items-center">
          <input id="pd-qty" type="number" value="1" min="1" style="width:80px" class="form-control form-control-sm" />
          <button id="pd-add" class="btn btn-primary">加入購物車</button>
        </div>
      </div>
    `;

    document.getElementById('pd-add').addEventListener('click', ()=>{
      const qty = Number(document.getElementById('pd-qty').value)||1;
      addToCart(p.id, qty);
    });
  }

  // cart page
  function renderCart(){
    const el = document.getElementById('cart-container');
    if(!el) return;
    let cart = {};
    try{
      const serverCart = await (async()=>{ try{ return await serverGetCart(); }catch(e){ return null; } })();
      cart = serverCart || getCart();
    }catch(e){ cart = getCart(); }
    const ids = Object.keys(cart).map(Number);
    if(ids.length===0){ el.innerHTML = '<div class="alert alert-info">購物車空空如也</div>'; return; }

    let html = '<table class="table"><thead><tr><th>商品</th><th>數量</th><th>單價</th><th>小計</th><th></th></tr></thead><tbody>';
    let total = 0;

    ids.forEach(id=>{
      const p = PRODUCTS.find(x=>x.id===id);
      const qty = cart[id];
      const subtotal = (p.price*qty);
      total += subtotal;
      html += `<tr data-id="${id}"><td>${p.title}</td><td><input class="form-control form-control-sm qty-input" value="${qty}" style="width:80px" /></td><td>$${p.price.toFixed(2)}</td><td>$${subtotal.toFixed(2)}</td><td><button class="btn btn-sm btn-outline-danger remove-item">移除</button></td></tr>`;
    });

    html += `</tbody></table><div class="d-flex justify-content-between align-items-center"><strong>總計：$${total.toFixed(2)}</strong><div><button id="checkout-start" class="btn btn-success">結帳</button></div></div>`;

    el.innerHTML = html;

    // attach events
    el.querySelectorAll('.remove-item').forEach(btn=> btn.addEventListener('click', async ()=>{
      const id = Number(btn.closest('tr').getAttribute('data-id'));
      try{ await serverModify('remove', { product_id: id }); }catch(e){}
      const cart = getCart(); delete cart[id]; saveCart(cart); renderCart(); updateCartCount();
    }));

    el.querySelectorAll('.qty-input').forEach(input=> input.addEventListener('change', async ()=>{
      const newQty = Number(input.value)||1;
      const id = Number(input.closest('tr').getAttribute('data-id'));
      try{ await serverModify('update', { product_id: id, quantity: newQty }); }catch(e){}
      const cart = getCart(); cart[id] = newQty; saveCart(cart); renderCart(); updateCartCount();
    }));

    const checkoutBtn = document.getElementById('checkout-start');
    if(checkoutBtn){ checkoutBtn.addEventListener('click', ()=>{
      document.getElementById('checkout-form').classList.remove('d-none');
      checkoutBtn.disabled = true;
    }); }

  }

  // checkout handlers
  function initCheckout(){
    const form = document.getElementById('checkout');
    if(!form) return;
    form.addEventListener('submit', async (e)=>{
      e.preventDefault();
      // simulate order — clear both server and local
      try{ await serverModify('clear'); }catch(e){}
      localStorage.removeItem('shop_cart');
      updateCartCount();
      alert('感謝訂購 — 訂單已建立（模擬）');
      document.getElementById('cart-container').innerHTML = '<div class="alert alert-success">訂單建立（模擬）</div>';
      document.getElementById('checkout-form').classList.add('d-none');
    });

    const cancelBtn = document.getElementById('cancel-checkout');
    if(cancelBtn) cancelBtn.addEventListener('click', ()=>{
      document.getElementById('checkout-form').classList.add('d-none');
      const btn = document.getElementById('checkout-start'); if(btn) btn.disabled = false;
    });
  }

  // init script routing
  document.addEventListener('DOMContentLoaded', ()=>{
    function updateAuthUI(){
      const authEl = document.getElementById('auth-area');
      if(!authEl) return;
      if(currentUser){
        authEl.innerHTML = `<span class="me-2">${currentUser.name || currentUser.email}</span><button id="logout-btn" class="btn btn-sm btn-outline-light">登出</button>`;
        document.getElementById('logout-btn').addEventListener('click', async ()=>{
          await fetch(API_AUTH, {method:'POST', credentials:'same-origin', headers: {'Content-Type':'application/json','X-CSRF-Token':csrfToken}, body: JSON.stringify({action:'logout'})});
          currentUser=null; location.reload();
        });
      } else {
        authEl.innerHTML = `<a class="btn btn-outline-light" href="signin.html">登入</a>`;
      }
    }

    // fetch auth info & csrf token then update UI
    fetch(API_AUTH, { credentials: 'same-origin' }).then(r=>r.json()).then(b=>{ if(b && b.success){ csrfToken = b.csrf || null; currentUser = b.user || null; } }).then(()=>{ updateCartCount(); updateAuthUI(); }).catch(()=>{ updateCartCount(); updateAuthUI(); });

    // home
    if(document.getElementById('home-samples')){
      renderHomeSamples();
    }

    // products page
    if(document.getElementById('product-grid')){
      initProductsPage();
    }

    // individual product
    if(document.getElementById('product-detail')){
      initProductDetail();
    }

    // cart page
    if(document.getElementById('cart-container')){
      renderCart();
      initCheckout();
    }

    // globally handle add-to-cart links
    document.body.addEventListener('click', (e)=>{
      if(e.target.matches('.add-to-cart')){
        e.preventDefault();
        const id = Number(e.target.getAttribute('data-id')); addToCart(id,1);
      }
    });

    // product made clickable from cards
    updateCartCount();

    // auth UI handled by updateAuthUI after auth fetch completes

  });

})();
