<script setup>
import { ref, reactive, computed, watch, onMounted } from "vue";
import CandidateCard from "./CandidateCard.vue";

const count = ref(0);

// カンバンの「1枚のカード」を模したオブジェクト
const candidate = reactive({
    name: "山田 太郎",
    status: "書類選考中",
});

// カンバンの元になる「カード一覧」データ(親が保持する)
const candidates = reactive([
    { id: 1, name: "山田 太郎", status: "書類選考中" },
    { id: 2, name: "佐藤 次郎", status: "書類選考中" },
]);

// 表示するカラム(選考ステータス)の並び
const columns = ["書類選考中", "一次面接", "ニ次面接"];

// 指定ステータスのカードだけ返す
function cardsInColumn(status) {
    return candidates.filter((c) => c.status === status);
}

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

// 子から promote 通知を受けたら、親がステータスを更新する
function handlePromote(id) {
    const target = candidates.find((c) => c.id === id);
    if (target) {
        target.status = "一次面接";
    }
}

watch(count, (newValue, oldValue) => {
    console.log(`countが ${oldValue} から ${newValue} に変わりました`);
});

onMounted(() => {
    console.log("App.vueがマウントされました(onMounted)");
});
</script>

<template>
    <hr />

    <h3>応募者カード(カラム別振り分け)</h3>
    <div style="display: flex; gap: 16px">
        <div
            v-for="column in columns"
            :key="column"
            style="border: 1px solid #999; padding: 8px; min-width: 200px"
        >
            <h4>{{ column }}({{ cardsInColumn(column).length }})</h4>
            <CandidateCard
                v-for="candidate in cardsInColumn(column)"
                :key="candidate.id"
                :name="candidate.name"
                :status="candidate.status"
                @promote="handlePromote(candidate.id)"
            />
        </div>
    </div>

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
