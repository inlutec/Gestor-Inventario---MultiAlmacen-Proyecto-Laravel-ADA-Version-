<template>
  <div class="space-y-4">
    <div class="space-y-2">
      <h1 class="text-2xl font-bold">Pequeño Material</h1>
      <p class="text-sm text-muted-foreground">Gestión de referencias, existencias y movimientos</p>
    </div>

    <!-- Subnavegación -->
    <nav class="bg-white shadow rounded p-2">
      <ul class="flex flex-wrap gap-1">
        <li>
          <RouterLink :to="{ name: 'MaterialReferencias' }" class="px-3 py-2 rounded hover:bg-slate-100" :class="isActive('MaterialReferencias') ? 'bg-slate-200 font-medium' : ''">Referencias</RouterLink>
        </li>
        <li>
          <RouterLink :to="{ name: 'MaterialExistencias' }" class="px-3 py-2 rounded hover:bg-slate-100" :class="isActive('MaterialExistencias') ? 'bg-slate-200 font-medium' : ''">Existencias</RouterLink>
        </li>
        <li>
          <RouterLink :to="{ name: 'MaterialMovimientos' }" class="px-3 py-2 rounded hover:bg-slate-100" :class="isActive('MaterialMovimientos') ? 'bg-slate-200 font-medium' : ''">Entradas/Salidas</RouterLink>
        </li>
        <li>
          <RouterLink :to="{ name: 'MaterialHistorico' }" class="px-3 py-2 rounded hover:bg-slate-100" :class="isActive('MaterialHistorico') ? 'bg-slate-200 font-medium' : ''">Histórico</RouterLink>
        </li>
      </ul>
    </nav>

    <!-- Contenido -->
    <section>
      <RouterView />
    </section>
  </div>
</template>

<script setup>
import { useRoute, RouterLink, RouterView } from 'vue-router'

const route = useRoute()
function isActive(name) {
  return route.name === name
}
</script>
