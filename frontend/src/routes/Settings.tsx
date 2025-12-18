
import { useMemo, useState } from 'react';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import { apiClient, ApiResponse } from '@/api/client';
import Students from './Students';

type Reason = { id: number; tieu_de: string; bien_diem: number; dang_hoat_dong: number };
type Gift = { id: number; ten: string; gia_diem: number; ton_kho: number; dang_hoat_dong: number; anh_url?: string | null };
type Lop = { id: number; ten: string; dang_hoat_dong: number; giao_vien_ids: number[] };
type GiaoVien = { id: number; ten_dang_nhap: string };
type TaiKhoan = { id: number; ten_dang_nhap: string; vai_tro: string };
type LogRow = { id: number; hanh_dong: string; noi_dung: string; tao_luc: string; ten_dang_nhap?: string };

type EditField = { name: string; label: string; type?: 'text' | 'number'; value?: string | number; required?: boolean; placeholder?: string };

type EditModalState = {
  title: string;
  fields: EditField[];
  onSave: (values: Record<string, string | number>) => Promise<void> | void;
};

async function safeGet<T>(path: string): Promise<T> {
  const res = await apiClient.get<T>(path);
  if ((res as ApiResponse<T>).ok && (res as ApiResponse<T>).du_lieu !== undefined) return (res as ApiResponse<T>).du_lieu as T;
  throw new Error((res as ApiResponse<T>).thong_bao || 'Không đọc được dữ liệu');
}

function formatDateTime(str?: string) {
  if (!str) return '';
  const m = String(str).match(/^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2}):(\d{2})/);
  if (!m) return str;
  const [, y, mo, d, h, mi, s] = m;
  return `${d}/${mo}/${y} ${h}:${mi}:${s}`;
}

