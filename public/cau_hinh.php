<?php
require __DIR__ . '/../config/db.php'; require __DIR__ . '/../lib/tro_giup.php';
if (!isset($_SESSION['giao_vien_id'])) { header('Location: dang_nhap.php'); exit; }
$can_doi_mk_bat_buoc = !empty($_SESSION['phai_doi_mat_khau']);
// URL gốc của api/ (cùng cấp public/ theo quy ước docroot của app) - dùng để nhúng vào mã QR
// token cho app di động, để app biết kết nối vào server nào (hỗ trợ tự host ở domain bất kỳ).
$scheme = dang_https() ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$goc_duong_dan = preg_replace('#/public/[^/]*$#', '', $_SERVER['SCRIPT_NAME'] ?? '/public/cau_hinh.php');
$api_goc_url = $scheme . '://' . $host . $goc_duong_dan . '/api/';
?><!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Cấu hình hệ thống</title>
<link rel="stylesheet" href="vendor/bootswatch/bootstrap.min.css"><link rel="stylesheet" href="vendor/bootstrap-icons/bootstrap-icons.css"><link rel="stylesheet" href="vendor/aos/aos.css"><link rel="stylesheet" href="vendor/theme.css"><link rel="stylesheet" href="vendor/custom_file.css"><link rel="stylesheet" href="vendor/date_picker.css"><style>.ld-stepper .ld-stepper-btns{min-width:38px;height:100%;}.ld-stepper .ld-stepper-btns .btn{padding:4px 0;line-height:1;height:50%;border-radius:0;}.ld-stepper .ld-stepper-btns .btn:first-child{border-top-right-radius:.375rem;}.ld-stepper .ld-stepper-btns .btn:last-child{border-bottom-right-radius:.375rem;}.ld-bd-wrap{position:relative;}.ld-bd-wrap input{padding-left:24px;}#ld_bien_diem::-webkit-outer-spin-button,#ld_bien_diem::-webkit-inner-spin-button{-webkit-appearance:none;margin:0;}#ld_bien_diem{-moz-appearance:textfield;}.ld-bd-plus{position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#0d6efd;font-weight:600;pointer-events:none;font-size:.95rem;}</style></head><body><?php include __DIR__ . '/_nav.php'; ?>
<div class="container py-3">
  <?php if ($can_doi_mk_bat_buoc): ?>
  <div class="alert alert-warning d-flex align-items-center gap-2" role="alert">
    <i class="bi bi-shield-exclamation"></i>
    <div>Tài khoản của bạn đang dùng mật khẩu mặc định. Vui lòng đổi mật khẩu ngay bên dưới trước khi tiếp tục sử dụng hệ thống.</div>
  </div>
  <?php endif; ?>
  <div class="d-flex align-items-center justify-content-between"><h5 class="ribbon-title-modern mb-0">Cấu hình hệ thống</h5></div>

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
          <h5 class="ribbon-title-modern">Thêm lý do</h5>
          <div class="mb-2"><label class="form-label">Tiêu đề</label><input id="ld_tieu_de" class="form-control"></div>
          <div class="mb-2"><label class="form-label">Biến điểm</label>
            <div class="input-group ld-stepper ld-bd-wrap">
              <span class="ld-bd-plus">+</span>
              <input id="ld_bien_diem" type="number" class="form-control" value="1">
              <div class="btn-group-vertical ld-stepper-btns" role="group" aria-label="Điều chỉnh điểm">
                <button type="button" class="btn btn-success ld-bd-inc"><i class="bi bi-plus"></i></button>
                <button type="button" class="btn btn-danger ld-bd-dec"><i class="bi bi-dash"></i></button>
              </div>
            </div>
          </div>
          <button class="btn btn-primary btn-sm" id="ld_them">Thêm</button>
        </div></div></div>
        <div class="col-md-8"><div class="card shadow-sm" data-aos="fade-up"><div class="card-body">
          <div class="d-flex align-items-center justify-content-between flex-wrap gap-2"><h5 class="ribbon-title-modern mb-0">Danh sách</h5><small class="text-muted">Bấm bật/tắt, sửa hoặc xóa</small></div>
          <div class="table-responsive table-responsive-stack mt-2">
            <table class="table table-sm align-middle"><thead><tr><th>#</th><th>Tiêu đề</th><th>Biến điểm</th><th>Trạng thái</th><th></th></tr></thead><tbody id="ld_ds"></tbody></table>
          </div>
        </div></div></div>
      </div>
    </div>
    <div class="tab-pane fade" id="tab-qua">
      <div class="row g-3">
        <div class="col-md-4"><div class="card shadow-sm" data-aos="fade-up"><div class="card-body">
          <h5 class="ribbon-title-modern">Thêm quà tặng</h5>
          <div class="mb-2"><label class="form-label">Tên</label><input id="q_ten" class="form-control"></div>
          <div class="mb-2"><label class="form-label">Giá điểm</label><input id="q_gia" type="number" class="form-control" value="1"></div>
          <div class="mb-2"><label class="form-label">Tồn kho</label><input id="q_ton" type="number" class="form-control" value="0"></div>
          <div class="mb-2"><label class="form-label">Ảnh (tùy chọn)</label>
            <input id="q_anh" class="form-control mb-1" placeholder="Dán URL ảnh https://...">
            <input type="file" id="q_anh_file" accept="image/*" class="form-control form-control-sm">
            <div class="d-flex align-items-center gap-2 mt-1">
              <img id="q_anh_preview" alt="" class="d-none" style="width:56px;height:56px;object-fit:cover;border:1px solid #ddd;border-radius:6px">
              <span id="q_anh_msg" class="small text-muted"></span>
            </div>
          </div>
          <button class="btn btn-primary btn-sm" id="q_them">Thêm</button>
        </div></div></div>
        <div class="col-md-8"><div class="card shadow-sm" data-aos="fade-up"><div class="card-body">
          <div class="d-flex align-items-center justify-content-between flex-wrap gap-2"><h5 class="ribbon-title-modern mb-0">Danh sách</h5><small class="text-muted">Bấm bật/tắt, sửa hoặc xóa</small></div>
          <div class="table-responsive table-responsive-stack mt-2">
            <table class="table table-sm align-middle"><thead><tr><th>#</th><th>Ảnh</th><th>Tên</th><th>Giá điểm</th><th>Tồn kho</th><th>Trạng thái</th><th></th></tr></thead><tbody id="q_ds"></tbody></table>
          </div>
        </div></div></div>
      </div>
    </div>
    <div class="tab-pane fade" id="tab-lop">
      <div class="row g-3">
        <div class="col-md-4"><div class="card shadow-sm" data-aos="fade-up"><div class="card-body">
          <h5 class="ribbon-title-modern">Thêm lớp học</h5>
          <div class="mb-2"><label class="form-label">Tên lớp</label><input id="l_ten" class="form-control"></div>
          <button class="btn btn-primary btn-sm" id="l_them">Thêm</button>
        </div></div></div>
        <div class="col-md-8"><div class="card shadow-sm" data-aos="fade-up"><div class="card-body">
          <div class="d-flex align-items-center justify-content-between flex-wrap gap-2"><h5 class="ribbon-title-modern mb-0">Danh sách</h5><small class="text-muted">Bấm bật/tắt, sửa hoặc xóa</small></div>
          <div class="table-responsive table-responsive-stack mt-2">
            <table class="table table-sm align-middle"><thead><tr><th>#</th><th>Tên</th><th>Trạng thái</th><th></th></tr></thead><tbody id="l_ds"></tbody></table>
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
            <h5 class="ribbon-title-modern">Thêm học sinh</h5>
            <div class="row g-2">
              <div class="col-12 col-sm-4"><input id="ma" class="form-control" placeholder="Mã (tùy chọn)"></div>
              <div class="col-12 col-sm-8"><input id="ho_ten" class="form-control" placeholder="Họ tên"></div>
              <div class="col-12 mt-2">
                <select id="them_lop" class="form-select"><option value="">Chọn lớp học...</option></select>
                <div id="them_lop_hint" class="small text-danger mt-1 d-none">Chưa có lớp học nào — tạo lớp ở tab <b>Lớp học</b> trước khi thêm học sinh.</div>
              </div>
              <div class="col-12 col-sm-6 mt-2"><input id="anh_dai_dien_url" class="form-control" placeholder="Ảnh đại diện URL"></div>
              <div class="col-12 mt-2">
                <label class="form-label mb-1">hoặc tải ảnh lên</label>
                <input type="file" id="hs_anh_file" accept="image/*" class="form-control form-control-sm">
                <div class="d-flex align-items-center gap-2 mt-1">
                  <img id="hs_anh_preview" alt="" class="avatar-xs d-none">
                  <span id="hs_anh_msg" class="small text-muted"></span>
                </div>
              </div>
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
                <img id="ct_avatar" class="hs-avatar" src="../upload/avatar/default.svg" alt="avatar" onerror="this.onerror=null;this.src='../upload/avatar/default.svg';">
                <div>
                  <div id="ct_ten" class="fw-semibold"></div>
                  <div class="small text-muted"><span id="ct_lop_text"></span> - <span id="ct_trang_thai"></span></div>
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
    <div class="tab-pane fade" id="tab-tai-khoan">
      <div class="card shadow-sm" data-aos="fade-up">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <div>
              <h5 class="ribbon-title-modern mb-1">Tài khoản giáo viên</h5>
              <div class="small text-muted">Đổi mật khẩu đăng nhập của bạn.</div>
            </div>
          </div>
          <div class="row g-3">
            <div class="col-md-6">
              <div class="border rounded-3 p-3 h-100">
                <h5 class="ribbon-title-modern mb-3">Đổi mật khẩu</h5>
                <div class="mb-2"><label class="form-label">Mật khẩu hiện tại</label><input id="gv_mk_cu" type="password" class="form-control"></div>
                <div class="mb-2"><label class="form-label">Mật khẩu mới</label><input id="gv_mk_moi" type="password" class="form-control"></div>
                <div class="mb-3"><label class="form-label">Nhập lại mật khẩu mới</label><input id="gv_mk_lai" type="password" class="form-control"></div>
                <div class="d-flex align-items-center gap-2">
                  <button class="btn btn-primary btn-sm" id="gv_doi">Đổi mật khẩu</button>
                  <span id="gv_doi_msg" class="small text-muted"></span>
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="border rounded-3 p-3 h-100">
                <h5 class="ribbon-title-modern mb-3">Token API (app di động)</h5>
                <div class="small text-muted mb-2">Dùng để đăng nhập ứng dụng di động (chấm điểm ngoại tuyến, đồng bộ khi có mạng). Tạo token mới sẽ vô hiệu token cũ trên thiết bị khác.</div>
                <div id="gv_token_trang_thai" class="small mb-2"></div>
                <div id="gv_token_box" class="d-none alert alert-warning py-2 px-3 small mb-2">
                  <div class="fw-semibold mb-1">Token mới (chỉ hiển thị 1 lần, hãy lưu ngay):</div>
                  <div class="d-flex flex-column flex-sm-row align-items-center gap-3">
                    <div id="gv_token_qr" class="bg-white p-2 rounded-3 border flex-shrink-0"></div>
                    <div class="flex-grow-1 w-100">
                      <div class="small text-muted mb-1">Mở app DClass trên điện thoại, chọn "Quét mã để đăng nhập" và quét mã này. Không quét được thì dùng token bên dưới:</div>
                      <div class="d-flex align-items-center gap-2">
                        <code id="gv_token_gia_tri" class="text-break flex-grow-1"></code>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="gv_token_copy">Sao chép</button>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                  <button class="btn btn-outline-primary btn-sm" id="gv_token_tao">Tạo token mới</button>
                  <button class="btn btn-outline-danger btn-sm" id="gv_token_thu_hoi">Thu hồi token</button>
                  <span id="gv_token_msg" class="small text-muted"></span>
                </div>
              </div>
            </div>
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
         <h5 class="ribbon-title-modern modal-title" id="editModalTitle">Chỉnh sửa</h5>
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

