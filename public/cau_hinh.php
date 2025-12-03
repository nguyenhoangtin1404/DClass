<?php
require __DIR__ . '/../config/db.php'; require __DIR__ . '/../lib/tro_giup.php';
if (!isset($_SESSION['giao_vien_id'])) { header('Location: dang_nhap.php'); exit; }
if (($_SESSION['vai_tro'] ?? '') !== 'ADMIN') { header('Location: trang_chinh.php'); exit; }
?><!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Cấu hình hệ thống</title>
<link rel="stylesheet" href="vendor/bootswatch/bootstrap.min.css"><link rel="stylesheet" href="vendor/bootstrap-icons/bootstrap-icons.css"><link rel="stylesheet" href="vendor/aos/aos.css"><link rel="stylesheet" href="theme.css"><link rel="stylesheet" href="custom_file.css"><link rel="stylesheet" href="date_picker.css"><style>
.nav-tabs.nav-square {
  background: #e9f0fb;
  border: 1px solid #d7e1f5;
  border-radius: 12px;
  padding: 4px;
  box-shadow: 0 10px 20px rgba(25, 75, 150, 0.12);
  gap: 4px;
}
.nav-tabs.nav-square .nav-link {
  border: none;
  border-radius: 8px;
  color: #5f6b85;
  padding: 10px 18px;
  font-weight: 600;
}
.nav-tabs.nav-square .nav-link:hover { color: #1d4ed8; }
.nav-tabs.nav-square .nav-link.active {
  background: #2f8af5;
  color: #fff;
  box-shadow: 0 8px 18px rgba(47, 138, 245, 0.35);
}
.hs-avatar { width:56px; height:56px; border-radius:8px; border:1px solid #ddd; object-fit:cover; object-position:center; background:#fff; display:block; }
</style></head><body><?php include __DIR__ . '/_nav.php'; ?>
<div class="container py-3">
  <div class="d-flex align-items-center justify-content-between"><h4 class="mb-0">Cấu hình hệ thống</h4></div>

  <ul class="nav nav-tabs mt-3 nav-square" id="tab" role="tablist">
    <li class="nav-item" role="presentation"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-ly-do" type="button">Lý do</button></li>
    <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-qua" type="button">Quà tặng</button></li>
    <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-lop" type="button">Lớp học</button></li>
    <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-hoc-sinh" type="button">Học sinh</button></li>
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
            <table class="table table-sm align-middle"><thead><tr><th>#</th><th>Tên</th><th>Trạng thái</th><th>Giáo viên quản lý</th><th></th></tr></thead><tbody id="l_ds"></tbody></table>
          </div>
        </div></div></div>
      </div>
    </div>
    <div class="tab-pane fade" id="tab-hoc-sinh">
      <div class="row g-3">
        <div class="col-md-5">
          <div class="card shadow-sm" data-aos="fade-up"><div class="card-body">
            <div class="mb-2"><input id="tu_khoa" class="form-control" placeholder="Tìm học sinh"></div>
            <div class="form-check mb-2">
              <input class="form-check-input" type="checkbox" id="hien_tat_ca">
              <label class="form-check-label" for="hien_tat_ca">Hiện học sinh đã tắt</label>
            </div>
            <div id="ds" class="list-group" style="max-height:60vh;overflow:auto"></div>
          </div></div>
        </div>
        <div class="col-md-7">
          <div class="card shadow-sm" data-aos="fade-up"><div class="card-body">
            <h6>Thêm học sinh</h6>
            <div class="row g-2">
              <div class="col-4"><input id="ma" class="form-control" placeholder="Mã (tùy chọn)"></div>
              <div class="col-8"><input id="ho_ten" class="form-control" placeholder="Họ tên"></div>
              <div class="col-6 mt-2"><input id="anh_dai_dien_url" class="form-control" placeholder="Ảnh đại diện URL"></div>
              <div class="col-3 mt-2">
                <select id="gioi_tinh" class="form-select">
                  <option value="">Giới tính...</option>
                  <option value="NAM">Nam</option>
                  <option value="NU">Nữ</option>
                  <option value="KHAC">Khác</option>
                </select>
              </div>
              <div class="col-3 mt-2"><input id="ngay_sinh" type="text" class="form-control date-picker" placeholder="Ngày sinh" data-datepicker="1"></div>
            </div>
            <div class="mt-2"><button id="them" class="btn btn-primary">Thêm</button><span id="msg" class="ms-2 small text-muted"></span></div>
            <hr>
            <div class="d-flex align-items-center gap-2">
              <div class="me-auto" style="max-width:320px;">
                <label class="form-label">Nhập CSV</label>
                <div class="custom-file-input-sm position-relative">
                  <input type="file" id="csv_file" accept=".csv" class="form-control form-control-sm" aria-label="Chọn file CSV">
                  <span class="custom-file-label" id="csv_file_label">Chưa chọn tệp</span>
                </div>
              </div>
              <div class="pt-4">
                <div class="d-flex gap-2">
                  <button id="nhap_csv" class="btn btn-outline-primary btn-sm px-3">Nhập CSV</button>
                  <button id="xuat_csv" class="btn btn-outline-primary btn-sm px-3">Xuất CSV</button>
                </div>
              </div>
            </div>
            <div id="csv_msg" class="small text-muted mt-2"></div>
            <hr>
            <h6>Chi tiết học sinh</h6>
            <div id="ct_no_sel" class="text-muted">Chưa chọn học sinh</div>
            <div id="ct_sel" class="d-none">
              <div class="d-flex align-items-center gap-3">
                <img id="ct_avatar" class="hs-avatar" src="../upload/avatar/default.svg" alt="avatar" onerror="this.onerror=null;this.src='../upload/avatar/default.svg';">
                <div>
                  <div id="ct_ten" class="fw-semibold"></div>
                  <div class="small text-muted"><span id="ct_lop"></span> - <span id="ct_trang_thai"></span></div>
                </div>
              </div>
              <div class="d-flex align-items-center gap-2 mt-2">
                <input type="file" id="up_anh" accept="image/*" class="form-control form-control-sm" style="max-width:320px">
                <button id="btn_up_anh" class="btn btn-outline-primary btn-sm">Upload ảnh</button>
                <button id="btn_toggle" class="btn btn-outline-warning btn-sm">Tắt</button>
              </div>
              <div id="ct_msg" class="small text-muted mt-1"></div>
              <div class="row g-2 mt-2">
                <div class="col-4"><label class="form-label">Mã</label><input id="ct_ma" class="form-control"></div>
                <div class="col-8"><label class="form-label">Họ tên</label><input id="ct_ho_ten" class="form-control"></div>
                <div class="col-6"><label class="form-label">Lớp</label><select id="ct_lop" class="form-select"></select></div>
                <div class="col-3"><label class="form-label">Giới tính</label><select id="ct_gioi" class="form-select"><option value="">--</option><option value="NAM">Nam</option><option value="NU">Nữ</option><option value="KHAC">Khác</option></select></div>
                <div class="col-3"><label class="form-label">Ngày sinh</label><input id="ct_ngay" type="text" class="form-control date-picker" data-datepicker="1" placeholder="dd/mm/yyyy"></div>
              </div>
              <div class="mt-2"><button id="btn_luu" class="btn btn-primary btn-sm">Lưu thay đổi</button></div>
            </div>
          </div></div>
        </div>
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
          <button class="btn btn-primary btn-sm" id="gv_them">Thêm</button>
          <span id="gv_them_msg" class="ms-2 small text-muted"></span>
        </div></div></div>
      </div>
      <div class="card shadow-sm mt-2" data-aos="fade-up">
        <div class="card-body">
          <h6 class="mb-2">Phân quyền tài khoản</h6>
          <div class="d-flex align-items-center justify-content-end mb-2">
            <button id="gv_log_reload" class="btn btn-primary btn-sm">Xem log thao tác</button>
          </div>
          <div class="table-responsive">
            <table class="table table-sm align-middle mb-0 role-table">
              <thead>
                <tr>
                  <th style="width:180px;">Tài khoản</th>
                  <th style="width:220px;">Vai trò</th>
                  <th style="width:200px;">Thao tác</th>
                </tr>
              </thead>
              <tbody id="gv_quyen_ds"></tbody>
            </table>
          </div>
          <div id="gv_quyen_msg" class="small text-muted mt-2"></div>
          <div class="table-responsive mt-3 d-none" id="gv_log_wrap">
            <table class="table table-sm align-middle mb-0">
              <thead><tr><th>Thời gian</th><th>Tài khoản</th><th>Hành động</th><th>Nội dung</th></tr></thead>
              <tbody id="gv_log_ds"></tbody>
            </table>
          </div>
        </div>
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

<script src="vendor/bootstrap/bootstrap.bundle.min.js"></script>
<script>
// Helpers
async function jfetch(url, opts){ const r = await fetch(url, opts); return await r.json(); }
function badge(on){ return `<span class="badge ${on? 'bg-success':'bg-warning text-dark'}">${on?'Bật':'Tắt'}</span>` }
let _gvDs = [];
async function loadGiaoVienDs(){
  const r = await jfetch('../api/giao_vien_quan_tri.php?hanh_dong=ds');
  if(r.ok && r.du_lieu && Array.isArray(r.du_lieu.giao_vien)) { _gvDs = r.du_lieu.giao_vien; }
}
async function gvNapQuyen(){
  const tb = document.getElementById('gv_quyen_ds'); const msg = document.getElementById('gv_quyen_msg');
  if(!tb) return;
  tb.innerHTML=''; if(msg) msg.textContent='';
  const j = await jfetch('../api/giao_vien_quan_tri.php?hanh_dong=ds_tk');
  if(!j.ok){ if(msg) msg.textContent = j.thong_bao||'Không tải được danh sách'; return; }
  const roles = ['ADMIN','GV'];
  (j.du_lieu?.tai_khoan||[]).forEach(tk=>{
    const tr=document.createElement('tr');
    const select=document.createElement('select');
    select.className='form-select form-select-sm';
    roles.forEach(r=>{ const opt=document.createElement('option'); opt.value=r; opt.textContent=r; if(String(tk.vai_tro||'GV')===r) opt.selected=true; select.appendChild(opt); });
    const btnSave=document.createElement('button'); btnSave.type='button'; btnSave.className='btn btn-primary btn-sm';
    btnSave.textContent='Lưu';
    btnSave.onclick=async()=>{
      btnSave.disabled=true; const jj = await jfetch('../api/giao_vien_quan_tri.php?hanh_dong=cap_nhat_vai_tro',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:tk.id, vai_tro: select.value})});
      if(jj.ok){ if(msg) msg.textContent='Đã lưu quyền cho ' + tk.ten_dang_nhap; }
      else { if(msg) msg.textContent=jj.thong_bao||'Lỗi'; }
      btnSave.disabled=false;
    };
    const btnReset=document.createElement('button'); btnReset.type='button'; btnReset.className='btn btn-danger btn-sm';
    btnReset.textContent='Đặt lại MK';
    btnReset.onclick=async()=>{
      const mk = prompt('Nhập mật khẩu mới cho '+tk.ten_dang_nhap);
      if(mk===null) return;
      const mkClean = (mk||'').trim();
      if(!mkClean){ if(msg) msg.textContent='Nhập mật khẩu mới'; return; }
      btnReset.disabled=true;
      const jj = await jfetch('../api/giao_vien_quan_tri.php?hanh_dong=reset_mat_khau',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:tk.id, mat_khau_moi: mkClean})});
      if(jj.ok){ if(msg) msg.textContent='Đã đặt lại mật khẩu cho ' + tk.ten_dang_nhap; }
      else { if(msg) msg.textContent=jj.thong_bao||'Lỗi'; }
      btnReset.disabled=false;
    };
    btnSave.classList.add('px-3');
    const tdUser=document.createElement('td'); tdUser.textContent=tk.ten_dang_nhap;
    const tdRole=document.createElement('td'); tdRole.appendChild(select);
    const tdBtn=document.createElement('td');
    const wrap=document.createElement('div'); wrap.className='d-flex flex-wrap gap-2';
    wrap.appendChild(btnSave); wrap.appendChild(btnReset); tdBtn.appendChild(wrap);
    tr.appendChild(tdUser); tr.appendChild(tdRole); tr.appendChild(tdBtn);
    tb.appendChild(tr);
  });
}
async function gvNapLog(){
  const wrap=document.getElementById('gv_log_wrap'); const ds=document.getElementById('gv_log_ds'); const msg=document.getElementById('gv_quyen_msg');
  if(!wrap || !ds) return;
  wrap.classList.remove('d-none');
  ds.innerHTML='';
  const j = await jfetch('../api/nhat_ky.php?limit=200');
  if(!j.ok){ if(msg) msg.textContent=j.thong_bao||'Không tải được log'; return; }
  (j.du_lieu||[]).forEach(row=>{
    const tr=document.createElement('tr');
    tr.innerHTML = `<td>${row.tao_luc||''}</td><td>${row.ten_dang_nhap||''}</td><td>${row.hanh_dong||''}</td><td>${row.noi_dung||''}</td>`;
    ds.appendChild(tr);
  });
}

