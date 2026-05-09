<?php
declare(strict_types=1);
/** @var string $sbPrefix dash-sb | adm-sb */
$p = ($sbPrefix === 'adm-sb') ? 'adm-sb' : 'dash-sb';
?>
        /* md–xl: compact icon rail; .sidebar-mid-expanded restores labels + width */
        @media (min-width: 768px) and (max-width: 1279.98px) {
            #sidebar.layout-sidebar-slim:not(.sidebar-mid-expanded) {
                width: 4.5rem !important;
                min-width: 4.5rem !important;
            }
            #sidebar.layout-sidebar-slim:not(.sidebar-mid-expanded) .sidebar-link {
                justify-content: center;
                padding-left: 0.5rem;
                padding-right: 0.5rem;
            }
            #sidebar.layout-sidebar-slim:not(.sidebar-mid-expanded) .sidebar-link.active {
                box-shadow: inset 0 0 0 2px rgba(20, 184, 166, 0.45);
            }
            #sidebar.layout-sidebar-slim.sidebar-mid-expanded {
                width: 260px !important;
                min-width: 260px !important;
            }
            #sidebar.layout-sidebar-slim.sidebar-mid-expanded .sidebar-link {
                justify-content: flex-start;
            }
            #sidebar:not(.sidebar-mid-expanded) .<?= $p ?>-brand,
            #sidebar:not(.sidebar-mid-expanded) .<?= $p ?>-heading,
            #sidebar:not(.sidebar-mid-expanded) .<?= $p ?>-text,
            #sidebar:not(.sidebar-mid-expanded) .<?= $p ?>-user-meta,
            #sidebar:not(.sidebar-mid-expanded) .<?= $p ?>-kpi-dot {
                display: none !important;
            }
            #sidebar.sidebar-mid-expanded .<?= $p ?>-user-meta {
                display: flex !important;
                flex-direction: column;
                align-items: flex-start;
            }
        }
        .<?= $p ?>-user-meta {
            flex: 1 1 0%;
            min-width: 0;
        }
        .<?= $p ?>-text {
            flex: 1 1 0%;
            min-width: 0;
        }
        @media (min-width: 1280px) {
            .<?= $p ?>-brand { display: block !important; }
            .<?= $p ?>-heading { display: block !important; }
            .<?= $p ?>-text { display: block !important; }
            .<?= $p ?>-user-meta { display: flex !important; flex-direction: column; }
            .<?= $p ?>-kpi-dot { display: inline-block !important; }
            #sidebar .sidebar-link { justify-content: flex-start; }
        }
