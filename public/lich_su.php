<?php
header('Content-Type: text/html; charset=utf-8');
require __DIR__ . '/../config/db.php'; require __DIR__ . '/../lib/tro_giup.php';
if (!isset($_SESSION['giao_vien_id'])) { header('Location: dang_nhap.php'); exit; }
?><!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Lịch sử</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootswatch@5.3.3/dist/morph/bootstrap.min.css"><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"><link rel="stylesheet" href="https://unpkg.com/aos@2.3.4/dist/aos.css"><link rel="stylesheet" href="theme.css"></head><body>
<?php include __DIR__ . '/_nav.php'; ?>
<div class="container py-3 safe-bottom">
  <div class="d-flex align-items-center justify-content-between">
    <h5>Lịch sử giao dịch</h5>
  </div>
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
const PAGE_SIZE = 20;
let duLieu = [];
let trang = 1;

function tenLoai(loai){
  switch(String(loai||'')){
    case 'CONG_DIEM': return 'Cộng Điểm';
    case 'DOI_DIEM': return 'Đổi Điểm';
    case 'HOAN_TAC': return 'Hoàn Tác';
    default: return loai;
  }
}
function veTrang(){
  const tb=document.getElementById('tb');
  const pgInfo=document.getElementById('pg_info');
  const total=duLieu.length;
  const totalPages=Math.max(1, Math.ceil(total/PAGE_SIZE));
  if(trang>totalPages) trang=totalPages;
  const start=(trang-1)*PAGE_SIZE;
  const end=Math.min(start+PAGE_SIZE, total);
  tb.innerHTML='';
  duLieu.slice(start,end).forEach(row=>{
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
  trang = 1;
  veTrang();
}
document.getElementById('pg_prev').onclick=()=>{ if(trang>1){ trang--; veTrang(); } };
document.getElementById('pg_next').onclick=()=>{ const totalPages=Math.max(1, Math.ceil((duLieu.length||0)/PAGE_SIZE)); if(trang<totalPages){ trang++; veTrang(); } };
nap();
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
<script>AOS.init({ duration: 350, once: true, easing: 'ease-out' });</script></body></html>



