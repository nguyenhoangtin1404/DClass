import { useEffect, useMemo, useRef, useState } from 'react';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import { apiClient, ApiResponse } from '@/api/client';

type Student = {
  id: number;
  ma?: string;
  ho_ten: string;
  stt?: number | null;
  lop_hoc_id?: number | null;
  ten_lop?: string;
  so_du?: number;
  anh_dai_dien_url?: string;
  gioi_tinh?: string;
  ngay_sinh?: string;
  dang_hoat_dong?: number | boolean;
};

type Lop = { id: number; ten: string };

const API_BASE = import.meta.env.VITE_API_BASE || '/api';

function toISODate(v?: string) {
  if (!v) return '';
  if (v.includes('/')) {
    const m = v.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/);
    if (!m) return v;
    const d = m[1].padStart(2, '0');
    const mm = m[2].padStart(2, '0');
    const y = m[3];
    return `${y}-${mm}-${d}`;
  }
  return v;
}

function normalizeActive(v?: number | boolean) {
  if (typeof v === 'boolean') return v;
  return Number(v) === 1;
}

async function safeGet<T>(path: string): Promise<T> {
  const res = await apiClient.get<T>(path);
  if ((res as ApiResponse<T>).ok && (res as ApiResponse<T>).du_lieu !== undefined) return (res as ApiResponse<T>).du_lieu as T;
  throw new Error((res as ApiResponse<T>).thong_bao || 'Khong doc duoc du lieu');
}

