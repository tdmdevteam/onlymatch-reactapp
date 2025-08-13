import React from 'react';
import { createRoot } from 'react-dom/client';
import { createBrowserRouter, RouterProvider } from 'react-router-dom';

import App from './App.jsx';
import Home from './pages/home.jsx';
import Admin from './pages/Admin.jsx';
import './styles/index.css';

const router = createBrowserRouter([
  {
    path: '/', element: <App />, children: [
      { index: true, element: <Home /> },
      { path: 'admin', element: <Admin /> },
    ]
  },
]);

createRoot(document.getElementById('root')).render(
  <React.StrictMode>
    <RouterProvider router={router} />
  </React.StrictMode>
);
