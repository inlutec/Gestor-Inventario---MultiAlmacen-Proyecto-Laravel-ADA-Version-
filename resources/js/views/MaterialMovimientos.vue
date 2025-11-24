<template>
  <div class="space-y-4">
    <div class="space-y-1">
      <h1 class="text-2xl font-bold">Entradas / Salidas</h1>
      <p class="text-sm text-muted-foreground">Crear un nuevo movimiento de material</p>
    </div>

    <section class="bg-white shadow rounded p-4">
      <h2 class="text-lg font-semibold mb-3">Nuevo movimiento</h2>
      <MaterialMovimientoForm @created="onCreado" />
    </section>
  </div>
</template>

<script setup>
import MaterialMovimientoForm from './MaterialMovimientoForm.vue'

function onCreado() {
  // Hook por si queremos navegar al histórico o refrescar existencias
}
</script>
