<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ----- categorias -----
        Schema::create('categorias', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('nombre', 80)->unique();
            $table->string('slug', 100)->unique();
            $table->string('descripcion', 255)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->index('slug');
        });

        // ----- clientes -----
        Schema::create('clientes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('nombre', 100);
            $table->string('apellido', 100)->nullable();
            $table->string('rif', 20)->comment('RIF o CI')->unique();
            $table->string('direccion', 255)->nullable();
            $table->string('telefono', 30)->nullable();
            $table->string('email', 120)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->index(['nombre', 'rif']);
        });

        // ----- proveedores -----
        Schema::create('proveedores', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('razon_social', 150);
            $table->string('rif', 20)->unique();
            $table->string('direccion', 255)->nullable();
            $table->string('telefono', 30)->nullable();
            $table->string('email', 120)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->index(['razon_social', 'rif']);
        });

        // ----- productos -----
        Schema::create('productos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('categoria_id')->nullable()
                ->constrained('categorias')->nullOnDelete()->cascadeOnUpdate();
            $table->string('codigo', 30)->unique();
            $table->string('nombre', 150);
            $table->decimal('precio_usd_base', 12, 4)->default(0);
            $table->decimal('precio_bs_base', 14, 2)->default(0);
            $table->decimal('tasa_usd_registro', 12, 4)->default(0);
            $table->string('unidad', 20)->default('pcs');
            $table->integer('stock')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->index(['nombre', 'categoria_id']);
        });

        // ----- vendedores -----
        Schema::create('vendedores', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('nombre', 120);
            $table->string('telefono', 30)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // ----- facturas -----
        Schema::create('facturas', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('cliente_id')->constrained('clientes');
            $table->foreignId('vendedor_id')->nullable()->constrained('vendedores')->nullOnDelete();
            $table->string('estado', 20)->default('pendiente'); // pendiente|pagada|anulada
            $table->string('tipo_documento', 20)->default('venta'); // venta|pago_directo
            $table->decimal('tasa_usd', 12, 4)->default(0);
            $table->decimal('total_usd', 14, 4)->default(0);
            $table->decimal('total_bs', 16, 2)->default(0);
            $table->decimal('saldo_usd', 14, 4)->default(0);
            $table->decimal('saldo_bs', 16, 2)->default(0);
            $table->dateTime('fecha_emision')->useCurrent();
            $table->dateTime('fecha_vencimiento')->nullable();
            $table->string('nota', 500)->nullable();
            $table->timestamps();

            $table->index(['cliente_id', 'estado', 'fecha_emision']);
            $table->index(['estado', 'fecha_emision']);
        });

        // ----- factura_detalles -----
        Schema::create('factura_detalles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('factura_id')->constrained('facturas')->cascadeOnDelete();
            $table->foreignId('producto_id')->constrained('productos');
            $table->decimal('cantidad', 12, 3);
            $table->decimal('precio_unitario_usd', 12, 4);
            $table->decimal('precio_unitario_bs', 12, 2);
            $table->decimal('subtotal_usd', 12, 4);
            $table->decimal('subtotal_bs', 12, 2);
            $table->decimal('tasa_usd_item', 12, 4)->default(0);
            $table->timestamps();

            $table->unique(['factura_id', 'producto_id']);
            $table->index(['producto_id']);
        });

        // ----- compras -----
        Schema::create('compras', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('proveedor_id')->constrained('proveedores')->cascadeOnDelete();
            $table->date('fecha')->nullable();
            $table->string('numero', 255)->nullable();
            $table->decimal('total_usd', 12, 2)->default(0);
            $table->decimal('total_bs', 14, 2)->default(0);
            $table->string('estado', 30)->default('registrada');
            $table->timestamps();
        });

        // ----- compra_detalles -----
        Schema::create('compra_detalles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('compra_id')->constrained('compras')->cascadeOnDelete();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->decimal('cantidad', 12, 2);
            $table->decimal('precio_unitario_usd', 12, 2)->default(0);
            $table->decimal('precio_unitario_bs', 14, 2)->default(0);
            $table->decimal('subtotal_usd', 12, 2)->default(0);
            $table->decimal('subtotal_bs', 14, 2)->default(0);
            $table->timestamps();
        });

        // ----- inventario_movimientos -----
        Schema::create('inventario_movimientos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('producto_id')->constrained('productos');
            $table->string('tipo', 20); // entrada|salida|ajuste
            $table->string('motivo', 30);
            $table->decimal('cantidad', 12, 3);
            $table->decimal('costo_unitario_usd', 12, 4)->nullable();
            $table->decimal('costo_unitario_bs', 14, 2)->nullable();
            $table->decimal('tasa_usd', 12, 4)->nullable();
            $table->foreignId('factura_id')->nullable()->constrained('facturas')->nullOnDelete();
            $table->foreignId('proveedor_id')->nullable()->constrained('proveedores')->nullOnDelete();
            $table->string('detalle', 255)->nullable();
            $table->timestamps();

            $table->index(['producto_id', 'tipo', 'created_at']);
        });

        // ----- pagos -----
        Schema::create('pagos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('factura_id')->constrained('facturas')->cascadeOnDelete();
            $table->string('metodo', 30);
            $table->decimal('monto_usd', 12, 4)->nullable();
            $table->decimal('monto_bs', 14, 2)->nullable();
            $table->decimal('tasa_usd', 12, 4)->nullable();
            $table->string('referencia', 120)->nullable();
            $table->date('fecha_pago')->nullable();
            $table->string('nota', 240)->nullable();
            // jsonb para Postgres; json para otros drivers (e.g., sqlite local)
            if (Schema::getConnection()->getDriverName() === 'pgsql') {
                $table->jsonb('extra')->nullable();
            } else {
                $table->json('extra')->nullable();
            }
            $table->timestamps();

            $table->index(['factura_id', 'fecha_pago']);
        });

        // ----- tasas (histórico BCV/manual) -----
        Schema::create('tasas', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->decimal('valor', 12, 4);
            $table->string('fuente', 10)->default('manual'); // bcv|manual
            $table->date('vigente_desde')->nullable();
            $table->timestamps();

            $table->index(['fuente', 'vigente_desde']);
        });

        // ----- users (ajuste de role) -----
        if (!Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role', 20)->default('cliente');
            });
        }
    }

    public function down(): void
    {
        // Orden inverso por FKs
        if (Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('role');
            });
        }

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
