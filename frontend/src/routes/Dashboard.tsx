import { useEffect, useMemo, useRef, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { apiClient, ApiResponse } from '@/api/client';
import { pushToast } from '@/components/ToastHost';

type Student = {
  id: number;
  ho_ten: string;
  so_du: number;
  anh_dai_dien_url?: string;
  ten_lop?: string;
  gioi_tinh?: string;
  ngay_sinh?: string;
};

type Reason = { id: number; tieu_de: string; bien_diem: number };
type Gift = { id: number; ten: string; ton_kho?: number; gia_diem?: number };
type HistoryItem = { id: number; tao_luc: string; ho_ten: string; loai: string; bien_diem: number; so_du_sau: number; ghi_chu?: string; qua?: string };

const SCRATCH_COST = 5;
const PAGE_SIZE = 10;

function formatDateTime(v?: string) {
  if (!v) return '';
  const parsed = new Date(String(v).replace(' ', 'T'));
  if (Number.isNaN(parsed.getTime())) return String(v);
  const dd = String(parsed.getDate()).padStart(2, '0');
  const mm = String(parsed.getMonth() + 1).padStart(2, '0');
  const yy = parsed.getFullYear();
  const hh = String(parsed.getHours()).padStart(2, '0');
  const mi = String(parsed.getMinutes()).padStart(2, '0');
  const ss = String(parsed.getSeconds()).padStart(2, '0');
  return `${dd}/${mm}/${yy} ${hh}:${mi}:${ss}`;
}

function badgeLoai(loai: string) {
  switch (loai) {
    case 'CONG_DIEM':
      return <span className="badge bg-success-subtle text-success border border-success-subtle">Cộng điểm</span>;
    case 'DOI_DIEM':
      return <span className="badge bg-warning-subtle text-warning border border-warning-subtle">Đổi điểm</span>;
    case 'HOAN_TAC':
      return <span className="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Hoàn tác</span>;
    default:
      return <span className="badge bg-light text-body-secondary">{loai}</span>;
  }
}

async function safeGet<T>(path: string): Promise<T> {
  const res = await apiClient.get<T>(path);
  if ((res as ApiResponse<T>).ok && (res as ApiResponse<T>).du_lieu !== undefined) return (res as ApiResponse<T>).du_lieu as T;
  throw new Error((res as ApiResponse<T>).thong_bao || 'Không đọc được dữ liệu');
}

export default function Dashboard() {
  const [tuKhoa, setTuKhoa] = useState('');
  const [reasonFilter, setReasonFilter] = useState('');
  const [selectedId, setSelectedId] = useState<number | null>(null);
  const [historyPage, setHistoryPage] = useState(1);
  const [showRedeemed, setShowRedeemed] = useState(false);
  const [scratchOpen, setScratchOpen] = useState(false);
  const [scratchRevealed, setScratchRevealed] = useState(false);
  const [scratchMessage, setScratchMessage] = useState('CÀO TẠI ĐÂY');
  const shouldAutoSelect = useRef(true);
  const canvasRef = useRef<HTMLCanvasElement | null>(null);
  const isDrawingRef = useRef(false);
  const lastPointRef = useRef<{ x: number; y: number }>({ x: 0, y: 0 });
  const strokeRef = useRef(0);
  const queryClient = useQueryClient();

  const { data: students = [], isLoading: loadingStudents } = useQuery({
    queryKey: ['students', tuKhoa],
    queryFn: () => safeGet<Student[]>(`/hoc_sinh.php?tu_khoa=${encodeURIComponent(tuKhoa)}`),
  });

  const selectedStudent = useMemo(() => students.find((s) => s.id === selectedId) || null, [selectedId, students]);

  useEffect(() => {
    if (shouldAutoSelect.current && students.length) {
      setSelectedId(students[0].id);
      shouldAutoSelect.current = false;
    }
  }, [students]);

  const { data: reasons = [], isLoading: loadingReasons } = useQuery({
    queryKey: ['reasons'],
    queryFn: () => safeGet<Reason[]>('/ly_do.php'),
  });

  const { data: gifts = [], isLoading: loadingGifts } = useQuery({
    queryKey: ['gifts'],
    queryFn: () => safeGet<Gift[]>('/qua_tang.php'),
  });

  const {
    data: history = [],
    isLoading: loadingHistory,
    refetch: refetchHistory,
  } = useQuery({
    queryKey: ['history', selectedStudent?.id],
    enabled: !!selectedStudent,
    queryFn: () => safeGet<HistoryItem[]>(`/diem.php?hanh_dong=lich_su&hoc_sinh_id=${selectedStudent?.id || 0}`),
  });

  const historyPages = Math.max(1, Math.ceil((history?.length || 0) / PAGE_SIZE));
  const historyPageData = history?.slice((historyPage - 1) * PAGE_SIZE, historyPage * PAGE_SIZE) || [];

  useEffect(() => {
    setHistoryPage(1);
  }, [selectedId]);

  const congDiem = useMutation({
    mutationFn: (payload: { hoc_sinh_id: number; ly_do_id: number }) =>
      apiClient.post<{ so_du: number }>('/diem.php?hanh_dong=cong', payload),
    onSuccess: (res) => {
      if (res.ok) {
        pushToast('Cộng điểm thành công', 'success');
        queryClient.invalidateQueries({ queryKey: ['students'] });
        refetchHistory();
      } else {
        pushToast(res.thong_bao || 'Không cộng được điểm', 'danger');
      }
    },
  });

  const redeemMutation = useMutation({
    mutationFn: (payload: { hoc_sinh_id: number; qua_tang_id: number }) =>
      apiClient.post<{ so_du: number }>('/diem.php?hanh_dong=quy_doi', { ...payload, scratch_cost: SCRATCH_COST }),
    onSuccess: (res) => {
      if (res.ok) {
        pushToast('Đổi điểm thành công', 'success');
        queryClient.invalidateQueries({ queryKey: ['students'] });
        queryClient.invalidateQueries({ queryKey: ['gifts'] });
        refetchHistory();
      } else {
        pushToast(res.thong_bao || 'Đổi điểm thất bại', 'danger');
      }
    },
  });

  const drawScratchCover = () => {
    const canvas = canvasRef.current;
    const ctx = canvas?.getContext('2d');
    if (!canvas || !ctx) return;
    const rect = canvas.parentElement?.getBoundingClientRect();
    if (!rect) return;
    canvas.width = Math.round(rect.width);
    canvas.height = Math.round(rect.height);
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    ctx.globalCompositeOperation = 'source-over';
    const grad = ctx.createLinearGradient(0, 0, canvas.width, canvas.height);
    grad.addColorStop(0, '#f7f7f7');
    grad.addColorStop(0.3, '#d0d0d0');
    grad.addColorStop(0.7, '#f0f0f0');
    grad.addColorStop(1, '#b8b8b8');
    ctx.fillStyle = grad;
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    ctx.fillStyle = 'rgba(255,255,255,0.25)';
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    ctx.fillStyle = '#555';
    ctx.font = 'bold 20px system-ui';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillText('THẺ CÀO', canvas.width / 2, canvas.height / 2 - 12);
    ctx.font = '12px system-ui';
    ctx.fillText('Cào để xem quà', canvas.width / 2, canvas.height / 2 + 10);
    ctx.globalCompositeOperation = 'destination-out';
    strokeRef.current = 0;
  };

  useEffect(() => {
    if (!scratchOpen) return;
    drawScratchCover();
    const handleResize = () => drawScratchCover();
    window.addEventListener('resize', handleResize);
    return () => window.removeEventListener('resize', handleResize);
  }, [scratchOpen]);

  const handleScratchDown = (e: React.PointerEvent<HTMLCanvasElement>) => {
    const canvas = canvasRef.current;
    if (!canvas) return;
    isDrawingRef.current = true;
    const rect = canvas.getBoundingClientRect();
    lastPointRef.current = { x: e.clientX - rect.left, y: e.clientY - rect.top };
    canvas.setPointerCapture?.(e.pointerId);
  };

  const handleScratchMove = (e: React.PointerEvent<HTMLCanvasElement>) => {
    if (!isDrawingRef.current) return;
    const canvas = canvasRef.current;
    const ctx = canvas?.getContext('2d');
    if (!canvas || !ctx) return;
    const rect = canvas.getBoundingClientRect();
    const x = e.clientX - rect.left;
    const y = e.clientY - rect.top;
    ctx.beginPath();
    ctx.lineJoin = 'round';
    ctx.lineCap = 'round';
    ctx.lineWidth = 26;
    ctx.moveTo(lastPointRef.current.x, lastPointRef.current.y);
    ctx.lineTo(x, y);
    ctx.stroke();
    lastPointRef.current = { x, y };
    strokeRef.current += 1;
    if (strokeRef.current > 80) {
      setScratchRevealed(true);
      const w = canvas.width;
      const h = canvas.height;
      ctx.globalCompositeOperation = 'destination-out';
      ctx.fillRect(0, 0, w, h);
      isDrawingRef.current = false;
    }
  };

  const handleScratchUp = (e: React.PointerEvent<HTMLCanvasElement>) => {
    const canvas = canvasRef.current;
    isDrawingRef.current = false;
    canvas?.releasePointerCapture?.(e.pointerId);
  };

  const availableGifts = useMemo(() => {
    return (gifts || []).filter((g) => {
      const ton = Number(g.ton_kho);
      return Number.isNaN(ton) || ton !== 0;
    });
  }, [gifts]);

  const handleRedeem = async () => {
    if (!selectedStudent) {
      pushToast('Hãy chọn học sinh.', 'warning');
      return;
    }
    const soDu = Number(selectedStudent.so_du) || 0;
    if (soDu < SCRATCH_COST) {
      pushToast(`Học sinh cần ít nhất ${SCRATCH_COST} điểm để cào.`, 'warning');
      return;
    }
    if (!availableGifts.length) {
      pushToast('Chưa có quà khả dụng.', 'warning');
      return;
    }
    setScratchRevealed(false);
    const reward = availableGifts[Math.floor(Math.random() * availableGifts.length)];
    setScratchMessage(reward.ten || 'Quà bí mật');
    await redeemMutation.mutateAsync({ hoc_sinh_id: selectedStudent.id, qua_tang_id: reward.id });
    setScratchOpen(true);
    requestAnimationFrame(drawScratchCover);
  };

  const filteredReasons = useMemo(() => {
    const kw = reasonFilter.trim().toLowerCase();
    return (reasons || []).filter((r) => !kw || r.tieu_de.toLowerCase().includes(kw));
  }, [reasonFilter, reasons]);

  const redeemedHistory = useMemo(() => history?.filter((h) => h.loai === 'DOI_DIEM') || [], [history]);

  return (
    <div className="row g-3">
      <div className="col-md-6">
        <div className="card shadow-sm" data-aos="fade-up">
          <div className="card-body">
            <h5 className="ribbon-title-modern">Danh sách học sinh</h5>
            <input
              className="form-control mt-2"
              placeholder="Tìm theo tên hoặc mã học sinh"
              value={tuKhoa}
              onChange={(e) => setTuKhoa(e.target.value)}
            />
            <div className="list-group mt-2" style={{ maxHeight: 320, overflow: 'auto' }}>
              {loadingStudents && <div className="text-muted small px-2 py-1">Đang tải...</div>}
              {!loadingStudents && !students.length && <div className="text-muted small px-2 py-1">Không có học sinh</div>}
              {students.map((s) => {
                const av =
                  s.anh_dai_dien_url && s.anh_dai_dien_url.trim() !== ''
                    ? s.anh_dai_dien_url
                    : '/upload/avatar/default.svg';
                const active = selectedId === s.id;
                return (
                  <button
                    key={s.id}
                    className={`list-group-item list-group-item-action text-start ${active ? 'active' : ''}`}
                    onClick={() => setSelectedId(s.id)}
                    style={{
                      backgroundImage: `url(${av})`,
                      backgroundRepeat: 'no-repeat',
                      backgroundSize: '24px 24px',
                      backgroundPosition: '8px center',
                      paddingLeft: 40,
                    }}
                  >
                    {s.ho_ten}
                    {s.ten_lop ? ` (${s.ten_lop})` : ''} • Số dư: {s.so_du}
                  </button>
                );
              })}
            </div>
          </div>
        </div>
      </div>
      <div className="col-md-6">
        <div className="card shadow-sm" data-aos="fade-up">
          <div className="card-body">
            <div className="d-flex align-items-center justify-content-between">
              <h5 className="ribbon-title-modern mb-0">Thông tin</h5>
              <button className="btn btn-outline-secondary px-3 py-2" disabled={!selectedStudent} onClick={() => setShowRedeemed(true)}>
                Quà đã đổi
              </button>
            </div>
            <div className="mb-2 text-muted">
              {!selectedStudent && 'Chưa chọn học sinh'}
              {selectedStudent && (
                <div className="d-flex align-items-center gap-3 mt-2">
                  <img
                    src={
                      selectedStudent.anh_dai_dien_url && selectedStudent.anh_dai_dien_url.trim() !== ''
                        ? selectedStudent.anh_dai_dien_url
                        : '/upload/avatar/default.svg'
                    }
                    onError={(e) => ((e.currentTarget.src = '/upload/avatar/default.svg'), undefined)}
                    className="avatar"
                    alt="avatar"
                  />
                  <div>
                    <div className="fw-semibold">{selectedStudent.ho_ten}</div>
                    <div className="small text-muted">
                      Lớp: {selectedStudent.ten_lop || '-'} • Điểm: {selectedStudent.so_du}
                    </div>
                    <div className="small text-muted">
                      Giới tính: {selectedStudent.gioi_tinh || '-'}
                      {selectedStudent.ngay_sinh ? ` • Ngày sinh: ${selectedStudent.ngay_sinh.substring(0, 10).split('-').reverse().join('/')}` : ''}
                    </div>
                  </div>
                </div>
              )}
            </div>
            <h5 className="ribbon-title-modern mt-2">Lý do</h5>
            <input
              className="form-control form-control-sm mb-2"
              placeholder="Lọc lý do..."
              value={reasonFilter}
              onChange={(e) => setReasonFilter(e.target.value)}
            />
            <div className="d-flex flex-wrap gap-2">
              {loadingReasons && <div className="text-muted small">Đang tải...</div>}
              {!loadingReasons &&
                filteredReasons.map((ld) => (
                  <button
                    key={ld.id}
                    className="btn btn-outline-primary btn-sm"
                    onClick={() =>
                      selectedStudent
                        ? congDiem.mutate({ hoc_sinh_id: selectedStudent.id, ly_do_id: ld.id })
                        : pushToast('Hãy chọn học sinh.', 'warning')
                    }
                  >
                    {ld.tieu_de} ({ld.bien_diem > 0 ? '+' : ''}
                    {ld.bien_diem})
                  </button>
                ))}
            </div>
            <hr />
            <div className="d-flex align-items-center justify-content-between">
              <h5 className="ribbon-title-modern mb-0">Thẻ cào</h5>
            </div>
            <div className="mb-3">
              <div className={`scratch-summary rounded-3 p-3 mb-2 shadow-sm ${availableGifts.length ? '' : 'scratch-summary-empty'}`}>
                <div className="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                  <div className="d-flex align-items-center gap-2">
                    <div className="fw-semibold text-primary">Sẵn sàng đổi</div>
                    <button type="button" className="btn btn-light btn-sm border text-primary fw-semibold shadow-sm">
                      {availableGifts.length} quà
                    </button>
                  </div>
                  <button className="btn btn-primary btn-sm shadow-sm" onClick={handleRedeem} disabled={redeemMutation.isLoading || !selectedStudent}>
                    Đổi điểm
                  </button>
                </div>
                {loadingGifts && <div className="small text-muted mb-1">Đang tải quà...</div>}
                {!availableGifts.length && <div className="small text-primary-emphasis mb-2">Chưa có quà khả dụng, hãy thêm quà mới.</div>}
                {!!availableGifts.length && <div className="small text-primary-emphasis mb-2">Quà sẽ được chọn ngẫu nhiên khi cào.</div>}
              </div>
              {scratchOpen && (
                <div className="scratch-card">
                  <div className="scratch-ticket">
                    <div className="scratch-area">
                      <div className="scratch-under">{scratchRevealed ? scratchMessage : 'CÀO TẠI ĐÂY'}</div>
                      <canvas
                        id="scratchCanvas"
                        ref={canvasRef}
                        onPointerDown={handleScratchDown}
                        onPointerMove={handleScratchMove}
                        onPointerUp={handleScratchUp}
                        onPointerLeave={handleScratchUp}
                      />
                    </div>
                  </div>
                </div>
              )}
            </div>
          </div>
        </div>
      </div>
      <div className="card mt-3 shadow-sm" data-aos="fade-up">
        <div className="card-body">
          <h5 className="ribbon-title-modern">Lịch sử gần đây</h5>
          <div className="table-responsive">
            <table className="table table-sm table-hover table-striped align-middle modern-table">
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
                {loadingHistory && (
                  <tr>
                    <td colSpan={6} className="text-muted small">
                      Đang tải...
                    </td>
                  </tr>
                )}
                {!loadingHistory && !historyPageData.length && (
                  <tr>
                    <td colSpan={6} className="text-muted small">
                      Chưa có dữ liệu
                    </td>
                  </tr>
                )}
                {historyPageData.map((row) => {
                  const delta = Number(row.bien_diem) || 0;
                  return (
                    <tr key={row.id}>
                      <td className="text-muted small">{formatDateTime(row.tao_luc)}</td>
                      <td>{row.ho_ten}</td>
                      <td>{badgeLoai(row.loai)}</td>
                      <td>
                        <span className={`fw-semibold ${delta > 0 ? 'text-success' : 'text-danger'}`}>
                          {delta > 0 ? '+' : ''}
                          {delta}
                        </span>
                      </td>
                      <td>
                        <span className="fw-semibold">{new Intl.NumberFormat('vi-VN').format(Number(row.so_du_sau) || 0)}</span>
                      </td>
                      <td className="cell-notes">
                        <span className="truncate">{row.ghi_chu || ''}</span>
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
          <div className="d-flex align-items-center justify-content-between mt-2">
            <div className="small text-muted">
              Trang {historyPage}/{historyPages} • Hiện {historyPageData.length ? (historyPage - 1) * PAGE_SIZE + 1 : 0}-
              {historyPageData.length ? (historyPage - 1) * PAGE_SIZE + historyPageData.length : 0} / {history?.length || 0}
            </div>
            <div className="btn-group btn-group-sm" role="group">
              <button
                type="button"
                className="btn btn-outline-secondary"
                onClick={() => setHistoryPage((p) => Math.max(1, p - 1))}
                disabled={historyPage <= 1}
              >
                Trước
              </button>
              <button
                type="button"
                className="btn btn-outline-secondary"
                onClick={() => setHistoryPage((p) => Math.min(historyPages, p + 1))}
                disabled={historyPage >= historyPages}
              >
                Sau
              </button>
            </div>
          </div>
        </div>
      </div>
      {showRedeemed && (
        <div className="modal fade show d-block" style={{ backgroundColor: 'rgba(0,0,0,0.35)' }}>
          <div className="modal-dialog modal-dialog-scrollable modal-sm modal-dialog-centered">
            <div className="modal-content">
              <div className="modal-header">
                <h5 className="ribbon-title-modern modal-title">Quà đã đổi</h5>
                <button type="button" className="btn-close" aria-label="Close" onClick={() => setShowRedeemed(false)} />
              </div>
              <div className="modal-body">
                {!redeemedHistory.length && <div className="text-muted small">Chưa có quà đã đổi</div>}
                {!!redeemedHistory.length && (
                  <div className="list-group list-group-flush">
                    {redeemedHistory.map((x) => (
                      <div key={x.id} className="list-group-item d-flex justify-content-between align-items-center">
                        <span>{x.qua || 'Quà'}</span>
                        <span className="text-muted small">{formatDateTime(x.tao_luc)}</span>
                      </div>
                    ))}
                  </div>
                )}
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