async function openClassModal(lop){
  if(!_gvDs.length){ await loadGiaoVienDs(); }
  const modalEl = document.getElementById('editModal'); const modal = new bootstrap.Modal(modalEl);
  document.getElementById('editModalTitle').textContent = lop ? 'Sửa lớp học' : 'Thêm lớp học';
  const form = document.getElementById('editModalForm'); const msg = document.getElementById('editModalMsg');
  form.innerHTML=''; msg.textContent='';
  const selected = new Set((lop && Array.isArray(lop.giao_vien_ids)) ? lop.giao_vien_ids.map(v=>String(v)) : []);
  const divTen = document.createElement('div'); divTen.className='mb-2';
  divTen.innerHTML = `<label class="form-label">Tên lớp</label><input id="f_lop_ten" class="form-control" value="${lop?.ten||''}" required>`;
  form.appendChild(divTen);
  const divGv = document.createElement('div'); divGv.className='mb-2';
  const lbl = document.createElement('label'); lbl.className='form-label'; lbl.textContent='Giáo viên quản lý'; divGv.appendChild(lbl);
  const filter = document.createElement('input'); filter.type='search'; filter.className='form-control form-control-sm mb-2'; filter.placeholder='Lọc giáo viên...';
  divGv.appendChild(filter);
  const wrap = document.createElement('div'); wrap.className='d-flex flex-column gap-1'; wrap.style.maxHeight='240px'; wrap.style.overflow='auto';
  _gvDs.forEach(g=>{
    const id = 'gvsel_'+g.id;
    const item = document.createElement('div'); item.className='form-check';
    item.dataset.label = (g.ten_dang_nhap||'').toLowerCase();
    const inp = document.createElement('input'); inp.type='checkbox'; inp.className='form-check-input'; inp.id=id; inp.value=g.id; if(selected.has(String(g.id))) inp.checked=true;
    const lb = document.createElement('label'); lb.className='form-check-label'; lb.setAttribute('for', id); lb.textContent=g.ten_dang_nhap;
    item.appendChild(inp); item.appendChild(lb); wrap.appendChild(item);
  });
  const applyFilter = ()=>{
    const q = (filter.value||'').toLowerCase();
    wrap.querySelectorAll('.form-check').forEach(el=>{
      const lblTxt = el.dataset.label||'';
      el.style.display = q==='' || lblTxt.includes(q) ? '' : 'none';
    });
  };
  filter.addEventListener('input', applyFilter);
  divGv.appendChild(wrap); form.appendChild(divGv);
  return await new Promise(resolve=>{
    const onHide = ()=>{ modalEl.removeEventListener('hidden.bs.modal', onHide); document.getElementById('editModalSave').removeEventListener('click', onSave); resolve(null); };
    const onSave = ()=>{ const tenVal = (document.getElementById('f_lop_ten')?.value||'').trim(); if(!tenVal){ msg.textContent='Nhập tên lớp'; return; }
      const ids = Array.from(form.querySelectorAll('input.form-check-input[type=\"checkbox\"]')).filter(i=>i.checked).map(i=>parseInt(i.value,10)).filter(n=>!isNaN(n));
      modal.hide(); modalEl.removeEventListener('hidden.bs.modal', onHide); document.getElementById('editModalSave').removeEventListener('click', onSave); resolve({ ten:tenVal, giao_vien_ids: ids }); };
    document.getElementById('editModalSave').addEventListener('click', onSave);
    modalEl.addEventListener('hidden.bs.modal', onHide);
    modal.show();
  });
}

