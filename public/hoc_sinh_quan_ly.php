<?php
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../lib/tro_giup.php';
header('Content-Type: text/html; charset=utf-8');
if (!isset($_SESSION['giao_vien_id'])) { header('Location: dang_nhap.php'); exit; }
if (!empty($_SESSION['phai_doi_mat_khau'])) { header('Location: cau_hinh.php'); exit; }
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
<title>Học sinh</title>
  <link rel="stylesheet" href="vendor/bootswatch/bootstrap.min.css">
  <link rel="stylesheet" href="vendor/bootstrap-icons/bootstrap-icons.css">
  <link rel="stylesheet" href="vendor/aos/aos.css">
  <link rel="stylesheet" href="vendor/theme.css">
  <link rel="stylesheet" href="vendor/custom_file.css">
  <link rel="stylesheet" href="vendor/date_picker.css">
</head>
<body>
<?php include __DIR__ . '/_nav.php'; ?>
<div class="container py-3 safe-bottom">
  <div class="d-flex align-items-center justify-content-between">
    <h5 class="ribbon-title-modern">Quản lý học sinh</h5>
    
  </div>
  <div class="row g-3 mt-1">
    <div class="col-md-5">
      <div class="card shadow-sm" data-aos="fade-up"><div class="card-body">
        <div class="mb-2"><input id="tu_khoa" class="form-control" placeholder="Tìm…"></div>
        <div class="form-check mb-2">
          <input class="form-check-input" type="checkbox" id="hien_tat_ca">
          <label class="form-check-label" for="hien_tat_ca">Hiện học sinh đã tắt</label>
        </div>
        <div id="ds" class="list-group" style="max-height:60vh;overflow:auto"></div>
      </div></div>
    </div>
    <div class="col-md-7">
      <div class="card shadow-sm" data-aos="fade-up"><div class="card-body">
        <h5 class="ribbon-title-modern">Thêm học sinh</h5>
        <div class="row g-2">
          <div class="col-12 col-sm-4"><input id="ma" class="form-control" placeholder="Mã (tùy chọn)"></div>
          <div class="col-12 col-sm-8"><input id="ho_ten" class="form-control" placeholder="Họ tên"></div>
          <div class="col-12 col-sm-6 mt-2"><input id="anh_dai_dien_url" class="form-control" placeholder="Ảnh đại diện URL"></div>
          <div class="col-6 col-sm-3 mt-2">
            <select id="gioi_tinh" class="form-select">
              <option value="">Giới tính...</option>
              <option value="NAM">Nam</option>
              <option value="NU">Nữ</option>
              <option value="KHAC">Khác</option>
            </select>
          </div>
          <div class="col-6 col-sm-3 mt-2"><input id="ngay_sinh" type="text" class="form-control date-picker" placeholder="Ngày sinh" data-datepicker="1"></div>
        </div>
        <div class="mt-2"><button id="them" class="btn btn-primary">Thêm</button><span id="msg" class="ms-2 small text-muted"></span></div>
        <hr>
        <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-2">
          <div class="me-sm-auto" style="max-width:320px;">
            <label class="form-label">Nhập CSV</label>
            <div class="custom-file-input-sm position-relative">
              <input type="file" id="csv_file" accept=".csv" class="form-control form-control-sm" aria-label="Chọn file CSV">
              <span class="custom-file-label" id="csv_file_label">Chưa chọn tệp</span>
            </div>
          </div>
          <div class="pt-sm-4">
            <div class="d-flex gap-2">
              <button id="nhap_csv" class="btn btn-outline-primary btn-sm px-3 flex-fill">Nhập CSV</button>
              <button id="xuat_csv" class="btn btn-outline-primary btn-sm px-3 flex-fill">Xuất CSV</button>
            </div>
          </div>
        </div>
        <div id="csv_msg" class="small text-muted mt-2"></div>
        <hr>
        <h5 class="ribbon-title-modern">Chi tiết học sinh</h5>
        <div id="ct_no_sel" class="text-muted">Chưa chọn học sinh</div>
        <div id="ct_sel" class="d-none">
          <div class="d-flex align-items-center gap-3">
            <img id="ct_avatar" class="avatar" src="../upload/avatar/default.svg" alt="avatar" onerror="this.onerror=null;this.src='../upload/avatar/default.svg';">
            <div>
              <div id="ct_ten" class="fw-semibold"></div>
              <div class="small text-muted"><span id="ct_lop"></span> · <span id="ct_trang_thai"></span></div>
            </div>
          </div>
          <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-2 mt-2">
            <input type="file" id="up_anh" accept="image/*" class="form-control form-control-sm" style="max-width:320px">
            <button id="btn_up_anh" class="btn btn-outline-primary btn-sm">Upload ảnh</button>
            <button id="btn_toggle" class="btn btn-outline-warning btn-sm">Tắt</button>
          </div>
          <div id="ct_msg" class="small text-muted mt-1"></div>
          <div class="row g-2 mt-2">
            <div class="col-12 col-sm-4"><label class="form-label">Mã</label><input id="ct_ma" class="form-control"></div>
            <div class="col-12 col-sm-8"><label class="form-label">Họ tên</label><input id="ct_ho_ten" class="form-control"></div>
            <div class="col-12 col-sm-6"><label class="form-label">Lớp</label><select id="ct_lop" class="form-select"></select></div>
            <div class="col-6 col-sm-3"><label class="form-label">Giới tính</label><select id="ct_gioi" class="form-select"><option value="">--</option><option value="NAM">Nam</option><option value="NU">Nữ</option><option value="KHAC">Khác</option></select></div>
            <div class="col-6 col-sm-3"><label class="form-label">Ngày sinh</label><input id="ct_ngay" type="text" class="form-control date-picker" data-datepicker="1" placeholder="dd/mm/yyyy"></div>
          </div>
          <div class="mt-2"><button id="btn_luu" class="btn btn-primary btn-sm">Lưu thay đổi</button></div>
        </div>
      </div></div>
    </div>
  </div>
