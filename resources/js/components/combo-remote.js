// resources/js/components/combo-remote.js
export default (cfg = {}) => ({
    endpoint: cfg.endpoint,
    labelFn: cfg.labelFn || ((o) => o.label ?? o.nombre ?? ""),
    onSelect: cfg.onSelect || (() => {}),
    minChars: cfg.minChars ?? 1,

    query: cfg.initialQuery || "",
    open: false,
    options: [],
    loading: false,
    cursor: -1,

    async search() {
        const q = this.query.trim();
        this.open = true;
        if (q.length < this.minChars) {
            this.options = [];
            this.cursor = -1;
            return;
        }
        this.loading = true;
        try {
            const p = new URLSearchParams({ q, limit: 10 });
            const r = await fetch(`${this.endpoint}?${p.toString()}`, {
                headers: { Accept: "application/json" },
            });
            const d = await r.json();
            this.options = (d.items || d.data || []).slice(0, 10);
            this.cursor = this.options.length ? 0 : -1;
        } catch (e) {
            console.error(e);
        } finally {
            this.loading = false;
        }
    },
    render(o) {
        try {
            return this.labelFn(o);
        } catch {
            return "";
        }
    },
    move(delta) {
        if (!this.options.length) return;
        this.cursor =
            (this.cursor + delta + this.options.length) % this.options.length;
    },
    choose(i = null) {
        const idx = i ?? this.cursor;
        const o = this.options[idx];
        if (!o) return;
        this.query = this.render(o);
        this.open = false;
        this.onSelect(o);
    },
    close() {
        this.open = false;
    },
});