// Lý do
async function ldNap(){ const j = await jfetch('../api/ly_do_quan_tri.php'); if(!j.ok) return; const tb = document.getElementById('ld_ds'); tb.innerHTML='';
  j.du_lieu.forEach(x => {
    const tr = document.createElement('tr');
    // Gắn data cho dòng (ly_do)
    tr.dataset.id = x.id;
    tr.dataset.tieu_de = x.tieu_de;
    tr.dataset.bien_diem = x.bien_diem;
    tr.innerHTML = `<td>${x.id}</td><td>${x.tieu_de}</td><td>${x.bien_diem}</td><td>${badge(x.dang_hoat_dong)}</td>
    <td class="text-end">
      <div class="d-flex flex-nowrap justify-content-end gap-2">
        <button class="btn btn-outline-primary rounded-pill px-3 py-2">Sửa</button>
        <button class="btn btn-outline-warning rounded-pill px-3 py-2">${x.dang_hoat_dong? 'Tắt':'Bật'}</button>
        <button class="btn btn-outline-danger rounded-pill px-3 py-2">Xóa</button>
      </div>
    </td>`;
    const [btnSua, btnToggle, btnXoa] = tr.querySelectorAll('button');
    btnSua.onclick = async()=>{
      const t = prompt('Tiêu đề', x.tieu_de); if(t===null) return; const b = prompt('Biến điểm', x.bien_diem); if(b===null) return;
      const jj = await jfetch('../api/ly_do_quan_tri.php?hanh_dong=sua',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:x.id, tieu_de:t.trim(), bien_diem:parseInt(b,10)||0})});
      if(jj.ok) ldNap(); else alert(jj.thong_bao||'Lỗi'); };
    btnToggle.onclick = async()=>{
      const jj = await jfetch('../api/ly_do_quan_tri.php?hanh_dong=bat_tat',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:x.id, dang_hoat_dong:x.dang_hoat_dong?0:1})});
      if(jj.ok) ldNap(); else alert(jj.thong_bao||'Lỗi'); };
    btnXoa.onclick = async()=>{
      if(!confirm('Xóa lý do này?')) return;
      const jj = await jfetch('../api/ly_do_quan_tri.php?hanh_dong=xoa',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:x.id})});
      if(jj.ok) ldNap(); else alert(jj.thong_bao||'Lỗi'); };
    tb.appendChild(tr);
  });
}
document.getElementById('ld_them').onclick = async()=>{
  const t = document.getElementById('ld_tieu_de').value.trim(); const b = parseInt(document.getElementById('ld_bien_diem').value,10)||0;
  const j = await jfetch('../api/ly_do_quan_tri.php?hanh_dong=them',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({tieu_de:t, bien_diem:b})});
  if(j.ok){ document.getElementById('ld_tieu_de').value=''; ldNap(); } else alert(j.thong_bao||'Lỗi');
};

