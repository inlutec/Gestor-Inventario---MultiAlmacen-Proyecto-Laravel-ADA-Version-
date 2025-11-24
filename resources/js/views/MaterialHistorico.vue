<template>
  <div class="space-y-4">
    <div class="space-y-1">
      <h1 class="text-2xl font-bold">Histórico</h1>
      <p class="text-sm text-muted-foreground">Consulta de todos los movimientos</p>
    </div>

    <section class="bg-white shadow rounded p-4" style="overflow: visible;">
      <h2 class="text-lg font-semibold mb-3">Movimientos</h2>
      <MaterialJustificante />
    </section>
  </div>
</template>

<script setup>
import MaterialJustificante from './MaterialJustificante.vue'
</script>
