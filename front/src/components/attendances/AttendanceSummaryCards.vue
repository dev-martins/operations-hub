<script setup>
defineProps({
  cards: {
    type: Array,
    default: () => [],
  },
})
</script>

<template>
  <div
    v-for="card in cards"
    :key="card.title"
    class="col-md-6 col-xl"
  >
    <div class="layers bd bgc-white p-20 metric-card h-100 compact-card">
      <div class="layer w-100">
        <h6>{{ card.title }}</h6>
        <h2>{{ card.value }}</h2>
        <small class="c-grey-600">{{ card.note }}</small>
      </div>
    </div>
  </div>
</template>
