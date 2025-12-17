import { useMemo, useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { apiClient, ApiResponse } from '@/api/client';

type Row = { id: number; tao_luc: string; ho_ten: string; loai: string; bien_diem: number; so_du_sau: number; ghi_chu?: string };

const SORTS = [
  { value: 'time_desc', label: 'Mới nhất' },
  { value: 'time_asc', label: 'Cũ nhất' },
  { value: 'delta_desc', label: 'Thay đổi giảm dần' },
  { value: 'delta_asc', label: 'Thay đổi tăng dần' },
  { value: 'balance_desc', label: 'Số dư cao' },
  { value: 'balance_asc', label: 'Số dư thấp' },
];

function formatDateTime(str?: string) {
  if (!str) return '';
  const m = String(str).match(/^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2}):(\d{2})/);
  if (!m) return str;
  const [, y, mo, d, h, mi, s] = m;
  return `${d}/${mo}/${y} ${h}:${mi}:${s}`;
}

function parseDate(str?: string) {
  if (!str) return 0;
  const parts = str.split(' ')[0];
  const ps = parts.split(/[-/]/);
  if (ps.length === 3) {
    const [a, b, c] = ps;
    const y = a.length === 4 ? +a : +c;
    const m = a.length === 4 ? +b : +b;
    const d = a.length === 4 ? +c : +a;
    const t = Date.parse(`${y}-${String(m).padStart(2, '0')}-${String(d).padStart(2, '0')}T00:00:00`);
    return Number.isNaN(t) ? 0 : t;
  }
  const iso = str.replace(' ', 'T');
  const t = Date.parse(iso);
  return Number.isNaN(t) ? 0 : t;
}

function badgeLoai(loai: string) {
  switch (loai) {
    case 'CONG_DIEM':
      return 'Cộng điểm';
    case 'DOI_DIEM':
      return 'Đổi điểm';
    case 'HOAN_TAC':
      return 'Hoàn tác';
    default:
      return loai;
  }
}

async function fetchHistory() {
  const res = await apiClient.get<Row[]>('/diem.php?hanh_dong=lich_su');
  if ((res as ApiResponse<Row[]>).ok && (res as ApiResponse<Row[]>).du_lieu) {
    return (res as ApiResponse<Row[]>).du_lieu as Row[];
  }
  return [];
}

