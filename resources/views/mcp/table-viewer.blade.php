<x-mcp::app :title="$title">
    <x-slot:head>
        <style>
            :root {
                --kv-bg: var(--color-background-primary, #ffffff);
                --kv-bg-soft: var(--color-background-secondary, #f1f5f9);
                --kv-text: var(--color-text-primary, #1e293b);
                --kv-muted: var(--color-text-secondary, #64748b);
                --kv-border: var(--color-border-primary, #cbd5e1);
                --kv-accent: #24247f;
                --kv-font: var(--font-sans, system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif);
            }
            :root[data-theme="dark"] {
                --kv-bg: var(--color-background-primary, #1c1c1e);
                --kv-bg-soft: var(--color-background-secondary, #2a2a2d);
                --kv-text: var(--color-text-primary, #e5e7eb);
                --kv-muted: var(--color-text-secondary, #9ca3af);
                --kv-border: var(--color-border-primary, #44444a);
                --kv-accent: #8b8bf0;
            }
            * { box-sizing: border-box; }
            body { margin: 0; font-family: var(--kv-font); font-size: 13px; color: var(--kv-text); background: transparent; }
            .kv { display: flex; flex-direction: column; gap: 8px; padding: 10px; background: var(--kv-bg); }
            .kv-top { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; }
            .kv-title { font-weight: 600; font-size: 14px; flex: 1 1 200px; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
            .kv-count { color: var(--kv-muted); font-size: 12px; white-space: nowrap; }
            input[type="search"] { flex: 1 1 180px; min-width: 120px; padding: 6px 9px; border: 1px solid var(--kv-border); border-radius: 6px; background: var(--kv-bg); color: var(--kv-text); font: inherit; }
            button { padding: 6px 11px; border: 1px solid var(--kv-border); border-radius: 6px; background: var(--kv-bg-soft); color: var(--kv-text); font: inherit; cursor: pointer; }
            button:hover:not(:disabled) { border-color: var(--kv-accent); }
            button:disabled { opacity: .45; cursor: default; }
            .kv-wrap { height: 460px; overflow: auto; border: 1px solid var(--kv-border); border-radius: 6px; position: relative; }
            body.full .kv { height: 100vh; }
            body.full .kv-wrap { height: auto; flex: 1 1 auto; min-height: 0; }
            table { border-collapse: collapse; table-layout: fixed; }
            th, td { height: 28px; padding: 0 9px; border-bottom: 1px solid var(--kv-border); text-align: left; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
            th { position: sticky; top: 0; background: var(--kv-bg-soft); cursor: pointer; user-select: none; font-weight: 600; z-index: 1; }
            th:hover { color: var(--kv-accent); }
            td.num { text-align: right; font-variant-numeric: tabular-nums; }
            td.nul { color: var(--kv-muted); }
            tr.par td { background: color-mix(in srgb, var(--kv-bg-soft) 45%, transparent); }
            tr.sp td { padding: 0; border: 0; height: auto; }
            .kv-msg { padding: 14px; color: var(--kv-muted); text-align: center; }
            .kv-msg.error { color: #dc2626; }
            .kv-note { font-size: 12px; color: var(--kv-muted); min-height: 16px; }
            .kv-note a { color: var(--kv-accent); }
            .kv-bar { height: 3px; background: var(--kv-bg-soft); border-radius: 2px; overflow: hidden; display: none; }
            .kv-bar > div { height: 100%; width: 0; background: var(--kv-accent); transition: width .2s; }
        </style>
        <script type="module">
            createMcpApp(async (app) => {
                const $ = (id) => document.getElementById(id);
                const ROW = 28;
                const OVERSCAN = 12;
                const CHUNK = 1000;
                const S = { token: null, gen: 0, columns: [], widths: [], rows: [], hay: [], view: [], search: '', sort: null, dir: 'asc', total: 0, loaded: 0, loading: false };
                let timer = null;

                const parse = (result) => {
                    const text = result?.content?.find?.((part) => part?.type === 'text')?.text;
                    try { return result?.structuredContent ?? JSON.parse(text ?? '{}'); } catch (e) { return {}; }
                };
                const clear = (node) => { while (node.firstChild) node.removeChild(node.firstChild); };
                const fmt = (value) => (value === null || value === undefined ? null : typeof value === 'boolean' ? (value ? 'Sí' : 'No') : String(value));
                const n = (value) => Number(value).toLocaleString('es-CO');

                function message(text, error = false) {
                    const wrap = $('wrap');
                    clear(wrap);
                    const div = document.createElement('div');
                    div.className = 'kv-msg' + (error ? ' error' : '');
                    div.textContent = text;
                    wrap.appendChild(div);
                    $('bar').style.display = 'none';
                    app.resize();
                }

                function setupColumns(columns, sample) {
                    S.columns = columns;
                    S.widths = columns.map((name, index) => {
                        let longest = String(name).length + 3;
                        sample.slice(0, 200).forEach((row) => { const v = fmt(row[index]); if (v !== null && v.length > longest) longest = v.length; });
                        return Math.max(70, Math.min(320, longest * 7.5 + 22));
                    });
                }

                function buildTable() {
                    const wrap = $('wrap');
                    clear(wrap);
                    const table = document.createElement('table');
                    const sum = S.widths.reduce((a, b) => a + b, 0);
                    const scale = Math.max(1, (wrap.clientWidth - 4) / sum);
                    table.style.width = Math.round(sum * scale) + 'px';
                    const group = document.createElement('colgroup');
                    S.widths.forEach((w) => { const col = document.createElement('col'); col.style.width = Math.round(w * scale) + 'px'; group.appendChild(col); });
                    table.appendChild(group);
                    const thead = document.createElement('thead');
                    const head = document.createElement('tr');
                    S.columns.forEach((name, index) => {
                        const th = document.createElement('th');
                        th.id = 'th' + index;
                        th.title = 'Ordenar por ' + name;
                        th.addEventListener('click', () => {
                            S.dir = S.sort === index && S.dir === 'asc' ? 'desc' : 'asc';
                            S.sort = index;
                            rebuild();
                        });
                        head.appendChild(th);
                    });
                    thead.appendChild(head);
                    table.appendChild(thead);
                    const tbody = document.createElement('tbody');
                    tbody.id = 'tbody';
                    table.appendChild(tbody);
                    wrap.appendChild(table);
                    paintHeads();
                }

                function paintHeads() {
                    S.columns.forEach((name, index) => {
                        const th = $('th' + index);
                        if (th) th.textContent = name + (S.sort === index ? (S.dir === 'asc' ? ' ▲' : ' ▼') : '');
                    });
                }

                const hay = (i) => (S.hay[i] ??= S.rows[i].map((v) => (v === null || v === undefined ? '' : String(v))).join('\u0001').toLowerCase());
                const compare = (a, b) => {
                    const x = S.rows[a][S.sort];
                    const y = S.rows[b][S.sort];
                    if (x === null || x === undefined) return (y === null || y === undefined) ? 0 : 1;
                    if (y === null || y === undefined) return -1;
                    const r = (typeof x === 'number' && typeof y === 'number') ? x - y : String(x).localeCompare(String(y), 'es', { numeric: true, sensitivity: 'base' });
                    return S.dir === 'desc' ? -r : r;
                };

                function rebuild() {
                    const q = S.search.toLowerCase();
                    let view = [];
                    for (let i = 0; i < S.rows.length; i++) { if (!q || hay(i).includes(q)) view.push(i); }
                    if (S.sort !== null) view.sort(compare);
                    S.view = view;
                    paintHeads();
                    counts();
                    paint();
                }

                function counts() {
                    const shown = S.view.length;
                    let text = (shown === S.loaded ? n(S.total) : n(shown) + ' de ' + n(S.total)) + ' filas';
                    if (S.loading) text = 'Cargando ' + n(S.loaded) + ' de ' + n(S.total) + '…';
                    $('count').textContent = text;
                    $('bar').style.display = S.loading ? 'block' : 'none';
                    $('barfill').style.width = (S.total ? Math.round(S.loaded * 100 / S.total) : 0) + '%';
                }

                function paint() {
                    const body = $('tbody');
                    if (!body) return;
                    const wrap = $('wrap');
                    const total = S.view.length;
                    const first = Math.max(0, Math.floor(wrap.scrollTop / ROW) - OVERSCAN);
                    const last = Math.min(total, first + Math.ceil(wrap.clientHeight / ROW) + OVERSCAN * 2);
                    const fragment = document.createDocumentFragment();
                    const spacer = (rows) => {
                        const tr = document.createElement('tr');
                        tr.className = 'sp';
                        tr.style.height = rows * ROW + 'px';
                        const td = document.createElement('td');
                        td.colSpan = S.columns.length;
                        tr.appendChild(td);
                        return tr;
                    };
                    if (first > 0) fragment.appendChild(spacer(first));
                    for (let v = first; v < last; v++) {
                        const row = S.rows[S.view[v]];
                        const tr = document.createElement('tr');
                        if (v % 2) tr.className = 'par';
                        for (let c = 0; c < S.columns.length; c++) {
                            const td = document.createElement('td');
                            const shown = fmt(row[c]);
                            if (shown === null) { td.textContent = '—'; td.className = 'nul'; }
                            else { td.textContent = shown; td.title = shown; if (typeof row[c] === 'number') td.className = 'num'; }
                            tr.appendChild(td);
                        }
                        fragment.appendChild(tr);
                    }
                    if (last < total) fragment.appendChild(spacer(total - last));
                    clear(body);
                    body.appendChild(fragment);
                    if (!total && S.loaded) {
                        const tr = document.createElement('tr');
                        const td = document.createElement('td');
                        td.colSpan = S.columns.length;
                        td.className = 'kv-msg';
                        td.style.height = 'auto';
                        td.textContent = S.search ? 'Ninguna fila coincide con la búsqueda.' : 'La consulta no devolvió filas.';
                        tr.appendChild(td);
                        body.appendChild(tr);
                    }
                }

                async function loadAll(token) {
                    const gen = ++S.gen;
                    Object.assign(S, { token, rows: [], hay: [], view: [], loaded: 0, total: 0, loading: true, sort: null, dir: 'asc' });
                    let page = 1;
                    let pages = 1;
                    try {
                        do {
                            const result = await app.callServerTool('table_page', { token, page, per_page: CHUNK });
                            if (gen !== S.gen) return;
                            const data = parse(result);
                            if (result?.isError || !data.ok) {
                                S.loading = false;
                                message(data.type === 'not_found' ? 'Este resultado venció. Pídele a Claude que vuelva a ejecutar la consulta.' : (data.message || 'No se pudo cargar la tabla.'), true);
                                return;
                            }
                            if (page === 1) {
                                S.total = data.total;
                                setupColumns(data.columns, data.rows);
                                buildTable();
                            }
                            for (const row of data.rows) S.rows.push(row);
                            S.loaded = S.rows.length;
                            pages = data.pages;
                            if (page === 1 || page === pages || page % 3 === 0) rebuild(); else counts();
                            page++;
                        } while (page <= pages);
                    } catch (e) {
                        S.loading = false;
                        message('No se pudo cargar la tabla. Pídele a Claude que la abra de nuevo.', true);
                        return;
                    }
                    S.loading = false;
                    rebuild();
                    app.resize();
                }

                $('wrap').addEventListener('scroll', paint, { passive: true });
                window.addEventListener('resize', paint);
                $('search').addEventListener('input', (event) => {
                    clearTimeout(timer);
                    timer = setTimeout(() => { S.search = event.target.value.trim(); $('wrap').scrollTop = 0; rebuild(); }, 250);
                });
                $('expand').addEventListener('click', () => { app.requestDisplayMode('fullscreen').then(() => applyMode({ displayMode: 'fullscreen' })).catch(() => {}); });
                $('excel').addEventListener('click', async () => {
                    if (!S.token) return;
                    $('excel').disabled = true;
                    $('note').textContent = 'Preparando el Excel…';
                    try {
                        const result = await app.callServerTool('table_export', { token: S.token, name: $('title').textContent || 'consulta' });
                        const data = parse(result);
                        if (result?.isError || !data.file) { $('note').textContent = data.message || 'No se pudo preparar el Excel.'; return; }
                        clear($('note'));
                        const link = document.createElement('a');
                        link.href = data.file.url; link.target = '_blank'; link.rel = 'noopener'; link.textContent = 'Descargar ' + data.file.name;
                        $('note').appendChild(link);
                        $('note').appendChild(document.createTextNode(' (vence en ' + data.file.expires_in_minutes + ' minutos)'));
                        app.openLink(data.file.url).catch(() => {});
                    } catch (e) {
                        $('note').textContent = 'No se pudo preparar el Excel.';
                    } finally {
                        $('excel').disabled = false;
                        app.resize();
                    }
                });

                function applyMode(context) {
                    document.body.classList.toggle('full', context?.displayMode === 'fullscreen');
                    requestAnimationFrame(() => { paint(); app.resize(); });
                }
                app.onHostContextChanged(applyMode);
                applyMode(app.getHostContext());

                app.onToolResult((result) => {
                    const data = parse(result);
                    if (result?.isError || !data.token) {
                        message(data.message || 'La consulta no devolvió una tabla.', true);
                        return;
                    }
                    S.search = '';
                    $('search').value = '';
                    $('title').textContent = data.title || 'Resultado de la consulta';
                    $('note').textContent = data.truncated ? 'Se muestra solo una parte: la consulta tiene más filas que el tope. Pide un filtro más estrecho.' : '';
                    loadAll(data.token);
                });

                message('Cargando la tabla…');
                app.autoResize();
            });
        </script>
    </x-slot:head>

    <div class="kv">
        <div class="kv-top">
            <div class="kv-title" id="title">Tabla de Kanri</div>
            <span class="kv-count" id="count"></span>
            <input id="search" type="search" placeholder="Buscar en la tabla…" autocomplete="off">
            <button id="excel" title="Descargar todo el resultado en Excel">Excel</button>
            <button id="expand" title="Ver en toda la ventana">Ampliar</button>
        </div>
        <div class="kv-bar" id="bar"><div id="barfill"></div></div>
        <div class="kv-wrap" id="wrap"></div>
        <div class="kv-note" id="note"></div>
    </div>
</x-mcp::app>
