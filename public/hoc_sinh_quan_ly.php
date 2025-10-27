<?php
require __DIR__ . '/../config/db.php'; require __DIR__ . '/../lib/tro_giup.php';
if (!isset($_SESSION['giao_vien_id'])) { header('Location: /public/dang_nhap.php'); exit; }
?><!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Hoc sinh</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"></head><body>
<div class="container py-3"><div class="d-flex align-items-center justify-content-between">
<h5>Quan ly hoc sinh</h5><a href="/public/trang_chinh.php" class="btn btn-secondary btn-sm">← Cham diem</a></div>
<div class="row g-3 mt-1">
  <div class="col-md-5"><div class="card"><div class="card-body">
    <div class="mb-2"><input id="tu_khoa" class="form-control" placeholder="Tim…"></div>
    <div id="ds" class="list-group" style="max-height:420px;overflow:auto"></div>
  </div></div></div>
  <div class="col-md-7"><div class="card"><div class="card-body">
    <h6>Them hoc sinh</h6>
    <div class="row g-2"><div class="col-4"><input id="ma" class="form-control" placeholder="Ma (tuy chon)"></div>
    <div class="col-8"><input id="ho_ten" class="form-control" placeholder="Ho ten"></div></div>
    <div class="mt-2"><button id="them" class="btn btn-primary btn-sm">Them</button><span id="msg" class="ms-2 small text-muted"></span></div>
  </div></div></div>
</div></div>
<script>
async function nap(){ const r=await fetch('/api/hoc_sinh.php?tu_khoa='+encodeURIComponent(tu_khoa.value||''));
  const j=await r.json(); const box=ds; box.innerHTML=''; if(!j.ok) return;
  j.du_lieu.forEach(s=>{ const a=document.createElement('a'); a.href='#'; a.className='list-group-item list-group-item-action';
    a.textContent=`${s.ho_ten} (${s.ten_lop||''}) · Diem: ${s.so_du}`; box.appendChild(a); });
}
tu_khoa.oninput=nap;
them.onclick=async()=>{
  const r=await fetch('/api/hoc_sinh.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({ma:ma.value, ho_ten:ho_ten.value})});
  const j=await r.json(); if(j.ok){ msg.textContent='Da them'; ho_ten.value=''; ma.value=''; nap(); } else msg.textContent=j.thong_bao||'Loi';
};
nap();
</script></body></html>
