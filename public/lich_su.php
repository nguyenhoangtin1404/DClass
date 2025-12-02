<?php
header('Content-Type: text/html; charset=utf-8');
require __DIR__ . '/../config/db.php'; require __DIR__ . '/../lib/tro_giup.php';
if (!isset($_SESSION['giao_vien_id'])) { header('Location: dang_nhap.php'); exit; }
?><!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Lịch sử</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootswatch@5.3.3/dist/morph/bootstrap.min.css"><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"><link rel="stylesheet" href="https://unpkg.com/aos@2.3.4/dist/aos.css"><link rel="stylesheet" href="theme.css"><link rel="stylesheet" href="date_picker.css"></head><body>
<?php include __DIR__ . '/_nav.php'; ?>
<style>
  .filter-compact .form-control-sm,
  .filter-compact .form-select-sm{
    min-height: 36px;
    padding-top: .35rem;
    padding-bottom: .35rem;
  }
</style>
<div class="container py-3 safe-bottom">
  <div class="card shadow-sm" data-aos="fade-up"><div class="card-body py-3">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
      <h5 class="mb-0">Lịch sử giao dịch</h5>
      <button class="btn btn-outline-primary btn-sm" id="btn_toggle_filter" type="button" title="Ẩn/hiện bộ lọc" aria-label="Ẩn/hiện bộ lọc"><i class="bi bi-funnel"></i></button>
    </div>
    <div id="filter_wrap" class="mt-3 d-none">
      <div class="row g-2 filter-compact">
        <div class="col-md-3 col-sm-6">
          <input id="loc_tu_khoa" class="form-control form-control-sm" placeholder="Tìm học sinh, ghi chú..." aria-label="Tìm học sinh, ghi chú...">
        </div>
        <div class="col-md-2 col-sm-6">
          <select id="loc_loai" class="form-select form-select-sm">
            <option value="">Loại: Tất cả</option>
            <option value="CONG_DIEM">Cộng điểm</option>
            <option value="DOI_DIEM">Đổi điểm</option>
            <option value="HOAN_TAC">Hoàn tác</option>
          </select>
        </div>
        <div class="col-md-2 col-sm-6">
          <input id="loc_tu_ngay" type="text" class="form-control form-control-sm date-picker" title="Từ ngày" placeholder="dd/mm/yyyy" data-datepicker="1">
        </div>
        <div class="col-md-2 col-sm-6">
          <input id="loc_den_ngay" type="text" class="form-control form-control-sm date-picker" title="Đến ngày" placeholder="dd/mm/yyyy" data-datepicker="1">
        </div>
        <div class="col-md-2 col-sm-6">
          <select id="loc_sort" class="form-select form-select-sm">
            <option value="time_desc">Mới nhất</option>
            <option value="time_asc">Cũ nhất</option>
            <option value="delta_desc">Thay đổi giảm dần</option>
            <option value="delta_asc">Thay đổi tăng dần</option>
            <option value="balance_desc">Số dư cao</option>
            <option value="balance_asc">Số dư thấp</option>
          </select>
        </div>
        <div class="col-md-1 col-sm-6">
          <select id="loc_page_size" class="form-select form-select-sm">
            <option value="20">20</option>
            <option value="50">50</option>
            <option value="100">100</option>
          </select>
        </div>
      </div>
    </div>
  </div></div>
  <div class="table-responsive mt-3" data-aos="fade-up">
    <table class="table table-sm align-middle">
      <thead><tr><th>Thời gian</th><th>Học sinh</th><th>Loại</th><th>Thay đổi</th><th>Số dư</th><th>Ghi chú</th></tr></thead>
      <tbody id="tb"></tbody>
    </table>
  </div>
  <div class="d-flex align-items-center justify-content-between mt-2">
    <div id="pg_info" class="small text-muted"></div>
    <div class="btn-group btn-group-sm" role="group">
      <button class="btn btn-outline-secondary" id="pg_prev">Trang trước</button>
      <button class="btn btn-outline-secondary" id="pg_next">Trang sau</button>
    </div>
  </div>
</div>
<script>
let PAGE_SIZE = 20;
let duLieu = [];
let duLieuLoc = [];
let trang = 1;

