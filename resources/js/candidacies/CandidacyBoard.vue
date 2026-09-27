<script setup>
import { ref, computed, onMounted, onUnmounted } from "vue";

const props = defineProps({
    columns: { type: Array, required: true },
    candidacies: { type: Array, required: true },
    jobPostings: { type: Array, required: true },
    selectedJobPostingId: { type: Number, default: null },
});

// 表示中のステータス値の配列。初期は defaultShown が true のもの(書類選考中~不合格の5つ)
const showStatuses = ref(
    props.columns.filter((col) => col.defaultShown).map((col) => col.value),
);

// 表示するカラム(colums の定義順を保ったまま、表示中のものだけ)
const visibleColumns = computed(() =>
    props.columns.filter((col) => showStatuses.value.includes(col.value)),
);

// 指定ステータスの選考カードを返す(名前検索の結果を反映)
const cardsOf = (statusValue) =>
    filteredCandidacies.value.filter((c) => c.status === statusValue);

// 応募者名の検索キーワード(クライアント側で即時フィルタ)
const nameQuery = ref("");

// 検索キーワードで絞り込んだ選考一覧
// 空白を無視して部分一致で判定する(「山田 太郎」を「山田太郎」でも検索できる)
const filteredCandidacies = computed(() => {
    const query = nameQuery.value.replace(/\s/g, "");

    if (query === "") {
        return props.candidacies;
    }

    return props.candidacies.filter((c) =>
        c.name.replace(/\s/g, "").includes(query),
    );
});

// 求人フィルタの選択値(変更時はサーバーへ再リクエストする)
const jobPostingFilter = ref(props.selectedJobPostingId ?? "");

const onJobPostingChange = () => {
    const url = new URL(window.location.href);

    if (jobPostingFilter.value === "") {
        url.searchParams.delete("job_posting_id");
    } else {
        url.searchParams.set("job_posting_id", jobPostingFilter.value);
    }

    window.location.href = url.toString();
};

// 「+追加」メニューの開閉状態
const addMenuOpen = ref(false);

// 現在表示していないカラム(+追加メニューの選択肢)
const hiddenColumns = computed(() =>
    props.columns.filter((col) => !showStatuses.value.includes(col.value)),
);

// カラムを非表示にする(chip の ×)
const removeColumn = (statusValue) => {
    showStatuses.value = showStatuses.value.filter((v) => v !== statusValue);
};

// カラムを表示に追加する(+追加メニューから選択)
const addColumn = (statusValue) => {
    showStatuses.value = [...showStatuses.value, statusValue];
    addMenuOpen.value = false;
};

// 「+追加」まわりのDOM要素への参照(外側クリック判定に使う)
const addWrapper = ref(null);

// メニューの外側がクリックされたらメニューを閉じる
const closeAddMenuOnOutsideClick = (event) => {
    if (addWrapper.value && !addWrapper.value.contains(event.target)) {
        addMenuOpen.value = false;
    }
};

// 詳細プレビューで表示中の選考(nullなら非表示)
const selectedCandidacy = ref(null);

const openPreview = (card) => {
    selectedCandidacy.value = card;
};

const closePreview = () => {
    selectedCandidacy.value = null;
};

// 面接官をラウンド別にまとめる(例: { 1: ['面接 花子'], 2: ['兼務 次郎']})
const interviewersByRound = computed(() => {
    if (selectedCandidacy.value === null) {
        return [];
    }

    const groups = selectedCandidacy.value.interviewers.reduce((acc, i) => {
        acc[i.round] = acc[i.round] ?? {
            round: i.round,
            label: i.roundLabel,
            names: [],
        };
        acc[i.round].names.push(i.name);
        return acc;
    }, {});

    // ラウンド番号順に並べた配列として返す
    return Object.values(groups).sort((a, b) => a.round - b.round);
});

onMounted(() => {
    document.addEventListener("click", closeAddMenuOnOutsideClick);
});

onUnmounted(() => {
    document.removeEventListener("click", closeAddMenuOnOutsideClick);
});
</script>

