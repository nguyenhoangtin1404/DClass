export interface ApiResponse<T> {
  ok: boolean;
  du_lieu?: T;
  thong_bao?: string;
}

const API_BASE = import.meta.env.VITE_API_BASE || '/api';

async function request<T>(path: string, options: RequestInit = {}): Promise<ApiResponse<T>> {
  const url = `${API_BASE}${path}`;
  let res: Response;
  try {
    res = await fetch(url, {
      credentials: 'include',
      headers: {
        'Content-Type': 'application/json',
        ...options.headers,
      },
      ...options,
    });
  } catch (_e) {
    return { ok: false, thong_bao: 'Khong ket noi duoc may chu' } as ApiResponse<T>;
  }
  try {
    return (await res.json()) as ApiResponse<T>;
  } catch (e) {
    return { ok: false, thong_bao: 'Khong doc duoc JSON' } as ApiResponse<T>;
  }
}

export const apiClient = {
  get: <T>(path: string) => request<T>(path, { method: 'GET' }),
  post: <T>(path: string, body?: unknown) =>
    request<T>(path, { method: 'POST', body: body ? JSON.stringify(body) : undefined }),
};
