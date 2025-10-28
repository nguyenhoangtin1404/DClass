<?php
require __DIR__ . '/../config/db.php'; require __DIR__ . '/../lib/tro_giup.php';
if (!isset($_SESSION['giao_vien_id'])) { header('Location: /public/dang_nhap.php'); exit; }
?><!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Cấu hình hệ thống</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootswatch@5.3.3/dist/morph/bootstrap.min.css"><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"><link rel="stylesheet" href="https://unpkg.com/aos@2.3.4/dist/aos.css"><link rel="stylesheet" href="/public/theme.css"></head><body>
<div class="container py-3">
  <div class="d-flex align-items-center justify-content-between"><h4 class="mb-0">Cấu hình hệ thống</h4>
    <div class="ms-auto"><a class="btn btn-secondary btn-sm" href="/public/trang_chinh.php">← Chấm điểm</a></div>
  </div>

  <ul class="nav nav-tabs mt-3" id="tab" role="tablist">
    <li class="nav-item" role="presentation"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-ly-do" type="button">Lý do</button></li>
    <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-qua" type="button">Quà tặng</button></li>
    <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-lop" type="button">Lớp học</button></li>
    <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-tai-khoan" type="button">Tài khoản</button></li>
  </ul>
  <div class="tab-content border border-top-0 p-3">
    <div class="tab-pane fade show active" id="tab-ly-do">
      <div class="row g-3">
        <div class="col-md-4"><div class="card shadow-sm" data-aos="fade-up"><div class="card-body">
          <h6>Thêm lý do</h6>
          <div class="mb-2"><label class="form-label">Tiêu đề</label><input id="ld_tieu_de" class="form-control"></div>
          <div class="mb-2"><label class="form-label">Biến điểm</label><input id="ld_bien_diem" type="number" class="form-control" value="1"></div>
          <button class="btn btn-primary btn-sm" id="ld_them">Thêm</button>
        </div></div></div>
        <div class="col-md-8"><div class="card shadow-sm" data-aos="fade-up"><div class="card-body">
          <div class="d-flex align-items-center justify-content-between"><h6 class="mb-0">Danh sách</h6><small class="text-muted">Bấm bật/tắt, sửa hoặc xóa</small></div>
          <div class="table-responsive mt-2">
            <table class="table table-sm align-middle"><thead><tr><th>#</th><th>Tiêu đề</th><th>Biến điểm</th><th>Trạng thái</th><th></th></tr></thead><tbody id="ld_ds"></tbody></table>
          </div>
        </div></div></div>
      </div>
    </div>
    <div class="tab-pane fade" id="tab-qua">
      <div class="row g-3">
        <div class="col-md-4"><div class="card shadow-sm" data-aos="fade-up"><div class="card-body">
          <h6>Thêm quà tặng</h6>
          <div class="mb-2"><label class="form-label">Tên</label><input id="q_ten" class="form-control"></div>
          <div class="mb-2"><label class="form-label">Giá điểm</label><input id="q_gia" type="number" class="form-control" value="1"></div>
          <div class="mb-2"><label class="form-label">Tồn kho</label><input id="q_ton" type="number" class="form-control" value="0"></div>
          <div class="mb-2"><label class="form-label">URL ảnh (tùy chọn)</label><input id="q_anh" class="form-control" placeholder="https://..."></div>
          <button class="btn btn-primary btn-sm" id="q_them">Thêm</button>
        </div></div></div>
        <div class="col-md-8"><div class="card shadow-sm" data-aos="fade-up"><div class="card-body">
          <div class="d-flex align-items-center justify-content-between"><h6 class="mb-0">Danh sách</h6><small class="text-muted">Bấm bật/tắt, sửa hoặc xóa</small></div>
          <div class="table-responsive mt-2">
            <table class="table table-sm align-middle"><thead><tr><th>#</th><th>Ảnh</th><th>Tên</th><th>Giá điểm</th><th>Tồn kho</th><th>Trạng thái</th><th></th></tr></thead><tbody id="q_ds"></tbody></table>
          </div>
        </div></div></div>
      </div>
    </div>
    <div class="tab-pane fade" id="tab-lop">
      <div class="row g-3">
        <div class="col-md-4"><div class="card shadow-sm" data-aos="fade-up"><div class="card-body">
          <h6>Thêm lớp học</h6>
          <div class="mb-2"><label class="form-label">Tên lớp</label><input id="l_ten" class="form-control"></div>
          <button class="btn btn-primary btn-sm" id="l_them">Thêm</button>
        </div></div></div>
        <div class="col-md-8"><div class="card shadow-sm" data-aos="fade-up"><div class="card-body">
          <div class="d-flex align-items-center justify-content-between"><h6 class="mb-0">Danh sách</h6><small class="text-muted">Bấm bật/tắt, sửa hoặc xóa</small></div>
          <div class="table-responsive mt-2">
            <table class="table table-sm align-middle"><thead><tr><th>#</th><th>Tên</th><th>Trạng thái</th><th></th></tr></thead><tbody id="l_ds"></tbody></table>
          </div>
        </div></div></div>
      </div>
    </div>
    <div class="tab-pane fade" id="tab-tai-khoan">
      <div class="row g-3">
        <div class="col-md-6"><div class="card shadow-sm" data-aos="fade-up"><div class="card-body">
          <h6>Đổi mật khẩu</h6>
          <div class="mb-2"><label class="form-label">Mật khẩu hiện tại</label><input id="gv_mk_cu" type="password" class="form-control"></div>
          <div class="mb-2"><label class="form-label">Mật khẩu mới</label><input id="gv_mk_moi" type="password" class="form-control"></div>
          <div class="mb-2"><label class="form-label">Nhập lại mật khẩu mới</label><input id="gv_mk_lai" type="password" class="form-control"></div>
          <button class="btn btn-primary btn-sm" id="gv_doi">Đổi mật khẩu</button>
          <span id="gv_doi_msg" class="ms-2 small text-muted"></span>
        </div></div></div>
        <div class="col-md-6"><div class="card shadow-sm" data-aos="fade-up"><div class="card-body">
          <h6>Thêm giáo viên mới</h6>
          <div class="mb-2"><label class="form-label">Tên đăng nhập</label><input id="gv_ten" class="form-control" placeholder="vd: gv2"></div>
          <div class="mb-2"><label class="form-label">Mật khẩu</label><input id="gv_mk" type="password" class="form-control"></div>
          <button class="btn btn-success btn-sm" id="gv_them">Thêm</button>
          <span id="gv_them_msg" class="ms-2 small text-muted"></span>
        </div></div></div>
      </div>
    </div>
  </div>
 </div>

 <!-- Modal chỉnh sửa chung -->
 <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
   <div class="modal-dialog">
     <div class="modal-content">
       <div class="modal-header">
         <h5 class="modal-title" id="editModalTitle">Chỉnh sửa</h5>
         <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
       </div>
       <div class="modal-body">
         <form id="editModalForm"></form>
         <div id="editModalMsg" class="small text-danger mt-1"></div>
       </div>
       <div class="modal-footer">
         <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
         <button type="button" class="btn btn-primary" id="editModalSave">Lưu</button>
       </div>
     </div>
   </div>
 </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Helpers
