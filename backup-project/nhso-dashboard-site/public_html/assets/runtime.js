// Stand-alone runtime: provides the small `dash` API the dashboard page uses, reading JSON files from ./data/
(() => {
  const SOURCES = window.DASH_SOURCES || {};
  const DATA = {}, STATUS = {}, calcs = {}, subs = [];
  let params = Object.assign({}, window.DASH_DEFAULT_PARAMS || {});
  const defaults = Object.assign({}, params);
  const css = getComputedStyle(document.documentElement);
  const colors = ['--c1', '--c2', '--c3', '--c4', '--c5', '--c6', '--c7', '--c8'].map((v) => css.getPropertyValue(v).trim() || '#2563eb');
  let scheduled = false;
  const fire = () => {
    if (scheduled) return;
    scheduled = true;
    requestAnimationFrame(() => {
      scheduled = false;
      subs.forEach((fn) => { try { fn(); } catch (e) { console.error(e); } });
    });
  };
  const resolve = (id) => {
    if (calcs[id]) {
      const c = calcs[id];
      if (!c.cache) {
        const ins = c.inputs.map(resolve);
        if (ins.some((x) => x.status === 'error')) return { status: 'error', data: [] };
        if (ins.some((x) => x.status !== 'ok')) return { status: 'loading', data: [] };
        try { c.cache = c.fn(...ins.map((x) => x.data)); } catch (e) { console.error(e); return { status: 'error', data: [] }; }
      }
      return { status: 'ok', data: c.cache };
    }
    if (STATUS[id] === 'ok') return { status: 'ok', data: DATA[id] };
    if (STATUS[id] === 'error') return { status: 'error', data: [] };
    return { status: 'loading', data: [] };
  };
  window.dash = {
    colors,
    data: (id) => { const r = resolve(id); return { ...r, rows: r.data, refreshing: false, meta: [] }; },
    onData: (fn) => { subs.push(fn); try { fn(); } catch (e) { console.error(e); } },
    calc: (id, def) => { calcs[id] = { inputs: def.inputs || [], fn: def.fn, cache: null }; },
    loader: () => {},
    params: () => Object.assign({}, params),
    setParams: (p) => {
      for (const [k, v] of Object.entries(p || {})) { if (v === null || v === undefined) delete params[k]; else params[k] = v; }
      fire();
    },
    resetParams: () => { params = Object.assign({}, defaults); fire(); },
    setLink: (id) => { try { history.replaceState(null, '', '#' + id); } catch (e) { /* ignore */ } },
    refresh: () => {},
  };
  // minimal `claude.use`: Excel download works through the browser; import is not available on the public site
  window.claude = {
    use: async (name) => {
      if (name !== 'downloads') return null;
      return {
        save: async ({ filename, data }) => {
          const blob = data instanceof Blob ? data : new Blob([data]);
          const a = document.createElement('a');
          a.href = URL.createObjectURL(blob); a.download = filename;
          document.body.appendChild(a); a.click(); a.remove();
          setTimeout(() => URL.revokeObjectURL(a.href), 4000);
          return { status: 'saved' };
        },
      };
    },
  };
  Object.entries(SOURCES).forEach(([id, file]) => {
    STATUS[id] = 'loading';
    fetch(file, { cache: 'no-cache' }).then((r) => { if (!r.ok) throw new Error(r.status); return r.json(); })
      .then((rows) => { DATA[id] = Array.isArray(rows) ? rows : rows.rows || rows.data || []; STATUS[id] = 'ok'; Object.values(calcs).forEach((c) => { c.cache = null; }); fire(); })
      .catch((e) => { console.error('load failed', id, e); STATUS[id] = 'error'; fire(); });
  });
  let rw = 0;
  window.addEventListener('resize', () => { clearTimeout(rw); rw = setTimeout(fire, 150); });
})();