// Quà tặng
async function qNap(){ const j = await jfetch('../api/qua_tang_quan_tri.php'); if(!j.ok) return; const tb = document.getElementById('q_ds'); tb.innerHTML='';
  j.du_lieu.forEach(x => {
    const tr = document.createElement('tr');
    // Gắn data cho dòng (qua_tang)
    tr.dataset.id = x.id;
    tr.dataset.ten = x.ten;
    tr.dataset.gia_diem = x.gia_diem;
    tr.dataset.ton_kho = x.ton_kho;
    tr.dataset.anh_url = x.anh_url || '';
    const av = (x.anh_url && String(x.anh_url).trim()!=='') ? x.anh_url : '../upload/avatar/default.svg';
    tr.innerHTML = `<td>${x.id}</td><td><img src="${av}" alt="" style="width:32px;height:32px;object-fit:cover;border:1px solid #ddd;border-radius:6px" onerror="this.onerror=null;this.src='../upload/avatar/default.svg';"></td><td>${x.ten}</td><td>${x.gia_diem}</td><td>${x.ton_kho}</td><td>${badge(x.dang_hoat_dong)}</td>
    <td class="text-end">
      <div class="d-flex flex-nowrap justify-content-end gap-2">
        <button class="btn btn-outline-primary rounded-pill px-3 py-2">Sửa</button>
        <button class="btn btn-outline-info rounded-pill px-3 py-2">Ảnh</button>
        <button class="btn btn-outline-warning rounded-pill px-3 py-2">${x.dang_hoat_dong? 'Tắt':'Bật'}</button>
        <button class="btn btn-outline-danger rounded-pill px-3 py-2">Xóa</button>
      </div>
    </td>`;
    const [btnSua, btnAnh, btnToggle, btnXoa] = tr.querySelectorAll('button');
    btnSua.onclick = async()=>{
      const ten = prompt('Tên', x.ten); if(ten===null) return; const gia = prompt('Giá điểm', x.gia_diem); if(gia===null) return; const ton = prompt('Tồn kho', x.ton_kho); if(ton===null) return;
      const anh = prompt('URL ảnh (bỏ trống để giữ nguyên)', tr.dataset.anh_url||''); if(anh===null) return;
      const jj = await jfetch('../api/qua_tang_quan_tri.php?hanh_dong=sua',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:x.id, ten:ten.trim(), gia_diem:parseInt(gia,10)||0, ton_kho:parseInt(ton,10)||0, anh_url:String(anh||'')})});
      if(jj.ok) qNap(); else alert(jj.thong_bao||'Lỗi'); };
    btnAnh.onclick = ()=>{
      const input = document.createElement('input'); input.type='file'; input.accept='image/*';
      input.onchange = async()=>{
        if(!input.files || !input.files[0]) return;
        const fd = new FormData(); fd.append('qua_tang_id', x.id); fd.append('file', input.files[0]);
        const r = await fetch('../api/upload_qua.php', { method:'POST', body: fd }); const jj = await r.json();
        if(jj.ok){ qNap(); } else alert(jj.thong_bao||'Lỗi upload');
      };
      input.click();
    };
    btnToggle.onclick = async()=>{
      const jj = await jfetch('../api/qua_tang_quan_tri.php?hanh_dong=bat_tat',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:x.id, dang_hoat_dong:x.dang_hoat_dong?0:1})});
      if(jj.ok) qNap(); else alert(jj.thong_bao||'Lỗi'); };
    btnXoa.onclick = async()=>{
      if(!confirm('Xóa quà tặng này?')) return;
      const jj = await jfetch('../api/qua_tang_quan_tri.php?hanh_dong=xoa',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:x.id})});
      if(jj.ok) qNap(); else alert(jj.thong_bao||'Lỗi'); };
    tb.appendChild(tr);
  });
}
document.getElementById('q_them').onclick = async()=>{ const ten = document.getElementById('q_ten').value.trim(); const gia = parseInt(document.getElementById('q_gia').value,10)||0; const ton = parseInt(document.getElementById('q_ton').value,10)||0; const anh = (document.getElementById('q_anh') ? document.getElementById('q_anh').value.trim() : ''); const j = await jfetch('../api/qua_tang_quan_tri.php?hanh_dong=them',{method:'POST',headers:{'Content-Type':'application/json'},body: JSON.stringify({ten, gia_diem:gia, ton_kho:ton, anh_url:anh})}); if(j.ok){ document.getElementById('q_ten').value=''; if(document.getElementById('q_anh')) document.getElementById('q_anh').value=''; qNap(); } else alert(j.thong_bao||'Loi'); };

