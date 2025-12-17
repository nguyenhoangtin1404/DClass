import { Routes, Route, Navigate } from 'react-router-dom';
import Layout from './components/Layout';
import Dashboard from './routes/Dashboard';
import Login from './routes/Login';
import Students from './routes/Students';
import Reports from './routes/Reports';
import History from './routes/History';
import Settings from './routes/Settings';
import { useAuth } from './state/auth';

function PrivateRoute({ children }: { children: JSX.Element }) {
  const { isAuthenticated } = useAuth();
  if (!isAuthenticated) return <Navigate to="/login" replace />;
  return children;
}

export default function App() {
  return (
    <Routes>
      <Route path="/login" element={<Login />} />
      <Route
        path="/*"
        element={
          <PrivateRoute>
            <Layout>
              <Routes>
                <Route path="/" element={<Dashboard />} />
                <Route path="/lich-su" element={<History />} />
                <Route path="/bao-cao" element={<Reports />} />
                <Route path="/hoc-sinh" element={<Students />} />
                <Route path="/cau-hinh" element={<Settings />} />
              </Routes>
            </Layout>
          </PrivateRoute>
        }
      />
    </Routes>
  );
}