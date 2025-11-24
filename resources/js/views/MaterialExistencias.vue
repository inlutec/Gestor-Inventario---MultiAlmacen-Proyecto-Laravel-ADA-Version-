<template>
  <div class="space-y-4">
    <div class="space-y-1">
      <h1 class="text-2xl font-bold">Existencias</h1>
      <p class="text-sm text-muted-foreground">Stock actual de referencias con disponibilidad</p>
    </div>

    <section class="bg-white shadow rounded p-4">
      <h2 class="text-lg font-semibold mb-3">Stock actual</h2>
      <MaterialExistenciasList :refresh-key="refreshKey" />
    </section>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import MaterialExistenciasList from './MaterialExistenciasList.vue'

const refreshKey = ref(0)
</script>
