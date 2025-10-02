// resources/js/modules/facturas.js
// Reemplazo completo — Paso 5 (JS + UX) con abonos exclusivos por moneda y tope por saldo

export default (el) => {
    // ====== helpers de combobox remoto (global) ======
    if (!window.comboRemote) {
        window.comboRemote = function comboRemote({
            endpoint,
            labelFn,
            onSelect,
        }) {
            return {
                endpoint,
                labelFn: labelFn || ((x) => x?.nombre ?? ""),
                onSelect: onSelect || (() => {}),
                query: "",
                options: [],
                open: false,
                loading: false,
                cursor: -1,
                render(opt) {
                    try {
                        return this.labelFn(opt);
                    } catch {
                        return "";
                    }
                },
                async search() {
                    const q = (this.query || "").trim();
                    this.loading = true;
                    this.open = true;
                    this.options = [];
                    this.cursor = -1;
                    try {
                        const url = new URL(
                            this.endpoint,
                            window.location.origin
                        );
                        url.searchParams.set("q", q);
                        url.searchParams.set("limit", "15");
                        const r = await fetch(url, {
                            headers: { Accept: "application/json" },
                        });
                        const d = await r.json();
                        this.options = Array.isArray(d?.items)
                            ? d.items
                            : Array.isArray(d)
                            ? d
                            : [];
                    } catch (e) {
                        console.error("comboRemote.search error", e);
                        this.options = [];
                    } finally {
                        this.loading = false;
                    }
                },
                move(delta) {
                    if (!this.options.length) return;
                    this.cursor =
                        (this.cursor + delta + this.options.length) %
                        this.options.length;
                },
                choose(i = null) {
                    const idx = i ?? this.cursor;
                    if (idx < 0 || idx >= this.options.length) return;
                    const opt = this.options[idx];
                    try {
                        this.onSelect(opt);
                    } finally {
                        this.query = this.render(opt);
                        this.open = false;
                        this.cursor = -1;
                        this.options = [];
                    }
                },
                close() {
                    this.open = false;
                    this.cursor = -1;
                },
            };
        };
    }

    // ===== dataset =====
    const initial = JSON.parse(el.dataset.initial || "[]");
    const listUrl = el.dataset.listUrl;
    const storeUrl = el.dataset.storeUrl;
    const abonarBase = el.dataset.abonarUrlBase; // /facturas
    const anularBase = el.dataset.anularUrlBase; // /facturas
    const clientesListUrl = el.dataset.clientesListUrl;
    const productosListUrl = el.dataset.productosListUrl;
    const clientesStoreUrl = el.dataset.clientesStoreUrl;
    const productosStoreUrl = el.dataset.productosStoreUrl;
    const vendedoresListUrl = el.dataset.vendedoresListUrl;
    const vendedoresStoreUrl = el.dataset.vendedoresStoreUrl;
    const bcvUrl = el.dataset.bcvUrl || "/api/bcv-rate";
    const showUrlBase = el.dataset.showUrlBase || "/facturas";

    // Métodos Bs vs USD
    const BS_METHODS = new Set([
        "efectivo_bs",
        "pmovil",
        "transferencia",
        "pos",
    ]);
    const USD_METHODS = new Set(["efectivo_usd", "zelle"]);
    const isBsMethod = (m) => BS_METHODS.has(String(m || "").toLowerCase());
    const isUsdMethod = (m) => USD_METHODS.has(String(m || "").toLowerCase());

    return {
        // ===== estado tabla =====
        items: initial,
        meta: { current_page: 1, last_page: 1, total: initial.length },
        q: "",
        estado: "",
        limit: 25,
        loading: false,

        // ===== tasa =====
        tasaActual: null,
        tasaLoading: false,

        // ===== crear =====
        createOpen: false,
        form: {
            tipo_documento: "venta",
            cliente_id: null,
            vendedor_id: null,
            fecha_emision: new Date().toISOString().slice(0, 10),
            fecha_vencimiento: null,
            tasa_usd: 0,
            nota: "",
            items: [],
            pagos: [],
            permitir_pendiente: false,
        },

        // ===== quick modals =====
        quickClienteOpen: false,
        newCliente: {
            nombre: "",
            apellido: "",
            rif: "",
            email: "",
            telefono: "",
            direccion: "",
            activo: true,
        },
        quickProductoOpen: false,
        newProducto: {
            categoria_id: "",
            nombre: "",
            unidad: "UND",
            precio_usd_base: 0,
            activo: true,
        },
        quickVendedorOpen: false,
        newVendedor: { nombre: "", telefono: "", activo: true },

        // ===== abonos ===== (solo UNA sección)
        abonoOpen: false,
        abonoFactura: null,
        abonoFacturaFull: null, // factura con pagos/detalles
        abonoLoading: false,
        abonoError: "",
        abono: {
            metodo: "efectivo_usd",
            monto_usd: 0,
            monto_bs: 0,
            tasa_usd: null,
            referencia: "",
            extra: {},
        },

        // vuelto al crear facturas
        vueltoEn: "usd",

        csrf: document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute("content"),

        // ===== helpers num =====
        toNum(v) {
            if (typeof v === "string") v = v.replace(",", ".").trim();
            const n = Number(v || 0);
            return isNaN(n) ? 0 : n;
        },
        round2(n) {
            return Math.round((Number(n) || 0) * 100) / 100;
        },
        fmtUsd(n) {
            return this.round2(n).toFixed(2);
        },
        fmtBs(n) {
            return this.round2(n).toFixed(2);
        },

        // ===== lifecycle =====
        init() {
            this.$watch("q", () => this.fetchList(1));
            this.$watch("estado", () => this.fetchList(1));
            this.$watch("limit", () => this.fetchList(1));

            // Watchers para abono dinámico
            this.$watch("abono.metodo", () => {
                this.prefillAbonoIfEmpty();
                this.normalizeAbono();
            });
            this.$watch("abono.monto_usd", () => this.normalizeAbono());
            this.$watch("abono.monto_bs", () => this.normalizeAbono());
            this.$watch("abono.tasa_usd", () => this.normalizeAbono());

            // Al cambiar la tasa de factura, normalizar pagos
            this.$watch("form.tasa_usd", () => this.normalizePagos());

            this.fetchTasa();
            this.fetchList();
        },

        // ===== tasa =====
        async fetchTasa() {
            this.tasaLoading = true;
            try {
                const r = await fetch(bcvUrl, {
                    headers: { Accept: "application/json" },
                    cache: "no-store",
                });
                const d = await r.json();
                this.tasaActual = Number(d?.usd ?? d?.dollar ?? null) || null;
                if (!this.form.tasa_usd && this.tasaActual)
                    this.form.tasa_usd = Number(this.tasaActual);
                if (!this.abono.tasa_usd && this.tasaActual)
                    this.abono.tasa_usd = Number(this.tasaActual);
            } catch (e) {
                console.error("BCV error:", e);
            } finally {
                this.tasaLoading = false;
            }
        },
        usarTasaActual() {
            if (this.tasaActual) this.form.tasa_usd = Number(this.tasaActual);
        },

        // ===== listado =====
        async fetchList(page = 1) {
            this.loading = true;
            try {
                const qs = new URLSearchParams({
                    q: this.q || "",
                    estado: this.estado || "",
                    limit: this.limit || 25,
                    page,
                });
                const r = await fetch(`${listUrl}?${qs.toString()}`, {
                    headers: { Accept: "application/json" },
                });
                const d = await r.json();
                this.items = d.items || [];
                this.meta = d.meta || {
                    current_page: 1,
                    last_page: 1,
                    total: 0,
                };
            } catch (e) {
                console.error(e);
            } finally {
                this.loading = false;
            }
        },
        nextPage() {
            if (this.meta.current_page < this.meta.last_page)
                this.fetchList(this.meta.current_page + 1);
        },
        prevPage() {
            if (this.meta.current_page > 1)
                this.fetchList(this.meta.current_page - 1);
        },

        // ===== crear =====
        openCreate() {
            this.resetForm();
            if (this.tasaActual) this.form.tasa_usd = Number(this.tasaActual);
            this.addItem();
            this.addPago();
            this.createOpen = true;
        },
        closeCreate() {
            this.createOpen = false;
        },

        // items
        addItem() {
            if (this.form.tipo_documento !== "venta") return;
            this.form.items.push({
                producto_id: null,
                nombre: "",
                cantidad: 1,
                precio_unitario_usd: 0,
                tasa_usd_item: null,
            });
        },
        removeItem(i) {
            this.form.items.splice(i, 1);
        },
        selectProducto(idx, p) {
            const it = this.form.items[idx];
            it.producto_id = p.id;
            it.nombre = `${p.codigo ?? ""} ${p.nombre}`.trim();
            it.precio_unitario_usd = this.round2(p.precio_usd_base ?? 0);
            it.tasa_usd_item = null;
        },

        // quick cliente
        openNewCliente() {
            this.newCliente = {
                nombre: "",
                apellido: "",
                rif: "",
                email: "",
                telefono: "",
                direccion: "",
                activo: true,
            };
            this.quickClienteOpen = true;
        },
        async submitQuickCliente() {
            try {
                const r = await fetch(clientesStoreUrl, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        Accept: "application/json",
                        "X-CSRF-TOKEN": this.csrf,
                    },
                    body: JSON.stringify(this.newCliente),
                });
                if (r.status === 422) {
                    console.warn(await r.json());
                    return;
                }
                const d = await r.json();
                const c = d.data;
                this.form.cliente_id = c.id;
                this.quickClienteOpen = false;
            } catch (e) {
                console.error(e);
            }
        },

        // quick producto
        openNewProducto() {
            this.newProducto = {
                categoria_id: "",
                nombre: "",
                unidad: "UND",
                precio_usd_base: 0,
                activo: true,
            };
            this.quickProductoOpen = true;
        },
        async submitQuickProducto() {
            const payload = {
                ...this.newProducto,
                tasa_usd_registro: Number(
                    this.tasaActual || this.form.tasa_usd || 0
                ),
                precio_bs_base: null,
            };
            try {
                const r = await fetch(productosStoreUrl, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        Accept: "application/json",
                        "X-CSRF-TOKEN": this.csrf,
                    },
                    body: JSON.stringify(payload),
                });
                if (r.status === 422) {
                    console.warn(await r.json());
                    return;
                }
                const d = await r.json();
                const p = d.data;
                const idx = this.form.items.findIndex((x) => !x.producto_id);
                const obj = {
                    id: p.id,
                    nombre: p.nombre,
                    codigo: p.codigo,
                    precio_usd_base: p.precio_usd_base,
                };
                if (idx >= 0) this.selectProducto(idx, obj);
                else {
                    this.addItem();
                    this.selectProducto(this.form.items.length - 1, obj);
                }
                this.quickProductoOpen = false;
            } catch (e) {
                console.error(e);
            }
        },

        // quick vendedor
        openNewVendedor() {
            this.newVendedor = { nombre: "", telefono: "", activo: true };
            this.quickVendedorOpen = true;
        },
        async submitQuickVendedor() {
            try {
                const r = await fetch(vendedoresStoreUrl, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        Accept: "application/json",
                        "X-CSRF-TOKEN": this.csrf,
                    },
                    body: JSON.stringify(this.newVendedor),
                });
                if (r.status === 422) {
                    console.warn(await r.json());
                    return;
                }
                const d = await r.json();
                const v = d.data;
                this.form.vendedor_id = v.id;
                this.quickVendedorOpen = false;
            } catch (e) {
                console.error(e);
            }
        },

        // pagos (al crear factura)
        addPago() {
            this.form.pagos.push({
                metodo: "efectivo_usd",
                monto_usd: 0,
                monto_bs: 0,
                tasa_usd: this.form.tasa_usd,
                referencia: "",
                extra: {},
            });
        },
        removePago(i) {
            this.form.pagos.splice(i, 1);
        },

        // ===== normalizadores =====
        normalizePagos() {
            const tFactura = this.toNum(this.form.tasa_usd);
            this.form.pagos.forEach((pg) => {
                const t = this.toNum(pg.tasa_usd || tFactura);
                let usd = this.toNum(pg.monto_usd);
                let bs = this.toNum(pg.monto_bs);

                if (this.isBs(pg.metodo)) {
                    if (usd > 0 && t > 0 && bs === 0) bs = this.round2(usd * t);
                    pg.monto_usd = 0;
                    pg.monto_bs = this.round2(bs);
                } else {
                    if (bs > 0 && t > 0 && usd === 0) usd = this.round2(bs / t);
                    pg.monto_bs = 0;
                    pg.monto_usd = this.round2(usd);
                }
            });
        },

        // ===== helpers abono =====
        // <-- Estas dos funciones resuelven el error "this.isAbonoBs is not a function"
        isAbonoBs() {
            return isBsMethod(this.abono?.metodo);
        },
        isAbonoUsd() {
            return isUsdMethod(this.abono?.metodo);
        },

        abonoSaldoUsd() {
            const fresh = this.toNum(this.abonoFacturaFull?.saldo_usd);
            if (fresh > 0) return fresh;
            return this.toNum(this.abonoFactura?.saldo_usd ?? 0);
        },
        abonoTasa() {
            return this.toNum(
                this.abono.tasa_usd || this.tasaActual || this.form.tasa_usd
            );
        },
        prefillAbonoIfEmpty() {
            const saldo = this.abonoSaldoUsd();
            const t = this.abonoTasa();
            if (
                this.toNum(this.abono.monto_usd) <= 0 &&
                this.toNum(this.abono.monto_bs) <= 0 &&
                saldo > 0
            ) {
                if (this.isAbonoBs()) {
                    this.abono.monto_usd = 0;
                    this.abono.monto_bs = t > 0 ? this.round2(saldo * t) : 0;
                } else {
                    this.abono.monto_usd = this.round2(saldo);
                    this.abono.monto_bs = 0;
                }
            }
        },
        normalizeAbono() {
            const t = this.abonoTasa();
            const saldo = this.abonoSaldoUsd();
            let usd = this.toNum(this.abono.monto_usd);
            let bs = this.toNum(this.abono.monto_bs);

            if (this.isAbonoBs()) {
                // Si llenan USD por error, lo pasamos a Bs y dejamos USD=0
                if (usd > 0 && bs === 0 && t > 0) bs = usd * t;
                usd = 0;
                if (t > 0) bs = Math.min(bs, this.round2(saldo * t));
                this.abono.monto_usd = 0;
                this.abono.monto_bs = this.round2(bs);
            } else {
                if (bs > 0 && usd === 0 && t > 0) usd = bs / t;
                bs = 0;
                usd = Math.min(usd, this.round2(saldo));
                this.abono.monto_usd = this.round2(usd);
                this.abono.monto_bs = 0;
            }
        },
        abonoEqUsd() {
            const t = this.abonoTasa();
            if (this.isAbonoBs())
                return t > 0
                    ? this.round2(this.toNum(this.abono.monto_bs) / t)
                    : 0;
            return this.round2(this.toNum(this.abono.monto_usd));
        },
        abonoEqBs() {
            const t = this.abonoTasa();
            if (this.isAbonoBs())
                return this.round2(this.toNum(this.abono.monto_bs));
            return t > 0
                ? this.round2(this.toNum(this.abono.monto_usd) * t)
                : 0;
        },
        abonoSaldoPost() {
            return this.round2(
                Math.max(0, this.abonoSaldoUsd() - this.abonoEqUsd())
            );
        },
        abonoDisabled() {
            return (
                this.abonoEqUsd() <= 0 ||
                !this.abono.metodo ||
                this.abonoLoading
            );
        },
        useBcvForAbono() {
            if (this.tasaActual) this.abono.tasa_usd = Number(this.tasaActual);
        },
        useFacturaRateForAbono() {
            if (this.abonoFactura?.tasa_usd)
                this.abono.tasa_usd = Number(this.abonoFactura.tasa_usd);
            else if (this.form.tasa_usd)
                this.abono.tasa_usd = Number(this.form.tasa_usd);
        },
        quickPercent(p) {
            const usd = this.round2(this.abonoSaldoUsd() * (p / 100));
            this.abono.monto_usd = usd;
            const t = this.abonoTasa();
            if (t > 0) this.abono.monto_bs = this.round2(usd * t);
            this.normalizeAbono();
        },

        // ===== totales =====
        subtotalUsd() {
            if (this.form.tipo_documento !== "venta") return 0;
            return this.form.items.reduce(
                (acc, it) =>
                    acc +
                    this.toNum(it.cantidad) *
                        this.toNum(it.precio_unitario_usd),
                0
            );
        },
        totalUsd() {
            if (this.form.tipo_documento === "venta")
                return this.round2(this.subtotalUsd()); // SIN IVA
            return this.round2(this.pagadoUsd());
        },
        totalBs() {
            return this.round2(
                this.totalUsd() * this.toNum(this.form.tasa_usd)
            );
        },
        pagadoUsd() {
            this.normalizePagos(); // asegurar exclusividad
            return this.form.pagos.reduce((acc, pg) => {
                const t = this.toNum(pg.tasa_usd || this.form.tasa_usd);
                if (this.isBs(pg.metodo))
                    return acc + (t > 0 ? this.toNum(pg.monto_bs) / t : 0);
                return acc + this.toNum(pg.monto_usd);
            }, 0);
        },
        saldoUsd() {
            if (this.form.tipo_documento !== "venta") return 0;
            return Math.max(0, this.round2(this.totalUsd() - this.pagadoUsd()));
        },
        vueltoTexto() {
            if (this.form.tipo_documento !== "venta") return "0.00";
            const over = this.pagadoUsd() - this.totalUsd();
            if (over <= 0) return "0.00";
            if (this.vueltoEn === "usd") return `${this.fmtUsd(over)} USD`;
            const t = this.toNum(this.form.tasa_usd);
            return t > 0 ? `${this.fmtBs(over * t)} Bs` : "—";
        },

        // ===== submit =====
        async submitCreate(_retrying = false) {
            if (!this.form.cliente_id) {
                alert("Seleccione un cliente");
                return;
            }

            if (this.form.tipo_documento === "venta") {
                if (
                    !this.form.items.length ||
                    !this.form.items.every(
                        (x) => x.producto_id && this.toNum(x.cantidad) > 0
                    )
                ) {
                    alert("Agregue al menos un ítem válido");
                    return;
                }
            } else {
                if (this.round2(this.pagadoUsd()) <= 0) {
                    alert("Agregue al menos un pago para el pago directo.");
                    return;
                }
            }

            this.loading = true;
            try {
                const payload = {
                    tipo_documento: this.form.tipo_documento,
                    cliente_id: this.form.cliente_id,
                    vendedor_id: this.form.vendedor_id || null,
                    fecha_emision: this.form.fecha_emision,
                    fecha_vencimiento: this.form.fecha_vencimiento || null,
                    nota: this.form.nota || null,
                    tasa_usd: this.toNum(this.form.tasa_usd),
                    permitir_pendiente: !!this.form.permitir_pendiente,
                    items:
                        this.form.tipo_documento === "venta"
                            ? this.form.items.map((it) => ({
                                  producto_id: it.producto_id,
                                  cantidad: this.toNum(it.cantidad),
                                  precio_unitario_usd: this.round2(
                                      it.precio_unitario_usd
                                  ),
                                  tasa_usd_item: it.tasa_usd_item
                                      ? this.toNum(it.tasa_usd_item)
                                      : null,
                              }))
                            : [],
                    pagos: this.form.pagos.map((pg) => ({
                        metodo: pg.metodo,
                        monto_usd: this.round2(pg.monto_usd) || null,
                        monto_bs: this.round2(pg.monto_bs) || null,
                        tasa_usd:
                            this.toNum(pg.tasa_usd || this.form.tasa_usd) ||
                            null,
                        referencia: pg.referencia || null,
                        extra:
                            pg.extra && typeof pg.extra === "object"
                                ? pg.extra
                                : null,
                    })),
                };

                const r = await fetch(storeUrl, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        Accept: "application/json",
                        "X-CSRF-TOKEN": this.csrf,
                    },
                    body: JSON.stringify(payload),
                });

                if (r.status === 422) {
                    let json = {};
                    try {
                        json = await r.json();
                    } catch {}
                    const faltante = json?.faltante_usd ?? null;
                    if (
                        this.form.tipo_documento === "venta" &&
                        faltante &&
                        !_retrying &&
                        confirm(
                            `El pago no cubre el total. Faltan ${Number(
                                faltante
                            ).toFixed(
                                2
                            )} USD.\n\n¿Crear cuenta pendiente por ese monto?`
                        )
                    ) {
                        this.form.permitir_pendiente = true;
                        await this.submitCreate(true);
                        return;
                    }
                    const errs = json?.errors || {};
                    const lista = Object.values(errs).flat().join("\n• ");
                    alert(
                        "Corrige los siguientes campos:\n• " +
                            (lista || json?.message || "Datos inválidos")
                    );
                    return;
                }

                if (!r.ok) {
                    let bodyText = await r.text();
                    let json = null;
                    try {
                        json = JSON.parse(bodyText);
                    } catch {}
                    console.error("[FACTURA] fallo", {
                        status: r.status,
                        json,
                        bodyText,
                        payload,
                    });
                    const msg = json?.message || "Error al guardar factura";
                    const det = json?.error ? `\n\nDetalle: ${json.error}` : "";
                    alert(msg + det);
                    return;
                }

                this.createOpen = false;
                await this.fetchList(this.meta.current_page);
                this.toast("Documento guardado con éxito");
            } catch (e) {
                console.error("[FACTURA] excepción", e);
                alert("Error guardando la factura (excepción en cliente)");
            } finally {
                this.loading = false;
            }
        },

        // ===== Abonos =====
        async openAbono(f) {
            this.abonoFactura = f;
            this.abonoFacturaFull = null;
            this.abonoError = "";
            this.abono = {
                metodo: "efectivo_usd",
                monto_usd: 0,
                monto_bs: 0,
                tasa_usd: this.tasaActual || this.form.tasa_usd || null,
                referencia: "",
                extra: {},
            };
            this.prefillAbonoIfEmpty();
            this.abonoOpen = true;

            // Cargar historial completo (pagos, saldo fresco)
            try {
                const r = await fetch(`${showUrlBase}/${f.id}`, {
                    headers: { Accept: "application/json" },
                });
                if (r.ok) this.abonoFacturaFull = await r.json();
            } catch (e) {
                console.warn("No se pudo cargar la factura completa", e);
            }

            // Autofocus
            this.$nextTick(() => {
                const first = document.querySelector(
                    "#abono-modal input[name='usd']"
                );
                if (first) first.focus();
            });
        },
        async submitAbono() {
            if (!this.abonoFactura) return;
            this.normalizeAbono();
            this.abonoError = "";
            this.abonoLoading = true;

            try {
                const url = `${abonarBase}/${this.abonoFactura.id}/pago`;
                const r = await fetch(url, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        Accept: "application/json",
                        "X-CSRF-TOKEN": this.csrf,
                    },
                    body: JSON.stringify(this.abono),
                });

                if (!r.ok) {
                    let json = null;
                    try {
                        json = await r.json();
                    } catch {}
                    console.error("[ABONO] fallo", { status: r.status, json });
                    this.abonoError =
                        json?.message || "Error registrando abono";
                    return;
                }

                await this.fetchList(this.meta.current_page);
                try {
                    const rs = await fetch(
                        `${showUrlBase}/${this.abonoFactura.id}`,
                        { headers: { Accept: "application/json" } }
                    );
                    if (rs.ok) this.abonoFacturaFull = await rs.json();
                } catch {}

                this.toast("Abono registrado");
                this.abonoOpen = false;
            } catch (e) {
                console.error(e);
                this.abonoError = "Error registrando abono";
            } finally {
                this.abonoLoading = false;
            }
        },

        // ===== Anular =====
        async anular(f) {
            if (!f?.id) return;
            if (!confirm(`¿Seguro que deseas anular la factura #${f.id}?`))
                return;
            this.loading = true;
            try {
                const url = `${anularBase}/${f.id}/anular`;
                const r = await fetch(url, {
                    method: "POST",
                    headers: {
                        Accept: "application/json",
                        "X-CSRF-TOKEN": this.csrf,
                    },
                });
                if (!r.ok) {
                    let bodyText = await r.text();
                    let json = null;
                    try {
                        json = JSON.parse(bodyText);
                    } catch {}
                    const msg = json?.message || "Error anulando factura";
                    const det = json?.error ? `\n\nDetalle: ${json.error}` : "";
                    alert(msg + det);
                    return;
                }
                await this.fetchList(this.meta.current_page);
                this.toast("Factura anulada");
            } catch (e) {
                console.error(e);
                alert("Error anulando factura");
            } finally {
                this.loading = false;
            }
        },

        // ===== Reset =====
        resetForm() {
            this.form = {
                tipo_documento: "venta",
                cliente_id: null,
                vendedor_id: null,
                fecha_emision: new Date().toISOString().slice(0, 10),
                fecha_vencimiento: null,
                tasa_usd: Number(this.tasaActual || 0),
                nota: "",
                items: [],
                pagos: [],
                permitir_pendiente: false,
            };
            this.vueltoEn = "usd";
        },

        // ===== UI =====
        toast(message = "Operación exitosa") {
            const w = document.createElement("div");
            w.innerHTML = `
      <div class="fixed inset-0 z-[60] flex items-center justify-center bg-black/40">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-sm p-6 text-center">
          <div class="flex items-center justify-center mb-3">
            <img src="/logo.png" class="w-14 h-14 rounded bg-emerald-600/10 p-2" alt="Logo">
          </div>
          <p class="text-base font-medium text-gray-800 dark:text-gray-100">${message}</p>
        </div>
      </div>`;
            document.body.appendChild(w);
            setTimeout(() => w.remove(), 1200);
        },
        isBs(m) {
            return isBsMethod(m);
        },
        isUsd(m) {
            return isUsdMethod(m);
        },
    };
};