// Lớp học
async function lNap(){ if(!_gvDs.length) await loadGiaoVienDs(); const j = await jfetch('../api/lop_hoc_quan_tri.php'); if(!j.ok) return; const tb = document.getElementById('l_ds'); tb.innerHTML='';
  const gvDs = Array.isArray(_gvDs) ? _gvDs : [];
  j.du_lieu.forEach(x => {
    const tr = document.createElement('tr');
    const gvNames = (Array.isArray(x.giao_vien_ids) ? x.giao_vien_ids : []).map(id=>{
      const f = gvDs.find(g=>String(g.id)===String(id));
      return f ? f.ten_dang_nhap : '';
    }).filter(Boolean).join(', ');
    // Gắn data cho dòng (lop_hoc)
    tr.dataset.id = x.id;
    tr.dataset.ten = x.ten;
    tr.dataset.giao_vien_ids = JSON.stringify(x.giao_vien_ids||[]);
    tr.innerHTML = `<td>${x.id}</td><td>${x.ten}</td><td>${badge(x.dang_hoat_dong)}</td>
    <td>${gvNames}</td>
    <td class="text-end">
      <div class="d-flex flex-nowrap justify-content-end gap-2">
        <button class="btn btn-outline-primary rounded-pill px-3 py-2">Sửa</button>
        <button class="btn btn-outline-warning rounded-pill px-3 py-2">${x.dang_hoat_dong? 'Tắt':'Bật'}</button>
        <button class="btn btn-outline-danger rounded-pill px-3 py-2">Xóa</button>
      </div>
    </td>`;
    const [btnSua, btnToggle, btnXoa] = tr.querySelectorAll('button');
    btnSua.onclick = async()=>{
      const res = await openClassModal(x);
      if(!res) return;
      const body = { id:x.id, ten:res.ten, giao_vien_ids: res.giao_vien_ids };
      const jj = await jfetch('../api/lop_hoc_quan_tri.php?hanh_dong=sua',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(body)});
      if(jj.ok) lNap(); else alert(jj.thong_bao||'Lỗi'); };
    btnToggle.onclick = async()=>{
      const jj = await jfetch('../api/lop_hoc_quan_tri.php?hanh_dong=bat_tat',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:x.id, dang_hoat_dong:x.dang_hoat_dong?0:1})});
      if(jj.ok) lNap(); else alert(jj.thong_bao||'Lỗi'); };
    btnXoa.onclick = async()=>{
      if(!confirm('Xóa lớp học này?')) return;
      const jj = await jfetch('../api/lop_hoc_quan_tri.php?hanh_dong=xoa',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:x.id})});
      if(jj.ok) lNap(); else alert(jj.thong_bao||'Lỗi'); };
    tb.appendChild(tr);
  });
}
document.getElementById('l_them').onclick = async()=>{
  const ten = document.getElementById('l_ten').value.trim();
  const j = await jfetch('../api/lop_hoc_quan_tri.php?hanh_dong=them',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({ten})});
  if(j.ok){ document.getElementById('l_ten').value=''; lNap(); } else alert(j.thong_bao||'Lỗi');
};

// Học sinh
let hsDangChon = null;