async function jfetch(url, opts){ const r = await fetch(url, opts); return await r.json(); }
function badge(on){ return `<span class="badge ${on? 'bg-success':'bg-secondary'}">${on?'Bật':'Tắt'}</span>` }

// Lý do
async function ldNap(){ const j = await jfetch('/api/ly_do_quan_tri.php'); if(!j.ok) return; const tb = document.getElementById('ld_ds'); tb.innerHTML='';
  j.du_lieu.forEach(x => {
    const tr = document.createElement('tr');
    // Gắn data cho dòng (ly_do)
    tr.dataset.id = x.id;
    tr.dataset.tieu_de = x.tieu_de;
    tr.dataset.bien_diem = x.bien_diem;
    tr.innerHTML = `<td>${x.id}</td><td>${x.tieu_de}</td><td>${x.bien_diem}</td><td>${badge(x.dang_hoat_dong)}</td>
    <td class="text-end">
      <button class="btn btn-sm btn-outline-primary me-1">Sửa</button>
      <button class="btn btn-sm btn-outline-warning me-1">${x.dang_hoat_dong? 'Tắt':'Bật'}</button>
      <button class="btn btn-sm btn-outline-danger">Xóa</button>
    </td>`;
    const [btnSua, btnToggle, btnXoa] = tr.querySelectorAll('button');
    btnSua.onclick = async()=>{
      const t = prompt('Tiêu đề', x.tieu_de); if(t===null) return; const b = prompt('Biến điểm', x.bien_diem); if(b===null) return;
      const jj = await jfetch('/api/ly_do_quan_tri.php?hanh_dong=sua',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:x.id, tieu_de:t.trim(), bien_diem:parseInt(b,10)||0})});
      if(jj.ok) ldNap(); else alert(jj.thong_bao||'Lỗi'); };
    btnToggle.onclick = async()=>{
      const jj = await jfetch('/api/ly_do_quan_tri.php?hanh_dong=bat_tat',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:x.id, dang_hoat_dong:x.dang_hoat_dong?0:1})});
      if(jj.ok) ldNap(); else alert(jj.thong_bao||'Lỗi'); };
    btnXoa.onclick = async()=>{
      if(!confirm('Xóa lý do này?')) return;
      const jj = await jfetch('/api/ly_do_quan_tri.php?hanh_dong=xoa',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:x.id})});
      if(jj.ok) ldNap(); else alert(jj.thong_bao||'Lỗi'); };
    tb.appendChild(tr);
  });
}
document.getElementById('ld_them').onclick = async()=>{
  const t = document.getElementById('ld_tieu_de').value.trim(); const b = parseInt(document.getElementById('ld_bien_diem').value,10)||0;
  const j = await jfetch('/api/ly_do_quan_tri.php?hanh_dong=them',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({tieu_de:t, bien_diem:b})});
  if(j.ok){ document.getElementById('ld_tieu_de').value=''; ldNap(); } else alert(j.thong_bao||'Lỗi');
};

