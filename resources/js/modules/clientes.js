// resources/js/modules/clientes.js
export default (el) => {
    const initial = JSON.parse(el.dataset.initial || "[]");
    const listUrl = el.dataset.listUrl;
    const storeUrl = el.dataset.storeUrl;
    const updateUrlBase = el.dataset.updateUrlBase;

    return {
        // Estado
        items: initial,
        meta: { current_page: 1, last_page: 1, total: initial.length },
        q: "",
        limit: 25,
        loading: false,

        // Modales
        createOpen: false,
        editOpen: false,
        deleteOpen: false,

        // Formularios
        form: {
            id: null,
            nombre: "",
            apellido: "",
            rif: "",
            direccion: "",
            telefono: "",
            email: "",
            activo: true,
        },
        errors: {},

        // Aux
        deleteId: null,
        csrf: document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute("content"),

        init() {
            this.$watch("q", () => this.fetchList(1));
            this.$watch("limit", () => this.fetchList(1));
            this.fetchList();
        },

        // Helpers
        fullName(c) {
            return [c?.nombre, c?.apellido].filter(Boolean).join(" ") || "—";
        },

        // Listado
        async fetchList(page = 1) {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    q: this.q || "",
                    limit: this.limit || 25,
                    page: page,
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
                this.successToast("Cliente creado con éxito");
            } catch (e) {
                console.error(e);
            } finally {
                this.loading = false;
            }
        },

        // Editar
        openEdit(c) {
            this.form = {
                id: c.id,
                nombre: c.nombre || "",
                apellido: c.apellido || "",
                rif: c.rif || "",
                direccion: c.direccion || "",
                telefono: c.telefono || "",
                email: c.email || "",
                activo: !!c.activo,
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
                this.successToast("Cliente actualizado con éxito");
            } catch (e) {
                console.error(e);
            } finally {
                this.loading = false;
            }
        },

        // Eliminar
        confirmDelete(c) {
            this.deleteId = c.id;
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
                this.successToast("Cliente eliminado con éxito");
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
                nombre: "",
                apellido: "",
                rif: "",
                direccion: "",
                telefono: "",
                email: "",
                activo: true,
            };
        },
        payloadFrom(o) {
            return {
                nombre: (o.nombre || "").trim(),
                apellido: o.apellido || null,
                rif: (o.rif || "").trim(),
                direccion: o.direccion || null,
                telefono: o.telefono || null,
                email: o.email || null,
                activo: !!o.activo,
            };
        },

        // Toast con logo
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
