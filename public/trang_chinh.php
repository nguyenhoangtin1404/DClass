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
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <style>
    .avatar-xs { width:24px; height:24px; border-radius:6px; border:1px solid #ddd; object-fit:cover; object-position:center; background:#fff; }
    .avatar { width:56px; height:56px; border-radius:8px; border:1px solid #ddd; object-fit:cover; object-position:center; background:#fff; display:block; }
  </style>
</head>
<body>
<nav class="navbar navbar-light bg-light px-3"><span class="navbar-brand">Chấm điểm</span>
  <div class="ms-auto">
    <a class="btn btn-outline-secondary btn-sm" href="/public/hoc_sinh_quan_ly.php">Học sinh</a>
    <a class="btn btn-outline-secondary btn-sm" href="/public/lich_su.php">Lịch sử</a>
    <a class="btn btn-outline-secondary btn-sm" href="/public/cau_hinh.php">Cấu hình</a>
    <button class="btn btn-outline-danger btn-sm" id="dang_xuat">Đăng xuất</button>
  </div>
</nav>
<div class="container py-3">
  <div class="row g-3">
    <div class="col-md-6"><div class="card"><div class="card-body">
      <h6>Tìm học sinh</h6>
      <input id="tu_khoa" class="form-control" placeholder="Tên hoặc mã">
      <div class="form-text">Dấu ( ... ) sau tên là tên lớp.</div>
      <div id="ds_hs" class="list-group mt-2" style="max-height:300px;overflow:auto"></div>
    </div></div></div>
    <div class="col-md-6"><div class="card"><div class="card-body">
      <div class="d-flex align-items-center justify-content-between"><h6 class="mb-0">Th&#244;ng tin</h6><button id="btn_qua_da_doi" class="btn btn-outline-secondary btn-sm" disabled>Qu&#224; &#273;&#227; &#273;&#7893;i</button></div><div id="thong_tin" class="mb-2 text-muted">Ch&#432;a ch&#7885;n h&#7885;c sinh</div>
      <h6 class="mt-2">Lý do</h6>      <input id="ly_do_loc" class="form-control form-control-sm mb-2" placeholder="Lọc lý do...">
<div id="ds_ly_do" class="d-flex flex-wrap gap-2"></div>
      <hr><h6>Đổi quà</h6>      <input id="qua_loc" class="form-control form-control-sm mb-2" placeholder="Lọc quà...">
<div id="ds_qua" class="d-flex flex-wrap gap-2"></div>
    </div></div></div>
  </div>
  <div class="card mt-3"><div class="card-body">
    <h6>Lịch sử gần đây</h6><div class="table-responsive"><table class="table table-sm">
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
let lsData=[]; let lsPage=1; const lsPageSize=10; let lyDoData=[]; let quaData=[];
function norm(s){ try { return String(s||'').normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase(); } catch(_e){ return String(s||'').toLowerCase(); } }
function tenLoai(loai){
  switch(String(loai||'')){
    case 'CONG_DIEM': return 'Cộng Điểm';
    case 'DOI_DIEM': return 'Đổi điểm';
    case 'HOAN_TAC': return 'Hoàn tác';
    default: return loai;
  }
}
async function napHocSinh(){ const r=await fetch('/api/hoc_sinh.php?tu_khoa='+encodeURIComponent(document.getElementById('tu_khoa').value||''));
  const j=await r.json(); const box=document.getElementById('ds_hs'); box.innerHTML=''; if(!j.ok) return;
  j.du_lieu.forEach(s=>{ const a=document.createElement('a'); a.href='#'; a.className='list-group-item list-group-item-action'; const av=(s.anh_dai_dien_url && String(s.anh_dai_dien_url).trim()!=='')?s.anh_dai_dien_url:'/upload/avatar/default.svg'; a.style.backgroundImage=`url(${av})`; a.style.backgroundRepeat='no-repeat'; a.style.backgroundSize='24px 24px'; a.style.backgroundPosition='8px center'; a.style.paddingLeft='40px';
    a.textContent=`${s.ho_ten} (${s.ten_lop||''}) · Điểm: ${s.so_du}`; a.onclick=ev=>{ev.preventDefault(); chonHS(s);}; box.appendChild(a); });
}
function renderLyDo(){
  const box=document.getElementById('ds_ly_do'); if(!box) return;
  box.innerHTML='';
  const kw = norm(document.getElementById('ly_do_loc')?.value||'');
  (lyDoData||[])
    .filter(ld => !kw || norm(ld.tieu_de).includes(kw))
    .forEach(ld => {
      const b=document.createElement('button');
      b.className='btn btn-outline-primary btn-sm';
      b.textContent = `${ld.tieu_de} (${ld.bien_diem>0?'+':''}${ld.bien_diem})`;
      b.onclick = async()=>{
        if(!hsHienTai) return alert('Chưa chọn học sinh');
        const res=await fetch('/api/diem.php?hanh_dong=cong',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({hoc_sinh_id:hsHienTai.id, ly_do_id:ld.id})});
        const jj=await res.json(); if(jj.ok){ hsHienTai.so_du=jj.du_lieu.so_du; hienThongTin(); napHocSinh(); napLichSu(); } else alert(jj.thong_bao||'Lỗi');
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
  const kw = norm(document.getElementById('qua_loc')?.value||'');
  (quaData||[])
    .filter(q => !kw || norm(q.ten).includes(kw))
    .forEach(q => {
      const b=document.createElement('button');
      b.className='btn btn-outline-success btn-sm d-flex align-items-center gap-2';
      const ton=q.ton_kho<0?'∞':q.ton_kho;
      const av=(q.anh_url && String(q.anh_url).trim()!=='')?q.anh_url:'/upload/avatar/default.svg';
      b.innerHTML = `<img src="${av}" alt="" class="avatar-xs" onerror="this.onerror=null;this.src='/upload/avatar/default.svg';"><span>${q.ten} (${q.gia_diem}) [${ton}]</span>`;
      b.onclick = async()=>{
        if(!hsHienTai) return alert('Chưa chọn học sinh');
        const res=await fetch('/api/diem.php?hanh_dong=quy_doi',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({hoc_sinh_id:hsHienTai.id, qua_tang_id:q.id})});
        const jj=await res.json(); if(jj.ok){ hsHienTai.so_du=jj.du_lieu.so_du; hienThongTin(); napHocSinh(); napLichSu(); napQua(); } else alert(jj.thong_bao||'Lỗi');
      };
      box.appendChild(b);
    });
}async function napQua(){
  const r=await fetch('/api/qua_tang.php');
  const j=await r.json();
  if(!j.ok){ quaData=[]; renderQua(); return; }
  quaData = Array.isArray(j.du_lieu) ? j.du_lieu : [];
  renderQua();
}function hienThongTin(){ const el=document.getElementById('thong_tin'); el.textContent = hsHienTai ? `${hsHienTai.ho_ten} · Lớp ${hsHienTai.ten_lop||''} · Điểm: ${hsHienTai.so_du}` : 'Chưa chọn học sinh'; }
async function napLichSu(){ const sid=hsHienTai?hsHienTai.id:0; const r=await fetch('/api/diem.php?hanh_dong=lich_su&hoc_sinh_id='+sid);
  const j=await r.json(); if(!j.ok){ lsData=[]; renderLichSu(); return; }
  lsData = Array.isArray(j.du_lieu) ? j.du_lieu : []; lsPage=1; renderLichSu();
}

function renderLichSu(){
  const tb=document.getElementById('bang_lich_su'); if (tb) tb.innerHTML='';
  const total = lsData.length; const totalPages = Math.max(1, Math.ceil(total/lsPageSize));
  lsPage = Math.min(Math.max(1, lsPage), totalPages);
  const start = (lsPage-1)*lsPageSize; const rows = lsData.slice(start, start+lsPageSize);
  rows.forEach(row=>{ const tr=document.createElement('tr'); tr.innerHTML=`<td>${row.tao_luc}</td><td>${row.ho_ten}</td><td>${tenLoai(row.loai)}</td><td>${row.bien_diem}</td><td>${row.so_du_sau}</td><td>${row.ghi_chu||''}</td>`; tb.appendChild(tr); });
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
}function chonHS(s){ hsHienTai=s; hienThongTin(); napLichSu(); const b=document.getElementById('btn_qua_da_doi'); if(b) b.removeAttribute('disabled'); }
document.getElementById('tu_khoa').oninput=napHocSinh;
document.getElementById('dang_xuat').onclick=async()=>{ await fetch('/api/dang_nhap.php?hanh_dong=dang_xuat',{method:'POST'}); location.href='/public/dang_nhap.php'; }; const btnQD = document.getElementById('btn_qua_da_doi'); if(btnQD) btnQD.onclick = moQuaDaDoi;
if(document.getElementById('ly_do_loc')) document.getElementById('ly_do_loc').oninput = renderLyDo;
if(document.getElementById('qua_loc')) document.getElementById('qua_loc').oninput = renderQua;
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
</script>
</body>
</html>
