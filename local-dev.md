# Local dev commands (Windows PowerShell)

Chạy từng tab (backend trước, frontend sau).

## Backend (PHP API)
```powershell
cd D:\Canhan\GithubRepo\DClass
# Chạy docroot ở gốc để /api/* map đúng (nếu đặt -t public sẽ 404)
php -S 0.0.0.0:8000
```

## Frontend (Vite React)
Lần đầu hoặc khi đổi deps:
```powershell
cd D:\Canhan\GithubRepo\DClass\frontend
Set-ExecutionPolicy -Scope Process -ExecutionPolicy Bypass
npm install
```

Mỗi lần chạy dev:
```powershell
cd D:\Canhan\GithubRepo\DClass\frontend
Set-ExecutionPolicy -Scope Process -ExecutionPolicy Bypass
npm run dev
```
Mở URL Vite in ra (thường `http://localhost:5173/react/`).

## Kiểm tra nhanh
Lint:
```powershell
cd D:\Canhan\GithubRepo\DClass\frontend
Set-ExecutionPolicy -Scope Process -ExecutionPolicy Bypass
npm run lint
```
Build thử:
```powershell
cd D:\Canhan\GithubRepo\DClass\frontend
Set-ExecutionPolicy -Scope Process -ExecutionPolicy Bypass
npm run build
```