function tenLoai(loai){
  switch(String(loai||'')){
    case 'CONG_DIEM': return 'Cộng Điểm';
    case 'DOI_DIEM': return 'Đổi Điểm';
    case 'HOAN_TAC': return 'Hoàn Tác';
    default: return loai;
  }
}
function parseDate(str){
  // expect "YYYY-MM-DD HH:MM:SS"
  if(!str) return 0;
  const parts = str.split(' ')[0];
  const ps = parts.split(/[-/]/);
  let y,m,d;
  if(ps.length===3){
    if(ps[0].length===4){ y=+ps[0]; m=+ps[1]; d=+ps[2]; }
    else { d=+ps[0]; m=+ps[1]; y=+ps[2]; if(y<100) y+=2000; }
    const t = Date.parse(`${y}-${String(m).padStart(2,'0')}-${String(d).padStart(2,'0')}T00:00:00`);
    return Number.isNaN(t) ? 0 : t;
  }
  const iso = str.replace(' ','T');
  const t = Date.parse(iso);
  return Number.isNaN(t) ? 0 : t;
}
function locVaSapXep(){
  const kw = (document.getElementById('loc_tu_khoa')?.value||'').trim().toLowerCase();
  const loai = document.getElementById('loc_loai')?.value||'';
  const tu = document.getElementById('loc_tu_ngay')?.value||'';
  const den = document.getElementById('loc_den_ngay')?.value||'';
  const sort = document.getElementById('loc_sort')?.value||'time_desc';
  const tuTs = tu ? Date.parse(tu+'T00:00:00') : null;
  const denTs = den ? Date.parse(den+'T23:59:59') : null;
  duLieuLoc = (duLieu||[]).filter(row=>{
    if (loai && row.loai !== loai) return false;
    const ts = parseDate(row.tao_luc);
    if (tuTs !== null && ts && ts < tuTs) return false;
    if (denTs !== null && ts && ts > denTs) return false;
    if (kw){
      const haystack = `${row.ho_ten||''} ${row.ghi_chu||''}`.toLowerCase();
      if (!haystack.includes(kw)) return false;
    }
    return true;
  });
  duLieuLoc.sort((a,b)=>{
    if (sort === 'time_asc') return parseDate(a.tao_luc) - parseDate(b.tao_luc);
    if (sort === 'time_desc') return parseDate(b.tao_luc) - parseDate(a.tao_luc);
    if (sort === 'delta_asc') return (a.bien_diem||0) - (b.bien_diem||0);
    if (sort === 'delta_desc') return (b.bien_diem||0) - (a.bien_diem||0);
    if (sort === 'balance_asc') return (a.so_du_sau||0) - (b.so_du_sau||0);
    if (sort === 'balance_desc') return (b.so_du_sau||0) - (a.so_du_sau||0);
    return 0;
  });
}
function veTrang(){
  const tb=document.getElementById('tb');
  const pgInfo=document.getElementById('pg_info');
  const total=duLieuLoc.length;
  const totalPages=Math.max(1, Math.ceil(total/PAGE_SIZE));
  if(trang>totalPages) trang=totalPages;
  const start=(trang-1)*PAGE_SIZE;
  const end=Math.min(start+PAGE_SIZE, total);
  tb.innerHTML='';
  duLieuLoc.slice(start,end).forEach(row=>{
    const tr=document.createElement('tr');
    tr.innerHTML=`<td>${row.tao_luc}</td><td>${row.ho_ten}</td><td>${tenLoai(row.loai)}</td><td>${row.bien_diem}</td><td>${row.so_du_sau}</td><td>${row.ghi_chu||''}</td>`;
    tb.appendChild(tr);
  });
  pgInfo.textContent = total ? `Trang ${trang}/${totalPages} – hiển thị ${start+1}-${end} / ${total}` : 'Không có dữ liệu';
  document.getElementById('pg_prev').disabled = trang<=1;
  document.getElementById('pg_next').disabled = trang>=totalPages;
}
async function nap(){
  const r=await fetch('../api/diem.php?hanh_dong=lich_su');
  const j=await r.json();
  if(!j.ok){ return; }
  duLieu = j.du_lieu || [];
  locVaSapXep(); trang = 1;
  veTrang();
}
document.getElementById('pg_prev').onclick=()=>{ if(trang>1){ trang--; veTrang(); } };
document.getElementById('pg_next').onclick=()=>{ const totalPages=Math.max(1, Math.ceil((duLieuLoc.length||0)/PAGE_SIZE)); if(trang<totalPages){ trang++; veTrang(); } };
['loc_tu_khoa','loc_loai','loc_tu_ngay','loc_den_ngay','loc_sort'].forEach(id=>{
  const el=document.getElementById(id);
  if(el){ el.addEventListener('input', ()=>{ locVaSapXep(); trang=1; veTrang(); }); }
});
const elPageSize=document.getElementById('loc_page_size');
if(elPageSize){
  elPageSize.addEventListener('change', ()=>{
    const v=parseInt(elPageSize.value,10); PAGE_SIZE = Number.isNaN(v)?20:v;
    trang=1; veTrang();
  });
}
const btnToggle=document.getElementById('btn_toggle_filter');
const filterWrap=document.getElementById('filter_wrap');
if(btnToggle && filterWrap){
  btnToggle.addEventListener('click', ()=>{
    filterWrap.classList.toggle('d-none');
  });
}
nap();
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
<script src="date_picker.js"></script>
<script>AOS.init({ duration: 350, once: true, easing: 'ease-out' });</script></body></html>



