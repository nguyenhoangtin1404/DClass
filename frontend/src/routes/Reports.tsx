import { useMemo } from 'react';
import { useQuery } from '@tanstack/react-query';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  Tooltip,
  Legend,
} from 'chart.js';
import { Line, Bar } from 'react-chartjs-2';
import { apiClient, ApiResponse } from '@/api/client';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, BarElement, Tooltip, Legend);

type TopRow = { ten?: string; tieu_de?: string; so_lan?: number; tong_diem?: number };

type BaoCao = {
  tong_cong: number;
  tong_doi: number;
  xu_huong: { labels: string[]; cong: number[]; doi: number[]; giao_dich: number[] };
  top_qua: TopRow[];
  top_ly_do: TopRow[];
  top_so_du: { labels: string[]; values: number[]; stats?: { min: number; max: number; p25: number; median: number; p75: number } | null };
};

async function fetchBaoCao() {
  const res = await apiClient.get<BaoCao>('/bao_cao.php');
  if ((res as ApiResponse<BaoCao>).ok && (res as ApiResponse<BaoCao>).du_lieu) {
    return (res as ApiResponse<BaoCao>).du_lieu as BaoCao;
  }
  return null;
}

export default function Reports() {
  const { data, isLoading } = useQuery({ queryKey: ['bao_cao'], queryFn: fetchBaoCao });

  const xuHuongData = useMemo(() => {
    if (!data?.xu_huong?.labels?.length) return null;
    return {
      labels: data.xu_huong.labels,
      datasets: [
        {
          label: 'Diem cong',
          data: data.xu_huong.cong,
          borderColor: '#0d6efd',
          backgroundColor: 'rgba(13,110,253,0.08)',
          tension: 0.3,
          fill: true,
        },
        {
          label: 'Diem doi',
          data: data.xu_huong.doi,
          borderColor: '#dc3545',
          backgroundColor: 'rgba(220,53,69,0.08)',
          tension: 0.3,
          fill: true,
        },
        {
          label: 'Giao dich',
          data: data.xu_huong.giao_dich,
          borderColor: '#6c757d',
          backgroundColor: 'rgba(108,117,125,0.08)',
          tension: 0.2,
          yAxisID: 'y1',
        },
      ],
    };
  }, [data]);

  const topQuaData = useMemo(() => {
    if (!data?.top_qua?.length) return null;
    return {
      labels: data.top_qua.map((r) => r.ten || ''),
      datasets: [
        {
          label: 'So lan doi',
          data: data.top_qua.map((r) => Number(r.so_lan || 0)),
          backgroundColor: '#0d6efd',
        },
      ],
    };
  }, [data]);

  const topLyDoData = useMemo(() => {
    if (!data?.top_ly_do?.length) return null;
    return {
      labels: data.top_ly_do.map((r) => r.tieu_de || ''),
      datasets: [
        {
          label: 'So lan cong',
          data: data.top_ly_do.map((r) => Number(r.so_lan || 0)),
          backgroundColor: '#20c997',
        },
      ],
    };
  }, [data]);

  const topSoDuData = useMemo(() => {
    if (!data?.top_so_du?.labels?.length) return null;
    return {
      labels: data.top_so_du.labels,
      datasets: [
        {
          label: 'So du',
          data: data.top_so_du.values,
          backgroundColor: '#fd7e14',
        },
      ],
    };
  }, [data]);

  return (
    <div className="container py-3 safe-bottom">
      <div className="d-flex align-items-center justify-content-between">
        <h5 className="ribbon-title-modern">Bao cao & Thong ke</h5>
      </div>
      <div className="row g-3 mt-1" data-aos="fade-up">
        <div className="col-sm-6">
          <div className="card shadow-sm">
            <div className="card-body">
              <div className="text-muted small">Tong diem cong</div>
              <div className="fs-4 fw-semibold text-success">+{data?.tong_cong?.toLocaleString('vi-VN') || 0}</div>
            </div>
          </div>
        </div>
        <div className="col-sm-6">
          <div className="card shadow-sm">
            <div className="card-body">
              <div className="text-muted small">Tong diem doi</div>
              <div className="fs-4 fw-semibold text-danger">{data?.tong_doi?.toLocaleString('vi-VN') || 0}</div>
            </div>
          </div>
        </div>
      </div>
      <div className="row g-3 mt-1" data-aos="fade-up">
        <div className="col-12">
          <div className="card shadow-sm">
            <div className="card-body">
              <div className="d-flex align-items-center justify-content-between">
                <h5 className="ribbon-title-modern mb-0">Xu huong 30 ngay</h5>
                <div className="text-muted small">Diem cong / Diem doi / giao dich</div>
              </div>
              <div className="mt-2" style={{ height: 280 }}>
                {isLoading && <div className="text-muted small">Dang tai...</div>}
                {!isLoading && !xuHuongData && <div className="text-muted small">Chua co du lieu</div>}
                {xuHuongData && (
                  <Line
                    data={xuHuongData}
                    options={{
                      responsive: true,
                      maintainAspectRatio: false,
                      interaction: { mode: 'index', intersect: false },
                      scales: {
                        y: { beginAtZero: true, title: { display: true, text: 'Diem' } },
                        y1: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false }, title: { display: true, text: 'Giao dich' } },
                      },
                    }}
                  />
                )}
              </div>
            </div>
          </div>
        </div>
      </div>
      <div className="row g-3 mt-1" data-aos="fade-up">
        <div className="col-lg-6">
          <div className="card shadow-sm h-100">
            <div className="card-body">
              <h5 className="ribbon-title-modern mb-0">Top 3 qua doi</h5>
              <div className="mt-3" style={{ height: 260 }}>
                {!isLoading && !topQuaData && <div className="text-muted small">Chua co giao dich doi qua</div>}
                {topQuaData && (
                  <Bar
                    data={topQuaData}
                    options={{ responsive: true, maintainAspectRatio: false, indexAxis: 'y', scales: { x: { beginAtZero: true } } }}
                  />
                )}
              </div>
            </div>
          </div>
        </div>
        <div className="col-lg-6">
          <div className="card shadow-sm h-100">
            <div className="card-body">
              <h5 className="ribbon-title-modern mb-0">Top ly do cong diem</h5>
              <div className="mt-3" style={{ height: 260 }}>
                {!isLoading && !topLyDoData && <div className="text-muted small">Chua co giao dich cong diem</div>}
                {topLyDoData && (
                  <Bar
                    data={topLyDoData}
                    options={{ responsive: true, maintainAspectRatio: false, indexAxis: 'y', scales: { x: { beginAtZero: true } } }}
                  />
                )}
              </div>
            </div>
          </div>
        </div>
      </div>
      <div className="row g-3 mt-1" data-aos="fade-up">
        <div className="col-12">
          <div className="card shadow-sm">
            <div className="card-body">
              <h5 className="ribbon-title-modern mb-0">Phan bo top 5 so du</h5>
              {!isLoading && !topSoDuData && <div className="text-muted small mt-2">Chua co du lieu so du</div>}
              {topSoDuData && (
                <div className="mt-3" style={{ height: 260 }}>
                  <Bar data={topSoDuData} options={{ responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } }} />
                </div>
              )}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