<template>
    <div class="toolbar">
        <div class="field">
            <label for="job-filter">求人で絞り込み</label>
            <select
                id="job-filter"
                v-model="jobPostingFilter"
                @change="onJobPostingChange"
            >
                <option value="">すべての求人</option>
                <option
                    v-for="job in jobPostings"
                    :key="job.id"
                    :value="job.id"
                >
                    {{ job.title }}
                </option>
            </select>
        </div>

        <div class="field">
            <label for="name-search">応募者名で検索</label>
            <input
                type="text"
                id="name-search"
                v-model="nameQuery"
                placeholder="例)山田"
            />
        </div>

        <p class="result-note">
            表示中: {{ filteredCandidacies.length }} 名 / 全
            {{ candidacies.length }} 名
        </p>
    </div>

    <div class="column-picker">
        <span class="picker-label">表示するステータス</span>

        <span v-for="col in visibleColumns" :key="col.value" class="chip">
            {{ col.label }}
            <button
                type="button"
                class="chip-remove"
                :aria-label="col.label + 'を非表示にする'"
                @click="removeColumn(col.value)"
            >
                ×
            </button>
        </span>

        <span class="add-wrapper" ref="addWrapper">
            <button
                type="button"
                class="chip-add"
                :disabled="hiddenColumns.length === 0"
                @click.stop="addMenuOpen = !addMenuOpen"
            >
                + 追加
            </button>

            <div v-if="addMenuOpen" class="add-menu">
                <button
                    v-for="col in hiddenColumns"
                    :key="col.value"
                    type="button"
                    class="add-menu-item"
                    @click="addColumn(col.value)"
                >
                    {{ col.label }}
                </button>
            </div>
        </span>
    </div>

    <div class="board">
        <div v-for="col in visibleColumns" :key="col.value" class="column">
            <div class="column-head">
                <span class="column-name">{{ col.label }}</span>
                <span class="column-count">{{
                    cardsOf(col.value).length
                }}</span>
            </div>

            <div class="column-body">
                <div
                    v-for="card in cardsOf(col.value)"
                    :key="card.id"
                    class="card"
                    :class="{
                        'card-selected': selectedCandidacy?.id === card.id,
                    }"
                    @click="openPreview(card)"
                >
                    <div class="card-name">{{ card.name }}</div>
                    <div class="card-meta">{{ card.jobPostingTitle }}</div>
                    <div class="card-meta">
                        エントリー: {{ card.entryDate }}
                    </div>
                </div>

                <p v-if="cardsOf(col.value).length === 0" class="column-empty">
                    該当者なし
                </p>
            </div>
        </div>
    </div>

    <!-- 詳細プレビュー -->
    <div v-if="selectedCandidacy" class="overlay" @click="closePreview"></div>

    <aside v-if="selectedCandidacy" class="preview-panel">
        <div class="panel-head">
            <div class="panel-title">
                <span class="panel-name">{{ selectedCandidacy.name }}</span>
                <span class="panel-status">{{
                    selectedCandidacy.statusLabel
                }}</span>
            </div>
            <button
                type="button"
                class="panel-close"
                aria-label="閉じる"
                @click="closePreview"
            >
                ×
            </button>
        </div>

        <div class="panel-body">
            <div class="panel-item">
                <span class="panel-key">対象求人</span>
                <span class="panel-value">{{
                    selectedCandidacy.jobPostingTitle
                }}</span>
            </div>
            <div class="panel-item">
                <span class="panel-key">メール</span>
                <span class="panel-value">{{ selectedCandidacy.email }}</span>
            </div>
            <div class="panel-item">
                <span class="panel-key">エントリー日</span>
                <span class="panel-value">{{
                    selectedCandidacy.entryDate
                }}</span>
            </div>
            <div class="panel-item">
                <span class="panel-key">アサイン面接官</span>
                <template v-if="selectedCandidacy.interviewers.length > 0">
                    <div
                        v-for="group in interviewersByRound"
                        :key="group.round"
                        class="panel-round"
                    >
                        <span class="panel-round-label">{{ group.label }}</span>
                        <span
                            v-for="name in group.names"
                            :key="name"
                            class="panel-tag"
                            >{{ name }}</span
                        >
                    </div>
                </template>
                <span v-else class="panel-value panel-empty">未アサイン</span>
            </div>
        </div>

        <div class="panel-foot">
            <a :href="selectedCandidacy.showUrl" class="panel-button"
                >応募者詳細を開く</a
            >
            <p class="panel-hint">
                ステータス変更・面接評価は応募者詳細画面で行います
            </p>
        </div>
    </aside>
</template>

