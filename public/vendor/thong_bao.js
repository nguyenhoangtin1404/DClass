/*
 * thong_bao.js — Hộp thoại dùng chung cho toàn app (thay alert()/confirm() gốc).
 *
 * API:
 *   await thongBao(noiDung, tuyChon?)  -> Promise<void>    (thay alert)
 *   await xacNhan(noiDung, tuyChon?)   -> Promise<boolean>  (thay confirm)
 *   toast(noiDung, {loai, thoiGian}?)  -> {dong}            (thông báo nhanh, tự tắt)
 *
 * tuyChon = {
 *   tieuDe:    string,                       // tiêu đề, mặc định theo loại
 *   loai:      'info'|'success'|'canh_bao'|'nguy_hiem'|'hoi',
 *   nhanDongY: string,                       // nhãn nút đồng ý (mặc định 'OK' / 'Đồng ý')
 *   nhanHuy:   string,                       // nhãn nút huỷ (mặc định 'Huỷ')
 *   nguyHiem:  boolean                       // nút đồng ý màu đỏ, focus mặc định vào Huỷ
 * }
 *
 * Không phụ thuộc Bootstrap JS. Tự chèn style + DOM, khoá cuộn nền, bẫy focus,
 * Esc = huỷ, Enter = đồng ý. Nhiều lời gọi liên tiếp được xếp hàng.
 */
