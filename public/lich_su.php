<?php
require __DIR__ . '/../config/db.php'; require __DIR__ . '/../lib/tro_giup.php';
if (!isset($_SESSION['giao_vien_id'])) { header('Location: /public/dang_nhap.php'); exit; }
?><!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Lịch sử</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"></head><body>
<div class="container py-3"><div class="d-flex align-items-center justify-content-between">
<h5>Lich su giao dich</h5><a href="/public/trang_chinh.php" class="btn btn-secondary btn-sm">← Chấm điểm</a></div>
<div class="table-responsive mt-3"><table class="table table-sm">
<thead><tr><th>Thời gian</th><th>Học sinh</th><th>Loại</th><th>Thay đổi</th><th>Số dư</th><th>Ghi chú</th></tr></thead>
<tbody id="tb"></tbody></table></div></div>
<script>
function tenLoai(loai){
  switch(String(loai||'')){
    case 'CONG_DIEM': return 'Cộng Điểm';
    case 'DOI_DIEM': return 'Đổi điểm';
    case 'HOAN_TAC': return 'Hoàn tác';
    default: return loai;
  }
}
async function nap(){ const r=await fetch('/api/diem.php?hanh_dong=lich_su'); const j=await r.json();
  const tb=document.getElementById('tb'); tb.innerHTML=''; if(!j.ok) return;
  j.du_lieu.forEach(row=>{ const tr=document.createElement('tr');
    tr.innerHTML=`<td>${row.tao_luc}</td><td>${row.ho_ten}</td><td>${tenLoai(row.loai)}</td><td>${row.bien_diem}</td><td>${row.so_du_sau}</td><td>${row.ghi_chu||''}</td>`;
    tb.appendChild(tr); });
} nap();
</script></body></html>