<style scoped>
.board {
    display: flex;
    gap: 12px;
    overflow-x: auto;
    padding-bottom: 8px;
    align-items: flex-start;
}
.column {
    flex: 0 0 240px;
    background: #f7f9fb;
    border: 1px solid #d7dde6;
    border-radius: 6px;
}
.column-head {
    display: flex;
    align-items: center;
    padding: 10px 12px;
    border-bottom: 1px solid #d7dde6;
}
.column-name {
    font-weight: 700;
    font-size: 14px;
}
.column-count {
    margin-left: auto;
    background: #fff;
    border: 1px solid #c7cfdb;
    border-radius: 999px;
    padding: 1px 8px;
    font-size: 12px;
}
.column-body {
    padding: 10px;
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.card {
    background: #fff;
    border: 1px solid #dde3ec;
    border-radius: 4px;
    padding: 10px;
    cursor: pointer;
    transition: border-color 0.12s ease;
}
.card:hover {
    border-color: #c7cfdb;
}
.card-selected {
    border-color: #4d68ad;
    box-shadow: 0 0 0 2px #e2eef0;
}
.card-name {
    font-weight: 700;
    font-size: 14px;
    margin-bottom: 4px;
}
.card-meta {
    font-size: 12px;
    color: #5c6b83;
}
.column-empty {
    color: #8a97ac;
    font-size: 12px;
    text-align: center;
    margin: 12px 0;
}
.toolbar {
    display: flex;
    gap: 16px;
    align-items: flex-end;
    margin-bottom: 16px;
    flex-wrap: wrap;
}
.field {
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.field label {
    font-size: 12px;
    color: #5c6b83;
}
.field select,
.field input {
    height: 32px;
    border: 1px solid #c7cfdb;
    border-radius: 4px;
    padding: 0 8px;
    font-size: 13px;
    min-width: 200px;
}
.result-note {
    margin-left: auto;
    font-size: 12px;
    color: #5c6b83;
}
.column-picker {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
    margin-bottom: 16px;
}
.picker-label {
    font-size: 12px;
    color: #5c6b83;
}
.chip {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    height: 26px;
    padding: 0 6px 0 10px;
    border: 1px solid #c7cfdb;
    border-radius: 999px;
    background: #fff;
    font-size: 12px;
}
.chip-remove {
    border: none;
    background: transparent;
    cursor: pointer;
    color: #8a97ac;
    font-size: 14px;
    line-height: 1;
    padding: 2px 4px;
    border-radius: 4px;
}
.chip-remove:hover {
    color: #c05b57;
    background: #f7e9e8;
}
.add-wrapper {
    position: relative;
}
.chip-add {
    height: 26px;
    padding: 0 12px;
    border: 1px dashed #3d5799;
    border-radius: 999px;
    background: #eef2f8;
    color: #3d5799;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
}
.chip-add:disabled {
    border-color: #c7cfdb;
    background: #f2f4f7;
    color: #8a97ac;
    cursor: not-allowed;
}
.add-menu {
    position: absolute;
    top: 30px;
    left: 0;
    z-index: 10;
    background: #fff;
    border: 1px solid #c7cfdb;
    border-radius: 6px;
    box-shadow: 0 2px 8px rgba(24, 34, 48, 0.12);
    padding: 4px;
    min-width: 150px;
}
.add-menu-item {
    display: block;
    width: 100%;
    text-align: left;
    border: none;
    background: transparent;
    padding: 6px 8px;
    border-radius: 4px;
    font-size: 13px;
    cursor: pointer;
}
.add-menu-item:hover {
    background: #f2f4f7;
}
.overlay {
    position: fixed;
    inset: 0;
    background: rgba(26, 34, 48, 0.28);
    z-index: 20;
}
.preview-panel {
    position: fixed;
    top: 0;
    right: 0;
    height: 100vh;
    width: 340px;
    max-width: 90vw;
    background: #fff;
    border-left: 1px solid #c7cfdb;
    box-shadow: -4px 0 16px rgba(26, 34, 48, 0.12);
    z-index: 30;
    display: flex;
    flex-direction: column;
}
.panel-head {
    display: flex;
    align-items: flex-start;
    padding: 16px;
    border-bottom: 1px solid #dfe4ec;
}
.panel-title {
    display: flex;
    align-items: center;
    gap: 16px;
}
.panel-name {
    font-size: 17px;
    font-weight: 700;
}
.panel-status {
    font-size: 12px;
    color: #5c6b83;
}
.panel-close {
    margin-left: auto;
    border: none;
    background: transparent;
    font-size: 20px;
    color: #8a97ac;
    cursor: pointer;
    padding: 0 4px;
}
.panel-close:hover {
    color: #1a2230;
}
.panel-body {
    flex: 1;
    padding: 16px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 14px;
}
.panel-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.panel-key {
    font-size: 11px;
    color: #7a869a;
}
.panel-value {
    font-size: 13px;
    color: #1a2230;
}
.panel-empty {
    color: #8a97ac;
}
.panel-round {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
    margin-top: 4px;
}
.panel-round-label {
    font-size: 11px;
    color: #7a869a;
    min-width: 56px;
}
.panel-tag {
    font-size: 12px;
    background: #eef1f5;
    border: 1px solid #dfe4ec;
    border-radius: 4px;
    padding: 2px 8px;
}
.panel-foot {
    margin: auto;
    padding: 14px 16px;
    border-top: 1px solid #dfe4ec;
}
.panel-button {
    display: block;
    text-align: center;
    text-decoration: none;
    background: #3d5799;
    color: #fff;
    font-weight: 700;
    font-size: 13px;
    padding: 10px;
    border-radius: 4px;
}
.panel-button:hover {
    background: #33487f;
}
.panel-hint {
    text-align: center;
    font-size: 11px;
    color: #8a97ac;
    margin: 8px 0 0;
}
</style>