<script>
// Helpers
async function jfetch(url, opts){ const r = await fetch(url, opts); return await r.json(); }
function formatSigned(n){ const v = Number(n)||0; return v>0?`+${v}`:String(v); }
function badge(on){ return `<span class="badge ${on? 'bg-success':'bg-warning text-dark'}">${on?'Bật':'Tắt'}</span>` }
// Dữ liệu như tên học sinh/lớp/lý do/quà là do giáo viên (bất kỳ ai được gán lớp đó) tự nhập -
// không escape trước khi ghép vào innerHTML thì một giáo viên có thể đặt tên chứa script và nó
// sẽ chạy trong trình duyệt của giáo viên khác cùng được gán lớp đó khi họ xem trang này.
function escapeHtml(s){
  return String(s ?? '').replace(/[&<>"']/g, c => ({
    '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;'
  }[c]));
}
// Mã lỗi trả về từ API (jj.thong_bao) là mã nội bộ (vd "lop_con_hoc_sinh") - dịch sang câu tiếng
// Việt dễ hiểu trước khi hiện cho giáo viên, thay vì alert() thẳng mã đó ra.
const THONG_BAO_LOI = {
  'lop_con_hoc_sinh': 'Lớp này còn học sinh, không thể xoá. Hãy chuyển/tắt học sinh trước, hoặc dùng nút "Tắt" thay vì xoá.',
  'ly_do_da_su_dung': 'Lý do này đã được dùng để cộng điểm trước đây nên không thể xoá (sẽ mất lịch sử). Dùng nút "Tắt" để ngừng dùng mà vẫn giữ lịch sử.',
  'qua_da_su_dung': 'Quà này đã có học sinh đổi trước đây nên không thể xoá (sẽ mất lịch sử). Dùng nút "Tắt" để ngừng dùng mà vẫn giữ lịch sử.',
  'ten_da_ton_tai': 'Tên này đã được dùng, hãy chọn tên khác.',
  'khong_du_quyen': 'Bạn không có quyền thực hiện thao tác này.',
  'thieu_tieu_de': 'Vui lòng nhập tiêu đề.',
  'thieu_ten': 'Vui lòng nhập tên.',
  'thieu_id': 'Thiếu thông tin dòng cần xử lý, hãy tải lại trang.',
  'gia_diem_khong_hop_le': 'Giá điểm phải lớn hơn 0 và không quá 1.000.000.',
  'ton_kho_khong_hop_le': 'Tồn kho phải từ -1 (không giới hạn) đến 1.000.000.',
  'khong_co_truong_cap_nhat': 'Không có thay đổi nào để lưu.',
  'khong_tim_thay': 'Không tìm thấy dữ liệu, hãy tải lại trang.',
  'thieu_file': 'Chưa chọn file ảnh.',
  'file_qua_lon': 'Ảnh quá lớn (tối đa 2MB).',
  'anh_qua_lon': 'Kích thước ảnh quá lớn (tối đa 4000x4000).',
  'dinh_dang_khong_ho_tro': 'Định dạng ảnh không hỗ trợ (chỉ JPG, PNG, GIF, WEBP).',
  'anh_khong_hop_le': 'File không phải ảnh hợp lệ.',
  'khong_ho_tro_webp': 'Máy chủ không hỗ trợ ảnh WEBP.',
  'khong_luu_duoc_file': 'Không lưu được ảnh, thử lại.',
  'upload_error': 'Tải ảnh lên thất bại, thử lại.',
  'qua_khong_hoat_dong': 'Quà đang tắt nên không thể đổi ảnh.',
  'qua_so_lan': 'Bạn tải ảnh quá nhiều lần, thử lại sau ít phút.',
  'khong_doc_duoc_file': 'Không đọc được file, thử lại.',
};
function dichLoi(ma, macDinh){
  return THONG_BAO_LOI[ma] || macDinh || ma || 'Lỗi';
}
// Hiển thị dấu + và điều chỉnh Biến điểm bằng nút ngoài
(function(){
  const input = document.getElementById('ld_bien_diem');
  const btnInc = document.querySelector('.ld-bd-inc');
  const btnDec = document.querySelector('.ld-bd-dec');
  const plus = document.querySelector('.ld-bd-plus');
  if(!input || !btnInc || !btnDec) return;
  const current = ()=>{ const v=parseInt(input.value,10); return Number.isNaN(v)?0:v; };
  const sync = ()=>{ const v=current(); if(plus) plus.style.display = v>0 ? 'block' : 'none'; input.value = v; };
  btnInc.onclick = ()=>{ input.value = current()+1; sync(); };
  btnDec.onclick = ()=>{ input.value = current()-1; sync(); };
  input.addEventListener('input', sync);
  input.addEventListener('change', sync);
  sync();
})();
// Hiển thị dấu + ở ô Biến điểm khi >0
const ldBienDiemInput = document.getElementById('ld_bien_diem');
const ldBienDiemPlus = document.querySelector('.ld-bd-plus');
if(ldBienDiemInput && ldBienDiemPlus){
  const updateBdPlus = ()=>{
    const v = Number(ldBienDiemInput.value)||0;
    ldBienDiemPlus.style.display = v>0 ? 'block' : 'none';
  };
  updateBdPlus();
  ldBienDiemInput.addEventListener('input', updateBdPlus);
  ldBienDiemInput.addEventListener('change', updateBdPlus);
}
async function openClassModal(lop){
  const modalEl = document.getElementById('editModal'); const modal = new bootstrap.Modal(modalEl);
  document.getElementById('editModalTitle').textContent = lop ? 'Sửa lớp học' : 'Thêm lớp học';
  const form = document.getElementById('editModalForm'); const msg = document.getElementById('editModalMsg');
  form.innerHTML=''; msg.textContent='';
  const divTen = document.createElement('div'); divTen.className='mb-2';
  divTen.innerHTML = `<label class="form-label">Tên lớp</label><input id="f_lop_ten" class="form-control" value="${escapeHtml(lop?.ten||'')}" required>`;
  form.appendChild(divTen);
  return await new Promise(resolve=>{
    const onHide = ()=>{ modalEl.removeEventListener('hidden.bs.modal', onHide); document.getElementById('editModalSave').removeEventListener('click', onSave); resolve(null); };
    const onSave = ()=>{ const tenVal = (document.getElementById('f_lop_ten')?.value||'').trim(); if(!tenVal){ msg.textContent='Nhập tên lớp'; return; }
      modal.hide(); modalEl.removeEventListener('hidden.bs.modal', onHide); document.getElementById('editModalSave').removeEventListener('click', onSave); resolve({ ten:tenVal }); };
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
    tr.innerHTML = `<td data-label="#">${x.id}</td><td data-label="Tiêu đề">${escapeHtml(x.tieu_de)}</td><td data-label="Biến điểm">${formatSigned(x.bien_diem)}</td><td data-label="Trạng thái">${badge(x.dang_hoat_dong)}</td>
    <td class="text-end stack-actions">
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
      if(jj.ok) ldNap(); else thongBao(dichLoi(jj.thong_bao), {loai:'canh_bao'}); };
    btnToggle.onclick = async()=>{
      const jj = await jfetch('../api/ly_do_quan_tri.php?hanh_dong=bat_tat',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:x.id, dang_hoat_dong:x.dang_hoat_dong?0:1})});
      if(jj.ok) ldNap(); else thongBao(dichLoi(jj.thong_bao), {loai:'canh_bao'}); };
    btnXoa.onclick = async()=>{
      if(!(await xacNhan('Xóa lý do này?', {loai:'nguy_hiem', nhanDongY:'Xóa', nguyHiem:true}))) return;
      const jj = await jfetch('../api/ly_do_quan_tri.php?hanh_dong=xoa',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:x.id})});
      if(jj.ok){ toast('Đã xoá lý do', {loai:'success'}); ldNap(); } else thongBao(dichLoi(jj.thong_bao), {loai:'canh_bao'}); };
    tb.appendChild(tr);
  });
}
document.getElementById('ld_them').onclick = async()=>{
  const t = document.getElementById('ld_tieu_de').value.trim(); const b = parseInt(document.getElementById('ld_bien_diem').value,10)||0;
  const j = await jfetch('../api/ly_do_quan_tri.php?hanh_dong=them',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({tieu_de:t, bien_diem:b})});
  if(j.ok){ document.getElementById('ld_tieu_de').value=''; toast('Đã thêm lý do', {loai:'success'}); ldNap(); } else thongBao(dichLoi(j.thong_bao), {loai:'canh_bao'});
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
    tr.innerHTML = `<td data-label="#">${x.id}</td><td data-label="Ảnh"><img src="${escapeHtml(av)}" alt="" style="width:32px;height:32px;object-fit:cover;border:1px solid #ddd;border-radius:6px" onerror="this.onerror=null;this.src='../upload/avatar/default.svg';"></td><td data-label="Tên">${escapeHtml(x.ten)}</td><td data-label="Giá điểm">${x.gia_diem}</td><td data-label="Tồn kho">${x.ton_kho}</td><td data-label="Trạng thái">${badge(x.dang_hoat_dong)}</td>
    <td class="text-end stack-actions">
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
      if(jj.ok) qNap(); else thongBao(dichLoi(jj.thong_bao), {loai:'canh_bao'}); };
    btnAnh.onclick = ()=>{
      const input = document.createElement('input'); input.type='file'; input.accept='image/*';
      input.onchange = async()=>{
        if(!input.files || !input.files[0]) return;
        const fd = new FormData(); fd.append('qua_tang_id', x.id); fd.append('file', input.files[0]);
        const r = await fetch('../api/upload_qua.php', { method:'POST', body: fd }); const jj = await r.json();
        if(jj.ok){ qNap(); } else thongBao(dichLoi(jj.thong_bao), {loai:'canh_bao'});
      };
      input.click();
    };
    btnToggle.onclick = async()=>{
      const jj = await jfetch('../api/qua_tang_quan_tri.php?hanh_dong=bat_tat',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:x.id, dang_hoat_dong:x.dang_hoat_dong?0:1})});
      if(jj.ok) qNap(); else thongBao(dichLoi(jj.thong_bao), {loai:'canh_bao'}); };
    btnXoa.onclick = async()=>{
      if(!(await xacNhan('Xóa quà tặng này?', {loai:'nguy_hiem', nhanDongY:'Xóa', nguyHiem:true}))) return;
      const jj = await jfetch('../api/qua_tang_quan_tri.php?hanh_dong=xoa',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:x.id})});
      if(jj.ok){ toast('Đã xoá quà tặng', {loai:'success'}); qNap(); } else thongBao(dichLoi(jj.thong_bao), {loai:'canh_bao'}); };
    tb.appendChild(tr);
  });
}
// Xem trước ảnh quà khi chọn file
(function(){
  const inp = document.getElementById('q_anh_file');
  const prev = document.getElementById('q_anh_preview');
  const msg = document.getElementById('q_anh_msg');
  if(!inp || !prev) return;
  inp.onchange = ()=>{
    msg.textContent=''; msg.className='small text-muted';
    const f = inp.files && inp.files[0];
    if(!f){ prev.classList.add('d-none'); prev.removeAttribute('src'); return; }
    if(f.size > 2*1024*1024){ msg.textContent = dichLoi('file_qua_lon'); msg.className='small text-danger'; inp.value=''; prev.classList.add('d-none'); return; }
    // Dùng data: URL (CSP chỉ cho phép img-src 'self' data:, không có blob:)
    const rd = new FileReader();
    rd.onload = ()=>{ prev.src = rd.result; prev.classList.remove('d-none'); };
    rd.onerror = ()=>{ prev.classList.add('d-none'); prev.removeAttribute('src'); };
    rd.readAsDataURL(f);
  };
})();
document.getElementById('q_them').onclick = async()=>{
  const btn = document.getElementById('q_them');
  const ten = document.getElementById('q_ten').value.trim();
  const gia = parseInt(document.getElementById('q_gia').value,10)||0;
  const ton = parseInt(document.getElementById('q_ton').value,10)||0;
  const anhEl = document.getElementById('q_anh');
  const anh = anhEl ? anhEl.value.trim() : '';
  const fileEl = document.getElementById('q_anh_file');
  const file = fileEl && fileEl.files && fileEl.files[0] ? fileEl.files[0] : null;
  const msg = document.getElementById('q_anh_msg');
  btn.disabled = true;
  try {
    const j = await jfetch('../api/qua_tang_quan_tri.php?hanh_dong=them',{method:'POST',headers:{'Content-Type':'application/json'},body: JSON.stringify({ten, gia_diem:gia, ton_kho:ton, anh_url:anh})});
    if(!j.ok){ thongBao(dichLoi(j.thong_bao), {loai:'canh_bao'}); return; }
    let anhLoi = false;
    if(file){
      const fd = new FormData(); fd.append('qua_tang_id', j.du_lieu.id); fd.append('file', file);
      const r = await fetch('../api/upload_qua.php', { method:'POST', body: fd }); const ju = await r.json();
      if(!ju.ok){ anhLoi = true; if(msg){ msg.textContent = 'Đã tạo quà nhưng tải ảnh lên lỗi: ' + dichLoi(ju.thong_bao); msg.className='small text-danger'; } }
    }
    toast(anhLoi ? 'Đã thêm quà (ảnh chưa lên được)' : 'Đã thêm quà tặng', {loai: anhLoi ? 'canh_bao' : 'success'});
    document.getElementById('q_ten').value='';
    if(anhEl) anhEl.value='';
    if(fileEl) fileEl.value='';
    const prev = document.getElementById('q_anh_preview'); if(prev){ prev.classList.add('d-none'); prev.removeAttribute('src'); }
    if(msg && msg.className.indexOf('text-danger')===-1){ msg.textContent=''; }
    qNap();
  } finally { btn.disabled = false; }
};

