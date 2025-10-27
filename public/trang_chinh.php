<?php
require __DIR__ . '/../config/db.php'; require __DIR__ . '/../lib/tro_giup.php';
if (!isset($_SESSION['giao_vien_id'])) { header('Location: /public/dang_nhap.php'); exit; }
?><!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Cháº¥m Ä‘iá»ƒm nhanh</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"></head><body>
<nav class="navbar navbar-light bg-light px-3"><span class="navbar-brand">Cháº¥m Ä‘iá»ƒm</span>
  <div class="ms-auto"><a class="btn btn-outline-secondary btn-sm" href="/public/hoc_sinh_quan_ly.php">Há»c sinh</a>
  <a class="btn btn-outline-secondary btn-sm" href="/public/lich_su.php">Lá»‹ch sá»­</a>
  <a class="btn btn-outline-secondary btn-sm" href="/public/cau_hinh.php">Cáº¥u hÃ¬nh</a>
  <button class="btn btn-outline-danger btn-sm" id="dang_xuat">ÄÄƒng xuáº¥t</button></div></nav>
<div class="container py-3">
  <div class="row g-3">
    <div class="col-md-6"><div class="card"><div class="card-body">
      <h6>Tìm học sinh</h6><input id="tu_khoa" class="form-control" placeholder="Tên hoặc mã"><div class="form-text">Dấu ( ... ) sau tên là tên lớp.</div>
      <div id="ds_hs" class="list-group mt-2" style="max-height:300px;overflow:auto"></div>
    </div></div></div>
    <div class="col-md-6"><div class="card"><div class="card-body">
      <h6>ThÃ´ng tin</h6><div id="thong_tin" class="mb-2 text-muted">ChÆ°a chá»n há»c sinh</div>
      <h6 class="mt-2">LÃ½ do</h6><div id="ds_ly_do" class="d-flex flex-wrap gap-2"></div>
      <hr><h6>Äá»•i quÃ </h6><div id="ds_qua" class="d-flex flex-wrap gap-2"></div>
    </div></div></div>
  </div>
  <div class="card mt-3"><div class="card-body">
    <h6>Lá»‹ch sá»­ gáº§n Ä‘Ã¢y</h6><div class="table-responsive"><table class="table table-sm">
      <thead><tr><th>Thá»i gian</th><th>Há»c sinh</th><th>Loáº¡i</th><th>Thay Ä‘á»•i</th><th>Sá»‘ dÆ°</th><th>Ghi chÃº</th></tr></thead>
      <tbody id="bang_lich_su"></tbody></table></div>
  </div></div>
</div>
<script>
let hsHienTai=null;
function tenLoai(loai){
  switch(String(loai||'')){
    case 'CONG_DIEM': return 'Cá»™ng Äiá»ƒm';
    case 'DOI_DIEM': return 'Äá»•i Ä‘iá»ƒm';
    case 'HOAN_TAC': return 'HoÃ n tÃ¡c';
    default: return loai;
  }
}
async function napHocSinh(){ const r=await fetch('/api/hoc_sinh.php?tu_khoa='+encodeURIComponent(tu_khoa.value||''));
  const j=await r.json(); const box=ds_hs; box.innerHTML=''; if(!j.ok) return;
  j.du_lieu.forEach(s=>{ const a=document.createElement('a'); a.href='#'; a.className='list-group-item list-group-item-action';
    a.textContent=`${s.ho_ten} (${s.ten_lop||''}) Â· Diem: ${s.so_du}`; a.onclick=ev=>{ev.preventDefault(); chonHS(s);}; box.appendChild(a); });
}
async function napLyDo(){ const r=await fetch('/api/ly_do.php'); const j=await r.json(); const box=ds_ly_do; box.innerHTML=''; if(!j.ok) return;
  j.du_lieu.forEach(ld=>{ const b=document.createElement('button'); b.className='btn btn-outline-primary btn-sm';
    b.textContent=`${ld.tieu_de} (${ld.bien_diem>0?'+':''}${ld.bien_diem})`; b.onclick=async()=>{
      if(!hsHienTai) return alert('ChÆ°a chá»n há»c sinh');
      const res=await fetch('/api/diem.php?hanh_dong=cong',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({hoc_sinh_id:hsHienTai.id, ly_do_id:ld.id})});
      const jj=await res.json(); if(jj.ok){ hsHienTai.so_du=jj.du_lieu.so_du; hienThongTin(); napHocSinh(); napLichSu(); } else alert(jj.thong_bao||'Loi');
    }; box.appendChild(b); });
}
async function napQua(){ const r=await fetch('/api/qua_tang.php'); const j=await r.json(); const box=ds_qua; box.innerHTML=''; if(!j.ok) return;
  j.du_lieu.forEach(q=>{ const b=document.createElement('button'); b.className='btn btn-outline-success btn-sm'; const ton=q.ton_kho<0?'âˆž':q.ton_kho;
    b.textContent=`${q.ten} (${q.gia_diem}) [${ton}]`; b.onclick=async()=>{
      if(!hsHienTai) return alert('ChÆ°a chá»n há»c sinh');
      const res=await fetch('/api/diem.php?hanh_dong=quy_doi',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({hoc_sinh_id:hsHienTai.id, qua_tang_id:q.id})});
      const jj=await res.json(); if(jj.ok){ hsHienTai.so_du=jj.du_lieu.so_du; hienThongTin(); napHocSinh(); napLichSu(); napQua(); } else alert(jj.thong_bao||'Loi');
    }; box.appendChild(b); });
}
function hienThongTin(){ const el=thong_tin; el.textContent = hsHienTai ? `${hsHienTai.ho_ten} Â· Lop ${hsHienTai.ten_lop||''} Â· Diem: ${hsHienTai.so_du}` : 'Chua ChÆ°a chá»n há»c sinh'; }
async function napLichSu(){ const sid=hsHienTai?hsHienTai.id:0; const r=await fetch('/api/diem.php?hanh_dong=lich_su&hoc_sinh_id='+sid);
  const j=await r.json(); const tb=bang_lich_su; tb.innerHTML=''; if(!j.ok) return;
  j.du_lieu.forEach(row=>{ const tr=document.createElement('tr'); tr.innerHTML=`<td>${row.tao_luc}</td><td>${row.ho_ten}</td><td>${tenLoai(row.loai)}</td><td>${row.bien_diem}</td><td>${row.so_du_sau}</td><td>${row.ghi_chu||''}</td>`; tb.appendChild(tr); });
}
function chonHS(s){ hsHienTai=s; hienThongTin(); napLichSu(); }
tu_khoa.oninput=napHocSinh;
dang_xuat.onclick=async()=>{ await fetch('/api/dang_nhap.php?hanh_dong=dang_xuat',{method:'POST'}); location.href='/public/dang_nhap.php'; };
napHocSinh(); napLyDo(); napQua(); napLichSu();
</script></body></html>



