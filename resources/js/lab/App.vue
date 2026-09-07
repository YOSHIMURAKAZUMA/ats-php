<script setup>
import { ref, reactive, computed, watch, onMounted } from "vue";

const count = ref(0);

// カンバンの「1枚のカード」を模したオブジェクト
const candidate = reactive({
    name: "山田 太郎",
    status: "書類選考中",
});

const countParity = computed(() => (count.value % 2 === 0 ? "偶数" : "奇数"));

function increment() {
    count.value++;
}

function decrement() {
    count.value--;
}

function promote() {
    candidate.status = "一次面接"; // .value は不要
}

watch(count, (newValue, oldValue) => {
    console.log(`countが ${oldValue} から ${newValue} に変わりました`);
});

onMounted(() => {
    console.log("App.vueがマウントされました(onMounted)");
});
</script>

<template>
    <div>
        <p>カウント: {{ count }}</p>
        <p>今の数は: {{ countParity }}</p>
        <button @click="decrement">-1</button>
        <button @click="increment">+1</button>

        <hr />

        <p>応募者: {{ candidate.name }} / ステータス: {{ candidate.status }}</p>
        <button @click="promote">次の選考へ進める</button>
    </div>
</template>
