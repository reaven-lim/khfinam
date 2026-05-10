/**
 * KHFinaM — ApexCharts theme bridge (tailwind `html.dark`).
 * Central tokens for readable light charts + premium dark charts.
 *
 * Depends on ApexCharts loaded first. Safe to early-load before DOM.
 *
 * Global: window.KhfApexTheme
 */
(function (global) {
    'use strict';

    /** @typedef {{mode:string,tooltipTheme:string,chartBg:string,foreColor:string,axisLabel:string,legend:string,title:string,grid:string,gridContrast:string,donutRingStroke:string,radialTrack:string,donutCenterLabel:string,donutCenterValue:string,markerOutline:string,incomeExpenseFillShade:string}} KhfTokens */

    function escapeHtml(s) {
        return String(s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function isDark() {
        try {
            return !!(global.document && global.document.documentElement.classList.contains('dark'));
        } catch (e) {
            return false;
        }
    }

    /** @returns {KhfTokens} */
    function tokens() {
        if (isDark()) {
            return {
                mode: 'dark',
                tooltipTheme: 'dark',
                chartBg: 'transparent',
                foreColor: '#94a3b8',
                axisLabel: '#94a3b8',
                legend: '#cbd5e1',
                title: '#f1f5f9',
                grid: 'rgba(148, 163, 184, 0.14)',
                gridContrast: 'rgba(148, 163, 184, 0.22)',
                donutRingStroke: '#0f172a',
                radialTrack: '#1e293b',
                donutCenterLabel: '#94a3b8',
                donutCenterValue: '#f8fafc',
                markerOutline: '#0f172a',
                incomeExpenseFillShade: 'dark',
            };
        }
        return {
            mode: 'light',
            tooltipTheme: 'light',
            chartBg: 'transparent',
            foreColor: '#64748b',
            axisLabel: '#475569',
            legend: '#334155',
            title: '#0f172a',
            grid: 'rgba(100, 116, 139, 0.28)',
            gridContrast: 'rgba(71, 85, 105, 0.22)',
            donutRingStroke: '#ffffff',
            radialTrack: '#e2e8f0',
            donutCenterLabel: '#64748b',
            donutCenterValue: '#0f172a',
            markerOutline: '#ffffff',
            incomeExpenseFillShade: 'light',
        };
    }

    /** @returns {'dark'|'light'} */
    function apexThemeMode() {
        return isDark() ? 'dark' : 'light';
    }

    /**
     * @param {Record<string, unknown>|undefined} chartExtras
     * @returns {{chart: Record<string, unknown>, theme: { mode: string }}}
     */
    function chart(chartExtras) {
        var t = tokens();
        return {
            chart: Object.assign(
                {
                    fontFamily: 'inherit, ui-sans-serif, system-ui, sans-serif',
                    background: t.chartBg,
                    foreColor: t.foreColor,
                    toolbar: { show: false },
                },
                chartExtras || {}
            ),
            theme: { mode: apexThemeMode() },
        };
    }

    /** @param {Record<string, unknown>|undefined} extra */
    function tooltip(extra) {
        var t = tokens();
        return Object.assign(
            {
                theme: t.tooltipTheme,
                style: { fontSize: '12px', fontFamily: 'inherit, system-ui, sans-serif' },
            },
            extra || {}
        );
    }

    /** @param {Partial<{fontSize:string,horizontalAlign:string,position:string,fontWeight:number}>} overrides */
    function legendTopRight(overrides) {
        var t = tokens();
        return Object.assign(
            {
                position: 'top',
                horizontalAlign: 'right',
                fontSize: '12px',
                fontWeight: 600,
                labels: { colors: t.legend, useSeriesColors: false },
                markers: { width: 8, height: 8, radius: 3 },
                itemMargin: { horizontal: 10, vertical: 6 },
            },
            overrides || {}
        );
    }

    /** @param {Partial<Record<string, unknown>>} overrides */
    function legendBottom(overrides) {
        var t = tokens();
        return Object.assign(
            {
                position: 'bottom',
                horizontalAlign: 'center',
                fontSize: '11px',
                fontWeight: 600,
                labels: { colors: t.legend, useSeriesColors: false },
                markers: { width: 7, height: 7, radius: 2 },
                itemMargin: { horizontal: 8, vertical: 4 },
            },
            overrides || {}
        );
    }

    /** @param {Record<string, unknown>|undefined} extraGrid */
    function grid(extraGrid) {
        var t = tokens();
        return Object.assign(
            {
                borderColor: t.grid,
                strokeDashArray: 4,
                padding: { top: 4, bottom: 0, left: 2, right: 8 },
                xaxis: { lines: { show: false } },
            },
            extraGrid || {}
        );
    }

    /**
     * Donut stroke + optional donut label tones (Apex donut labels.total.color).
     *
     * @param {number} donutSizePct optional e.g. 66–70
     */
    function donutAppearance(donutSizePct) {
        var t = tokens();
        var size = donutSizePct != null ? String(donutSizePct) : '66%';
        var ringW = isDark() ? 2 : 1.25;
        return {
            stroke: {
                show: true,
                width: ringW,
                colors: [t.donutRingStroke],
            },
            plotOptions: {
                pie: {
                    expandOnClick: false,
                    donut: {
                        size: size.indexOf('%') === -1 ? size + '%' : size,
                        background: 'transparent',
                        labels: {
                            show: true,
                            name: { color: t.donutCenterLabel },
                            value: { color: t.donutCenterValue },
                            total: {
                                show: true,
                                color: t.donutCenterLabel,
                                fontSize: '11px',
                                fontWeight: 600,
                            },
                        },
                    },
                },
            },
        };
    }

    /** @param {() => void} renderer destroy + recreate charts inside this closure */
    function mountOnTheme(renderer) {
        if (typeof renderer !== 'function') {
            return;
        }
        var tid = null;
        function run() {
            if (tid) {
                global.clearTimeout(tid);
            }
            tid = global.setTimeout(function () {
                tid = null;
                renderer();
            }, 20);
        }
        if (typeof document !== 'undefined') {
            document.addEventListener('khf-apex-theme-change', run);
            run();
        }
    }

    /**
     * @param {string} message
     * @param {'xs'|'sm'} size
     * @returns {string} HTML snippet
     */
    function emptyStateHtml(message, size) {
        var d = isDark();
        var sz = size === 'sm' ? 'text-[11px] py-6' : 'text-xs py-8';
        var fg = d ? 'text-slate-400' : 'text-slate-600';
        var sub = d ? '' : '';
        void sub;
        return '<p class="' + sz + ' ' + fg + ' text-center leading-snug">' + escapeHtml(message) + '</p>';
    }

    var lastEmitted = null;
    function observeHtmlClass() {
        if (!global.document || !global.document.documentElement) {
            return;
        }
        var html = global.document.documentElement;
        try {
            var obs = new MutationObserver(function () {
                var cur = apexThemeMode();
                if (cur !== lastEmitted) {
                    lastEmitted = cur;
                    global.window.dispatchEvent(
                        new CustomEvent('khf-apex-theme-change', { detail: { dark: isDark(), mode: cur } })
                    );
                }
            });
            obs.observe(html, { attributes: true, attributeFilter: ['class'] });
            lastEmitted = apexThemeMode();
        } catch (e) {
            /* ignore */
        }
    }

    if (typeof document !== 'undefined') {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', observeHtmlClass);
        } else {
            observeHtmlClass();
        }
    }

    global.KhfApexTheme = {
        tokens: tokens,
        isDark: isDark,
        apexThemeMode: apexThemeMode,
        chart: chart,
        tooltip: tooltip,
        legendTopRight: legendTopRight,
        legendBottom: legendBottom,
        grid: grid,
        donutAppearance: donutAppearance,
        emptyStateHtml: emptyStateHtml,
        mountOnTheme: mountOnTheme,
    };
})(typeof window !== 'undefined' ? window : globalThis);
