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
            .kv-count { color: var(--kv-muted); font-size: 12px; }
            input[type="search"] { flex: 1 1 180px; min-width: 120px; padding: 6px 9px; border: 1px solid var(--kv-border); border-radius: 6px; background: var(--kv-bg); color: var(--kv-text); font: inherit; }
            button { padding: 6px 11px; border: 1px solid var(--kv-border); border-radius: 6px; background: var(--kv-bg-soft); color: var(--kv-text); font: inherit; cursor: pointer; }
            button:hover:not(:disabled) { border-color: var(--kv-accent); }
            button:disabled { opacity: .45; cursor: default; }
            button.primary { background: var(--kv-accent); border-color: var(--kv-accent); color: #fff; }
            .kv-wrap { overflow: auto; max-height: 460px; border: 1px solid var(--kv-border); border-radius: 6px; }
            table { border-collapse: collapse; width: max-content; min-width: 100%; }
            th, td { padding: 5px 9px; border-bottom: 1px solid var(--kv-border); text-align: left; max-width: 280px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
            th { position: sticky; top: 0; background: var(--kv-bg-soft); cursor: pointer; user-select: none; font-weight: 600; z-index: 1; }
            th:hover { color: var(--kv-accent); }
            td.num { text-align: right; font-variant-numeric: tabular-nums; }
            td.nul { color: var(--kv-muted); }
            tr:nth-child(even) td { background: color-mix(in srgb, var(--kv-bg-soft) 45%, transparent); }
            .kv-bottom { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; justify-content: space-between; }
            .kv-pager { display: flex; align-items: center; gap: 6px; }
            .kv-msg { padding: 14px; color: var(--kv-muted); text-align: center; }
            .kv-msg.error { color: #dc2626; }
            .kv-note { font-size: 12px; color: var(--kv-muted); }
            .kv-note a { color: var(--kv-accent); }
            select { padding: 5px; border: 1px solid var(--kv-border); border-radius: 6px; background: var(--kv-bg); color: var(--kv-text); font: inherit; }
        </style>
        <script type="module">
            createMcpApp(async (app) => {
                const $ = (id) => document.getElementById(id);
                const state = { token: null, page: 1, perPage: 50, search: '', sort: null, dir: 'asc', columns: [], busy: false };
                let timer = null;

                const parse = (result) => {
                    const text = result?.content?.find?.((part) => part?.type === 'text')?.text;
                    try { return result?.structuredContent ?? JSON.parse(text ?? '{}'); } catch (e) { return {}; }
                };
                const clear = (node) => { while (node.firstChild) node.removeChild(node.firstChild); };
                const message = (text, error = false) => {
                    const wrap = $('wrap');
                    clear(wrap);
                    const div = document.createElement('div');
                    div.className = 'kv-msg' + (error ? ' error' : '');
                    div.textContent = text;
                    wrap.appendChild(div);
                    $('bottom').style.visibility = 'hidden';
                    app.resize();
                };
                const format = (value) => (value === null || value === undefined ? null : typeof value === 'boolean' ? (value ? 'Sí' : 'No') : String(value));

                function render(data) {
                    const wrap = $('wrap');
                    clear(wrap);

                    if (!data.rows.length) {
                        message(state.search ? 'Ninguna fila coincide con la búsqueda.' : 'La consulta no devolvió filas.');
                        $('count').textContent = '0 filas';
                        return;
                    }

                    const table = document.createElement('table');
                    const head = document.createElement('tr');
                    data.columns.forEach((name, index) => {
                        const th = document.createElement('th');
                        th.textContent = name + (state.sort === index ? (state.dir === 'asc' ? ' ▲' : ' ▼') : '');
                        th.title = 'Ordenar por ' + name;
                        th.addEventListener('click', () => {
                            state.dir = state.sort === index && state.dir === 'asc' ? 'desc' : 'asc';
                            state.sort = index;
                            state.page = 1;
                            load();
                        });
                        head.appendChild(th);
                    });
                    const thead = document.createElement('thead');
                    thead.appendChild(head);
                    table.appendChild(thead);

                    const tbody = document.createElement('tbody');
                    data.rows.forEach((row) => {
                        const tr = document.createElement('tr');
                        row.forEach((value) => {
                            const td = document.createElement('td');
                            const shown = format(value);
                            if (shown === null) { td.textContent = '—'; td.className = 'nul'; }
                            else { td.textContent = shown; td.title = shown; if (typeof value === 'number') td.className = 'num'; }
                            tr.appendChild(td);
                        });
                        tbody.appendChild(tr);
                    });
                    table.appendChild(tbody);
                    wrap.appendChild(table);

                    const filtered = data.filtered;
                    const total = data.total;
                    $('count').textContent = (filtered === total ? total.toLocaleString('es-CO') : filtered.toLocaleString('es-CO') + ' de ' + total.toLocaleString('es-CO')) + ' filas';
                    $('info').textContent = 'Página ' + data.page + ' de ' + data.pages;
                    $('prev').disabled = data.page <= 1;
                    $('next').disabled = data.page >= data.pages;
                    $('bottom').style.visibility = 'visible';
                    app.resize();
                }

                async function load() {
                    if (!state.token || state.busy) return;
                    state.busy = true;
                    try {
                        const result = await app.callServerTool('table_page', {
                            token: state.token, page: state.page, per_page: state.perPage, search: state.search,
                            sort: state.sort === null ? undefined : state.sort, dir: state.dir,
                        });
                        const data = parse(result);
                        if (result?.isError || !data.ok) {
                            message(data.type === 'not_found' ? 'Este resultado venció. Pídele a Claude que vuelva a ejecutar la consulta.' : (data.message || 'No se pudo cargar la tabla.'), true);
                        } else {
                            render(data);
                        }
                    } catch (e) {
                        message('No se pudo cargar la tabla. Pídele a Claude que la abra de nuevo.', true);
                    } finally {
                        state.busy = false;
                    }
                }

                $('search').addEventListener('input', (event) => {
                    clearTimeout(timer);
                    timer = setTimeout(() => { state.search = event.target.value; state.page = 1; load(); }, 350);
                });
                $('perPage').addEventListener('change', (event) => { state.perPage = Number(event.target.value); state.page = 1; load(); });
                $('prev').addEventListener('click', () => { state.page = Math.max(1, state.page - 1); load(); });
                $('next').addEventListener('click', () => { state.page += 1; load(); });
                $('expand').addEventListener('click', () => { app.requestDisplayMode('fullscreen').catch(() => {}); });
                $('excel').addEventListener('click', async () => {
                    if (!state.token) return;
                    $('excel').disabled = true;
                    $('note').textContent = 'Preparando el Excel…';
                    try {
                        const result = await app.callServerTool('table_export', { token: state.token, name: $('title').textContent || 'consulta' });
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

                app.onToolResult((result) => {
                    const data = parse(result);
                    if (result?.isError || !data.token) {
                        message(data.message || 'La consulta no devolvió una tabla.', true);
                        return;
                    }
                    state.token = data.token;
                    state.page = 1; state.sort = null; state.dir = 'asc'; state.search = '';
                    $('search').value = '';
                    $('title').textContent = data.title || 'Resultado de la consulta';
                    $('note').textContent = data.truncated ? 'Se muestra solo una parte: la consulta tiene más filas que el tope. Pide un filtro más estrecho.' : '';
                    load();
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
            <button id="expand" title="Ampliar">Ampliar</button>
        </div>
        <div class="kv-wrap" id="wrap"></div>
        <div class="kv-bottom" id="bottom" style="visibility: hidden;">
            <div class="kv-pager">
                <button id="prev">‹ Anterior</button>
                <span id="info"></span>
                <button id="next">Siguiente ›</button>
            </div>
            <label class="kv-note">Filas por página
                <select id="perPage">
                    <option value="25">25</option>
                    <option value="50" selected>50</option>
                    <option value="100">100</option>
                    <option value="200">200</option>
                </select>
            </label>
        </div>
        <div class="kv-note" id="note"></div>
    </div>
</x-mcp::app>