</div>

<script>
// Tên học sinh/lớp là do giáo viên (bất kỳ ai được gán lớp đó) tự nhập - không escape trước khi
// ghép vào innerHTML thì một giáo viên có thể đặt tên chứa script và nó sẽ chạy trong trình
// duyệt của giáo viên khác cùng được gán lớp đó khi họ xem trang này.
function escapeHtml(s){
  return String(s ?? '').replace(/[&<>"']/g, c => ({
    '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;'
  }[c]));
}
let hsDangChon = null;

function hienChiTiet(){
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
  if(lopSel && lopSel.tagName === 'SELECT') {
    if (lopSel.options && lopSel.options.length) {
      const v = (hsDangChon.lop_hoc_id===null || hsDangChon.lop_hoc_id===undefined || hsDangChon.lop_hoc_id==='') ? '' : String(hsDangChon.lop_hoc_id);
      lopSel.value = v;
    }
  }
  const lopText = document.querySelector('span#ct_lop');
  if(lopText && lopText.tagName !== 'SELECT') { lopText.textContent = 'Lớp: ' + (hsDangChon.ten_lop||''); }
  const active = Number(hsDangChon.dang_hoat_dong) === 1;
  if(tt) tt.textContent = active ? 'Đang bật' : 'Đang tắt';
  if(av) av.src = (hsDangChon.anh_dai_dien_url && hsDangChon.anh_dai_dien_url.trim()!=='') ? hsDangChon.anh_dai_dien_url : '../upload/avatar/default.svg';
  const btnToggle = document.getElementById('btn_toggle');
  if(btnToggle) btnToggle.textContent = active ? 'Tắt' : 'Bật';
}