// Quà tặng
async function qNap(){ const j = await jfetch('/api/qua_tang_quan_tri.php'); if(!j.ok) return; const tb = document.getElementById('q_ds'); tb.innerHTML='';
  j.du_lieu.forEach(x => {
    const tr = document.createElement('tr');
    // Gắn data cho dòng (qua_tang)
    tr.dataset.id = x.id;
    tr.dataset.ten = x.ten;
    tr.dataset.gia_diem = x.gia_diem;
    tr.dataset.ton_kho = x.ton_kho;
    tr.dataset.anh_url = x.anh_url || '';
    const av = (x.anh_url && String(x.anh_url).trim()!=='') ? x.anh_url : '/upload/avatar/default.svg';
    tr.innerHTML = `<td>${x.id}</td><td><img src="${av}" alt="" style="width:32px;height:32px;object-fit:cover;border:1px solid #ddd;border-radius:6px" onerror="this.onerror=null;this.src='/upload/avatar/default.svg';"></td><td>${x.ten}</td><td>${x.gia_diem}</td><td>${x.ton_kho}</td><td>${badge(x.dang_hoat_dong)}</td>
    <td class="text-end">
      <button class="btn btn-sm btn-outline-primary me-1">Sửa</button>
      <button class="btn btn-sm btn-outline-info me-1">Ảnh</button>
      <button class="btn btn-sm btn-outline-warning me-1">${x.dang_hoat_dong? 'Tắt':'Bật'}</button>
      <button class="btn btn-sm btn-outline-danger">Xóa</button>
    </td>`;
    const [btnSua, btnAnh, btnToggle, btnXoa] = tr.querySelectorAll('button');
    btnSua.onclick = async()=>{
      const ten = prompt('Tên', x.ten); if(ten===null) return; const gia = prompt('Giá điểm', x.gia_diem); if(gia===null) return; const ton = prompt('Tồn kho', x.ton_kho); if(ton===null) return;
      const anh = prompt('URL ảnh (bỏ trống để giữ nguyên)', tr.dataset.anh_url||''); if(anh===null) return;
      const jj = await jfetch('/api/qua_tang_quan_tri.php?hanh_dong=sua',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:x.id, ten:ten.trim(), gia_diem:parseInt(gia,10)||0, ton_kho:parseInt(ton,10)||0, anh_url:String(anh||'')})});
      if(jj.ok) qNap(); else alert(jj.thong_bao||'Lỗi'); };
    btnAnh.onclick = ()=>{
      const input = document.createElement('input'); input.type='file'; input.accept='image/*';
      input.onchange = async()=>{
        if(!input.files || !input.files[0]) return;
        const fd = new FormData(); fd.append('qua_tang_id', x.id); fd.append('file', input.files[0]);
        const r = await fetch('/api/upload_qua.php', { method:'POST', body: fd }); const jj = await r.json();
        if(jj.ok){ qNap(); } else alert(jj.thong_bao||'Lỗi upload');
      };
      input.click();
    };
    btnToggle.onclick = async()=>{
      const jj = await jfetch('/api/qua_tang_quan_tri.php?hanh_dong=bat_tat',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:x.id, dang_hoat_dong:x.dang_hoat_dong?0:1})});
      if(jj.ok) qNap(); else alert(jj.thong_bao||'Lỗi'); };
    btnXoa.onclick = async()=>{
      if(!confirm('Xóa quà tặng này?')) return;
      const jj = await jfetch('/api/qua_tang_quan_tri.php?hanh_dong=xoa',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:x.id})});
      if(jj.ok) qNap(); else alert(jj.thong_bao||'Lỗi'); };
    tb.appendChild(tr);
  });
}
document.getElementById('q_them').onclick = async()=>{ const ten = document.getElementById('q_ten').value.trim(); const gia = parseInt(document.getElementById('q_gia').value,10)||0; const ton = parseInt(document.getElementById('q_ton').value,10)||0; const anh = (document.getElementById('q_anh') ? document.getElementById('q_anh').value.trim() : ''); const j = await jfetch('/api/qua_tang_quan_tri.php?hanh_dong=them',{method:'POST',headers:{'Content-Type':'application/json'},body: JSON.stringify({ten, gia_diem:gia, ton_kho:ton, anh_url:anh})}); if(j.ok){ document.getElementById('q_ten').value=''; if(document.getElementById('q_anh')) document.getElementById('q_anh').value=''; qNap(); } else alert(j.thong_bao||'Loi'); };

