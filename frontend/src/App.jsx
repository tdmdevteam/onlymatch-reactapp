import { NavLink, Outlet } from 'react-router-dom';

export default function App() {
  return (
    <div className="app">
      <header className="site-header">
        <nav className="nav">
          <NavLink to="/" className="brand">OnlyMatch</NavLink>
          <div className="spacer" />
          <NavLink to="/" end>Home</NavLink>
          <NavLink to="/admin">Log In</NavLink>
        </nav>
      </header>

      <main className="content">
        <Outlet />
      </main>

      <footer className="site-footer">
        <small>© {new Date().getFullYear()} OnlyMatch</small>
      </footer>
    </div>
  );
}
