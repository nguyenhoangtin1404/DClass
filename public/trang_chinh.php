<?php
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../lib/tro_giup.php';
header('Content-Type: text/html; charset=utf-8');
if (!isset($_SESSION['giao_vien_id'])) { header('Location: /public/dang_nhap.php'); exit; }
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Chấm điểm nhanh</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootswatch@5.3.3/dist/morph/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="https://unpkg.com/aos@2.3.4/dist/aos.css">
  <link rel="stylesheet" href="/public/theme.css">
  <style>
    .avatar-xs { width:24px; height:24px; border-radius:6px; border:1px solid #ddd; object-fit:cover; object-position:center; background:#fff; }
    .avatar { width:56px; height:56px; border-radius:8px; border:1px solid #ddd; object-fit:cover; object-position:center; background:#fff; display:block; }
    /* Table polish */
    .modern-table thead th { position: sticky; top: 0; z-index: 1; background: var(--bs-body-bg); border-bottom: 1px solid var(--bs-border-color); }
    .modern-table tbody tr:hover { background-color: var(--bs-tertiary-bg); }
    .modern-table td, .modern-table th { vertical-align: middle; }
    .cell-notes { max-width: 260px; }
    .cell-notes .truncate { display:inline-block; max-width:100%; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .scratch-card-wrapper { margin-top: 1.5rem; }
    .scratch-card { text-align: center; }
    .scratch-card-title { font-size: 1.15rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; }
    .scratch-card-hint { font-size: .9rem; color: #0b3a69; margin-bottom: 1rem; }
    .scratch-ticket { position: relative; width: 100%; max-width: 360px; margin: 0 auto 1rem; padding: 1.2rem; border-radius: 16px; background: #fff; box-shadow: 0 10px 20px rgba(0,0,0,.2); }
    .scratch-reward-title { font-size: .8rem; text-transform: uppercase; color: #888; margin-bottom: .2rem; letter-spacing: .05em; }
    .scratch-reward-value { font-size: 1.4rem; font-weight: 800; margin-bottom: .35rem; }
    .scratch-reward-note { font-size: .8rem; color: #666; margin-bottom: .5rem; }
    .scratch-area { position: relative; width: 100%; height: 130px; border-radius: 12px; overflow: hidden; border: 2px solid #c0c0c0; }
    .scratch-under { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; background: #fffbe6; color: #c46b1c; font-size: 1rem; font-weight: 700; z-index: 0; }
    #scratchCanvas { position: absolute; inset: 0; z-index: 1; cursor: crosshair; touch-action: none; }
    .scratch-actions { display: flex; gap: .75rem; justify-content: center; flex-wrap: wrap; }
    .scratch-actions .scratch-btn { border-radius: 999px; font-weight: 600; padding: .4rem 1.4rem; box-shadow: 0 2px 6px rgba(0,0,0,.2); }
    .scratch-actions .scratch-btn:active { transform: translateY(1px); box-shadow: 0 1px 3px rgba(0,0,0,.3); }
  </style>
</head>
<body><?php include __DIR__ . '/_nav.php'; ?>
<div class="toast-container position-fixed top-0 end-0 p-3" id="toastContainer"></div>
<div class="container py-3 safe-bottom">
  <div class="row g-3">
    <div class="col-md-6"><div class="card shadow-sm" data-aos="fade-up"><div class="card-body">
      <h6>Tìm học sinh</h6>
      <input id="tu_khoa" class="form-control" placeholder="Tên hoặc mã">
      <div class="form-text">Dấu ( ... ) sau tên là tên lớp.</div>
      <div id="ds_hs" class="list-group mt-2" style="max-height:300px;overflow:auto"></div>
    </div></div></div>
    <div class="col-md-6"><div class="card shadow-sm" data-aos="fade-up"><div class="card-body">
      <div class="d-flex align-items-center justify-content-between"><h6 class="mb-0">Thông tin</h6><button id="btn_qua_da_doi" class="btn btn-outline-secondary btn-sm" disabled>Qu&#224; &#273;&#227; &#273;&#7893;i</button></div><div id="thong_tin" class="mb-2 text-muted"></div>
      <h6 class="mt-2">Lý do</h6>      <input id="ly_do_loc" class="form-control form-control-sm mb-2" placeholder="Lọc lý do...">
<div id="ds_ly_do" class="d-flex flex-wrap gap-2"></div>
      <hr><div class="d-flex align-items-center justify-content-between">
        <h6 class="mb-0">Thẻ cào</h6>
        <button type="button" class="btn btn-outline-primary btn-sm" id="btnToggleScratch">Đổi điểm</button>
      </div>
      <div id="ds_qua" class="mb-3"></div>
      <div class="scratch-card d-none" id="scratchSection">
        <div class="scratch-ticket">
          <div class="scratch-area">
            <div class="scratch-under" id="scratchLabel">CÀO TẠI ĐÂY</div>
            <canvas id="scratchCanvas"></canvas>
          </div>
        </div>
        <div class="scratch-actions">
          <button class="btn btn-outline-primary scratch-btn d-none" id="scratchBtnNew">Thẻ mới</button>
        </div>
      </div>
    </div></div></div>
  </div>
  <div class="card mt-3 shadow-sm" data-aos="fade-up"><div class="card-body">
    <h6>Lịch sử gần đây</h6><div class="table-responsive"><table class="table table-sm table-hover table-striped align-middle modern-table">
      <thead><tr><th>Thời gian</th><th>Học sinh</th><th>Loại</th><th>Thay đổi</th><th>Số dư</th><th>Ghi chú</th></tr></thead>
      <tbody id="bang_lich_su"></tbody></table></div>
    <div class="d-flex align-items-center justify-content-between mt-2">
      <div id="ls_info" class="small text-muted"></div>
      <div class="btn-group btn-group-sm" role="group">
        <button id="ls_prev" type="button" class="btn btn-outline-secondary">Trước</button>
        <button id="ls_next" type="button" class="btn btn-outline-secondary">Sau</button>
      </div>
    </div>
  </div></div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
<script>AOS.init({ duration: 350, once: true, easing: 'ease-out' });</script>
<div class="modal fade" id="quaModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable modal-sm modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title">Quà đã đổi</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="qua_modal_body"></div>
    </div>
  </div>
</div><script>
let hsHienTai=null;
let lsData=[]; let lsPage=1; const lsPageSize=10; let lyDoData=[]; let quaData=[]; let thongKe=null;
let shouldAutoSelectFirstStudent=true;
const toastVariantClasses={
  success:'text-bg-success',
  info:'text-bg-primary',
  warning:'text-bg-warning',
  danger:'text-bg-danger'
};
function showToast(message='',variant='info',delay=4000){
  const container=document.getElementById('toastContainer');
  if(!container || typeof bootstrap==='undefined' || typeof bootstrap.Toast==='undefined'){
    console.warn('Toast:', message);
    return;
  }
  const toastEl=document.createElement('div');
  toastEl.className=`toast align-items-center border-0 ${toastVariantClasses[variant]||toastVariantClasses.info}`;
  toastEl.setAttribute('role','alert');
  toastEl.setAttribute('aria-live','assertive');
  toastEl.setAttribute('aria-atomic','true');
  const inner=document.createElement('div');
  inner.className='d-flex';
  const body=document.createElement('div');
  body.className='toast-body';
  body.textContent=message||'Th\u00f4ng b\u00e1o';
  const btn=document.createElement('button');
  btn.type='button';
  btn.className='btn-close btn-close-white me-2 m-auto';
  btn.setAttribute('data-bs-dismiss','toast');
  btn.setAttribute('aria-label','Close');
  inner.appendChild(body);
  inner.appendChild(btn);
  toastEl.appendChild(inner);
  container.appendChild(toastEl);
  const toastInstance=new bootstrap.Toast(toastEl,{delay,autohide:true});
  toastEl.addEventListener('hidden.bs.toast',()=>toastEl.remove());
  toastInstance.show();
}
function norm(s){ try { return String(s||'').normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase(); } catch(_e){ return String(s||'').toLowerCase(); } }
function tenLoai(loai){
  switch(String(loai||'')){
    case 'CONG_DIEM': return 'Cộng Điểm';
    case 'DOI_DIEM': return 'Đổi điểm';
    case 'HOAN_TAC': return 'Hoàn tác';
    default: return loai;
  }
}
async function napHocSinh(){
  const r=await fetch('/api/hoc_sinh.php?tu_khoa='+encodeURIComponent(document.getElementById('tu_khoa').value||''));
  const j=await r.json();
  const box=document.getElementById('ds_hs');
  box.innerHTML='';
  if(!j.ok) return;
  const danhSach=Array.isArray(j.du_lieu)?j.du_lieu:[];
  danhSach.forEach(s=>{
    const a=document.createElement('a');
    a.href='#';
    a.className='list-group-item list-group-item-action';
    const av=(s.anh_dai_dien_url && String(s.anh_dai_dien_url).trim()!=='')?s.anh_dai_dien_url:'/upload/avatar/default.svg';
    a.style.backgroundImage=`url(${av})`;
    a.style.backgroundRepeat='no-repeat';
    a.style.backgroundSize='24px 24px';
    a.style.backgroundPosition='8px center';
    a.style.paddingLeft='40px';
    a.textContent=`${s.ho_ten} (${s.ten_lop||''}) · Điểm: ${s.so_du}`;
    a.onclick=ev=>{ev.preventDefault(); chonHS(s);};
    box.appendChild(a);
  });
  if(shouldAutoSelectFirstStudent){
    if(danhSach.length){
      chonHS(danhSach[0]);
    }
    shouldAutoSelectFirstStudent=false;
  }
}
function renderLyDo(){
  const box=document.getElementById('ds_ly_do'); if(!box) return;
  box.innerHTML='';
  const kw = norm(document.getElementById('ly_do_loc')?.value||'');
  (lyDoData||[])
    .filter(ld => !kw || norm(ld.tieu_de).includes(kw))
    .forEach(ld => {
      const b=document.createElement('button');
      b.className='btn btn-outline-primary';
      b.textContent = `${ld.tieu_de} (${ld.bien_diem>0?'+':''}${ld.bien_diem})`;
      b.onclick = async()=>{
        if(!hsHienTai){
          showToast('Hãy chọn học sinh.','warning');
          return;
        }
        const res=await fetch('/api/diem.php?hanh_dong=cong',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({hoc_sinh_id:hsHienTai.id, ly_do_id:ld.id})});
        const jj=await res.json();
        if(jj.ok){
          hsHienTai.so_du=jj.du_lieu.so_du;
          hienThongTin(); napHocSinh(); napLichSu(); napThongKe();
        } else {
          showToast(jj.thong_bao||'L\u1ed7i','danger');
        }
      };
      box.appendChild(b);
    });
}async function napLyDo(){
  const r=await fetch('/api/ly_do.php');
  const j=await r.json();
  if(!j.ok){ lyDoData=[]; renderLyDo(); return; }
  lyDoData = Array.isArray(j.du_lieu) ? j.du_lieu : [];
  renderLyDo();
}function renderQua(){
  const box=document.getElementById('ds_qua'); if(!box) return;
  box.innerHTML='';
  const available=(quaData||[]).filter(q=>{
    const ton=Number(q.ton_kho);
    return Number.isNaN(ton) || ton!==0;
  }).length;
  const info=document.createElement('div');
  info.className='alert alert-info small mb-0';
  info.textContent = available
    ? ('Có ' + available + ' quà sẵn sàng để đổi.')
    : 'Hiện chưa có quà khả dụng để đổi. Vui lòng thêm quà mới.';
  box.appendChild(info);
}

async function napQua(){
  const r=await fetch('/api/qua_tang.php');
  const j=await r.json();
  if(!j.ok){ quaData=[]; renderQua(); return; }
  quaData = Array.isArray(j.du_lieu) ? j.du_lieu : [];
  renderQua();
}function hienThongTin(){ const el=document.getElementById('thong_tin'); el.textContent = hsHienTai ? `${hsHienTai.ho_ten} · Lớp ${hsHienTai.ten_lop||''} · Điểm: ${hsHienTai.so_du}` : ''; }
async function napLichSu(){ const sid=hsHienTai?hsHienTai.id:0; const r=await fetch('/api/diem.php?hanh_dong=lich_su&hoc_sinh_id='+sid);
  const j=await r.json(); if(!j.ok){ lsData=[]; renderLichSu(); return; }
  lsData = Array.isArray(j.du_lieu) ? j.du_lieu : []; lsPage=1; renderLichSu();
}

function renderLichSu(){
  const tb=document.getElementById('bang_lich_su'); if (tb) tb.innerHTML='';
  const total = lsData.length; const totalPages = Math.max(1, Math.ceil(total/lsPageSize));
  lsPage = Math.min(Math.max(1, lsPage), totalPages);
  const start = (lsPage-1)*lsPageSize; const rows = lsData.slice(start, start+lsPageSize);
  rows.forEach(row=>{
    const loaiHtml = (() => {
      switch(String(row.loai||'')){
        case 'CONG_DIEM': return '<span class="badge bg-success-subtle text-success border border-success-subtle">Cộng điểm</span>';
        case 'DOI_DIEM': return '<span class="badge bg-warning-subtle text-warning border border-warning-subtle">Đổi điểm</span>';
        case 'HOAN_TAC': return '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Hoàn tác</span>';
        default: return `<span class="badge bg-light text-body-secondary">${tenLoai(row.loai)}</span>`;
      }
    })();
    const delta = Number(row.bien_diem)||0;
    const deltaHtml = `<span class="fw-semibold ${delta>0?'text-success':'text-danger'}">${delta>0?'+':''}${delta}</span>`;
    const soDu = Number(row.so_du_sau)||0;
    const soDuHtml = `<span class="fw-semibold">${new Intl.NumberFormat('vi-VN').format(soDu)}</span>`;
    const ghiChu = row.ghi_chu||'';
    const tr=document.createElement('tr');
    tr.innerHTML = `
      <td class="text-muted small">${row.tao_luc||''}</td>
      <td>${row.ho_ten||''}</td>
      <td>${loaiHtml}</td>
      <td>${deltaHtml}</td>
      <td>${soDuHtml}</td>
      <td class="cell-notes"><span class="truncate">${ghiChu}</span></td>`;
    tb.appendChild(tr);
  });
  const info=document.getElementById('ls_info'); if(info){ const from=total?start+1:0; const to=Math.min(start+rows.length,total); info.textContent=`Trang ${lsPage}/${totalPages} · ${from}-${to}/${total}`; }
  const prev=document.getElementById('ls_prev'); const next=document.getElementById('ls_next'); if(prev) prev.disabled=(lsPage<=1); if(next) next.disabled=(lsPage>=totalPages);
}
function moQuaDaDoi(){
  if(!hsHienTai) return;
  const el = document.getElementById('qua_modal_body'); if(el) el.innerHTML = '<div class="text-muted small">Đang tải...</div>';
  (async()=>{
    try {
      const r = await fetch('/api/diem.php?hanh_dong=lich_su&hoc_sinh_id=' + (hsHienTai?hsHienTai.id:0));
      const j = await r.json();
      let rows = Array.isArray(j.du_lieu) ? j.du_lieu : [];
      rows = rows.filter(x => String(x.loai||'') === 'DOI_DIEM');
      if(!el) return;
      if(!rows.length){ el.innerHTML = '<div class="text-muted small">Chưa có quà đã đổi</div>'; }
      else {
        const ul = document.createElement('div');
        ul.className = 'list-group list-group-flush';
        rows.forEach(x=>{
          const item = document.createElement('div');
          item.className = 'list-group-item d-flex justify-content-between align-items-center';
          const ten = x.qua || 'Quà';
          const tg = x.tao_luc || '';
          item.innerHTML = `<span>${ten}</span><span class="text-muted small">${tg}</span>`;
          ul.appendChild(item);
        });
        el.innerHTML = '';
        el.appendChild(ul);
      }
      const modalEl = document.getElementById('quaModal');
      if (modalEl) new bootstrap.Modal(modalEl).show();
    } catch(_e) {
      if(el) el.innerHTML = '<div class="text-danger small">Lỗi tải dữ liệu</div>';
    }
  })();
}function chonHS(s){
  shouldAutoSelectFirstStudent=false;
  hsHienTai=s;
  hienThongTin();
  napLichSu();
  const b=document.getElementById('btn_qua_da_doi');
  if(b) b.removeAttribute('disabled');
}
document.getElementById('tu_khoa').oninput=napHocSinh;
document.getElementById('dang_xuat').onclick=async()=>{ await fetch('/api/dang_nhap.php?hanh_dong=dang_xuat',{method:'POST'}); location.href='/public/dang_nhap.php'; }; const btnQD = document.getElementById('btn_qua_da_doi'); if(btnQD) btnQD.onclick = moQuaDaDoi;
if(document.getElementById('ly_do_loc')) document.getElementById('ly_do_loc').oninput = renderLyDo;
document.getElementById('ls_prev').onclick=()=>{ lsPage=Math.max(1, lsPage-1); renderLichSu(); };
document.getElementById('ls_next').onclick=()=>{ lsPage=lsPage+1; renderLichSu(); };

// Hiển thị thông tin học sinh: thêm avatar, giới tính, ngày sinh
function hienThongTinDep(){
  const el=document.getElementById('thong_tin');
  if(!hsHienTai){ if(el) el.textContent='Ch\u01B0a ch\u1ECDn h\u1ECDc sinh'; return; }
  const av = (hsHienTai.anh_dai_dien_url && String(hsHienTai.anh_dai_dien_url).trim()!=='') ? hsHienTai.anh_dai_dien_url : '/upload/avatar/default.svg';
  const gioi = (hsHienTai.gioi_tinh||'').toUpperCase();
  const gioiLbl = gioi==='NAM' ? 'Nam' : gioi==='NU' ? 'Nữ' : gioi==='KHAC' ? 'Khác' : '';
  const raw = (hsHienTai.ngay_sinh||'').substring(0,10);
  let ngayLbl = '';
  if(raw && /^\d{4}-\d{2}-\d{2}$/.test(raw)){
    const [y,m,d] = raw.split('-'); ngayLbl = `${d}/${m}/${y}`;
  }
  if(el) el.innerHTML = `
    <div class="d-flex align-items-center gap-3">
      <img class="avatar" src="${av}" alt="avatar" onerror="this.onerror=null;this.src='/upload/avatar/default.svg';">
      <div>
        <div class="fw-semibold">${hsHienTai.ho_ten||''}</div>
        <div class="small text-muted">Lớp: ${hsHienTai.ten_lop||''} · Điểm: ${hsHienTai.so_du}</div>
        <div class="small text-muted">Giới tính: ${gioiLbl||'-'}${ngayLbl? ' · Ngày sinh: '+ngayLbl : ''}</div>
      </div>
    </div>`;
}
// Ghi đè hàm mặc định nếu đã có
try { hienThongTin = hienThongTinDep; } catch(_e) {}

napHocSinh(); napLyDo(); napQua(); napLichSu();

(function initScratchCard(){
  const SCRATCH_COST = 5;
  const scratchCanvas=document.getElementById('scratchCanvas');
  const scratchLabel=document.getElementById('scratchLabel');
  const btnNew=document.getElementById('scratchBtnNew');
  const scratchSection=document.getElementById('scratchSection');
  const btnToggleScratch=document.getElementById('btnToggleScratch');
  if(!scratchCanvas||!scratchLabel||!btnNew) return;

  if(btnToggleScratch && scratchSection){
    btnToggleScratch.addEventListener('click',()=>{
      const wasHidden = scratchSection.classList.contains('d-none');
      if(wasHidden && !hsHienTai){
        showToast('Hãy chọn học sinh.','warning');
        return;
      }
      const isHidden=scratchSection.classList.toggle('d-none');
      btnToggleScratch.textContent = isHidden ? 'Đổi điểm' : 'Ẩn thẻ cào';
      if(!isHidden){
        scratchSection.scrollIntoView({behavior:'smooth', block:'nearest'});
        setTimeout(()=>prepareScratchCard(), 10);
      }
    });
  }

  const ctx=scratchCanvas.getContext('2d');
  let isDrawing=false;
  let lastX=0;
  let lastY=0;
  let strokeCounter=0;
  let isProcessing=false;
  let scratchComplete=false;
  let currentReward=null;
  let hasRedeemedCurrent=false;
  let revealedRewardText='';

  function availableRewards(){
    return (quaData||[]).filter(q=>{
      const ton=Number(q.ton_kho);
      return Number.isNaN(ton) || ton!==0;
    });
  }

  function refreshScratchText(){
    let text='';
    if(isProcessing){
      text='Đang đổi điểm...';
    } else if(hasRedeemedCurrent && revealedRewardText){
      text=revealedRewardText;
    }
    scratchLabel.textContent=text;
  }

  function updateRedeemButton(){
    const show = scratchComplete && !isProcessing;
    btnNew.classList.toggle('d-none', !show);
    btnNew.disabled=!show;
  }

  function markScratchComplete(){
    if(!scratchComplete){
      scratchComplete=true;
      updateRedeemButton();
      refreshScratchText();
    }
  }

  function drawCover(){
    const w=scratchCanvas.width;
    const h=scratchCanvas.height;
    ctx.clearRect(0,0,w,h);
    ctx.globalCompositeOperation='source-over';
    const grad=ctx.createLinearGradient(0,0,w,h);
    grad.addColorStop(0,'#f7f7f7');
    grad.addColorStop(0.3,'#d0d0d0');
    grad.addColorStop(0.7,'#f0f0f0');
    grad.addColorStop(1,'#b8b8b8');
    ctx.fillStyle=grad;
    ctx.fillRect(0,0,w,h);
    ctx.fillStyle='rgba(255,255,255,0.25)';
    ctx.fillRect(0,0,w,h);
    ctx.fillStyle='#555';
    ctx.font='bold 20px system-ui';
    ctx.textAlign='center';
    ctx.textBaseline='middle';
    ctx.fillText('THẺ CÀO', w/2, h/2-12);
    ctx.font='12px system-ui';
    ctx.fillText('Cào để xem quà', w/2, h/2+10);
    ctx.globalCompositeOperation='destination-out';
    strokeCounter=0;
  }

  function resizeCanvas(){
    const rect=scratchCanvas.parentElement.getBoundingClientRect();
    scratchCanvas.width=Math.round(rect.width);
    scratchCanvas.height=Math.round(rect.height);
    drawCover();
  }

  function prepareScratchCard(silentIfNoStudent=false){
    if(isProcessing) return;
    scratchComplete=false;
    hasRedeemedCurrent=false;
    currentReward=null;
    revealedRewardText='';
    refreshScratchText();
    updateRedeemButton();
    resizeCanvas();
    redeemRandomReward(silentIfNoStudent);
  }

  function revealAll(){
    ctx.globalCompositeOperation='destination-out';
    ctx.fillRect(0,0,scratchCanvas.width,scratchCanvas.height);
    strokeCounter=0;
    markScratchComplete();
  }

  function drawStroke(x,y){
    if(!isDrawing) return;
    strokeCounter++;
    ctx.beginPath();
    ctx.lineJoin='round';
    ctx.lineCap='round';
    ctx.lineWidth=26;
    ctx.moveTo(lastX,lastY);
    ctx.lineTo(x,y);
    ctx.stroke();
    lastX=x; lastY=y;
    if(strokeCounter>80) revealAll();
  }

  function pointerPos(e){
    const rect=scratchCanvas.getBoundingClientRect();
    return { x: e.clientX-rect.left, y: e.clientY-rect.top };
  }

  function stopDrawing(e){
    if(!isDrawing) return;
    isDrawing=false;
    if(e && scratchCanvas.releasePointerCapture){
      try{ scratchCanvas.releasePointerCapture(e.pointerId); }catch(_err){}
    }
  }

  scratchCanvas.addEventListener('pointerdown',e=>{
    e.preventDefault();
    const {x,y}=pointerPos(e);
    isDrawing=true;
    lastX=x; lastY=y;
    if(scratchCanvas.setPointerCapture){
      try{ scratchCanvas.setPointerCapture(e.pointerId); }catch(_err){}
    }
  });
  scratchCanvas.addEventListener('pointermove',e=>{
    if(!isDrawing) return;
    e.preventDefault();
    const {x,y}=pointerPos(e);
    drawStroke(x,y);
  });
  ['pointerup','pointerleave','pointercancel'].forEach(evt=>{
    scratchCanvas.addEventListener(evt,e=>{
      e.preventDefault();
      stopDrawing(e);
    });
  });

  async function redeemRandomReward(silentIfNoStudent=false){
    if(isProcessing || hasRedeemedCurrent) return;
    if(!hsHienTai){
      if(!silentIfNoStudent) showToast('Hãy chọn học sinh.','warning');
      return;
    }
    const soDuHienTai = Number(hsHienTai.so_du)||0;
    if(soDuHienTai < SCRATCH_COST){
      showToast(`Học sinh cần ít nhất ${SCRATCH_COST} điểm để cào thẻ.`,'warning');
      return;
    }
    const pool=availableRewards();
    if(!pool.length){ showToast('Chưa có quà khả dụng để đổi.','warning'); return; }
    isProcessing=true;
    refreshScratchText();
    updateRedeemButton();
    const reward=pool[Math.floor(Math.random()*pool.length)];
    try{
      const res=await fetch('/api/diem.php?hanh_dong=quy_doi',{
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body:JSON.stringify({hoc_sinh_id:hsHienTai.id, qua_tang_id:reward.id, scratch_cost:SCRATCH_COST})
      });
      const jj=await res.json();
      if(!jj.ok){
        showToast(jj.thong_bao||'Đổi điểm thất bại','danger');
        return;
      }
      if(jj.du_lieu && typeof jj.du_lieu.so_du!=='undefined'){
        hsHienTai.so_du=jj.du_lieu.so_du;
      }
      currentReward=reward;
      revealedRewardText = `${reward.ten||'Quà bí mật'} (${reward.gia_diem||0} điểm)`;
      hasRedeemedCurrent=true;
      refreshScratchText();
      hienThongTin(); napHocSinh(); napLichSu(); napQua();
      if(typeof napThongKe==='function') napThongKe();
    } catch(err){
      console.error(err);
      showToast('Lỗi kết nối.','danger');
    } finally{
      isProcessing=false;
      updateRedeemButton();
      refreshScratchText();
    }
  }

  btnNew.addEventListener('click',()=>prepareScratchCard());
  window.addEventListener('resize',resizeCanvas);

  prepareScratchCard(true);
})();

</script>
</body>
</html>