// Lớp học
async function lNap(){ const j = await jfetch('/api/lop_hoc_quan_tri.php'); if(!j.ok) return; const tb = document.getElementById('l_ds'); tb.innerHTML='';
  j.du_lieu.forEach(x => {
    const tr = document.createElement('tr');
    // Gắn data cho dòng (lop_hoc)
    tr.dataset.id = x.id;
    tr.dataset.ten = x.ten;
    tr.innerHTML = `<td>${x.id}</td><td>${x.ten}</td><td>${badge(x.dang_hoat_dong)}</td>
    <td class="text-end">
      <button class="btn btn-sm btn-outline-primary me-1">Sửa</button>
      <button class="btn btn-sm btn-outline-warning me-1">${x.dang_hoat_dong? 'Tắt':'Bật'}</button>
      <button class="btn btn-sm btn-outline-danger">Xóa</button>
    </td>`;
    const [btnSua, btnToggle, btnXoa] = tr.querySelectorAll('button');
    btnSua.onclick = async()=>{
      const ten = prompt('Tên lớp', x.ten); if(ten===null) return;
      const jj = await jfetch('/api/lop_hoc_quan_tri.php?hanh_dong=sua',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:x.id, ten:ten.trim()})});
      if(jj.ok) lNap(); else alert(jj.thong_bao||'Lỗi'); };
    btnToggle.onclick = async()=>{
      const jj = await jfetch('/api/lop_hoc_quan_tri.php?hanh_dong=bat_tat',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:x.id, dang_hoat_dong:x.dang_hoat_dong?0:1})});
      if(jj.ok) lNap(); else alert(jj.thong_bao||'Lỗi'); };
    btnXoa.onclick = async()=>{
      if(!confirm('Xóa lớp học này?')) return;
      const jj = await jfetch('/api/lop_hoc_quan_tri.php?hanh_dong=xoa',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:x.id})});
      if(jj.ok) lNap(); else alert(jj.thong_bao||'Lỗi'); };
    tb.appendChild(tr);
  });
}
document.getElementById('l_them').onclick = async()=>{
  const ten = document.getElementById('l_ten').value.trim();
  const j = await jfetch('/api/lop_hoc_quan_tri.php?hanh_dong=them',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({ten})});
  if(j.ok){ document.getElementById('l_ten').value=''; lNap(); } else alert(j.thong_bao||'Lỗi');
};