function hsHienChiTiet(){
  const noSel = document.getElementById('ct_no_sel');
  const sel = document.getElementById('ct_sel');
  const msg = document.getElementById('ct_msg');
  if(!noSel || !sel) return;
  if(msg) msg.textContent='';
  if(!hsDangChon){ noSel.classList.remove('d-none'); sel.classList.add('d-none'); return; }
  noSel.classList.add('d-none'); sel.classList.remove('d-none');
  const ten = document.getElementById('ct_ten');
  const lopSel = document.querySelector('select#ct_lop');
  const tt = document.getElementById('ct_trang_thai');
  const av = document.getElementById('ct_avatar');
  if(ten) ten.textContent = hsDangChon.ho_ten || '';
  if(lopSel && lopSel.options && lopSel.options.length){
    const v = (hsDangChon.lop_hoc_id===null || hsDangChon.lop_hoc_id===undefined || hsDangChon.lop_hoc_id==='') ? '' : String(hsDangChon.lop_hoc_id);
    lopSel.value = v;
  }
  const lopText = document.querySelector('span#ct_lop');
  if(lopText && lopText.tagName !== 'SELECT') { lopText.textContent = 'Lớp: ' + (hsDangChon.ten_lop||''); }
  const active = Number(hsDangChon.dang_hoat_dong) === 1;
  if(tt) tt.textContent = active ? 'Đang bật' : 'Đang tắt';
  if(av) av.src = (hsDangChon.anh_dai_dien_url && hsDangChon.anh_dai_dien_url.trim()!=='') ? hsDangChon.anh_dai_dien_url : '../upload/avatar/default.svg';
  const btnToggle = document.getElementById('btn_toggle');
  if(btnToggle) btnToggle.textContent = active ? 'Tắt' : 'Bật';
}

async function hsNap(keepId=null){
  const tuKhoaEl = document.getElementById('tu_khoa');
  const tatCaEl = document.getElementById('hien_tat_ca');
  const tat_ca = tatCaEl && tatCaEl.checked ? 1 : 0;
  const r = await fetch('../api/hoc_sinh.php?tu_khoa='+encodeURIComponent(tuKhoaEl ? tuKhoaEl.value||'' : '')+'&tat_ca='+tat_ca);
  const j = await r.json(); const box=document.getElementById('ds'); if(box) box.innerHTML=''; else return;
  if(!j.ok) return;
  const sorted = [...j.du_lieu].sort((a,b)=>{
    const as = (a.stt===null || a.stt===undefined || a.stt==='') ? Number.POSITIVE_INFINITY : Number(a.stt);
    const bs = (b.stt===null || b.stt===undefined || b.stt==='') ? Number.POSITIVE_INFINITY : Number(b.stt);
    if (as !== bs) return as - bs;
    return String(a.ho_ten||'').localeCompare(String(b.ho_ten||''));
  });
  if(keepId){
    const found = sorted.find(s=>String(s.id)===String(keepId));
    if(found) hsDangChon = found;
  }
  sorted.forEach(s=>{
    const a=document.createElement('a'); a.href='#'; a.className='list-group-item list-group-item-action d-flex justify-content-between align-items-center';
    const sttText = (s.stt===null || s.stt===undefined || s.stt==='') ? '' : `${s.stt}. `;
    const active = Number(s.dang_hoat_dong) === 1;
    a.innerHTML = `<span>${sttText}${s.ho_ten} (${s.ten_lop||''}) - Điểm: ${s.so_du}</span>` + (active? '<span class="badge bg-success">Bật</span>' : '<span class="badge bg-warning text-dark">Tắt</span>');
    if(hsDangChon && String(hsDangChon.id)===String(s.id)) a.classList.add('active');
    a.onclick = (ev)=>{ ev.preventDefault(); hsDangChon = s; hsHienChiTiet(); hsSetChiTietInputs(); setTimeout(hsSyncLopSelectOnce, 0); };
    box.appendChild(a);
  });
  hsHienChiTiet();
}
const hsTuKhoa = document.getElementById('tu_khoa'); if(hsTuKhoa) hsTuKhoa.oninput=()=>hsNap();
const hsChkTatCa = document.getElementById('hien_tat_ca'); if(hsChkTatCa) hsChkTatCa.onchange = ()=>hsNap();
function hsToISODate(v){
  if(!v) return '';
  const m=v.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/);
  if(m){
    const d=m[1].padStart(2,'0'); const mm=m[2].padStart(2,'0'); const y=m[3];
    return `${y}-${mm}-${d}`;
  }
  return v;
}
function hsToVNDate(v){
  if(!v) return '';
  const parts=v.split('-');
  if(parts.length===3){
    const [y,m,d]=parts;
    return `${d.padStart(2,'0')}/${m.padStart(2,'0')}/${y}`;
  }
  return v;
}
const hsBtnThem = document.getElementById('them');
if(hsBtnThem) hsBtnThem.onclick=async()=>{
  const body = {
    ma: document.getElementById('ma').value,
    ho_ten: document.getElementById('ho_ten').value,
    anh_dai_dien_url: document.getElementById('anh_dai_dien_url').value,
    gioi_tinh: document.getElementById('gioi_tinh').value,
    ngay_sinh: hsToISODate(document.getElementById('ngay_sinh').value)
  };
  const r=await fetch('../api/hoc_sinh.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(body)});
  const j=await r.json(); const ms=document.getElementById('msg'); if(j.ok){ if(ms) ms.textContent='Đã thêm'; document.getElementById('ho_ten').value=''; document.getElementById('ma').value=''; hsNap(); } else if(ms) ms.textContent=j.thong_bao||'Lỗi';
};
hsNap();

// Toggle trạng thái
const hsBtnToggle = document.getElementById('btn_toggle');
if(hsBtnToggle) hsBtnToggle.onclick = async()=>{
  const msg = document.getElementById('ct_msg'); if(!hsDangChon) return;
  const current = Number(hsDangChon.dang_hoat_dong) === 1;
  const newState = current ? 0 : 1;
  const r = await fetch('../api/hoc_sinh.php?hanh_dong=bat_tat',{ method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ id: hsDangChon.id, dang_hoat_dong: newState })});
  const j = await r.json(); if(j.ok){
    if(newState===0 && document.getElementById('hien_tat_ca')) document.getElementById('hien_tat_ca').checked=true;
    hsDangChon.dang_hoat_dong = !!newState;
    if(msg) msg.textContent='Đã cập nhật trạng thái';
    await hsNap(hsDangChon.id);
    hsSetChiTietInputs();
  } else { if(msg) msg.textContent=j.thong_bao||'Lỗi'; }
};