async function nap(keepId=null){
  const tat_ca = document.getElementById('hien_tat_ca') && document.getElementById('hien_tat_ca').checked ? 1 : 0;
  const r=await fetch('../api/hoc_sinh.php?tu_khoa='+encodeURIComponent(document.getElementById('tu_khoa').value||'')+'&tat_ca='+tat_ca);
  const j=await r.json(); const box=document.getElementById('ds'); box.innerHTML=''; if(!j.ok) return;
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
  a.innerHTML = `<span>${escapeHtml(sttText)}${escapeHtml(s.ho_ten)} (${escapeHtml(s.ten_lop||'')}) · Điểm: ${s.so_du}</span>` + (active? '<span class="badge bg-success">Bật</span>' : '<span class="badge bg-warning text-dark">Tắt</span>');
    if(hsDangChon && String(hsDangChon.id)===String(s.id)) a.classList.add('active');
    a.onclick = (ev)=>{ ev.preventDefault(); hsDangChon = s; hienChiTiet(); setChiTietInputs(); setTimeout(syncLopSelectOnce, 0); };
    box.appendChild(a);
  });
  hienChiTiet();
}

document.getElementById('tu_khoa').oninput=nap;
const chkTatCa = document.getElementById('hien_tat_ca'); if(chkTatCa) chkTatCa.onchange = nap;
function toISODate(v){
  if(!v) return '';
  const m=v.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/);
  if(m){
    const d=m[1].padStart(2,'0'); const mm=m[2].padStart(2,'0'); const y=m[3];
    return `${y}-${mm}-${d}`;
  }
  return v;
}
function toVNDate(v){
  if(!v) return '';
  const parts=v.split('-');
  if(parts.length===3){
    const [y,m,d]=parts;
    return `${d.padStart(2,'0')}/${m.padStart(2,'0')}/${y}`;
  }
  return v;
}
document.getElementById('them').onclick=async()=>{
  const body = {
    ma: document.getElementById('ma').value,
    ho_ten: document.getElementById('ho_ten').value,
    anh_dai_dien_url: document.getElementById('anh_dai_dien_url').value,
    gioi_tinh: document.getElementById('gioi_tinh').value,
    ngay_sinh: toISODate(document.getElementById('ngay_sinh').value)
  };
  const r=await fetch('../api/hoc_sinh.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(body)});
  const j=await r.json(); const ms=document.getElementById('msg'); if(j.ok){ if(ms) ms.textContent='Đã thêm'; document.getElementById('ho_ten').value=''; document.getElementById('ma').value=''; nap(); } else if(ms) ms.textContent=j.thong_bao||'Lỗi';
};
nap();

// Toggle trạng thái
document.getElementById('btn_toggle').onclick = async()=>{
  const msg = document.getElementById('ct_msg'); if(!hsDangChon) return;
  const current = Number(hsDangChon.dang_hoat_dong) === 1;
  const newState = current ? 0 : 1;
  const r = await fetch('../api/hoc_sinh.php?hanh_dong=bat_tat',{ method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ id: hsDangChon.id, dang_hoat_dong: newState })});
  const j = await r.json(); if(j.ok){
    if(newState===0 && document.getElementById('hien_tat_ca')) document.getElementById('hien_tat_ca').checked=true;
    hsDangChon.dang_hoat_dong = !!newState;
    if(msg) msg.textContent='Đã cập nhật trạng thái';
    await nap(hsDangChon.id);
    setChiTietInputs();
  } else { if(msg) msg.textContent=j.thong_bao||'Lỗi'; }
};

// Upload avatar
document.getElementById('btn_up_anh').onclick = async()=>{
  const f = document.getElementById('up_anh').files[0]; const msg = document.getElementById('ct_msg'); if(!hsDangChon) return;
  if(!f){ if(msg) msg.textContent='Chọn ảnh để upload'; return; }
  const fd = new FormData(); fd.append('hoc_sinh_id', hsDangChon.id); fd.append('file', f);
  const r = await fetch('../api/upload_avatar.php', { method:'POST', body: fd }); const j = await r.json();
  if(j.ok){ hsDangChon = { ...hsDangChon, anh_dai_dien_url: j.du_lieu.url }; hienChiTiet(); setChiTietInputs(); if(msg) msg.textContent='Đã cập nhật ảnh'; document.getElementById('up_anh').value=''; }
  else { if(msg) msg.textContent = j.thong_bao || 'Lỗi upload ảnh'; }
};