// Tài khoản giáo viên
(function(){
  const btnDoi = document.getElementById('gv_doi');
  if (btnDoi) btnDoi.onclick = async()=>{
    const mk_cu = document.getElementById('gv_mk_cu').value;
    const mk_moi = document.getElementById('gv_mk_moi').value;
    const mk_lai = document.getElementById('gv_mk_lai').value;
    const msg = document.getElementById('gv_doi_msg');
    msg.textContent='';
    if (!mk_moi || mk_moi !== mk_lai) { msg.textContent='Mật khẩu nhập lại không khớp'; return; }
    const j = await jfetch('/api/giao_vien_quan_tri.php?hanh_dong=doi_mat_khau',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({mat_khau_cu:mk_cu, mat_khau_moi:mk_moi})});
    if (j.ok) { msg.textContent='Đã đổi mật khẩu'; document.getElementById('gv_mk_cu').value=''; document.getElementById('gv_mk_moi').value=''; document.getElementById('gv_mk_lai').value=''; }
    else { msg.textContent=j.thong_bao||'Lỗi'; }
  };
  const btnThem = document.getElementById('gv_them');
  if (btnThem) btnThem.onclick = async()=>{
    const ten = document.getElementById('gv_ten').value.trim();
    const mk = document.getElementById('gv_mk').value;
    const msg = document.getElementById('gv_them_msg');
    msg.textContent='';
    if (!ten || !mk) { msg.textContent='Nhập đủ tên đăng nhập và mật khẩu'; return; }
    const j = await jfetch('/api/giao_vien_quan_tri.php?hanh_dong=them',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({ten_dang_nhap:ten, mat_khau:mk})});
    if (j.ok) { msg.textContent='Đã thêm giáo viên'; document.getElementById('gv_ten').value=''; document.getElementById('gv_mk').value=''; }
    else { msg.textContent=j.thong_bao||'Lỗi'; }
  };
})();

// Popup nhập liệu (Bootstrap modal)
async function openEdit(opts){
  const title = opts.title || 'Chỉnh sửa';
  const fields = Array.isArray(opts.fields)? opts.fields : [];
  document.getElementById('editModalTitle').textContent = title;
  const form = document.getElementById('editModalForm');
  form.innerHTML = '';
  const msg = document.getElementById('editModalMsg');
  msg.textContent = '';
  fields.forEach(f => {
    const id = 'f_' + f.name;
    const div = document.createElement('div');
    div.className = 'mb-2';
    const label = document.createElement('label');
    label.className = 'form-label';
    label.textContent = f.label || f.name;
    label.setAttribute('for', id);
    const input = document.createElement('input');
    input.className = 'form-control';
    input.id = id;
    input.type = f.type || 'text';
    if (f.placeholder) input.placeholder = f.placeholder;
    if (f.value !== undefined && f.value !== null) input.value = f.value;
    div.appendChild(label); div.appendChild(input);
    form.appendChild(div);
  });
  const modalEl = document.getElementById('editModal');
  const modal = new bootstrap.Modal(modalEl);
  return await new Promise(resolve => {
    const onHide = () => { modalEl.removeEventListener('hidden.bs.modal', onHide); resolve(null); };
    modalEl.addEventListener('hidden.bs.modal', onHide);
    const saveBtn = document.getElementById('editModalSave');
    const onSave = () => {
      const out = {};
      let valid = true;
      fields.forEach(f => {
        const el = document.getElementById('f_' + f.name);
        let v = el.value;
        if (f.type === 'number') { v = parseInt(v, 10); if (isNaN(v)) v = 0; }
        if (f.required && (v === '' || v === null || v === undefined)) valid = false;
        out[f.name] = v;
      });
      if (!valid) { msg.textContent = 'Vui lòng nhập đầy đủ thông tin'; return; }
      saveBtn.removeEventListener('click', onSave);
      modalEl.removeEventListener('hidden.bs.modal', onHide);
      modal.hide();
      resolve(out);
    };
    saveBtn.addEventListener('click', onSave);
    modal.show();
  });
}