// Upload avatar
const hsBtnUpAnh = document.getElementById('btn_up_anh');
if(hsBtnUpAnh) hsBtnUpAnh.onclick = async()=>{
  const f = document.getElementById('up_anh').files[0]; const msg = document.getElementById('ct_msg'); if(!hsDangChon) return;
  if(!f){ if(msg) msg.textContent='Chọn ảnh để upload'; return; }
  const fd = new FormData(); fd.append('hoc_sinh_id', hsDangChon.id); fd.append('file', f);
  const r = await fetch('../api/upload_avatar.php', { method:'POST', body: fd }); const j = await r.json();
  if(j.ok){ hsDangChon = { ...hsDangChon, anh_dai_dien_url: j.du_lieu.url }; hsHienChiTiet(); hsSetChiTietInputs(); if(msg) msg.textContent='Đã cập nhật ảnh'; document.getElementById('up_anh').value=''; }
  else { if(msg) msg.textContent = j.thong_bao || 'Lỗi upload ảnh'; }
};

// CSV handlers
const hsBtnXuatCsv = document.getElementById('xuat_csv');
if(hsBtnXuatCsv) hsBtnXuatCsv.onclick = ()=>{
  const tu = encodeURIComponent(document.getElementById('tu_khoa').value||'');
  window.location = '../api/hoc_sinh_csv.php?hanh_dong=xuat&tu_khoa=' + tu;
};
const hsBtnNhapCsv = document.getElementById('nhap_csv');
if(hsBtnNhapCsv) hsBtnNhapCsv.onclick = async()=>{
  const fileInput = document.getElementById('csv_file');
  const f = fileInput.files[0]; const msgEl = document.getElementById('csv_msg'); msgEl.textContent='';
  if (!f) { msgEl.textContent = 'Chọn file CSV trước khi nhập'; return; }
  msgEl.textContent = 'Đang đọc file...';
  const fdPreview = new FormData(); fdPreview.append('file', f);
  const rPreview = await fetch('../api/hoc_sinh_csv.php?hanh_dong=xem_truoc', { method:'POST', body: fdPreview });
  const jPreview = await rPreview.json();
  if (!jPreview.ok) { msgEl.textContent = jPreview.thong_bao || 'Lỗi đọc CSV'; return; }
  const mau = (jPreview.du_lieu && Array.isArray(jPreview.du_lieu.mau)) ? jPreview.du_lieu.mau : [];
  const tong = jPreview.du_lieu?.tong_dong || 0;
  let previewText = mau.map((it,i)=>`${i+1}. ${it.ho_ten||''} | ${it.ma||''} | ${it.ngay_sinh||''} | ${it.gioi_tinh||''}`).join('\n');
  if (!previewText) previewText = '(không có dữ liệu xem trước)';
  const ask = `Đọc được ${tong} dòng (bỏ qua dòng trống).\nMẫu:\n${previewText}\n\nTiếp tục nhập?`;
  if (!confirm(ask)) { msgEl.textContent = 'Đã hủy nhập CSV'; return; }
  msgEl.textContent = 'Đang nhập...';
  const fd = new FormData(); fd.append('file', f);
  const r = await fetch('../api/hoc_sinh_csv.php?hanh_dong=nhap', { method:'POST', body: fd });
  const j = await r.json();
  if (j.ok) { msgEl.textContent = 'Nhập CSV xong: ' + (j.du_lieu?.tong_dong||0) + ' dòng'; hsNap(); }
  else { msgEl.textContent = j.thong_bao || 'Lỗi nhập CSV'; }
  if (fileInput) {
    const lbl = document.getElementById('csv_file_label');
    if (lbl) lbl.textContent = 'Chưa chọn tệp';
    fileInput.value = '';
  }
};

const hsCsvInput = document.getElementById('csv_file');
if (hsCsvInput) {
  hsCsvInput.addEventListener('change', ()=>{
    const lbl = document.getElementById('csv_file_label');
    if (!lbl) return;
    if (hsCsvInput.files && hsCsvInput.files[0]) {
      lbl.textContent = hsCsvInput.files[0].name;
    } else {
      lbl.textContent = 'Chưa chọn tệp';
    }
  });
}