// CSV handlers
document.getElementById('xuat_csv').onclick = ()=>{
  const tu = encodeURIComponent(document.getElementById('tu_khoa').value||'');
  window.location = '../api/hoc_sinh_csv.php?hanh_dong=xuat&tu_khoa=' + tu;
};
document.getElementById('nhap_csv').onclick = async()=>{
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
  if (j.ok) { msgEl.textContent = 'Nhập CSV xong: ' + (j.du_lieu?.tong_dong||0) + ' dòng'; nap(); }
  else { msgEl.textContent = j.thong_bao || 'Lỗi nhập CSV'; }
  if (fileInput) {
    const lbl = document.getElementById('csv_file_label');
    if (lbl) lbl.textContent = 'Chưa chọn tệp';
    fileInput.value = '';
  }
};

const csvInput = document.getElementById('csv_file');
if (csvInput) {
  csvInput.addEventListener('change', ()=>{
    const lbl = document.getElementById('csv_file_label');
    if (!lbl) return;
    if (csvInput.files && csvInput.files[0]) {
      lbl.textContent = csvInput.files[0].name;
    } else {
      lbl.textContent = 'Chưa chọn tệp';
    }
  });
}

// Nạp danh sách lớp và đổ dữ liệu form chi tiết
async function napLopOptions(){ const sel = document.getElementById('ct_lop'); if(!sel) return; try { const r = await fetch('../api/lop_hoc_quan_tri.php?cua_toi=1'); const j = await r.json(); if(!j.ok) return; sel.innerHTML=''; const opt0=document.createElement('option'); opt0.value=''; opt0.textContent='-- Không gán lớp --'; sel.appendChild(opt0); j.du_lieu.forEach(l=>{ const o=document.createElement('option'); o.value=l.id; o.textContent=l.ten; sel.appendChild(o); }); } catch(_e){} }
napLopOptions();
syncLopSelectOnce();
function setChiTietInputs(){ const fma=document.getElementById('ct_ma'); const ften=document.getElementById('ct_ho_ten'); const fgioi=document.getElementById('ct_gioi'); const fngay=document.getElementById('ct_ngay'); const flop=document.querySelector('select#ct_lop'); if(!hsDangChon) return; if(fma) fma.value = hsDangChon.ma || ''; if(ften) ften.value = hsDangChon.ho_ten || ''; if(fgioi) fgioi.value = hsDangChon.gioi_tinh || ''; if(fngay) fngay.value = toVNDate((hsDangChon.ngay_sinh || '').substring(0,10)); if(flop && flop.options && typeof flop.options.length==='number' && flop.options.length){ const v = (hsDangChon.lop_hoc_id===null || hsDangChon.lop_hoc_id===undefined || hsDangChon.lop_hoc_id==='') ? '' : String(hsDangChon.lop_hoc_id); flop.value = v; } }

// Lưu thay đổi thông tin
document.getElementById('btn_luu').onclick = async()=>{
  const msg = document.getElementById('ct_msg'); if(msg) msg.textContent=''; if(!hsDangChon) return;
  const body = {
    id: hsDangChon.id,
    ma: (document.getElementById('ct_ma')?.value||'').trim(),
    ho_ten: (document.getElementById('ct_ho_ten')?.value||'').trim(),
    gioi_tinh: document.getElementById('ct_gioi')?.value||'',
    ngay_sinh: toISODate(document.getElementById('ct_ngay')?.value||''),
    lop_hoc_id: (document.querySelector('select#ct_lop')?.value||'')
  };
  const r = await fetch('../api/hoc_sinh.php?hanh_dong=sua',{ method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(body)});
  const j = await r.json(); if(j.ok){ if(msg) msg.textContent='Đã lưu'; await nap(); }
  else { if(msg) msg.textContent=j.thong_bao||'Lỗi lưu'; }
};

function syncLopSelectOnce(){
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
</script>
<script src="vendor/bootstrap/bootstrap.bundle.min.js"></script>
<script src="vendor/aos/aos.js"></script>
<script src="vendor/date_picker.js"></script>
<script>AOS.init({ duration: 350, once: true, easing: 'ease-out' });</script>
</body>
</html>




