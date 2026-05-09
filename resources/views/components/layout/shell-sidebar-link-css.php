        .sidebar-link {
            display: flex; align-items: center; gap: 10px;
            padding: 8px 12px; border-radius: 10px;
            font-size: 13.5px; font-weight: 500;
            transition: background 0.15s, color 0.15s;
        }
        .sidebar-link.active { background: rgba(167,243,208,0.78); color: #0f766e; }
        .dark .sidebar-link.active { background: rgba(19,78,74,0.45); color: #5eead4; }
        .sidebar-link:not(.active) { color: #64748b; }
        .sidebar-link:not(.active):hover { background: #f8fafc; color: #1e293b; }
        .dark .sidebar-link:not(.active) { color: #94a3b8; }
        .dark .sidebar-link:not(.active):hover { background: #1e293b; color: #e2e8f0; }