export default function History() {
  const [showFilter, setShowFilter] = useState(false);
  const [kw, setKw] = useState('');
  const [loai, setLoai] = useState('');
  const [tuNgay, setTuNgay] = useState('');
  const [denNgay, setDenNgay] = useState('');
  const [sort, setSort] = useState('time_desc');
  const [pageSize, setPageSize] = useState(20);
  const [page, setPage] = useState(1);

  const { data = [], isLoading } = useQuery({
    queryKey: ['lich_su'],
    queryFn: fetchHistory,
  });

  const filtered = useMemo(() => {
    const tuTs = tuNgay ? Date.parse(`${tuNgay}T00:00:00`) : null;
    const denTs = denNgay ? Date.parse(`${denNgay}T23:59:59`) : null;
    const rows = (data || []).filter((row) => {
      if (loai && row.loai !== loai) return false;
      const ts = parseDate(row.tao_luc);
      if (tuTs !== null && ts && ts < tuTs) return false;
      if (denTs !== null && ts && ts > denTs) return false;
      if (kw) {
        const haystack = `${row.ho_ten || ''} ${row.ghi_chu || ''}`.toLowerCase();
        if (!haystack.includes(kw.toLowerCase())) return false;
      }
      return true;
    });
    rows.sort((a, b) => {
      if (sort === 'time_asc') return parseDate(a.tao_luc) - parseDate(b.tao_luc);
      if (sort === 'time_desc') return parseDate(b.tao_luc) - parseDate(a.tao_luc);
      if (sort === 'delta_asc') return (a.bien_diem || 0) - (b.bien_diem || 0);
      if (sort === 'delta_desc') return (b.bien_diem || 0) - (a.bien_diem || 0);
      if (sort === 'balance_asc') return (a.so_du_sau || 0) - (b.so_du_sau || 0);
      if (sort === 'balance_desc') return (b.so_du_sau || 0) - (a.so_du_sau || 0);
      return 0;
    });
    return rows;
  }, [data, kw, loai, tuNgay, denNgay, sort]);

  const totalPages = Math.max(1, Math.ceil((filtered.length || 0) / pageSize));
  const start = (page - 1) * pageSize;
  const end = Math.min(start + pageSize, filtered.length);
  const pageRows = filtered.slice(start, end);

  return (
    <div className="container-fluid px-0">
      <div className="card shadow-sm" data-aos="fade-up">
        <div className="card-body py-3">
          <div className="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h5 className="ribbon-title-modern mb-0">Lịch sử giao dịch</h5>
            <button className="btn btn-outline-primary btn-sm" type="button" onClick={() => setShowFilter((x) => !x)} aria-label="Ẩn/hiện bộ lọc">
              <i className="bi bi-funnel" />
            </button>
          </div>
          {showFilter && (
            <div className="mt-3">
              <div className="row g-2 filter-compact">
                <div className="col-md-3 col-sm-6">
                  <input className="form-control form-control-sm" placeholder="Tìm học sinh, ghi chú..." value={kw} onChange={(e) => setKw(e.target.value)} />
                </div>
                <div className="col-md-2 col-sm-6">
                  <select className="form-select form-select-sm" value={loai} onChange={(e) => setLoai(e.target.value)}>
                    <option value="">Loại: Tất cả</option>
                    <option value="CONG_DIEM">Cộng điểm</option>
                    <option value="DOI_DIEM">Đổi điểm</option>
                    <option value="HOAN_TAC">Hoàn tác</option>
                  </select>
                </div>
                <div className="col-md-2 col-sm-6">
                  <input type="date" className="form-control form-control-sm" value={tuNgay} onChange={(e) => setTuNgay(e.target.value)} />
                </div>
                <div className="col-md-2 col-sm-6">
                  <input type="date" className="form-control form-control-sm" value={denNgay} onChange={(e) => setDenNgay(e.target.value)} />
                </div>
                <div className="col-md-2 col-sm-6">
                  <select className="form-select form-select-sm" value={sort} onChange={(e) => setSort(e.target.value)}>
                    {SORTS.map((s) => (
                      <option key={s.value} value={s.value}>
                        {s.label}
                      </option>
                    ))}
                  </select>
                </div>
                <div className="col-md-1 col-sm-6">
                  <select
                    className="form-select form-select-sm"
                    value={pageSize}
                    onChange={(e) => {
                      setPageSize(parseInt(e.target.value, 10));
                      setPage(1);
                    }}
                  >
                    {[20, 50, 100].map((v) => (
                      <option key={v} value={v}>
                        {v}
                      </option>
                    ))}
                  </select>
                </div>
              </div>
            </div>
          )}
        </div>
      </div>
      <div className="table-responsive mt-3" data-aos="fade-up">
        <table className="table table-sm align-middle">
          <thead>
            <tr>
              <th>Thời gian</th>
              <th>Học sinh</th>
              <th>Loại</th>
              <th>Thay đổi</th>
              <th>Số dư</th>
              <th>Ghi chú</th>
            </tr>
          </thead>
          <tbody>
            {isLoading && (
              <tr>
                <td colSpan={6} className="text-muted small">
                  Đang tải...
                </td>
              </tr>
            )}
            {!isLoading && !pageRows.length && (
              <tr>
                <td colSpan={6} className="text-muted small">
                  Không có dữ liệu
                </td>
              </tr>
            )}
            {pageRows.map((row) => (
              <tr key={row.id}>
                <td>{formatDateTime(row.tao_luc)}</td>
                <td>{row.ho_ten}</td>
                <td>{badgeLoai(row.loai)}</td>
                <td>{row.bien_diem}</td>
                <td>{row.so_du_sau}</td>
                <td>{row.ghi_chu || ''}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
      <div className="d-flex align-items-center justify-content-between mt-2">
        <div className="small text-muted">
          {filtered.length
            ? `Trang ${page}/${totalPages} • Hiện ${start + 1}-${end} / ${filtered.length}`
            : 'Không có dữ liệu'}
        </div>
        <div className="btn-group btn-group-sm" role="group">
          <button className="btn btn-outline-secondary" onClick={() => setPage((p) => Math.max(1, p - 1))} disabled={page <= 1}>
            Trang trước
          </button>
          <button
            className="btn btn-outline-secondary"
            onClick={() => setPage((p) => Math.min(totalPages, p + 1))}
            disabled={page >= totalPages}
          >
            Trang sau
          </button>
        </div>
      </div>
    </div>
  );
}