// Bắt sự kiện sửa bằng modal (ghi đè trước onclick cũ)
document.getElementById('ld_ds').addEventListener('click', async (e) => {
  const btn = e.target.closest('button.btn-outline-primary'); if(!btn) return;
  const tr = btn.closest('tr'); if(!tr) return; e.preventDefault(); e.stopImmediatePropagation();
  const res = await openEdit({ title: 'Sửa lý do', fields: [
    { name:'tieu_de', label:'Tiêu đề', type:'text', value: tr.dataset.tieu_de || '', required: true },
    { name:'bien_diem', label:'Biến điểm', type:'number', value: tr.dataset.bien_diem || 0, required: true }
  ]});
  if(!res) return;
  const jj = await jfetch('/api/ly_do_quan_tri.php?hanh_dong=sua',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:parseInt(tr.dataset.id,10)||0, tieu_de:String(res.tieu_de||'').trim(), bien_diem:parseInt(res.bien_diem,10)||0})});
  if(jj.ok) ldNap(); else alert(jj.thong_bao||'Loi');
}, true);

document.getElementById('q_ds').addEventListener('click', async (e) => {
  const btn = e.target.closest('button.btn-outline-primary'); if(!btn) return;
  const tr = btn.closest('tr'); if(!tr) return; e.preventDefault(); e.stopImmediatePropagation();
  const res = await openEdit({ title: 'Sửa quà tặng', fields: [
    { name:'ten', label:'Tên', type:'text', value: tr.dataset.ten || '', required: true },
    { name:'gia_diem', label:'Giá điểm', type:'number', value: tr.dataset.gia_diem || 0, required: true },
    { name:'ton_kho', label:'Tồn kho', type:'number', value: tr.dataset.ton_kho || 0, required: true }
  ]});
  if(!res) return;
  const jj = await jfetch('/api/qua_tang_quan_tri.php?hanh_dong=sua',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:parseInt(tr.dataset.id,10)||0, ten:String(res.ten||'').trim(), gia_diem:parseInt(res.gia_diem,10)||0, ton_kho:parseInt(res.ton_kho,10)||0})});
  if(jj.ok) qNap(); else alert(jj.thong_bao||'Loi');
}, true);

document.getElementById('l_ds').addEventListener('click', async (e) => {
  const btn = e.target.closest('button.btn-outline-primary'); if(!btn) return;
  const tr = btn.closest('tr'); if(!tr) return; e.preventDefault(); e.stopImmediatePropagation();
  const res = await openEdit({ title: 'Sửa lớp học', fields: [
    { name:'ten', label:'Tên lớp', type:'text', value: tr.dataset.ten || '', required: true }
  ]});
  if(!res) return;
  const jj = await jfetch('/api/lop_hoc_quan_tri.php?hanh_dong=sua',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:parseInt(tr.dataset.id,10)||0, ten:String(res.ten||'').trim()})});
  if(jj.ok) lNap(); else alert(jj.thong_bao||'Loi');
}, true);

// Load
ldNap(); qNap(); lNap();
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
<script>AOS.init({ duration: 350, once: true, easing: 'ease-out' });</script>
</body></html>

