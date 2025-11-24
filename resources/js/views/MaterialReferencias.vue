<template>
  <div class="space-y-4">
    <div class="space-y-1">
      <h1 class="text-2xl font-bold">Referencias</h1>
      <p class="text-sm text-muted-foreground">Gestión de todas las referencias de pequeño material</p>
    </div>

    <section class="bg-white shadow rounded p-4">
      <h2 class="text-lg font-semibold mb-3">Catálogo de referencias</h2>
      <MaterialCatalogo @updated="onCatalogoActualizado" />
    </section>
  </div>
</template>

<script setup>
import MaterialCatalogo from './MaterialCatalogo.vue'

function onCatalogoActualizado() {
  // Gancho para refrescar otras vistas si fuera necesario
}
</script>
