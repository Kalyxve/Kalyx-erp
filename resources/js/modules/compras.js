// resources/js/modules/compras.js
import TomSelect from "tom-select/dist/js/tom-select.complete.js";
import "tom-select/dist/css/tom-select.css";

console.log("[compras] módulo cargado");

export default () => ({
    // ===== Estado =====
    loading: false,
    table: {
        items: [],
        meta: { current_page: 1, last_page: 1, total: 0, per_page: 25 },
    },
    q: "",
    limit: 25,

    newOpen: false,
    editId: null,
    toast: { show: false, text: "" },

    // Compra
    form: {
        fecha: new Date().toISOString().slice(0, 10),
        numero: "",
        proveedor_id: null,
        tasa_usd: 0,
        items: [], // { tempId, producto_id, producto_text, cantidad, precio_unitario_usd, precio_unitario_bs, subtotal_usd, subtotal_bs }
        total_usd: 0,
        total_bs: 0,
    },

    // Proveedor modal
    proveedorModalOpen: false,
    proveedorEditId: null,
    proveedor: {
        razon_social: "",
        rif: "",
        direccion: "",
        telefono: "",
        email: "",
        activo: true,
    },

    // Tom Select instances
    tsProveedor: null,
    tsProductos: {}, // { [tempId]: TomSelect }

    // CSRF
    csrf: document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute("content"),

    // ===== Helper fetch con CSRF =====
    async apiFetch(url, { method = "GET", json = null, headers = {} } = {}) {
        const base = { Accept: "application/json", ...headers };
        const needsCsrf = ["POST", "PUT", "PATCH", "DELETE"].includes(
            method.toUpperCase()
        );
        if (json !== null) base["Content-Type"] = "application/json";
        if (needsCsrf) base["X-CSRF-TOKEN"] = this.csrf || "";

        const res = await fetch(url, {
            method,
            headers: base,
            body: json !== null ? JSON.stringify(json) : undefined,
        });
        if (res.status === 419)
            alert(
                "CSRF token mismatch. Recarga la página (F5) e inténtalo de nuevo."
            );
        return res;
    },

    // ===== Init =====
    async init() {
        console.log("[compras] init()", this);
        window.__comprasPageRef = this; // debug
        await this.fetchList();
    },

    // ===== Utils =====
    async fetchBCV() {
        try {
            const res = await fetch("/api/bcv-rate", {
                headers: { Accept: "application/json" },
                cache: "no-store",
            });
            const data = await res.json();
            const rate = Number(data?.usd ?? data?.dollar ?? null);
            if (rate && rate > 0) this.form.tasa_usd = Number(rate.toFixed(4));
        } catch (e) {
            console.error("BCV fetch error", e);
        }
    },

    // Normaliza coma/punto y NaN
    parseNum(v) {
        if (typeof v === "string") v = v.replace(",", ".");
        const n = Number(v);
        return Number.isFinite(n) ? n : 0;
    },

    money(n, d = 2) {
        const x = Number(n ?? 0);
        return x.toLocaleString("es-VE", {
            minimumFractionDigits: d,
            maximumFractionDigits: d,
        });
    },

    showToast(text) {
        this.toast.text = text;
        this.toast.show = true;
        setTimeout(() => (this.toast.show = false), 2000);
    },

    resetForm() {
        this.editId = null;
        this.form = {
            fecha: new Date().toISOString().slice(0, 10),
            numero: "",
            proveedor_id: null,
            tasa_usd: this.form.tasa_usd || 0,
            items: [],
            total_usd: 0,
            total_bs: 0,
        };
        this.destroyProveedorTS();
        this.destroyAllProductosTS();
    },

    // ===== Listado =====
    async fetchList(page = 1) {
        this.loading = true;
        try {
            const url = new URL(window.location.origin + "/compras/list");
            if (this.q) url.searchParams.set("q", this.q);
            url.searchParams.set("limit", this.limit);
            url.searchParams.set("page", page);

            const res = await fetch(url, {
                headers: { Accept: "application/json" },
            });
            const { items, meta } = await res.json();
            this.table.items = items;
            this.table.meta = meta;
        } catch (e) {
            console.error(e);
        } finally {
            this.loading = false;
        }
    },

    // ===== Modales =====
    async openNew() {
        this.resetForm();
        await this.fetchBCV();
        this.newOpen = true;
        this.$nextTick(() => {
            this.mountProveedorTS();
            this.addItemRow(); // fila por defecto
        });
    },

    async openEdit(id) {
        this.resetForm();
        this.editId = id;
        const { data } = await (
            await fetch(`/compras/${id}`, {
                headers: { Accept: "application/json" },
            })
        ).json();

        this.form.fecha = data.fecha ?? new Date().toISOString().slice(0, 10);
        this.form.numero = data.numero ?? "";
        this.form.proveedor_id = data.proveedor?.id ?? null;

        await this.fetchBCV();

        this.form.items = [];
        this.newOpen = true;

        this.$nextTick(() => {
            this.mountProveedorTS(data.proveedor);
            (data.detalles || []).forEach((d) =>
                this.addItemRow({
                    producto_id: d.producto_id,
                    producto_text:
                        d.producto_text ??
                        d.producto?.texto ??
                        d.producto?.nombre ??
                        "",
                    cantidad: d.cantidad,
                    precio_unitario_usd: d.precio_unitario_usd ?? 0,
                    precio_unitario_bs: d.precio_unitario_bs ?? 0,
                    subtotal_usd: d.subtotal_usd ?? 0,
                    subtotal_bs: d.subtotal_bs ?? 0,
                })
            );
        });
    },

    closeModal() {
        this.newOpen = false;
        this.destroyAllProductosTS();
    },

    // ===== Tom Select Proveedor =====
    mountProveedorTS(preselected = null) {
        const el = this.$refs.selectProveedor;
        if (!el) return;

        this.destroyProveedorTS();

        this.tsProveedor = new TomSelect(el, {
            valueField: "id",
            labelField: "text",
            searchField: ["text"],
            preload: true,
            allowEmptyOption: true,
            load: async (query, cb) => {
                try {
                    const url = new URL(
                        window.location.origin + "/proveedores/list"
                    );
                    if (query) url.searchParams.set("q", query);
                    const res = await fetch(url, {
                        headers: { Accept: "application/json" },
                    });
                    const data = await res.json();
                    cb(data.items || []);
                } catch (e) {
                    console.error(e);
                    cb();
                }
            },
            onChange: (val) => {
                this.form.proveedor_id = val ? Number(val) : null;
            },
            render: {
                option: (data) =>
                    `<div class="text-sm"><div class="font-medium">${data.text}</div></div>`,
                item: (data) => `<div>${data.text}</div>`,
            },
        });

        if (preselected?.id) {
            const opt = {
                id: preselected.id,
                text: `${preselected.rif} — ${preselected.razon_social}`,
            };
            this.tsProveedor.addOption(opt);
            this.tsProveedor.setValue(String(preselected.id), true);
        }
    },

    destroyProveedorTS() {
        if (this.tsProveedor) {
            this.tsProveedor.destroy();
            this.tsProveedor = null;
        }
    },

    // ===== Productos con Tom Select (AJAX) =====
    /**
     * Llamar desde Blade: x-init="$nextTick(() => mountProductoTS($el, row.tempId, row))"
     */
    mountProductoTS(el, tempId, pre = null) {
        console.log("[compras] mountProductoTS()", { tempId, preOK: !!pre });
        // Evita fugas
        this.destroyProductoTS(tempId);

        const self = this;

        const ts = new TomSelect(el, {
            valueField: "id",
            labelField: "text",
            searchField: ["text"],
            preload: true,
            allowEmptyOption: true,
            maxOptions: 200,
            placeholder: "-- Selecciona producto --",
            loadThrottle: 250,
            load: async (query, cb) => {
                try {
                    const url = new URL(
                        window.location.origin + "/productos/combo"
                    );
                    if (query) url.searchParams.set("q", query);
                    url.searchParams.set("limit", "30");
                    const res = await fetch(url, {
                        headers: { Accept: "application/json" },
                    });
                    const data = await res.json();
                    cb(data.items || []);
                } catch (e) {
                    console.error("Error cargando productos", e);
                    cb();
                }
            },

            // Importante: algunos clics sólo disparan onItemAdd; redirigimos a onChange
            onItemAdd(value) {
                this.settings.onChange.call(this, value);
            },

            onChange(value) {
                const row = self.form.items.find((r) => r.tempId === tempId);
                if (!row) return;

                if (!value) {
                    row.producto_id = null;
                    row.producto_text = "";
                    row.precio_unitario_usd = 0;
                    row.precio_unitario_bs = 0;
                    self.recalcRow(tempId);
                    return;
                }

                const opt = this.options?.[value]; // opción en caché
                row.producto_id = Number(value);
                row.producto_text = opt?.text ?? "";

                const usd = self.parseNum(opt?.precio_usd_base ?? 0);
                const bs = self.parseNum(opt?.precio_bs_base ?? 0);
                if (usd > 0) {
                    row.precio_unitario_usd = usd;
                    const t = self.parseNum(self.form.tasa_usd);
                    row.precio_unitario_bs =
                        t > 0 ? Number((usd * t).toFixed(2)) : bs;
                } else {
                    row.precio_unitario_usd = 0;
                    row.precio_unitario_bs = bs;
                }

                self.recalcRow(tempId);
            },

            render: {
                option: (data) =>
                    `<div class="text-sm leading-tight">
             <div class="font-medium">${data.text}</div>
             ${
                 (data.precio_usd_base ?? null) !== null
                     ? `<div class="text-xs opacity-70">USD ${Number(
                           data.precio_usd_base
                       ).toFixed(2)} / Bs ${Number(
                           data.precio_bs_base ?? 0
                       ).toFixed(2)}</div>`
                     : ""
             }
           </div>`,
                item: (data) => `<div>${data.text}</div>`,
            },
        });

        // Prefill al editar
        if (pre?.producto_id) {
            ts.addOption({
                id: pre.producto_id,
                text: pre.producto_text || `#${pre.producto_id}`,
                precio_usd_base: pre.precio_unitario_usd ?? null,
                precio_bs_base: pre.precio_unitario_bs ?? null,
            });
            ts.setValue(String(pre.producto_id), true);
            // por si TomSelect no llama onChange con setValue(true)
            this.recalcRow(tempId);
        }

        this.tsProductos[tempId] = ts;
    },

    destroyProductoTS(tempId) {
        const inst = this.tsProductos?.[tempId];
        if (inst) {
            inst.destroy();
            delete this.tsProductos[tempId];
        }
    },

    destroyAllProductosTS() {
        Object.keys(this.tsProductos).forEach((k) => this.destroyProductoTS(k));
    },

    // ===== Filas =====
    addItemRow(prefill = null) {
        const tempId =
            typeof crypto !== "undefined" && crypto.randomUUID
                ? crypto.randomUUID()
                : `tmp_${Date.now()}_${Math.random()}`;

        const row = {
            tempId,
            producto_id: prefill?.producto_id ?? null,
            producto_text: prefill?.producto_text ?? "",
            cantidad: prefill?.cantidad ?? 1,
            precio_unitario_usd: prefill?.precio_unitario_usd ?? 0,
            precio_unitario_bs: prefill?.precio_unitario_bs ?? 0,
            subtotal_usd: prefill?.subtotal_usd ?? 0,
            subtotal_bs: prefill?.subtotal_bs ?? 0,
        };
        this.form.items.push(row);

        // El select se monta vía x-init del Blade
        this.recalcRow(tempId);
    },

    removeItemRow(tempId) {
        const idx = this.form.items.findIndex((r) => r.tempId === tempId);
        if (idx >= 0) {
            this.form.items.splice(idx, 1);
            this.destroyProductoTS(tempId);
            this.recalcTotals();
        }
    },

    onPriceUsdChange(tempId) {
        const r = this.form.items.find((x) => x.tempId === tempId);
        const t = this.parseNum(this.form.tasa_usd);
        const puu = this.parseNum(r?.precio_unitario_usd);
        r.precio_unitario_bs = t > 0 ? Number((puu * t).toFixed(2)) : 0;
        this.recalcRow(tempId);
    },

    onPriceBsChange(tempId) {
        const r = this.form.items.find((x) => x.tempId === tempId);
        const t = this.parseNum(this.form.tasa_usd);
        const pub = this.parseNum(r?.precio_unitario_bs);
        r.precio_unitario_usd = t > 0 ? Number((pub / t).toFixed(2)) : 0;
        this.recalcRow(tempId);
    },

    onTasaChange() {
        for (const r of this.form.items) {
            const t = this.parseNum(this.form.tasa_usd);
            if (t > 0) {
                if (this.parseNum(r.precio_unitario_usd) > 0) {
                    r.precio_unitario_bs = Number(
                        (this.parseNum(r.precio_unitario_usd) * t).toFixed(2)
                    );
                } else if (this.parseNum(r.precio_unitario_bs) > 0) {
                    r.precio_unitario_usd = Number(
                        (this.parseNum(r.precio_unitario_bs) / t).toFixed(2)
                    );
                }
            }
            r.subtotal_usd = Number(
                (
                    this.parseNum(r.cantidad) *
                    this.parseNum(r.precio_unitario_usd)
                ).toFixed(2)
            );
            r.subtotal_bs = Number(
                (
                    this.parseNum(r.cantidad) *
                    this.parseNum(r.precio_unitario_bs)
                ).toFixed(2)
            );
        }
        this.recalcTotals();
    },

    recalcRow(tempId) {
        const r = this.form.items.find((x) => x.tempId === tempId);
        if (!r) return;
        const qty = this.parseNum(r.cantidad);
        const puu = this.parseNum(r.precio_unitario_usd);
        const pub = this.parseNum(r.precio_unitario_bs);
        r.subtotal_usd = Number((qty * puu).toFixed(2));
        r.subtotal_bs = Number((qty * pub).toFixed(2));
        this.recalcTotals();
    },

    recalcTotals() {
        const usd = this.form.items.reduce(
            (acc, r) => acc + this.parseNum(r.subtotal_usd),
            0
        );
        const bs = this.form.items.reduce(
            (acc, r) => acc + this.parseNum(r.subtotal_bs),
            0
        );
        this.form.total_usd = Number(usd.toFixed(2));
        this.form.total_bs = Number(bs.toFixed(2));
    },

    // ===== ANIMACIÓN =====
    injectKalyxFxCss() {
        if (document.getElementById("kx-fx-css")) return;
        const st = document.createElement("style");
        st.id = "kx-fx-css";
        st.textContent = `
@keyframes kx-slide-in {
  0%   { transform: translate(var(--fromX), var(--fromY)) scale(.92); opacity: 0; }
  60%  { transform: translate(calc(var(--fromX) * .15), calc(var(--fromY) * .15)) scale(1.02); opacity: .95; }
  100% { transform: translate(0,0) scale(1); opacity: 1; }
}
.kx-fx-host { position: fixed; inset: 0; z-index: 9999; display:flex; align-items:center; justify-content:center; pointer-events:none; }
.kx-card { display:flex; flex-direction:column; align-items:center; gap:.5rem; animation: kx-slide-in 950ms cubic-bezier(.22,1,.36,1) both; will-change: transform, opacity; }
.kx-logo { width: 140px; height: 140px; object-fit: contain; filter: drop-shadow(0 8px 24px rgba(16,185,129,.35)); }
.kx-title { margin: 0; font-size: clamp(18px, 2.2vw, 26px); font-weight: 800; text-align:center; line-height:1.2; color: #0f172a; }
.dark .kx-title { color: #e5e7eb; }
`;
        document.head.appendChild(st);
    },

    showFX({ title = "Operación exitosa", corner = "br" } = {}) {
        this.injectKalyxFxCss();
        const map = {
            tl: ["-50vw", "-50vh"],
            tr: ["50vw", "-50vh"],
            bl: ["-50vw", "50vh"],
            br: ["50vw", "50vh"],
        };
        const [fx, fy] = map[corner] || map.br;

        const host = document.createElement("div");
        host.className = "kx-fx-host";
        host.innerHTML = `
      <div class="kx-card" style="--fromX:${fx}; --fromY:${fy}">
        <img src="/img/logo-kalyx.png" alt="Kalyx" class="kx-logo">
        <h2 class="kx-title">${title}</h2>
      </div>
    `;
        document.body.appendChild(host);
        setTimeout(() => host.remove(), 1400);
    },

    // ===== Proveedor en tiempo real =====
    openProveedorModal(edit = false) {
        this.proveedorEditId = edit ? this.form.proveedor_id ?? null : null;
        this.proveedor = {
            razon_social: "",
            rif: "",
            direccion: "",
            telefono: "",
            email: "",
            activo: true,
        };
        if (edit && this.proveedorEditId) {
            fetch(`/proveedores/${this.proveedorEditId}`, {
                headers: { Accept: "application/json" },
            })
                .then((r) => r.json())
                .then((p) => {
                    this.proveedor = {
                        razon_social: p.razon_social ?? "",
                        rif: p.rif ?? "",
                        direccion: p.direccion ?? "",
                        telefono: p.telefono ?? "",
                        email: p.email ?? "",
                        activo: !!p.activo,
                    };
                })
                .catch(console.error);
        }
        this.proveedorModalOpen = true;
    },

    closeProveedorModal() {
        this.proveedorModalOpen = false;
    },

    async saveProveedor() {
        try {
            const method = this.proveedorEditId ? "PUT" : "POST";
            const url = this.proveedorEditId
                ? `/proveedores/${this.proveedorEditId}`
                : "/proveedores";

            const res = await this.apiFetch(url, {
                method,
                json: this.proveedor,
            });
            const data = await res.json();

            if (!res.ok)
                throw new Error(data?.message || "Error guardando proveedor");

            if (this.tsProveedor) {
                this.tsProveedor.addOption(data.option);
                this.tsProveedor.refreshOptions(false);
                this.tsProveedor.setValue(String(data.option.id), true);
            }
            this.form.proveedor_id = data.option.id;

            this.showFX({
                title: this.proveedorEditId
                    ? "Proveedor actualizado"
                    : "Proveedor creado",
                corner: "tr",
            });
            this.closeProveedorModal();
        } catch (e) {
            alert(e.message);
        }
    },

    // ===== Guardar compra =====
    async submit() {
        try {
            if (!this.form.proveedor_id)
                throw new Error("Selecciona un proveedor");
            if (!this.form.tasa_usd || this.form.tasa_usd <= 0)
                throw new Error("Tasa inválida");
            if (this.form.items.length === 0)
                throw new Error("Agrega al menos un producto");

            const payload = {
                proveedor_id: this.form.proveedor_id,
                fecha: this.form.fecha,
                numero: this.form.numero || null,
                tasa_usd: Number(this.form.tasa_usd),
                items: this.form.items.map((r) => ({
                    producto_id: r.producto_id,
                    cantidad: Number(r.cantidad),
                    precio_unitario_usd:
                        r.precio_unitario_usd !== null
                            ? Number(r.precio_unitario_usd)
                            : null,
                    precio_unitario_bs:
                        r.precio_unitario_bs !== null
                            ? Number(r.precio_unitario_bs)
                            : null,
                })),
            };

            const method = this.editId ? "PUT" : "POST";
            const url = this.editId ? `/compras/${this.editId}` : "/compras";

            const res = await this.apiFetch(url, { method, json: payload });
            const data = await res.json().catch(() => ({}));
            if (!res.ok)
                throw new Error(data?.message || "Error al guardar la compra");

            this.showFX({
                title: this.editId
                    ? "Compra actualizada"
                    : "Compra registrada con éxito",
                corner: "br",
            });

            this.closeModal();
            await this.fetchList();
        } catch (e) {
            alert(e.message);
        }
    },

    async remove(id) {
        if (!confirm("¿Eliminar esta compra? Esto revertirá el stock.")) return;
        const res = await this.apiFetch(`/compras/${id}`, { method: "DELETE" });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
            alert(data?.message || "No se pudo eliminar");
            return;
        }
        this.showFX({ title: "Compra eliminada", corner: "tl" });
        await this.fetchList();
    },
});
