// resources/js/app.js

// Axios global
import axios from "axios";
window.axios = axios;
window.axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";

const token = document
    .querySelector('meta[name="csrf-token"]')
    ?.getAttribute("content");
if (token) {
    window.axios.defaults.headers.common["X-CSRF-TOKEN"] = token;
}

// Alpine
import Alpine from "alpinejs";
window.Alpine = Alpine;

// ===== Global Components =====
Alpine.data("headerBcvRate", () => ({
    rate: null,
    loading: false,
    async fetchBcv() {
        this.loading = true;
        try {
            const res = await fetch("/api/bcv-rate", {
                headers: { Accept: "application/json" },
                cache: "no-store",
            });
            const data = await res.json();
            this.rate = Number(data?.usd ?? data?.dollar ?? null);
        } catch (e) {
            console.error("Error BCV header:", e);
            this.rate = null;
        } finally {
            this.loading = false;
        }
    },
    init() {
        this.fetchBcv();
    },
}));

// ===== Módulos por separado =====
import facturasPage from "./modules/facturas.js";
import comboRemote from "./components/combo-remote.js";
Alpine.data("facturasPage", facturasPage);
Alpine.data("comboRemote", comboRemote);

import clientesPage from "./modules/clientes.js";
Alpine.data("clientesPage", clientesPage);

import productosPage from "./modules/productos.js";
Alpine.data("productosPage", productosPage);

import comprasPage from "./modules/compras.js";
Alpine.data("comprasPage", comprasPage);

// import proveedoresPage from "./modules/proveedores.js";
// Alpine.data("proveedoresPage", proveedoresPage);

// ...

// Inicia Alpine
Alpine.start();