function EditModal({ state, onClose }: { state: EditModalState | null; onClose: () => void }) {
  const [values, setValues] = useState<Record<string, string | number>>({});
  const [error, setError] = useState('');

  if (!state) return null;

  const initValues: Record<string, string | number> = {};
  state.fields.forEach((f) => {
    initValues[f.name] = f.value ?? '';
  });

  const currentValues = Object.keys(values).length ? values : initValues;

  const handleSave = async () => {
    setError('');
    for (const f of state.fields) {
      const v = currentValues[f.name];
      if (f.required && (v === '' || v === undefined || v === null)) {
        setError('Vui lòng nhập đầy đủ thông tin');
        return;
      }
    }
    await state.onSave(currentValues);
    onClose();
  };

  return (
    <div className="modal fade show d-block" style={{ backgroundColor: 'rgba(0,0,0,0.35)' }}>
      <div className="modal-dialog">
        <div className="modal-content">
          <div className="modal-header">
            <h5 className="ribbon-title-modern modal-title">{state.title}</h5>
            <button type="button" className="btn-close" aria-label="Close" onClick={onClose} />
          </div>
          <div className="modal-body">
            {state.fields.map((f) => (
              <div className="mb-2" key={f.name}>
                <label className="form-label">{f.label}</label>
                <input
                  className="form-control"
                  type={f.type || 'text'}
                  placeholder={f.placeholder}
                  value={currentValues[f.name] ?? ''}
                  onChange={(e) => {
                    const next = f.type === 'number' ? Number(e.target.value) : e.target.value;
                    setValues((prev) => ({ ...prev, [f.name]: next }));
                  }}
                />
              </div>
            ))}
            {error && <div className="small text-danger">{error}</div>}
          </div>
          <div className="modal-footer">
            <button className="btn btn-secondary" onClick={onClose}>
              Hủy
            </button>
            <button className="btn btn-primary" onClick={handleSave}>
              Lưu
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}

export default function Settings() {
  const queryClient = useQueryClient();
  const [activeTab, setActiveTab] = useState('ly-do');
  const [editModal, setEditModal] = useState<EditModalState | null>(null);
  const [classModal, setClassModal] = useState<{ open: boolean; id: number; ten: string; giao_vien_ids: number[] }>({
    open: false,
    id: 0,
    ten: '',
    giao_vien_ids: [],
  });
  const [ldTieuDe, setLdTieuDe] = useState('');
  const [ldBienDiem, setLdBienDiem] = useState(1);
  const [qTen, setQTen] = useState('');
  const [qGia, setQGia] = useState(1);
  const [qTon, setQTon] = useState(0);
  const [qAnh, setQAnh] = useState('');
  const [lTen, setLTen] = useState('');
  const [gvMkCu, setGvMkCu] = useState('');
  const [gvMkMoi, setGvMkMoi] = useState('');
  const [gvMkLai, setGvMkLai] = useState('');
  const [gvTen, setGvTen] = useState('');
  const [gvMk, setGvMk] = useState('');
  const [gvMsg, setGvMsg] = useState('');
  const [logSearch, setLogSearch] = useState('');
  const [logAction, setLogAction] = useState('');
  const [logUser, setLogUser] = useState('');

  const { data: reasons = [] } = useQuery({ queryKey: ['ly-do'], queryFn: () => safeGet<Reason[]>('/ly_do_quan_tri.php') });
  const { data: gifts = [] } = useQuery({ queryKey: ['qua'], queryFn: () => safeGet<Gift[]>('/qua_tang_quan_tri.php') });
  const { data: lops = [] } = useQuery({ queryKey: ['lop'], queryFn: () => safeGet<Lop[]>('/lop_hoc_quan_tri.php') });
  const { data: giaoVienDs = [] } = useQuery({
    queryKey: ['giao-vien-ds'],
    queryFn: async () => {
      const res = await apiClient.get<{ giao_vien: GiaoVien[] }>('/giao_vien_quan_tri.php?hanh_dong=ds');
      if (res.ok && res.du_lieu?.giao_vien) return res.du_lieu.giao_vien;
      return [];
    },
  });
  const { data: taiKhoan = [] } = useQuery({
    queryKey: ['tai-khoan'],
    queryFn: async () => {
      const res = await apiClient.get<{ tai_khoan: TaiKhoan[] }>('/giao_vien_quan_tri.php?hanh_dong=ds_tk');
      if (res.ok && res.du_lieu?.tai_khoan) return res.du_lieu.tai_khoan;
      return [];
    },
  });
  const { data: logs = [] } = useQuery({ queryKey: ['nhat-ky'], queryFn: () => safeGet<LogRow[]>('/nhat_ky.php?limit=200') });

  const logActions = useMemo(() => Array.from(new Set(logs.map((l) => l.hanh_dong))).filter(Boolean), [logs]);
  const logUsers = useMemo(() => Array.from(new Set(logs.map((l) => l.ten_dang_nhap || ''))).filter(Boolean), [logs]);
  const filteredLogs = useMemo(() => {
    return logs.filter((l) => {
      if (logAction && l.hanh_dong !== logAction) return false;
      if (logUser && (l.ten_dang_nhap || '') !== logUser) return false;
      if (logSearch) {
        const hay = `${l.noi_dung || ''} ${l.hanh_dong || ''} ${l.ten_dang_nhap || ''}`.toLowerCase();
        if (!hay.includes(logSearch.toLowerCase())) return false;
      }
      return true;
    });
  }, [logs, logAction, logUser, logSearch]);

  const openEdit = (title: string, fields: EditField[], onSave: EditModalState['onSave']) => {
    setEditModal({ title, fields, onSave });
  };

  const handleAddLyDo = async () => {
    const res = await apiClient.post('/ly_do_quan_tri.php?hanh_dong=them', { tieu_de: ldTieuDe.trim(), bien_diem: ldBienDiem });
    if (res.ok) {
      setLdTieuDe('');
      queryClient.invalidateQueries({ queryKey: ['ly-do'] });
    }
  };
  const handleAddQua = async () => {
    const res = await apiClient.post('/qua_tang_quan_tri.php?hanh_dong=them', {
      ten: qTen.trim(),
      gia_diem: qGia,
      ton_kho: qTon,
      anh_url: qAnh.trim(),
    });
    if (res.ok) {
      setQTen('');
      setQGia(1);
      setQTon(0);
      setQAnh('');
      queryClient.invalidateQueries({ queryKey: ['qua'] });
    }
  };

  const handleAddLop = async () => {
    const res = await apiClient.post('/lop_hoc_quan_tri.php?hanh_dong=them', { ten: lTen.trim() });
    if (res.ok) {
      setLTen('');
      queryClient.invalidateQueries({ queryKey: ['lop'] });
    }
  };

  const handleDoiMatKhau = async () => {
    setGvMsg('');
    if (!gvMkMoi || gvMkMoi !== gvMkLai) {
      setGvMsg('Mật khẩu nhập lại không khớp');
      return;
    }
    const res = await apiClient.post('/giao_vien_quan_tri.php?hanh_dong=doi_mat_khau', {
      mat_khau_cu: gvMkCu,
      mat_khau_moi: gvMkMoi,
    });
    if (res.ok) {
      setGvMsg('Đã đổi mật khẩu');
      setGvMkCu('');
      setGvMkMoi('');
      setGvMkLai('');
    } else {
      setGvMsg(res.thong_bao || 'Lỗi');
    }
  };

  const handleThemGiaoVien = async () => {
    setGvMsg('');
    if (!gvTen || !gvMk) {
      setGvMsg('Nhập đầy đủ tài khoản và mật khẩu');
      return;
    }
    const res = await apiClient.post('/giao_vien_quan_tri.php?hanh_dong=them', {
      ten_dang_nhap: gvTen.trim(),
      mat_khau: gvMk,
    });
    if (res.ok) {
      setGvMsg('Đã thêm giáo viên');
      setGvTen('');
      setGvMk('');
      queryClient.invalidateQueries({ queryKey: ['tai-khoan'] });
      queryClient.invalidateQueries({ queryKey: ['giao-vien-ds'] });
    } else {
      setGvMsg(res.thong_bao || 'Lỗi');
    }
  };

  const handleUpdateRole = async (id: number, vai_tro: string) => {
    const res = await apiClient.post('/giao_vien_quan_tri.php?hanh_dong=cap_nhat_vai_tro', { id, vai_tro });
    if (res.ok) queryClient.invalidateQueries({ queryKey: ['tai-khoan'] });
  };

  const handleResetMatKhau = async (id: number, ten: string) => {
    const mk = window.prompt(`Nhập mật khẩu mới cho ${ten}`);
    if (mk === null) return;
    if (!mk.trim()) {
      setGvMsg('Nhập mật khẩu mới');
      return;
    }
    const res = await apiClient.post('/giao_vien_quan_tri.php?hanh_dong=reset_mat_khau', { id, mat_khau_moi: mk.trim() });
    if (res.ok) setGvMsg(`Đã reset mật khẩu cho ${ten}`);
  };

  const classModalTeachers = giaoVienDs.map((gv) => ({
    ...gv,
    checked: classModal.giao_vien_ids.includes(gv.id),
  }));

  return (
    <div className="container py-3">
      <div className="d-flex align-items-center justify-content-between">
        <h5 className="ribbon-title-modern mb-0">Cấu hình hệ thống</h5>
      </div>

      <ul className="nav nav-tabs mt-3 nav-square">
        <li className="nav-item">
          <button className={`nav-link ${activeTab === 'ly-do' ? 'active' : ''}`} onClick={() => setActiveTab('ly-do')}>
            Lý do
          </button>
        </li>
        <li className="nav-item">
          <button className={`nav-link ${activeTab === 'qua' ? 'active' : ''}`} onClick={() => setActiveTab('qua')}>
            Quà tặng
          </button>
        </li>
        <li className="nav-item">
          <button className={`nav-link ${activeTab === 'lop' ? 'active' : ''}`} onClick={() => setActiveTab('lop')}>
            Lớp học
          </button>
        </li>
        <li className="nav-item">
          <button className={`nav-link ${activeTab === 'hoc-sinh' ? 'active' : ''}`} onClick={() => setActiveTab('hoc-sinh')}>
            Học sinh
          </button>
        </li>
        <li className="nav-item">
          <button className={`nav-link ${activeTab === 'tai-khoan' ? 'active' : ''}`} onClick={() => setActiveTab('tai-khoan')}>
            Tài khoản
          </button>
        </li>
      </ul>

      <div className="tab-content border border-top-0 p-3">
        {activeTab === 'ly-do' && (
          <div className="row g-3">
            <div className="col-md-4">
              <div className="card shadow-sm" data-aos="fade-up">
                <div className="card-body">
                  <h5 className="ribbon-title-modern">Thêm lý do</h5>
                  <div className="mb-2">
                    <label className="form-label">Tiêu đề</label>
                    <input className="form-control" value={ldTieuDe} onChange={(e) => setLdTieuDe(e.target.value)} />
                  </div>
                  <div className="mb-2">
                    <label className="form-label">Biến điểm</label>
                    <input type="number" className="form-control" value={ldBienDiem} onChange={(e) => setLdBienDiem(Number(e.target.value))} />
                  </div>
                  <button className="btn btn-primary btn-sm" onClick={handleAddLyDo}>
                    Thêm
                  </button>
                </div>
              </div>
            </div>
            <div className="col-md-8">
              <div className="card shadow-sm" data-aos="fade-up">
                <div className="card-body">
                  <div className="d-flex align-items-center justify-content-between">
                    <h5 className="ribbon-title-modern mb-0">Danh sách</h5>
                    <small className="text-muted">Bật/tắt, sửa hoặc xóa</small>
                  </div>
                  <div className="table-responsive mt-2">
                    <table className="table table-sm align-middle">
                      <thead>
                        <tr>
                          <th>#</th>
                          <th>Tiêu đề</th>
                          <th>Biến điểm</th>
                          <th>Trạng thái</th>
                          <th />
                        </tr>
                      </thead>
                      <tbody>
                        {reasons.map((r, idx) => (
                          <tr key={r.id}>
                            <td>{idx + 1}</td>
                            <td>{r.tieu_de}</td>
                            <td>{r.bien_diem}</td>
                            <td>
                              <span className={`badge ${r.dang_hoat_dong ? 'bg-success' : 'bg-warning text-dark'}`}>
                                {r.dang_hoat_dong ? 'Bật' : 'Tắt'}
                              </span>
                            </td>
                            <td className="text-end">
                              <div className="btn-group btn-group-sm">
                                <button
                                  className="btn btn-outline-primary"
                                  onClick={() =>
                                    openEdit(
                                      'Sửa lý do',
                                      [
                                        { name: 'tieu_de', label: 'Tiêu đề', value: r.tieu_de, required: true },
                                        { name: 'bien_diem', label: 'Biến điểm', type: 'number', value: r.bien_diem, required: true },
                                      ],
                                      async (values) => {
                                        await apiClient.post('/ly_do_quan_tri.php?hanh_dong=sua', {
                                          id: r.id,
                                          tieu_de: String(values.tieu_de || '').trim(),
                                          bien_diem: Number(values.bien_diem || 0),
                                        });
                                        queryClient.invalidateQueries({ queryKey: ['ly-do'] });
                                      }
                                    )
                                  }
                                >
                                  Sửa
                                </button>
                                <button
                                  className="btn btn-outline-secondary"
                                  onClick={async () => {
                                    await apiClient.post('/ly_do_quan_tri.php?hanh_dong=bat_tat', { id: r.id, dang_hoat_dong: r.dang_hoat_dong ? 0 : 1 });
                                    queryClient.invalidateQueries({ queryKey: ['ly-do'] });
                                  }}
                                >
                                  {r.dang_hoat_dong ? 'Tắt' : 'Bật'}
                                </button>
                                <button
                                  className="btn btn-outline-danger"
                                  onClick={async () => {
                                    if (!window.confirm('Xóa lý do này?')) return;
                                    await apiClient.post('/ly_do_quan_tri.php?hanh_dong=xoa', { id: r.id });
                                    queryClient.invalidateQueries({ queryKey: ['ly-do'] });
                                  }}
                                >
                                  Xóa
                                </button>
                              </div>
                            </td>
                          </tr>
                        ))}
                        {!reasons.length && (
                          <tr>
                            <td colSpan={5} className="text-muted small">
                              Chưa có dữ liệu
                            </td>
                          </tr>
                        )}
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </div>
        )}

        {activeTab === 'qua' && (
          <div className="row g-3">
            <div className="col-md-4">
              <div className="card shadow-sm" data-aos="fade-up">
                <div className="card-body">
                  <h5 className="ribbon-title-modern">Thêm quà tặng</h5>
                  <div className="mb-2">
                    <label className="form-label">Tên</label>
                    <input className="form-control" value={qTen} onChange={(e) => setQTen(e.target.value)} />
                  </div>
                  <div className="mb-2">
                    <label className="form-label">Giá điểm</label>
                    <input type="number" className="form-control" value={qGia} onChange={(e) => setQGia(Number(e.target.value))} />
                  </div>
                  <div className="mb-2">
                    <label className="form-label">Tồn kho</label>
                    <input type="number" className="form-control" value={qTon} onChange={(e) => setQTon(Number(e.target.value))} />
                  </div>
                  <div className="mb-2">
                    <label className="form-label">URL ảnh (tùy chọn)</label>
                    <input className="form-control" value={qAnh} onChange={(e) => setQAnh(e.target.value)} />
                  </div>
                  <button className="btn btn-primary btn-sm" onClick={handleAddQua}>
                    Thêm
                  </button>
                </div>
              </div>
            </div>
            <div className="col-md-8">
              <div className="card shadow-sm" data-aos="fade-up">
                <div className="card-body">
                  <div className="d-flex align-items-center justify-content-between">
                    <h5 className="ribbon-title-modern mb-0">Danh sách</h5>
                    <small className="text-muted">Bật/tắt, sửa hoặc xóa</small>
                  </div>
                  <div className="table-responsive mt-2">
                    <table className="table table-sm align-middle">
                      <thead>
                        <tr>
                          <th>#</th>
                          <th>Ảnh</th>
                          <th>Tên</th>
                          <th>Giá điểm</th>
                          <th>Tồn kho</th>
                          <th>Trạng thái</th>
                          <th />
                        </tr>
                      </thead>
                      <tbody>
                        {gifts.map((g, idx) => (
                          <tr key={g.id}>
                            <td>{idx + 1}</td>
                            <td>{g.anh_url ? <img src={g.anh_url} alt="" style={{ width: 32, height: 32, objectFit: 'cover' }} /> : '-'}</td>
                            <td>{g.ten}</td>
                            <td>{g.gia_diem}</td>
                            <td>{g.ton_kho}</td>
                            <td>
                              <span className={`badge ${g.dang_hoat_dong ? 'bg-success' : 'bg-warning text-dark'}`}>
                                {g.dang_hoat_dong ? 'Bật' : 'Tắt'}
                              </span>
                            </td>
                            <td className="text-end">
                              <div className="btn-group btn-group-sm">
                                <button
                                  className="btn btn-outline-primary"
                                  onClick={() =>
                                    openEdit(
                                      'Sửa quà tặng',
                                      [
                                        { name: 'ten', label: 'Tên', value: g.ten, required: true },
                                        { name: 'gia_diem', label: 'Giá điểm', type: 'number', value: g.gia_diem, required: true },
                                        { name: 'ton_kho', label: 'Tồn kho', type: 'number', value: g.ton_kho, required: true },
                                        { name: 'anh_url', label: 'URL ảnh', value: g.anh_url || '' },
                                      ],
                                      async (values) => {
                                        await apiClient.post('/qua_tang_quan_tri.php?hanh_dong=sua', {
                                          id: g.id,
                                          ten: String(values.ten || '').trim(),
                                          gia_diem: Number(values.gia_diem || 0),
                                          ton_kho: Number(values.ton_kho || 0),
                                          anh_url: String(values.anh_url || '').trim(),
                                        });
                                        queryClient.invalidateQueries({ queryKey: ['qua'] });
                                      }
                                    )
                                  }
                                >
                                  Sửa
                                </button>
                                <button
                                  className="btn btn-outline-secondary"
                                  onClick={async () => {
                                    await apiClient.post('/qua_tang_quan_tri.php?hanh_dong=bat_tat', { id: g.id, dang_hoat_dong: g.dang_hoat_dong ? 0 : 1 });
                                    queryClient.invalidateQueries({ queryKey: ['qua'] });
                                  }}
                                >
                                  {g.dang_hoat_dong ? 'Tắt' : 'Bật'}
                                </button>
                                <button
                                  className="btn btn-outline-danger"
                                  onClick={async () => {
                                    if (!window.confirm('Xóa quà tặng này?')) return;
                                    await apiClient.post('/qua_tang_quan_tri.php?hanh_dong=xoa', { id: g.id });
                                    queryClient.invalidateQueries({ queryKey: ['qua'] });
                                  }}
                                >
                                  Xóa
                                </button>
                              </div>
                            </td>
                          </tr>
                        ))}
                        {!gifts.length && (
                          <tr>
                            <td colSpan={7} className="text-muted small">
                              Chưa có dữ liệu
                            </td>
                          </tr>
                        )}
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </div>
        )}
        {activeTab === 'lop' && (
          <div className="row g-3">
            <div className="col-md-4">
              <div className="card shadow-sm" data-aos="fade-up">
                <div className="card-body">
                  <h5 className="ribbon-title-modern">Thêm lớp học</h5>
                  <div className="mb-2">
                    <label className="form-label">Tên lớp</label>
                    <input className="form-control" value={lTen} onChange={(e) => setLTen(e.target.value)} />
                  </div>
                  <button className="btn btn-primary btn-sm" onClick={handleAddLop}>
                    Thêm
                  </button>
                </div>
              </div>
            </div>
            <div className="col-md-8">
              <div className="card shadow-sm" data-aos="fade-up">
                <div className="card-body">
                  <div className="d-flex align-items-center justify-content-between">
                    <h5 className="ribbon-title-modern mb-0">Danh sách</h5>
                    <small className="text-muted">Bật/tắt, sửa hoặc xóa</small>
                  </div>
                  <div className="table-responsive mt-2">
                    <table className="table table-sm align-middle">
                      <thead>
                        <tr>
                          <th>#</th>
                          <th>Tên</th>
                          <th>Trạng thái</th>
                          <th>Giáo viên quản lý</th>
                          <th />
                        </tr>
                      </thead>
                      <tbody>
                        {lops.map((l, idx) => (
                          <tr key={l.id}>
                            <td>{idx + 1}</td>
                            <td>{l.ten}</td>
                            <td>
                              <span className={`badge ${l.dang_hoat_dong ? 'bg-success' : 'bg-warning text-dark'}`}>
                                {l.dang_hoat_dong ? 'Bật' : 'Tắt'}
                              </span>
                            </td>
                            <td>
                              {l.giao_vien_ids
                                .map((id) => giaoVienDs.find((gv) => gv.id === id)?.ten_dang_nhap || '')
                                .filter(Boolean)
                                .join(', ') || '-'}
                            </td>
                            <td className="text-end">
                              <div className="btn-group btn-group-sm">
                                <button
                                  className="btn btn-outline-primary"
                                  onClick={() => setClassModal({ open: true, id: l.id, ten: l.ten, giao_vien_ids: l.giao_vien_ids || [] })}
                                >
                                  Sửa
                                </button>
                                <button
                                  className="btn btn-outline-secondary"
                                  onClick={async () => {
                                    await apiClient.post('/lop_hoc_quan_tri.php?hanh_dong=bat_tat', { id: l.id, dang_hoat_dong: l.dang_hoat_dong ? 0 : 1 });
                                    queryClient.invalidateQueries({ queryKey: ['lop'] });
                                  }}
                                >
                                  {l.dang_hoat_dong ? 'Tắt' : 'Bật'}
                                </button>
                                <button
                                  className="btn btn-outline-danger"
                                  onClick={async () => {
                                    if (!window.confirm('Xóa lớp học này?')) return;
                                    await apiClient.post('/lop_hoc_quan_tri.php?hanh_dong=xoa', { id: l.id });
                                    queryClient.invalidateQueries({ queryKey: ['lop'] });
                                  }}
                                >
                                  Xóa
                                </button>
                              </div>
                            </td>
                          </tr>
                        ))}
                        {!lops.length && (
                          <tr>
                            <td colSpan={5} className="text-muted small">
                              Chưa có dữ liệu
                            </td>
                          </tr>
                        )}
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </div>
        )}

        {activeTab === 'hoc-sinh' && <Students />}

        {activeTab === 'tai-khoan' && (
          <div>
            <div className="card shadow-sm" data-aos="fade-up">
              <div className="card-body">
                <div className="row g-3">
                  <div className="col-md-6">
                    <div className="border rounded-3 p-3 h-100">
                      <h5 className="ribbon-title-modern mb-3">Đổi mật khẩu</h5>
                      <div className="mb-2">
                        <label className="form-label">Mật khẩu hiện tại</label>
                        <input type="password" className="form-control" value={gvMkCu} onChange={(e) => setGvMkCu(e.target.value)} />
                      </div>
                      <div className="mb-2">
                        <label className="form-label">Mật khẩu mới</label>
                        <input type="password" className="form-control" value={gvMkMoi} onChange={(e) => setGvMkMoi(e.target.value)} />
                      </div>
                      <div className="mb-3">
                        <label className="form-label">Nhập lại mật khẩu mới</label>
                        <input type="password" className="form-control" value={gvMkLai} onChange={(e) => setGvMkLai(e.target.value)} />
                      </div>
                      <div className="d-flex align-items-center gap-2">
                        <button className="btn btn-primary btn-sm" onClick={handleDoiMatKhau}>
                          Đổi mật khẩu
                        </button>
                        <span className="small text-muted">{gvMsg}</span>
                      </div>
                    </div>
                  </div>
                  <div className="col-md-6">
                    <div className="border rounded-3 p-3 h-100">
                      <h5 className="ribbon-title-modern mb-3">Thêm giáo viên mới</h5>
                      <div className="mb-2">
                        <label className="form-label">Tên đăng nhập</label>
                        <input className="form-control" value={gvTen} onChange={(e) => setGvTen(e.target.value)} placeholder="vd: gv2" />
                      </div>
                      <div className="mb-3">
                        <label className="form-label">Mật khẩu</label>
                        <input type="password" className="form-control" value={gvMk} onChange={(e) => setGvMk(e.target.value)} />
                      </div>
                      <div className="d-flex align-items-center gap-2">
                        <button className="btn btn-primary btn-sm" onClick={handleThemGiaoVien}>
                          Thêm
                        </button>
                        <span className="small text-muted">{gvMsg}</span>
                      </div>
                    </div>
                  </div>
                </div>

                <hr className="my-4" />

                <div className="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                  <h5 className="ribbon-title-modern mb-0">Phân quyền tài khoản</h5>
                  <div className="small text-muted">Cập nhật vai trò hoặc reset mật khẩu.</div>
                </div>
                <div className="table-responsive">
                  <table className="table table-sm align-middle mb-0 role-table">
                    <thead>
                      <tr>
                        <th style={{ width: 180 }}>Tài khoản</th>
                        <th style={{ width: 220 }}>Vai trò</th>
                        <th style={{ width: 200 }}>Thao tác</th>
                      </tr>
                    </thead>
                    <tbody>
                      {taiKhoan.map((tk) => (
                        <tr key={tk.id}>
                          <td>{tk.ten_dang_nhap}</td>
                          <td>
                            <select
                              className="form-select form-select-sm"
                              defaultValue={tk.vai_tro || 'GV'}
                              onChange={(e) => handleUpdateRole(tk.id, e.target.value)}
                            >
                              <option value="ADMIN">ADMIN</option>
                              <option value="GV">GV</option>
                            </select>
                          </td>
                          <td>
                            <button className="btn btn-danger btn-sm" onClick={() => handleResetMatKhau(tk.id, tk.ten_dang_nhap)}>
                              Đặt lại MK
                            </button>
                          </td>
                        </tr>
                      ))}
                      {!taiKhoan.length && (
                        <tr>
                          <td colSpan={3} className="text-muted small">
                            Chưa có dữ liệu
                          </td>
                        </tr>
                      )}
                    </tbody>
                  </table>
                </div>
              </div>
            </div>

            <div className="card shadow-sm mt-3" data-aos="fade-up">
              <div className="card-body">
                <div className="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                  <div>
                    <h5 className="ribbon-title-modern mb-1">Log thao tác</h5>
                    <div className="small text-muted">Tối đa 200 dòng gần nhất.</div>
                  </div>
                </div>
                <div className="row g-2 mb-3">
                  <div className="col-sm-12 col-lg-4">
                    <input className="form-control form-control-sm" placeholder="Tìm nội dung, tài khoản..." value={logSearch} onChange={(e) => setLogSearch(e.target.value)} />
                  </div>
                  <div className="col-sm-6 col-lg-3">
                    <select className="form-select form-select-sm" value={logAction} onChange={(e) => setLogAction(e.target.value)}>
                      <option value="">Hành động</option>
                      {logActions.map((a) => (
                        <option key={a} value={a}>
                          {a}
                        </option>
                      ))}
                    </select>
                  </div>
                  <div className="col-sm-6 col-lg-3">
                    <select className="form-select form-select-sm" value={logUser} onChange={(e) => setLogUser(e.target.value)}>
                      <option value="">Tài khoản</option>
                      {logUsers.map((u) => (
                        <option key={u} value={u}>
                          {u}
                        </option>
                      ))}
                    </select>
                  </div>
                  <div className="col-sm-12 col-lg-2 text-lg-end">
                    <button className="btn btn-outline-primary btn-sm w-100 w-lg-auto" onClick={() => queryClient.invalidateQueries({ queryKey: ['nhat-ky'] })}>
                      Tải lại log
                    </button>
                  </div>
                </div>
                <div className="table-responsive">
                  <table className="table table-sm align-middle mb-0">
                    <thead>
                      <tr>
                        <th>Thời gian</th>
                        <th>Tài khoản</th>
                        <th>Hành động</th>
                        <th>Nội dung</th>
                      </tr>
                    </thead>
                    <tbody>
                      {filteredLogs.map((l) => (
                        <tr key={l.id}>
                          <td>{formatDateTime(l.tao_luc)}</td>
                          <td>{l.ten_dang_nhap || '-'}</td>
                          <td>{l.hanh_dong}</td>
                          <td>{l.noi_dung}</td>
                        </tr>
                      ))}
                      {!filteredLogs.length && (
                        <tr>
                          <td colSpan={4} className="text-muted small">
                            Không có dữ liệu
                          </td>
                        </tr>
                      )}
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        )}
      </div>

      {editModal && <EditModal state={editModal} onClose={() => setEditModal(null)} />}

      {classModal.open && (
        <div className="modal fade show d-block" style={{ backgroundColor: 'rgba(0,0,0,0.35)' }}>
          <div className="modal-dialog">
            <div className="modal-content">
              <div className="modal-header">
                <h5 className="ribbon-title-modern modal-title">Sửa lớp học</h5>
                <button className="btn-close" aria-label="Close" onClick={() => setClassModal({ ...classModal, open: false })} />
              </div>
              <div className="modal-body">
                <div className="mb-2">
                  <label className="form-label">Tên lớp</label>
                  <input
                    className="form-control"
                    value={classModal.ten}
                    onChange={(e) => setClassModal((prev) => ({ ...prev, ten: e.target.value }))}
                  />
                </div>
                <div className="mb-2">
                  <label className="form-label">Giáo viên quản lý</label>
                  <div className="border rounded-3 p-2" style={{ maxHeight: 240, overflow: 'auto' }}>
                    {classModalTeachers.map((gv) => (
                      <div className="form-check" key={gv.id}>
                        <input
                          className="form-check-input"
                          type="checkbox"
                          checked={gv.checked}
                          onChange={(e) => {
                            const checked = e.target.checked;
                            setClassModal((prev) => ({
                              ...prev,
                              giao_vien_ids: checked ? [...prev.giao_vien_ids, gv.id] : prev.giao_vien_ids.filter((id) => id !== gv.id),
                            }));
                          }}
                        />
                        <label className="form-check-label">{gv.ten_dang_nhap}</label>
                      </div>
                    ))}
                    {!giaoVienDs.length && <div className="text-muted small">Chưa có giáo viên</div>}
                  </div>
                </div>
              </div>
              <div className="modal-footer">
                <button className="btn btn-secondary" onClick={() => setClassModal({ ...classModal, open: false })}>
                  Hủy
                </button>
                <button
                  className="btn btn-primary"
                  onClick={async () => {
                    await apiClient.post('/lop_hoc_quan_tri.php?hanh_dong=sua', {
                      id: classModal.id,
                      ten: classModal.ten.trim(),
                      giao_vien_ids: classModal.giao_vien_ids,
                    });
                    setClassModal({ open: false, id: 0, ten: '', giao_vien_ids: [] });
                    queryClient.invalidateQueries({ queryKey: ['lop'] });
                  }}
                >
                  Lưu
                </button>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
