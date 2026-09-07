<script setup>
const props = defineProps({
    name: {
        type: String,
        required: true,
    },
    status: {
        type: String,
        required: true,
    },
});

const emit = defineEmits(["promote"]);
</script>

<template>
    <div style="border: 1px solid #ccc; padding: 8px; margin: 4px 0">
        <p>{{ props.name }}(状態: {{ props.status }})</p>
        <button @click="emit('promote')">次の選考へ</button>
    </div>
</template>