// Nạp danh sách lớp và dữ liệu form chi tiết
async function hsNapLopOptions(){ const sel = document.getElementById('ct_lop'); if(!sel) return; try { const r = await fetch('../api/lop_hoc_quan_tri.php?cua_toi=1'); const j = await r.json(); if(!j.ok) return; sel.innerHTML=''; const opt0=document.createElement('option'); opt0.value=''; opt0.textContent='-- Không gán lớp --'; sel.appendChild(opt0); j.du_lieu.forEach(l=>{ const o=document.createElement('option'); o.value=l.id; o.textContent=l.ten; sel.appendChild(o); }); } catch(_e){} }
hsNapLopOptions();
hsSyncLopSelectOnce();
function hsSetChiTietInputs(){ const fma=document.getElementById('ct_ma'); const ften=document.getElementById('ct_ho_ten'); const fgioi=document.getElementById('ct_gioi'); const fngay=document.getElementById('ct_ngay'); const flop=document.querySelector('select#ct_lop'); if(!hsDangChon) return; if(fma) fma.value = hsDangChon.ma || ''; if(ften) ften.value = hsDangChon.ho_ten || ''; if(fgioi) fgioi.value = hsDangChon.gioi_tinh || ''; if(fngay) fngay.value = hsToVNDate((hsDangChon.ngay_sinh || '').substring(0,10)); if(flop && flop.options && typeof flop.options.length==='number' && flop.options.length){ const v = (hsDangChon.lop_hoc_id===null || hsDangChon.lop_hoc_id===undefined || hsDangChon.lop_hoc_id==='') ? '' : String(hsDangChon.lop_hoc_id); flop.value = v; } }

// Lưu thay đổi thông tin
const hsBtnLuu = document.getElementById('btn_luu');
if(hsBtnLuu) hsBtnLuu.onclick = async()=>{
  const msg = document.getElementById('ct_msg'); if(msg) msg.textContent=''; if(!hsDangChon) return;
  const body = {
    id: hsDangChon.id,
    ma: (document.getElementById('ct_ma')?.value||'').trim(),
    ho_ten: (document.getElementById('ct_ho_ten')?.value||'').trim(),
    gioi_tinh: document.getElementById('ct_gioi')?.value||'',
    ngay_sinh: hsToISODate(document.getElementById('ct_ngay')?.value||''),
    lop_hoc_id: (document.querySelector('select#ct_lop')?.value||'')
  };
  const r = await fetch('../api/hoc_sinh.php?hanh_dong=sua',{ method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(body)});
  const j = await r.json(); if(j.ok){ if(msg) msg.textContent='Đã lưu'; await hsNap(); }
  else { if(msg) msg.textContent=j.thong_bao||'Lỗi lưu'; }
};

function hsSyncLopSelectOnce(){
  const sel = document.querySelector('select#ct_lop');
  if(!sel) return;
  let tries = 0;
  const h = setInterval(()=>{
    tries++;
    if (sel.options && sel.options.length) {
      if (hsDangChon && typeof hsDangChon==='object') {
        const v = (hsDangChon.lop_hoc_id===null || hsDangChon.lop_hoc_id===undefined || hsDangChon.lop_hoc_id==='') ? '' : String(hsDangChon.lop_hoc_id);
        sel.value = v;
      }
      clearInterval(h);
    }
    if (tries > 30) clearInterval(h);
  }, 100);
}

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
    const j = await jfetch('../api/giao_vien_quan_tri.php?hanh_dong=doi_mat_khau',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({mat_khau_cu:mk_cu, mat_khau_moi:mk_moi})});
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
    const j = await jfetch('../api/giao_vien_quan_tri.php?hanh_dong=them',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({ten_dang_nhap:ten, mat_khau:mk})});
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
  const jj = await jfetch('../api/ly_do_quan_tri.php?hanh_dong=sua',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:parseInt(tr.dataset.id,10)||0, tieu_de:String(res.tieu_de||'').trim(), bien_diem:parseInt(res.bien_diem,10)||0})});
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
  const jj = await jfetch('../api/qua_tang_quan_tri.php?hanh_dong=sua',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:parseInt(tr.dataset.id,10)||0, ten:String(res.ten||'').trim(), gia_diem:parseInt(res.gia_diem,10)||0, ton_kho:parseInt(res.ton_kho,10)||0})});
  if(jj.ok) qNap(); else alert(jj.thong_bao||'Loi');
}, true);

document.getElementById('l_ds').addEventListener('click', async (e) => {
  const btn = e.target.closest('button.btn-outline-primary'); if(!btn) return;
  const tr = btn.closest('tr'); if(!tr) return; e.preventDefault(); e.stopImmediatePropagation();
  let gvSelected = [];
  try { gvSelected = JSON.parse(tr.dataset.giao_vien_ids||'[]'); } catch(_e){}
  const res = await openClassModal({ id: parseInt(tr.dataset.id,10)||0, ten: tr.dataset.ten || '', giao_vien_ids: gvSelected });
  if(!res) return;
  const jj = await jfetch('../api/lop_hoc_quan_tri.php?hanh_dong=sua',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:parseInt(tr.dataset.id,10)||0, ten:String(res.ten||'').trim(), giao_vien_ids: res.giao_vien_ids})});
  if(jj.ok) lNap(); else alert(jj.thong_bao||'Loi');
}, true);

// Load
ldNap(); qNap(); loadGiaoVienDs().then(()=>{ lNap(); gvNapQuyen(); });
const btnLog = document.getElementById('gv_log_reload'); if(btnLog){ btnLog.onclick = gvNapLog; }
</script>
<script src="vendor/bootstrap/bootstrap.bundle.min.js"></script>
<script src="vendor/aos/aos.js"></script>
<script src="date_picker.js"></script>
<script>AOS.init({ duration: 350, once: true, easing: 'ease-out' });</script>
</body></html>

