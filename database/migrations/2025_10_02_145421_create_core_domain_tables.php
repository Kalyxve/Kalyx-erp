<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ----- categorias -----
        Schema::create('categorias', function (Blueprint \) {
            \->bigIncrements('id');
            \->string('nombre', 80)->unique();
            \->string('slug', 100)->unique();
            \->string('descripcion', 255)->nullable();
            \->boolean('activo')->default(true);
            \->timestamps();
            \->index('slug');
        });

        // ----- clientes -----
        Schema::create('clientes', function (Blueprint \) {
            \->bigIncrements('id');
            \->string('nombre', 100);
            \->string('apellido', 100)->nullable();
            \->string('rif', 20)->comment('RIF o CI')->unique();
            \->string('direccion', 255)->nullable();
            \->string('telefono', 30)->nullable();
            \->string('email', 120)->nullable();
            \->boolean('activo')->default(true);
            \->timestamps();
            \->index(['nombre', 'rif']);
        });

        // ----- proveedores -----
        Schema::create('proveedores', function (Blueprint \) {
            \->bigIncrements('id');
            \->string('razon_social', 150);
            \->string('rif', 20)->unique();
            \->string('direccion', 255)->nullable();
            \->string('telefono', 30)->nullable();
            \->string('email', 120)->nullable();
            \->boolean('activo')->default(true);
            \->timestamps();
            \->index(['razon_social', 'rif']);
        });

        // ----- productos -----
        Schema::create('productos', function (Blueprint \) {
            \->bigIncrements('id');
            \->foreignId('categoria_id')->nullable()->constrained('categorias')->nullOnDelete()->cascadeOnUpdate();
            \->string('codigo', 30)->unique();
            \->string('nombre', 150);
            \->decimal('precio_usd_base', 12, 4)->default(0);
            \->decimal('precio_bs_base', 14, 2)->default(0);
            \->decimal('tasa_usd_registro', 12, 4)->default(0);
            \->string('unidad', 20)->default('pcs');
            \->integer('stock')->default(0);
            \->boolean('activo')->default(true);
            \->timestamps();
            \->index(['nombre', 'categoria_id']);
        });

        // ----- vendedores -----
        Schema::create('vendedores', function (Blueprint \) {
            \->bigIncrements('id');
            \->string('nombre', 120);
            \->string('telefono', 30)->nullable();
            \->boolean('activo')->default(true);
            \->timestamps();
        });

        // ----- facturas -----
        Schema::create('facturas', function (Blueprint \) {
            \->bigIncrements('id');
            \->foreignId('cliente_id')->constrained('clientes');
            \->foreignId('vendedor_id')->nullable()->constrained('vendedores')->nullOnDelete();
            \->string('estado', 20)->default('pendiente'); // (pendiente|pagada|anulada)
            \->string('tipo_documento', 20)->default('venta'); // (venta|pago_directo)
            \->decimal('tasa_usd', 12, 4)->default(0);
            \->decimal('total_usd', 14, 4)->default(0);
            \->decimal('total_bs', 16, 2)->default(0);
            \->decimal('saldo_usd', 14, 4)->default(0);
            \->decimal('saldo_bs', 16, 2)->default(0);
            \->dateTime('fecha_emision')->useCurrent();
            \->dateTime('fecha_vencimiento')->nullable();
            \->string('nota', 500)->nullable();
            \->timestamps();

            \->index(['cliente_id','estado','fecha_emision']);
            \->index(['estado','fecha_emision']);
        });

        // ----- factura_detalles -----
        Schema::create('factura_detalles', function (Blueprint \) {
            \->bigIncrements('id');
            \->foreignId('factura_id')->constrained('facturas')->cascadeOnDelete();
            \->foreignId('producto_id')->constrained('productos');
            \->decimal('cantidad', 12, 3);
            \->decimal('precio_unitario_usd', 12, 4);
            \->decimal('precio_unitario_bs', 12, 2);
            \->decimal('subtotal_usd', 12, 4);
            \->decimal('subtotal_bs', 12, 2);
            \->decimal('tasa_usd_item', 12, 4)->default(0);
            \->timestamps();

            \->unique(['factura_id','producto_id']);
            \->index(['producto_id']);
        });

        // ----- compras -----
        Schema::create('compras', function (Blueprint \) {
            \->bigIncrements('id');
            \->foreignId('proveedor_id')->constrained('proveedores')->cascadeOnDelete();
            \->date('fecha')->nullable();
            \->string('numero', 255)->nullable();
            \->decimal('total_usd', 12, 2)->default(0);
            \->decimal('total_bs', 14, 2)->default(0);
            \->string('estado', 30)->default('registrada');
            \->timestamps();
        });

        // ----- compra_detalles -----
        Schema::create('compra_detalles', function (Blueprint \) {
            \->bigIncrements('id');
            \->foreignId('compra_id')->constrained('compras')->cascadeOnDelete();
            \->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            \->decimal('cantidad', 12, 2);
            \->decimal('precio_unitario_usd', 12, 2)->default(0);
            \->decimal('precio_unitario_bs', 14, 2)->default(0);
            \->decimal('subtotal_usd', 12, 2)->default(0);
            \->decimal('subtotal_bs', 14, 2)->default(0);
            \->timestamps();
        });

        // ----- inventario_movimientos -----
        Schema::create('inventario_movimientos', function (Blueprint \) {
            \->bigIncrements('id');
            \->foreignId('producto_id')->constrained('productos');
            \->string('tipo', 20); // entrada|salida|ajuste
            \->string('motivo', 30);
            \->decimal('cantidad', 12, 3);
            \->decimal('costo_unitario_usd', 12, 4)->nullable();
            \->decimal('costo_unitario_bs', 14, 2)->nullable();
            \->decimal('tasa_usd', 12, 4)->nullable();
            \->foreignId('factura_id')->nullable()->constrained('facturas')->nullOnDelete();
            \->foreignId('proveedor_id')->nullable()->constrained('proveedores')->nullOnDelete();
            \->string('detalle', 255)->nullable();
            \->timestamps();

            \->index(['producto_id','tipo','created_at']);
        });

        // ----- pagos -----
        Schema::create('pagos', function (Blueprint \) {
            \->bigIncrements('id');
            \->foreignId('factura_id')->constrained('facturas')->cascadeOnDelete();
            \->string('metodo', 30);
            \->decimal('monto_usd', 12, 4)->nullable();
            \->decimal('monto_bs', 14, 2)->nullable();
            \->decimal('tasa_usd', 12, 4)->nullable();
            \->string('referencia', 120)->nullable();
            \->date('fecha_pago')->nullable();
            \->string('nota', 240)->nullable();
            \->jsonb('extra')->nullable();
            \->timestamps();

            \->index(['factura_id','fecha_pago']);
        });

        // ----- tasas (histórico BCV/manual) -----
        Schema::create('tasas', function (Blueprint \) {
            \->bigIncrements('id');
            \->decimal('valor', 12, 4);
            \->string('fuente', 10)->default('manual'); // bcv|manual
            \->date('vigente_desde')->nullable();
            \->timestamps();

            \->index(['fuente','vigente_desde']);
        });

        // ----- users (ajuste de role) -----
        Schema::table('users', function (Blueprint \) {
            if (!Schema::hasColumn('users','role')) {
                \->string('role', 20)->default('cliente');
            }
        });
    }

    public function down(): void
    {
        // Orden inverso por FKs
        Schema::table('users', function (Blueprint \) {
            if (Schema::hasColumn('users','role')) {
                \->dropColumn('role');
            }
        });
        Schema::dropIfExists('tasas');
        Schema::dropIfExists('pagos');
        Schema::dropIfExists('inventario_movimientos');
        Schema::dropIfExists('compra_detalles');
        Schema::dropIfExists('compras');
        Schema::dropIfExists('factura_detalles');
        Schema::dropIfExists('facturas');
        Schema::dropIfExists('vendedores');
        Schema::dropIfExists('productos');
        Schema::dropIfExists('proveedores');
        Schema::dropIfExists('clientes');
        Schema::dropIfExists('categorias');
    }
};