// Lớp học
async function lNap(){ const j = await jfetch('../api/lop_hoc_quan_tri.php'); if(!j.ok) return; const tb = document.getElementById('l_ds'); tb.innerHTML='';
  j.du_lieu.forEach(x => {
    const tr = document.createElement('tr');
    // Gắn data cho dòng (lop_hoc)
    tr.dataset.id = x.id;
    tr.dataset.ten = x.ten;
    tr.innerHTML = `<td data-label="#">${x.id}</td><td data-label="Tên">${escapeHtml(x.ten)}</td><td data-label="Trạng thái">${badge(x.dang_hoat_dong)}</td>
    <td class="text-end stack-actions">
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
      const body = { id:x.id, ten:res.ten };
      const jj = await jfetch('../api/lop_hoc_quan_tri.php?hanh_dong=sua',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(body)});
      if(jj.ok) lNap(); else thongBao(dichLoi(jj.thong_bao), {loai:'canh_bao'}); };
    btnToggle.onclick = async()=>{
      const jj = await jfetch('../api/lop_hoc_quan_tri.php?hanh_dong=bat_tat',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:x.id, dang_hoat_dong:x.dang_hoat_dong?0:1})});
      if(jj.ok) lNap(); else thongBao(dichLoi(jj.thong_bao), {loai:'canh_bao'}); };
    btnXoa.onclick = async()=>{
      if(!(await xacNhan('Xóa lớp học này?', {loai:'nguy_hiem', nhanDongY:'Xóa', nguyHiem:true}))) return;
      const jj = await jfetch('../api/lop_hoc_quan_tri.php?hanh_dong=xoa',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:x.id})});
      if(jj.ok){ toast('Đã xoá lớp học', {loai:'success'}); lNap(); } else thongBao(dichLoi(jj.thong_bao), {loai:'canh_bao'}); };
    tb.appendChild(tr);
  });
}
document.getElementById('l_them').onclick = async()=>{
  const ten = document.getElementById('l_ten').value.trim();
  const j = await jfetch('../api/lop_hoc_quan_tri.php?hanh_dong=them',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({ten})});
  if(j.ok){ document.getElementById('l_ten').value=''; toast('Đã thêm lớp học', {loai:'success'}); lNap(); hsNapThemLopOptions && hsNapThemLopOptions(); } else thongBao(dichLoi(j.thong_bao), {loai:'canh_bao'});
};

// Học sinh
let hsDangChon = null;

function hsResolveAvatar(url){
  const u = (url||'').trim();
  if (!u) return '../upload/avatar/default.svg';
  if (/^https?:\/\//i.test(u)) return u;
  if (u.startsWith('../')) return u;
  if (u.startsWith('/upload/')) return '..' + u;
  if (u.startsWith('upload/')) return '../' + u;
  return u;
}

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
  const lopText = document.querySelector('span#ct_lop_text');
  if(lopText && lopText.tagName !== 'SELECT') { lopText.textContent = 'Lớp: ' + (hsDangChon.ten_lop||''); }
  const active = Number(hsDangChon.dang_hoat_dong) === 1;
  if(tt) tt.textContent = active ? 'Đang bật' : 'Đang tắt';
  if(av) av.src = hsResolveAvatar(hsDangChon.anh_dai_dien_url);
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
    a.innerHTML = `<span>${escapeHtml(sttText)}${escapeHtml(s.ho_ten)} (${escapeHtml(s.ten_lop||'')}) - Điểm: ${s.so_du}</span>` + (active? '<span class="badge bg-success">Bật</span>' : '<span class="badge bg-warning text-dark">Tắt</span>');
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
// Nạp danh sách lớp vào ô chọn lớp của form "Thêm học sinh".
async function hsNapThemLopOptions(){
  const sel=document.getElementById('them_lop'); const hint=document.getElementById('them_lop_hint'); const btn=document.getElementById('them');
  if(!sel) return;
  try{
    const j=await jfetch('../api/lop_hoc_quan_tri.php');
    const ds=(j.ok && Array.isArray(j.du_lieu)) ? j.du_lieu.filter(l=>Number(l.dang_hoat_dong)===1) : [];
    const cu=sel.value;
    sel.innerHTML='<option value="">Chọn lớp học...</option>';
    ds.forEach(l=>{ const o=document.createElement('option'); o.value=l.id; o.textContent=l.ten; sel.appendChild(o); });
    if(cu && ds.some(l=>String(l.id)===String(cu))) sel.value=cu;
    const trong=ds.length===0;
    if(hint) hint.classList.toggle('d-none', !trong);
    if(btn) btn.disabled=trong;
  }catch(_e){}
}
hsNapThemLopOptions();
// Lớp có thể vừa được tạo ở tab "Lớp học" -> nạp lại khi quay lại tab "Học sinh".
document.querySelectorAll('#tab button[data-bs-target="#tab-hoc-sinh"]').forEach(b=>{
  b.addEventListener('shown.bs.tab', hsNapThemLopOptions);
});

const hsBtnThem = document.getElementById('them');
if(hsBtnThem) hsBtnThem.onclick=async()=>{
  const lop=document.getElementById('them_lop').value;
  if(!lop){ toast('Hãy chọn lớp học cho học sinh', {loai:'canh_bao'}); return; }
  const body = {
    ma: document.getElementById('ma').value,
    ho_ten: document.getElementById('ho_ten').value,
    lop_hoc_id: lop,
    anh_dai_dien_url: document.getElementById('anh_dai_dien_url').value,
    gioi_tinh: document.getElementById('gioi_tinh').value,
    ngay_sinh: hsToISODate(document.getElementById('ngay_sinh').value)
  };
  const r=await fetch('../api/hoc_sinh.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(body)});
  const j=await r.json();
  if(j.ok){
    toast('Đã thêm học sinh', {loai:'success'});
    document.getElementById('ho_ten').value=''; document.getElementById('ma').value='';
    document.getElementById('anh_dai_dien_url').value=''; hsResetAnhUpload(); hsNap();
  } else {
    toast(dichLoi(j.thong_bao) || 'Không thêm được học sinh', {loai:'nguy_hiem'});
  }
};

// Tải ảnh đại diện lên (endpoint chung upload_anh.php) rồi điền URL vào ô "Ảnh đại diện URL".
function hsResetAnhUpload(){
  const fi=document.getElementById('hs_anh_file'); const pv=document.getElementById('hs_anh_preview'); const mg=document.getElementById('hs_anh_msg');
  if(fi) fi.value=''; if(pv){ pv.classList.add('d-none'); pv.removeAttribute('src'); } if(mg){ mg.textContent=''; mg.className='small text-muted'; }
}
(function(){
  const fi=document.getElementById('hs_anh_file'); if(!fi) return;
  const pv=document.getElementById('hs_anh_preview'); const mg=document.getElementById('hs_anh_msg');
  const urlEl=document.getElementById('anh_dai_dien_url');
  fi.onchange=async()=>{
    mg.textContent=''; mg.className='small text-muted';
    const f=fi.files && fi.files[0];
    if(!f){ pv.classList.add('d-none'); pv.removeAttribute('src'); return; }
    if(f.size > 2*1024*1024){ mg.textContent=dichLoi('file_qua_lon'); mg.className='small text-danger'; fi.value=''; pv.classList.add('d-none'); return; }
    const rd=new FileReader(); rd.onload=()=>{ pv.src=rd.result; pv.classList.remove('d-none'); }; rd.readAsDataURL(f);
    mg.textContent='Đang tải ảnh lên...';
    const fd=new FormData(); fd.append('file', f);
    try{
      const rr=await fetch('../api/upload_anh.php',{method:'POST',body:fd}); const jj=await rr.json();
      if(jj.ok){ urlEl.value=jj.du_lieu.url; mg.textContent='Đã tải ảnh lên'; mg.className='small text-success'; }
      else { mg.textContent='Lỗi tải ảnh: '+dichLoi(jj.thong_bao); mg.className='small text-danger'; }
    }catch(_e){ mg.textContent='Lỗi mạng khi tải ảnh'; mg.className='small text-danger'; }
  };
})();
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
    toast(newState ? 'Đã bật học sinh' : 'Đã tắt học sinh', {loai:'success'});
    await hsNap(hsDangChon.id);
    hsSetChiTietInputs();
  } else { toast(dichLoi(j.thong_bao)||'Không cập nhật được', {loai:'nguy_hiem'}); }
};

// Upload avatar
const hsBtnUpAnh = document.getElementById('btn_up_anh');
if(hsBtnUpAnh) hsBtnUpAnh.onclick = async()=>{
  const f = document.getElementById('up_anh').files[0]; if(!hsDangChon) return;
  if(!f){ toast('Chọn ảnh để tải lên', {loai:'canh_bao'}); return; }
  const fd = new FormData(); fd.append('hoc_sinh_id', hsDangChon.id); fd.append('file', f);
  const r = await fetch('../api/upload_avatar.php', { method:'POST', body: fd });
  let j = null;
  try { j = await r.json(); }
  catch(_e){ toast('Tải ảnh thất bại (phản hồi không hợp lệ)', {loai:'nguy_hiem'}); return; }
  if(j.ok){ hsDangChon = { ...hsDangChon, anh_dai_dien_url: hsResolveAvatar(j.du_lieu.url) }; hsHienChiTiet(); hsSetChiTietInputs(); toast('Đã cập nhật ảnh đại diện', {loai:'success'}); document.getElementById('up_anh').value=''; }
  else { toast(dichLoi(j.thong_bao) || 'Lỗi tải ảnh', {loai:'nguy_hiem'}); }
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
  if (!(await xacNhan(ask, {tieuDe:'Nhập CSV', loai:'hoi', nhanDongY:'Nhập'}))) { msgEl.textContent = 'Đã hủy nhập CSV'; return; }
  msgEl.textContent = 'Đang nhập...';
  const fd = new FormData(); fd.append('file', f);
  const r = await fetch('../api/hoc_sinh_csv.php?hanh_dong=nhap', { method:'POST', body: fd });
  const j = await r.json();
  if (j.ok) { msgEl.textContent=''; toast('Nhập CSV xong: ' + (j.du_lieu?.tong_dong||0) + ' dòng', {loai:'success'}); hsNap(); }
  else { msgEl.textContent=''; toast(j.thong_bao || 'Lỗi nhập CSV', {loai:'nguy_hiem'}); }
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
async function hsNapLopOptions(){ const sel = document.querySelector('select#ct_lop'); if(!sel) return; try { const r = await fetch('../api/lop_hoc_quan_tri.php'); const j = await r.json(); if(!j.ok) return; sel.innerHTML=''; const opt0=document.createElement('option'); opt0.value=''; opt0.textContent='-- Không gán lớp --'; sel.appendChild(opt0); j.du_lieu.forEach(l=>{ const o=document.createElement('option'); o.value=l.id; o.textContent=l.ten; sel.appendChild(o); }); } catch(_e){} }
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
  const j = await r.json(); if(j.ok){ toast('Đã lưu thay đổi', {loai:'success'}); await hsNap(); }
  else { toast(dichLoi(j.thong_bao)||'Không lưu được', {loai:'nguy_hiem'}); }
};

function hsSyncLopSelectOnce(){
  const sel = document.querySelector('select#ct_lop');
  if(!sel) return;
  let tries = 0;
  const h = setInterval(()=>{
    tries++;
    const want = (hsDangChon && hsDangChon.lop_hoc_id!=null && hsDangChon.lop_hoc_id!=='') ? String(hsDangChon.lop_hoc_id) : '';
    // Chỉ dừng khi option cần chọn ĐÃ có trong select và set được đúng value
    // (tránh set value khi options chưa nạp xong -> selectedIndex=-1, hiển thị trống).
    if ([...sel.options].some(o=>o.value===want)) {
      sel.value = want;
      if (sel.value === want) { clearInterval(h); }
    }
    if (tries > 50) clearInterval(h);
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
    if (j.ok) {
      msg.textContent='Đã đổi mật khẩu';
      document.getElementById('gv_mk_cu').value=''; document.getElementById('gv_mk_moi').value=''; document.getElementById('gv_mk_lai').value='';
      <?php if ($can_doi_mk_bat_buoc): ?>location.reload();<?php endif; ?>
    }
    else { msg.textContent=j.thong_bao||'Lỗi'; }
  };
})();

// Token API cho app di động
(function(){
  const trangThai = document.getElementById('gv_token_trang_thai');
  const box = document.getElementById('gv_token_box');
  const qrEl = document.getElementById('gv_token_qr');
  const gtEl = document.getElementById('gv_token_gia_tri');
  const msg = document.getElementById('gv_token_msg');
  const btnTao = document.getElementById('gv_token_tao');
  const btnThuHoi = document.getElementById('gv_token_thu_hoi');
  const btnCopy = document.getElementById('gv_token_copy');
  const apiGocUrl = <?php echo json_encode($api_goc_url, JSON_UNESCAPED_SLASHES); ?>;
  if (!trangThai || !btnTao || !btnThuHoi) return;

  function ve(coToken){
    trangThai.innerHTML = coToken
      ? '<span class="text-success fw-semibold">Đã có token đang hoạt động</span>'
      : '<span class="text-muted">Chưa tạo token nào</span>';
  }

  function veQr(token){
    if (!qrEl) return;
    qrEl.innerHTML = '';
    if (typeof qrcode === 'undefined') return; // thư viện lỗi tải thì vẫn còn token chữ để dùng
    // Payload gồm cả token lẫn địa chỉ server, để app di động biết kết nối vào đâu (hỗ trợ tự host).
    const payload = JSON.stringify({ token: token, api_url: apiGocUrl });
    // typeNumber=0: tự chọn phiên bản QR nhỏ nhất vừa đủ chứa payload.
    const qr = qrcode(0, 'M');
    qr.addData(payload);
    qr.make();
    qrEl.innerHTML = qr.createSvgTag({ scalable: true });
    const svg = qrEl.querySelector('svg');
    if (svg) { svg.style.width = '160px'; svg.style.height = '160px'; svg.style.display = 'block'; }
  }

  async function napTrangThai(){
    try {
      const j = await jfetch('../api/giao_vien_quan_tri.php');
      if (j.ok) ve(!!j.du_lieu.co_token_api);
    } catch(_e){}
  }

  btnTao.onclick = async()=>{
    msg.textContent=''; box.classList.add('d-none');
    if (!(await xacNhan('Tạo token mới sẽ vô hiệu token cũ (nếu có) trên mọi thiết bị đang dùng. Tiếp tục?', {tieuDe:'Tạo token API', loai:'canh_bao', nhanDongY:'Tạo mới'}))) return;
    const j = await jfetch('../api/giao_vien_quan_tri.php?hanh_dong=tao_token_api',{method:'POST',headers:{'Content-Type':'application/json'},body:'{}'});
    if (j.ok) {
      gtEl.textContent = j.du_lieu.token;
      veQr(j.du_lieu.token);
      box.classList.remove('d-none');
      ve(true);
    } else { msg.textContent = j.thong_bao || 'Lỗi'; }
  };

  btnThuHoi.onclick = async()=>{
    msg.textContent='';
    if (!(await xacNhan('Thu hồi token hiện tại? Ứng dụng di động đang dùng token này sẽ không đăng nhập được nữa.', {tieuDe:'Thu hồi token API', loai:'nguy_hiem', nhanDongY:'Thu hồi', nguyHiem:true}))) return;
    const j = await jfetch('../api/giao_vien_quan_tri.php?hanh_dong=thu_hoi_token_api',{method:'POST',headers:{'Content-Type':'application/json'},body:'{}'});
    if (j.ok) { box.classList.add('d-none'); ve(false); msg.textContent='Đã thu hồi token'; }
    else { msg.textContent = j.thong_bao || 'Lỗi'; }
  };

  if (btnCopy) btnCopy.onclick = async()=>{
    try { await navigator.clipboard.writeText(gtEl.textContent||''); msg.textContent='Đã sao chép'; }
    catch(_e){ msg.textContent='Không sao chép được, hãy chọn thủ công'; }
  };

  napTrangThai();
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
  if(jj.ok){ toast('Đã lưu lý do', {loai:'success'}); ldNap(); } else thongBao(dichLoi(jj.thong_bao), {loai:'canh_bao'});
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
  if(jj.ok){ toast('Đã lưu quà tặng', {loai:'success'}); qNap(); } else thongBao(dichLoi(jj.thong_bao), {loai:'canh_bao'});
}, true);

document.getElementById('l_ds').addEventListener('click', async (e) => {
  const btn = e.target.closest('button.btn-outline-primary'); if(!btn) return;
  const tr = btn.closest('tr'); if(!tr) return; e.preventDefault(); e.stopImmediatePropagation();
  const res = await openClassModal({ id: parseInt(tr.dataset.id,10)||0, ten: tr.dataset.ten || '' });
  if(!res) return;
  const jj = await jfetch('../api/lop_hoc_quan_tri.php?hanh_dong=sua',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:parseInt(tr.dataset.id,10)||0, ten:String(res.ten||'').trim()})});
  if(jj.ok){ toast('Đã lưu lớp học', {loai:'success'}); lNap(); hsNapThemLopOptions && hsNapThemLopOptions(); } else thongBao(dichLoi(jj.thong_bao), {loai:'canh_bao'});
}, true);

// Load
ldNap(); qNap(); lNap();
</script>
<script src="vendor/bootstrap/bootstrap.bundle.min.js"></script>
<script src="vendor/aos/aos.js"></script>
<script src="vendor/date_picker.js"></script>
<script src="vendor/qrcode/qrcode.js"></script>
<script>AOS.init({ duration: 350, once: true, easing: 'ease-out' });</script>
</body></html>



