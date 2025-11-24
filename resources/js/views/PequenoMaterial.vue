<template>
  <div class="pequeno-material">
    <h1>Pequeño Material</h1>
    <!-- Listado de existencias -->
    <MaterialExistenciasList />
    <!-- Registro de entradas/salidas -->
    <MaterialMovimientoForm />
    <!-- Justificantes y firma -->
    <MaterialJustificante />
    <!-- Enlace público para albarán -->
    <MaterialAlbaranPublico />
  </div>
</template>

<script>
// Importar componentes (se crearán en pasos siguientes)
export default {
  name: 'PequenoMaterial',
  components: {
    MaterialExistenciasList: () => import('./MaterialExistenciasList.vue'),
    MaterialMovimientoForm: () => import('./MaterialMovimientoForm.vue'),
    MaterialJustificante: () => import('./MaterialJustificante.vue'),
    MaterialAlbaranPublico: () => import('./MaterialAlbaranPublico.vue'),
  },
};
</script>

<style scoped>
.pequeno-material {
  padding: 2rem;
}
</style>