export default function Students() {
  const queryClient = useQueryClient();
  const [tuKhoa, setTuKhoa] = useState('');
  const [hienTatCa, setHienTatCa] = useState(false);
  const [selected, setSelected] = useState<Student | null>(null);
  const [msg, setMsg] = useState('');
  const [csvMsg, setCsvMsg] = useState('');
  const [ctMsg, setCtMsg] = useState('');
  const [newMa, setNewMa] = useState('');
  const [newTen, setNewTen] = useState('');
  const [newAnh, setNewAnh] = useState('');
  const [newGioi, setNewGioi] = useState('');
  const [newNgay, setNewNgay] = useState('');
  const [ctMa, setCtMa] = useState('');
  const [ctTen, setCtTen] = useState('');
  const [ctGioi, setCtGioi] = useState('');
  const [ctNgay, setCtNgay] = useState('');
  const [ctLop, setCtLop] = useState('');
  const [csvFileName, setCsvFileName] = useState('Chua chon tep');
  const fileInputRef = useRef<HTMLInputElement | null>(null);
  const avatarInputRef = useRef<HTMLInputElement | null>(null);

  const { data: students = [], isLoading } = useQuery({
    queryKey: ['students-manage', tuKhoa, hienTatCa],
    queryFn: () => safeGet<Student[]>(`/hoc_sinh.php?tu_khoa=${encodeURIComponent(tuKhoa)}&tat_ca=${hienTatCa ? 1 : 0}`),
  });

  const { data: lopHoc = [] } = useQuery({
    queryKey: ['lop-hoc-cua-toi'],
    queryFn: () => safeGet<Lop[]>('/lop_hoc_quan_tri.php?cua_toi=1'),
  });

  const sortedStudents = useMemo(() => {
    const list = [...students];
    list.sort((a, b) => {
      const as = a.stt === null || a.stt === undefined || a.stt === '' ? Number.POSITIVE_INFINITY : Number(a.stt);
      const bs = b.stt === null || b.stt === undefined || b.stt === '' ? Number.POSITIVE_INFINITY : Number(b.stt);
      if (as !== bs) return as - bs;
      return String(a.ho_ten || '').localeCompare(String(b.ho_ten || ''));
    });
    return list;
  }, [students]);

  useEffect(() => {
    if (!selected) return;
    setCtMa(selected.ma || '');
    setCtTen(selected.ho_ten || '');
    setCtGioi(selected.gioi_tinh || '');
    setCtNgay(toISODate((selected.ngay_sinh || '').substring(0, 10)));
    setCtLop(selected.lop_hoc_id ? String(selected.lop_hoc_id) : '');
  }, [selected]);

  const handleAdd = async () => {
    setMsg('');
    const res = await apiClient.post<{ id: number }>('/hoc_sinh.php', {
      ma: newMa,
      ho_ten: newTen,
      anh_dai_dien_url: newAnh,
      gioi_tinh: newGioi,
      ngay_sinh: toISODate(newNgay),
    });
    if (res.ok) {
      setMsg('Da them');
      setNewMa('');
      setNewTen('');
      queryClient.invalidateQueries({ queryKey: ['students-manage'] });
    } else {
      setMsg(res.thong_bao || 'Loi');
    }
  };

  const handleToggle = async () => {
    if (!selected) return;
    setCtMsg('');
    const nextState = normalizeActive(selected.dang_hoat_dong) ? 0 : 1;
    const res = await apiClient.post('/hoc_sinh.php?hanh_dong=bat_tat', {
      id: selected.id,
      dang_hoat_dong: nextState,
    });
    if (res.ok) {
      setCtMsg('Da cap nhat trang thai');
      if (nextState === 0) setHienTatCa(true);
      queryClient.invalidateQueries({ queryKey: ['students-manage'] });
    } else {
      setCtMsg(res.thong_bao || 'Loi');
    }
  };

  const handleSave = async () => {
    if (!selected) return;
    setCtMsg('');
    const res = await apiClient.post('/hoc_sinh.php?hanh_dong=sua', {
      id: selected.id,
      ma: ctMa.trim(),
      ho_ten: ctTen.trim(),
      gioi_tinh: ctGioi,
      ngay_sinh: toISODate(ctNgay),
      lop_hoc_id: ctLop,
    });
    if (res.ok) {
      setCtMsg('Da luu');
      queryClient.invalidateQueries({ queryKey: ['students-manage'] });
    } else {
      setCtMsg(res.thong_bao || 'Loi');
    }
  };

  const handleUploadAvatar = async () => {
    if (!selected) return;
    const fileInput = avatarInputRef.current;
    if (!fileInput || !fileInput.files?.[0]) {
      setCtMsg('Chon anh de upload');
      return;
    }
    const fd = new FormData();
    fd.append('hoc_sinh_id', String(selected.id));
    fd.append('file', fileInput.files[0]);
    setCtMsg('');
    try {
      const res = await fetch(`${API_BASE}/upload_avatar.php`, { method: 'POST', body: fd, credentials: 'include' });
      const json = (await res.json()) as ApiResponse<{ url: string }>;
      if (json.ok && json.du_lieu?.url) {
        setCtMsg('Da cap nhat anh');
        fileInput.value = '';
        queryClient.invalidateQueries({ queryKey: ['students-manage'] });
      } else {
        setCtMsg(json.thong_bao || 'Loi upload anh');
      }
    } catch (_e) {
      setCtMsg('Khong ket noi duoc may chu');
    }
  };

  const handleExportCsv = () => {
    const tu = encodeURIComponent(tuKhoa || '');
    window.location.href = `${API_BASE}/hoc_sinh_csv.php?hanh_dong=xuat&tu_khoa=${tu}`;
  };

  const handlePreviewCsv = async () => {
    const fileInput = fileInputRef.current;
    if (!fileInput?.files?.[0]) {
      setCsvMsg('Chon file CSV truoc khi nhap');
      return;
    }
    const f = fileInput.files[0];
    setCsvMsg('Dang doc file...');
    try {
      const fdPreview = new FormData();
      fdPreview.append('file', f);
      const res = await fetch(`${API_BASE}/hoc_sinh_csv.php?hanh_dong=xem_truoc`, { method: 'POST', body: fdPreview, credentials: 'include' });
      const preview = (await res.json()) as ApiResponse<{ mau?: Student[]; tong_dong?: number }>;
      if (!preview.ok) {
        setCsvMsg(preview.thong_bao || 'Loi doc CSV');
        return;
      }
      const mau = preview.du_lieu?.mau || [];
      const tong = preview.du_lieu?.tong_dong || 0;
      const previewText =
        mau.map((it, i) => `${i + 1}. ${it.ho_ten || ''} | ${it.ma || ''} | ${it.ngay_sinh || ''} | ${it.gioi_tinh || ''}`).join('\n') ||
        '(khong co du lieu xem truoc)';
      const ask = `Doc duoc ${tong} dong (bo qua dong trong).\nMau:\n${previewText}\n\nTiep tuc nhap?`;
      if (!window.confirm(ask)) {
        setCsvMsg('Da huy nhap CSV');
        return;
      }
      setCsvMsg('Dang nhap...');
      const fd = new FormData();
      fd.append('file', f);
      const resImport = await fetch(`${API_BASE}/hoc_sinh_csv.php?hanh_dong=nhap`, { method: 'POST', body: fd, credentials: 'include' });
      const json = (await resImport.json()) as ApiResponse<{ tong_dong?: number }>;
      if (json.ok) {
        setCsvMsg(`Nhap CSV xong: ${json.du_lieu?.tong_dong || 0} dong`);
        queryClient.invalidateQueries({ queryKey: ['students-manage'] });
      } else {
        setCsvMsg(json.thong_bao || 'Loi nhap CSV');
      }
    } catch (_e) {
      setCsvMsg('Khong ket noi duoc may chu');
    } finally {
      if (fileInput) {
        fileInput.value = '';
        setCsvFileName('Chua chon tep');
      }
    }
  };

  useEffect(() => {
    if (!selected && sortedStudents.length) setSelected(sortedStudents[0]);
  }, [sortedStudents, selected]);

  return (
    <div className="container py-3 safe-bottom">
      <div className="d-flex align-items-center justify-content-between">
        <h5 className="ribbon-title-modern">Quan ly hoc sinh</h5>
      </div>
      <div className="row g-3 mt-1">
        <div className="col-md-5">
          <div className="card shadow-sm" data-aos="fade-up">
            <div className="card-body">
              <div className="mb-2">
                <input className="form-control" placeholder="Ten" value={tuKhoa} onChange={(e) => setTuKhoa(e.target.value)} />
              </div>
              <div className="form-check mb-2">
                <input className="form-check-input" type="checkbox" id="hien_tat_ca" checked={hienTatCa} onChange={(e) => setHienTatCa(e.target.checked)} />
                <label className="form-check-label" htmlFor="hien_tat_ca">
                  Hien hoc sinh da tat
                </label>
              </div>
              <div className="list-group" style={{ maxHeight: '60vh', overflow: 'auto' }}>
                {isLoading && <div className="text-muted small px-2 py-1">Dang tai...</div>}
                {!isLoading && !sortedStudents.length && <div className="text-muted small px-2 py-1">Khong co hoc sinh</div>}
                {sortedStudents.map((s) => {
                  const sttText = s.stt === null || s.stt === undefined || s.stt === '' ? '' : `${s.stt}. `;
                  const active = normalizeActive(s.dang_hoat_dong);
                  return (
                    <button
                      key={s.id}
                      className={`list-group-item list-group-item-action d-flex justify-content-between align-items-center ${selected?.id === s.id ? 'active' : ''}`}
                      onClick={() => setSelected(s)}
                    >
                      <span>
                        {sttText}
                        {s.ho_ten} ({s.ten_lop || ''}) - Diem: {s.so_du || 0}
                      </span>
                      <span className={`badge ${active ? 'bg-success' : 'bg-warning text-dark'}`}>{active ? 'Bat' : 'Tat'}</span>
                    </button>
                  );
                })}
              </div>
            </div>
          </div>
        </div>
        <div className="col-md-7">
          <div className="card shadow-sm" data-aos="fade-up">
            <div className="card-body">
              <h5 className="ribbon-title-modern">Them hoc sinh</h5>
              <div className="row g-2">
                <div className="col-4">
                  <input className="form-control" placeholder="Ma (tuy chon)" value={newMa} onChange={(e) => setNewMa(e.target.value)} />
                </div>
                <div className="col-8">
                  <input className="form-control" placeholder="Ho ten" value={newTen} onChange={(e) => setNewTen(e.target.value)} />
                </div>
                <div className="col-6 mt-2">
                  <input className="form-control" placeholder="Anh dai dien URL" value={newAnh} onChange={(e) => setNewAnh(e.target.value)} />
                </div>
                <div className="col-3 mt-2">
                  <select className="form-select" value={newGioi} onChange={(e) => setNewGioi(e.target.value)}>
                    <option value="">Gioi tinh...</option>
                    <option value="NAM">Nam</option>
                    <option value="NU">Nu</option>
                    <option value="KHAC">Khac</option>
                  </select>
                </div>
                <div className="col-3 mt-2">
                  <input type="date" className="form-control" value={newNgay} onChange={(e) => setNewNgay(e.target.value)} />
                </div>
              </div>
              <div className="mt-2">
                <button className="btn btn-primary" onClick={handleAdd}>
                  Them
                </button>
                <span className="ms-2 small text-muted">{msg}</span>
              </div>
              <hr />
              <div className="d-flex align-items-center gap-2">
                <div className="me-auto" style={{ maxWidth: 320 }}>
                  <label className="form-label">Nhap CSV</label>
                  <div className="custom-file-input-sm position-relative">
                    <input
                      ref={fileInputRef}
                      type="file"
                      accept=".csv"
                      className="form-control form-control-sm"
                      aria-label="Chon file CSV"
                      onChange={(e) => {
                        const file = e.target.files?.[0];
                        setCsvFileName(file ? file.name : 'Chua chon tep');
                      }}
                    />
                    <span className="custom-file-label">{csvFileName}</span>
                  </div>
                </div>
                <div className="pt-4">
                  <div className="d-flex gap-2">
                    <button className="btn btn-outline-primary btn-sm px-3" onClick={handlePreviewCsv}>
                      Nhap CSV
                    </button>
                    <button className="btn btn-outline-primary btn-sm px-3" onClick={handleExportCsv}>
                      Xuat CSV
                    </button>
                  </div>
                </div>
              </div>
              <div className="small text-muted mt-2">{csvMsg}</div>
              <hr />
              <h5 className="ribbon-title-modern">Chi tiet hoc sinh</h5>
              {!selected && <div className="text-muted">Chua chon hoc sinh</div>}
              {selected && (
                <div>
                  <div className="d-flex align-items-center gap-3">
                    <img
                      className="avatar"
                      src={
                        selected.anh_dai_dien_url && selected.anh_dai_dien_url.trim() !== ''
                          ? selected.anh_dai_dien_url
                          : '/upload/avatar/default.svg'
                      }
                      alt="avatar"
                      onError={(e) => ((e.currentTarget.src = '/upload/avatar/default.svg'), undefined)}
                    />
                    <div>
                      <div className="fw-semibold">{selected.ho_ten}</div>
                      <div className="small text-muted">
                        Lop: {selected.ten_lop || '-'} - {normalizeActive(selected.dang_hoat_dong) ? 'Dang bat' : 'Dang tat'}
                      </div>
                    </div>
                  </div>
                  <div className="d-flex align-items-center gap-2 mt-2">
                    <input ref={avatarInputRef} type="file" accept="image/*" className="form-control form-control-sm" style={{ maxWidth: 320 }} />
                    <button className="btn btn-outline-primary btn-sm" onClick={handleUploadAvatar}>
                      Upload anh
                    </button>
                    <button className="btn btn-outline-warning btn-sm" onClick={handleToggle}>
                      {normalizeActive(selected.dang_hoat_dong) ? 'Tat' : 'Bat'}
                    </button>
                  </div>
                  <div className="small text-muted mt-1">{ctMsg}</div>
                  <div className="row g-2 mt-2">
                    <div className="col-4">
                      <label className="form-label">Ma</label>
                      <input className="form-control" value={ctMa} onChange={(e) => setCtMa(e.target.value)} />
                    </div>
                    <div className="col-8">
                      <label className="form-label">Ho ten</label>
                      <input className="form-control" value={ctTen} onChange={(e) => setCtTen(e.target.value)} />
                    </div>
                    <div className="col-6">
                      <label className="form-label">Lop</label>
                      <select className="form-select" value={ctLop} onChange={(e) => setCtLop(e.target.value)}>
                        <option value="">-- Khong gan lop --</option>
                        {lopHoc.map((l) => (
                          <option key={l.id} value={l.id}>
                            {l.ten}
                          </option>
                        ))}
                      </select>
                    </div>
                    <div className="col-3">
                      <label className="form-label">Gioi tinh</label>
                      <select className="form-select" value={ctGioi} onChange={(e) => setCtGioi(e.target.value)}>
                        <option value="">--</option>
                        <option value="NAM">Nam</option>
                        <option value="NU">Nu</option>
                        <option value="KHAC">Khac</option>
                      </select>
                    </div>
                    <div className="col-3">
                      <label className="form-label">Ngay sinh</label>
                      <input type="date" className="form-control" value={ctNgay} onChange={(e) => setCtNgay(e.target.value)} />
                    </div>
                  </div>
                  <div className="mt-2">
                    <button className="btn btn-primary btn-sm" onClick={handleSave}>
                      Luu thay doi
                    </button>
                  </div>
                </div>
              )}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
