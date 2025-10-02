// resources/js/modules/productos.js
export default (el) => {
    const initial = JSON.parse(el.dataset.initial || "[]");
    const categorias = JSON.parse(el.dataset.categories || "[]");
    const listUrl = el.dataset.listUrl;
    const storeUrl = el.dataset.storeUrl;
    const updateUrlBase = el.dataset.updateUrlBase;
    const bcvUrl = el.dataset.bcvUrl || "/api/bcv-rate";

    return {
        // Estado
        items: initial,
        categorias,
        meta: { current_page: 1, last_page: 1, total: initial.length },
        q: "",
        categoria_id: 0,
        limit: 25,
        loading: false,

        // Tasa BCV
        tasaActual: null,
        tasaLoading: false,

        // Modales
        createOpen: false,
        editOpen: false,
        deleteOpen: false,

        // Formularios
        form: {
            id: null,
            categoria_id: "",
            nombre: "",
            unidad: "UND",
            stock: 0,
            precio_usd_base: 0,
            precio_bs_base: 0,
            tasa_usd_registro: 0,
            activo: true,
        },
        errors: {},
        deleteId: null,

        csrf: document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute("content"),

        init() {
            this.$watch("q", () => this.fetchList(1));
            this.$watch("limit", () => this.fetchList(1));
            this.$watch("categoria_id", () => this.fetchList(1));
            // Recalcular Bs si cambia USD en el form (crear/editar)
            this.$watch("form.precio_usd_base", () => this.recalcularBs());
            this.$watch("form.tasa_usd_registro", () => this.recalcularBs());

            this.fetchTasa();
            this.fetchList();
        },

        async fetchTasa() {
            this.tasaLoading = true;
            try {
                const res = await fetch(bcvUrl, {
                    headers: { Accept: "application/json" },
                    cache: "no-store",
                });
                const data = await res.json();
                this.tasaActual =
                    Number(data?.usd ?? data?.dollar ?? null) || null;
            } catch (e) {
                console.error("Error tasa BCV:", e);
                this.tasaActual = null;
            } finally {
                this.tasaLoading = false;
            }
        },

        recalcularBs() {
            const usd = Number(this.form.precio_usd_base || 0);
            const tasa = Number(this.form.tasa_usd_registro || 0);
            if (usd > 0 && tasa > 0) {
                this.form.precio_bs_base = Number((usd * tasa).toFixed(2));
            } else {
                this.form.precio_bs_base = 0;
            }
        },

        usarTasaActual() {
            if (this.tasaActual) {
                this.form.tasa_usd_registro = Number(this.tasaActual);
            }
        },

        recalcularConTasaActual() {
            if (this.tasaActual) {
                this.form.tasa_usd_registro = Number(this.tasaActual);
                this.recalcularBs();
            }
        },

        // Listado
        async fetchList(page = 1) {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    q: this.q || "",
                    limit: this.limit || 25,
                    page: page,
                    categoria_id: this.categoria_id || 0,
                });
                const res = await fetch(`${listUrl}?${params.toString()}`, {
                    headers: { Accept: "application/json" },
                });
                const data = await res.json();
                this.items = data.items || [];
                this.meta = data.meta || {
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

        // Crear
        openCreate() {
            this.resetForm();
            // por defecto, usa la tasa del día
            if (this.tasaActual)
                this.form.tasa_usd_registro = Number(this.tasaActual);
            this.recalcularBs();
            this.createOpen = true;
            this.errors = {};
        },
        closeCreate() {
            this.createOpen = false;
        },
        async submitCreate() {
            this.loading = true;
            this.errors = {};
            try {
                const payload = this.payloadFrom(this.form);
                const res = await fetch(storeUrl, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        Accept: "application/json",
                        "X-CSRF-TOKEN": this.csrf,
                    },
                    body: JSON.stringify(payload),
                });

                if (res.status === 422) {
                    const data = await res.json();
                    this.errors = data.errors || {};
                    return;
                }
                if (!res.ok) throw new Error("Error al crear");

                this.createOpen = false;
                await this.fetchList(this.meta.current_page);
                this.successToast("Producto creado con éxito");
            } catch (e) {
                console.error(e);
            } finally {
                this.loading = false;
            }
        },

        // Editar
        openEdit(p) {
            this.form = {
                id: p.id,
                categoria_id: p.categoria_id,
                nombre: p.nombre || "",
                unidad: p.unidad || "UND",
                stock: Number(p.stock ?? 0),
                precio_usd_base: Number(p.precio_usd_base ?? 0),
                precio_bs_base: Number(p.precio_bs_base ?? 0),
                tasa_usd_registro: Number(
                    p.tasa_usd_registro ?? (this.tasaActual || 0)
                ),
                activo: !!p.activo,
            };
            this.errors = {};
            this.editOpen = true;
        },
        closeEdit() {
            this.editOpen = false;
        },
        async submitEdit() {
            if (!this.form.id) return;
            this.loading = true;
            this.errors = {};
            try {
                const url = `${updateUrlBase}/${this.form.id}`;
                const payload = this.payloadFrom(this.form);

                const res = await fetch(url, {
                    method: "PUT",
                    headers: {
                        "Content-Type": "application/json",
                        Accept: "application/json",
                        "X-CSRF-TOKEN": this.csrf,
                    },
                    body: JSON.stringify(payload),
                });

                if (res.status === 422) {
                    const data = await res.json();
                    this.errors = data.errors || {};
                    return;
                }
                if (!res.ok) throw new Error("Error al actualizar");

                this.editOpen = false;
                await this.fetchList(this.meta.current_page);
                this.successToast("Producto actualizado con éxito");
            } catch (e) {
                console.error(e);
            } finally {
                this.loading = false;
            }
        },

        // Eliminar
        confirmDelete(p) {
            this.deleteId = p.id;
            this.deleteOpen = true;
        },
        async submitDelete() {
            if (!this.deleteId) return;
            this.loading = true;
            try {
                const url = `${updateUrlBase}/${this.deleteId}`;
                const res = await fetch(url, {
                    method: "DELETE",
                    headers: {
                        Accept: "application/json",
                        "X-CSRF-TOKEN": this.csrf,
                    },
                });
                if (!res.ok) throw new Error("Error al eliminar");

                this.deleteOpen = false;
                const willBeEmpty =
                    this.items.length === 1 && this.meta.current_page > 1;
                await this.fetchList(
                    willBeEmpty
                        ? this.meta.current_page - 1
                        : this.meta.current_page
                );
                this.successToast("Producto eliminado con éxito");
            } catch (e) {
                console.error(e);
            } finally {
                this.loading = false;
                this.deleteId = null;
            }
        },

        // Utils
        resetForm() {
            this.form = {
                id: null,
                categoria_id: "",
                nombre: "",
                unidad: "UND",
                stock: 0,
                precio_usd_base: 0,
                precio_bs_base: 0,
                tasa_usd_registro: 0,
                activo: true,
            };
        },
        payloadFrom(o) {
            return {
                categoria_id: o.categoria_id || null,
                nombre: (o.nombre || "").trim(),
                unidad: (o.unidad || "UND").trim(),
                stock: Number(o.stock ?? 0),
                precio_usd_base: Number(o.precio_usd_base ?? 0),
                tasa_usd_registro: Number(o.tasa_usd_registro ?? 0),
                precio_bs_base: Number(o.precio_bs_base ?? 0), // el backend recalcula si no lo pasas
                activo: !!o.activo,
            };
        },

        successToast(message = "Operación exitosa") {
            const wrap = document.createElement("div");
            wrap.innerHTML = `
        <div class="fixed inset-0 z-[60] flex items-center justify-center bg-black/40">
          <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-sm p-6 text-center">
            <div class="flex items-center justify-center mb-3">
              <img src="/logo.png" class="w-14 h-14 rounded bg-emerald-600/10 p-2" alt="Logo">
            </div>
            <p class="text-base font-medium text-gray-800 dark:text-gray-100">${message}</p>
          </div>
        </div>`;
            document.body.appendChild(wrap);
            setTimeout(() => wrap.remove(), 1500);
        },
    };
};