(function () {
  'use strict';
  if (window.__dc_thongbao_loaded) return;
  window.__dc_thongbao_loaded = true;

  var CSS = [
    '.dc-modal-overlay{position:fixed;inset:0;z-index:2000;display:flex;align-items:center;justify-content:center;',
    'padding:16px;background:rgba(15,23,42,.55);backdrop-filter:blur(3px);-webkit-backdrop-filter:blur(3px);',
    'opacity:0;transition:opacity .12s ease}',
    '.dc-modal-overlay.dc-show{opacity:1}',
    '.dc-modal{width:100%;max-width:420px;background:#fff;border:1px solid #e7e0d6;border-radius:18px;',
    'box-shadow:0 24px 60px rgba(15,40,90,.28);overflow:hidden;transform:translateY(8px) scale(.94);',
    'opacity:0;transition:transform .18s cubic-bezier(.2,.9,.3,1.2),opacity .18s ease;',
    "font-family:var(--bs-body-font-family,'Nunito',system-ui,-apple-system,'Segoe UI',Roboto,sans-serif);color:#0a1a2f}",
    '.dc-modal-overlay.dc-show .dc-modal{transform:translateY(0) scale(1);opacity:1}',
    '.dc-modal-head{display:flex;align-items:center;gap:12px;padding:18px 20px 12px}',
    '.dc-modal-ico{flex:0 0 auto;width:44px;height:44px;border-radius:50%;display:flex;align-items:center;justify-content:center}',
    '.dc-modal-ico svg{width:24px;height:24px}',
    '.dc-modal-title{margin:0;font-size:18px;font-weight:800;line-height:1.25;color:#111}',
    '.dc-modal-body{padding:0 20px 18px;font-size:15px;line-height:1.55;white-space:pre-wrap;word-break:break-word;color:#1f2d3d}',
    '.dc-modal-foot{display:flex;justify-content:flex-end;gap:10px;padding:14px 18px;background:linear-gradient(135deg,#ffe4f3,#fff3c9)}',
    '.dc-btn{border-radius:999px;font-weight:800;letter-spacing:.01em;border:2px dashed transparent;',
    'padding:.5rem 1.25rem;min-height:42px;background:#fff;cursor:pointer;font-size:14px;transition:background-color .15s ease,transform .08s ease}',
    '.dc-btn:active{transform:translateY(1px)}',
    '.dc-btn:focus-visible{outline:2px solid #2f8af5;outline-offset:2px}',
    '.dc-btn-ghost{color:#1c2f50;border-color:#9bb6e0}',
    '.dc-btn-ghost:hover{background:rgba(155,182,224,.16)}',
    '.dc-btn-primary{color:#2151d1;border-color:#2151d1}',
    '.dc-btn-primary:hover{background:rgba(33,81,209,.08)}',
    '.dc-btn-danger{color:#d6254f;border-color:#d6254f}',
    '.dc-btn-danger:hover{background:rgba(214,37,79,.1)}',
    '.dc-ico-info{background:rgba(33,81,209,.12);color:#2151d1}',
    '.dc-ico-success{background:rgba(13,154,74,.14);color:#0d9a4a}',
    '.dc-ico-canh_bao{background:rgba(208,135,0,.16);color:#d08700}',
    '.dc-ico-nguy_hiem{background:rgba(214,37,79,.12);color:#d6254f}',
    '.dc-ico-hoi{background:rgba(47,138,245,.14);color:#2f8af5}',
    'body.dc-modal-open{overflow:hidden}',
    '@media (max-width:575.98px){.dc-modal{max-width:100%}.dc-btn{flex:1 1 auto}}',
    // Toast
    '.dc-toast-wrap{position:fixed;top:72px;right:16px;z-index:2100;display:flex;flex-direction:column;gap:10px;',
    'max-width:min(360px,calc(100vw - 32px));pointer-events:none}',
    ".dc-toast{display:flex;align-items:flex-start;gap:10px;padding:12px 14px;border-radius:14px;background:#fff;",
    'border:1px solid #e7e0d6;border-left:5px solid #2151d1;box-shadow:0 12px 30px rgba(15,40,90,.18);',
    "font-family:var(--bs-body-font-family,'Nunito',system-ui,-apple-system,'Segoe UI',Roboto,sans-serif);",
    'color:#1f2d3d;font-size:14px;line-height:1.45;opacity:0;transform:translateX(24px);',
    'transition:opacity .18s ease,transform .18s ease;pointer-events:auto;cursor:pointer}',
    '.dc-toast.dc-show{opacity:1;transform:translateX(0)}',
    '.dc-toast.dc-hide{opacity:0;transform:translateX(24px)}',
    '.dc-toast-ico{flex:0 0 auto;width:22px;height:22px;margin-top:1px}',
    '.dc-toast-ico svg{width:22px;height:22px}',
    '.dc-toast-msg{flex:1;white-space:pre-wrap;word-break:break-word}',
    '.dc-toast-info{border-left-color:#2151d1}.dc-toast-info .dc-toast-ico{color:#2151d1}',
    '.dc-toast-success{border-left-color:#0d9a4a}.dc-toast-success .dc-toast-ico{color:#0d9a4a}',
    '.dc-toast-canh_bao{border-left-color:#d08700}.dc-toast-canh_bao .dc-toast-ico{color:#d08700}',
    '.dc-toast-nguy_hiem{border-left-color:#d6254f}.dc-toast-nguy_hiem .dc-toast-ico{color:#d6254f}',
    '@media (max-width:575.98px){.dc-toast-wrap{top:auto;bottom:16px;left:16px;right:16px;max-width:none}',
    '.dc-toast{transform:translateY(24px)}.dc-toast.dc-show{transform:translateY(0)}.dc-toast.dc-hide{transform:translateY(24px)}}',
    '@media (prefers-reduced-motion:reduce){.dc-modal-overlay,.dc-modal,.dc-toast{transition:none}}'
  ].join('');

  var ICONS = {
    info: '<path fill="currentColor" d="M12 2a10 10 0 100 20 10 10 0 000-20zm1 15h-2v-6h2zm0-8h-2V7h2z"/>',
    success: '<path fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" d="M20 6L9 17l-5-5"/>',
    canh_bao: '<path fill="currentColor" d="M12 2L1 21h22L12 2zm1 15h-2v-2h2zm0-4h-2V8h2z"/>',
    nguy_hiem: '<path fill="currentColor" d="M12 2L1 21h22L12 2zm1 15h-2v-2h2zm0-4h-2V8h2z"/>',
    hoi: '<path fill="currentColor" d="M12 2a10 10 0 100 20 10 10 0 000-20zm1 15h-2v-2h2zm1.07-7.75l-.9.92c-.72.73-1.17 1.33-1.17 2.83h-2v-.5c0-1.1.45-2.1 1.17-2.83l1.24-1.26A1.98 1.98 0 0010 7a2 2 0 00-2 2H6a4 4 0 118 .25c0 .8-.32 1.53-.93 2z"/>'
  };
  var TITLE_MAC_DINH = {
    info: 'Thông báo', success: 'Thành công', canh_bao: 'Lưu ý',
    nguy_hiem: 'Xác nhận', hoi: 'Xác nhận'
  };

  function themStyle() {
    if (document.getElementById('dc-thongbao-style')) return;
    var s = document.createElement('style');
    s.id = 'dc-thongbao-style';
    s.textContent = CSS;
    (document.head || document.documentElement).appendChild(s);
  }

  var hangCho = [];
  var dangMo = false;

  function moTiepTheo() {
    if (dangMo || !hangCho.length) return;
    dangMo = true;
    var cfg = hangCho.shift();
    hienThi(cfg);
  }

  function hienThi(cfg) {
    themStyle();
    var loai = cfg.loai || (cfg.laXacNhan ? 'hoi' : 'info');
    if (!ICONS[loai]) loai = cfg.laXacNhan ? 'hoi' : 'info';
    var nguyHiem = !!cfg.nguyHiem || loai === 'nguy_hiem';
    var tieuDe = cfg.tieuDe || TITLE_MAC_DINH[loai] || 'Thông báo';
    var nhanOK = cfg.nhanDongY || (cfg.laXacNhan ? 'Đồng ý' : 'OK');
    var nhanHuy = cfg.nhanHuy || 'Huỷ';

    var oTruoc = document.activeElement;
    var overlay = document.createElement('div');
    overlay.className = 'dc-modal-overlay';

    var box = document.createElement('div');
    box.className = 'dc-modal';
    box.setAttribute('role', cfg.laXacNhan ? 'alertdialog' : 'dialog');
    box.setAttribute('aria-modal', 'true');
    box.setAttribute('aria-labelledby', 'dc-modal-title');
    box.setAttribute('aria-describedby', 'dc-modal-body');

    var head = document.createElement('div');
    head.className = 'dc-modal-head';
    head.innerHTML =
      '<span class="dc-modal-ico dc-ico-' + loai + '">' +
        '<svg viewBox="0 0 24 24" aria-hidden="true">' + ICONS[loai] + '</svg>' +
      '</span>' +
      '<h3 class="dc-modal-title" id="dc-modal-title"></h3>';
    head.querySelector('.dc-modal-title').textContent = tieuDe;

    var body = document.createElement('div');
    body.className = 'dc-modal-body';
    body.id = 'dc-modal-body';
    body.textContent = cfg.noiDung == null ? '' : String(cfg.noiDung);

    var foot = document.createElement('div');
    foot.className = 'dc-modal-foot';

    var btnHuy = null;
    if (cfg.laXacNhan) {
      btnHuy = document.createElement('button');
      btnHuy.type = 'button';
      btnHuy.className = 'dc-btn dc-btn-ghost';
      btnHuy.textContent = nhanHuy;
      foot.appendChild(btnHuy);
    }
    var btnOK = document.createElement('button');
    btnOK.type = 'button';
    btnOK.className = 'dc-btn ' + (nguyHiem ? 'dc-btn-danger' : 'dc-btn-primary');
    btnOK.textContent = nhanOK;
    foot.appendChild(btnOK);

    box.appendChild(head);
    box.appendChild(body);
    box.appendChild(foot);
    overlay.appendChild(box);
    document.body.appendChild(overlay);
    document.body.classList.add('dc-modal-open');

    // Animation vào — ép reflow rồi thêm class (rAF bị tạm dừng khi tab ẩn)
    void overlay.offsetWidth;
    overlay.classList.add('dc-show');

    var xong = false;
    function dong(ketQua) {
      if (xong) return;
      xong = true;
      overlay.classList.remove('dc-show');
      document.removeEventListener('keydown', onKey, true);
      var goBo = function () {
        if (overlay.parentNode) overlay.parentNode.removeChild(overlay);
        if (!document.querySelector('.dc-modal-overlay')) document.body.classList.remove('dc-modal-open');
        try { if (oTruoc && oTruoc.focus) oTruoc.focus(); } catch (e) {}
        dangMo = false;
        moTiepTheo();
      };
      var d = 180;
      setTimeout(goBo, d);
      try { cfg.resolve(cfg.laXacNhan ? !!ketQua : undefined); } catch (e) {}
    }

    function onKey(e) {
      if (e.key === 'Escape') { e.preventDefault(); dong(false); return; }
      if (e.key === 'Enter') {
        if (document.activeElement === btnHuy) { e.preventDefault(); dong(false); return; }
        e.preventDefault(); dong(true); return;
      }
      if (e.key === 'Tab') {
        var focusables = btnHuy ? [btnHuy, btnOK] : [btnOK];
        var idx = focusables.indexOf(document.activeElement);
        e.preventDefault();
        var next = e.shiftKey ? idx - 1 : idx + 1;
        if (next < 0) next = focusables.length - 1;
        if (next >= focusables.length) next = 0;
        focusables[next].focus();
      }
    }

    btnOK.addEventListener('click', function () { dong(true); });
    if (btnHuy) btnHuy.addEventListener('click', function () { dong(false); });
    overlay.addEventListener('mousedown', function (e) {
      if (e.target === overlay) dong(false); // click nền = huỷ (alert cũng coi như đóng)
    });
    document.addEventListener('keydown', onKey, true);

    ((nguyHiem && btnHuy) ? btnHuy : btnOK).focus();
  }

  function taoLoiGoi(noiDung, tuyChon, laXacNhan) {
    tuyChon = tuyChon || {};
    return new Promise(function (resolve) {
      hangCho.push({
        noiDung: noiDung,
        tieuDe: tuyChon.tieuDe,
        loai: tuyChon.loai,
        nhanDongY: tuyChon.nhanDongY,
        nhanHuy: tuyChon.nhanHuy,
        nguyHiem: tuyChon.nguyHiem,
        laXacNhan: laXacNhan,
        resolve: resolve
      });
      moTiepTheo();
    });
  }

  function thongBao(noiDung, tuyChon) { return taoLoiGoi(noiDung, tuyChon, false); }
  function xacNhan(noiDung, tuyChon) { return taoLoiGoi(noiDung, tuyChon, true); }

  // Toast: thông báo nhanh, không chặn thao tác, tự tắt sau vài giây. Bấm để tắt sớm.
  function toast(noiDung, tuyChon) {
    tuyChon = tuyChon || {};
    themStyle();
    var loai = ICONS[tuyChon.loai] ? tuyChon.loai : 'success';
    var wrap = document.querySelector('.dc-toast-wrap');
    if (!wrap) {
      wrap = document.createElement('div');
      wrap.className = 'dc-toast-wrap';
      wrap.setAttribute('aria-live', 'polite');
      document.body.appendChild(wrap);
    }
    var el = document.createElement('div');
    el.className = 'dc-toast dc-toast-' + loai;
    el.setAttribute('role', 'status');
    el.innerHTML = '<span class="dc-toast-ico"><svg viewBox="0 0 24 24" aria-hidden="true">' + ICONS[loai] + '</svg></span><span class="dc-toast-msg"></span>';
    el.querySelector('.dc-toast-msg').textContent = noiDung == null ? '' : String(noiDung);
    wrap.appendChild(el);
    void el.offsetWidth;
    el.classList.add('dc-show');
    var thoiGian = typeof tuyChon.thoiGian === 'number' ? tuyChon.thoiGian : 3200;
    var hen;
    function dong() {
      if (el.__closing) return;
      el.__closing = true;
      clearTimeout(hen);
      el.classList.remove('dc-show');
      el.classList.add('dc-hide');
      setTimeout(function () {
        if (el.parentNode) el.parentNode.removeChild(el);
        if (wrap && !wrap.children.length && wrap.parentNode) wrap.parentNode.removeChild(wrap);
      }, 200);
    }
    el.addEventListener('click', dong);
    if (thoiGian > 0) hen = setTimeout(dong, thoiGian);
    return { dong: dong };
  }

  window.thongBao = thongBao;
  window.xacNhan = xacNhan;
  window.toast = toast;
  window.DCModal = { thongBao: thongBao, xacNhan: xacNhan, toast: toast };

  // Lưới an toàn: mọi alert() còn sót (hoặc code tương lai) vẫn ra hộp thoại đồng bộ.
  // Giữ bản gốc ở window.__alert_native phòng khi cần.
  window.__alert_native = window.alert;
  window.alert = function (m) { thongBao(m); };
})();
