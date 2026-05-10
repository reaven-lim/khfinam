<?php
declare(strict_types=1);

/**
 * Ensures ApexCharts SVG/canvas blend with Tailwind light/dark shells.
 * Tooltip + axis/legend legibility in light mode (Apex defaults skew dark).
 */
?>
        html:not(.dark) .apexcharts-tooltip.apexcharts-theme-light,
        html:not(.dark) .apexcharts-tooltip.apexcharts-theme-light .apexcharts-tooltip-title {
            background: #ffffff !important;
            border-color: rgb(226 232 240) !important;
            box-shadow:
                0 18px 44px -12px rgba(15,23,42,0.16),
                0 8px 20px -8px rgba(15,23,42,0.1),
                0 1px 0 rgba(255,255,255,0.92) inset !important;
            color: #0f172a !important;
        }
        html:not(.dark) .apexcharts-tooltip.apexcharts-theme-light .apexcharts-tooltip-title {
            border-bottom-color: rgb(226 232 240) !important;
        }
        html:not(.dark) .apexcharts-tooltip-text-y-value,
        html:not(.dark) .apexcharts-tooltip-text-y-label { color: #334155 !important; }
        html:not(.dark) .apexcharts-xaxis-label,
        html:not(.dark) .apexcharts-yaxis-label { fill: rgb(71,85,105) !important; }
        html:not(.dark) .apexcharts-legend-text { color: #334155 !important; fill: #334155 !important; }
        html:not(.dark) .apexcharts-svg,
        html:not(.dark) .apexcharts-canvas { background: transparent !important; }
        html:not(.dark) .apexcharts-datalabels text,
        html:not(.dark) .apexcharts-datalabel text { fill: #334155 !important; }
        html:not(.dark) .apexcharts-radialbar .apexcharts-texts text { fill: #0f172a !important; }
        .dark .apexcharts-svg,
        .dark .apexcharts-canvas { background: transparent !important; }
        .dark .apexcharts-tooltip.apexcharts-theme-dark,
        .dark .apexcharts-tooltip.apexcharts-theme-dark .apexcharts-tooltip-title {
            background: rgba(15,23,42,0.96) !important;
            border-color: rgba(51,65,85,0.7) !important;
            color: #f1f5f9 !important;
        }
        .dark .apexcharts-tooltip-series-group { color: #e2e8f0 !important; }
        .dark .apexcharts-legend-text { color: #cbd5e1 !important; fill: #cbd5e1 !important; }
        .dark .apexcharts-xaxis-label,
        .dark .apexcharts-yaxis-label { fill: #94a3b8 !important; }
        .dark .apexcharts-datalabels text,
        .dark .apexcharts-datalabel text { fill: #f1f5f9 !important; }
