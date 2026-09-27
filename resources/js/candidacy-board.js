import { createApp } from "vue";
import CandidacyBoard from "./candidacies/CandidacyBoard.vue";

const el = document.getElementById("candidacy-board");

if (el) {
    // Blade から data-board 属性で渡されたJSONを読み取り、props として渡す
    const board = JSON.parse(el.dataset.board);

    createApp(CandidacyBoard, {
        columns: board.columns,
        candidacies: board.candidacies,
        jobPostings: board.jobPostings,
        selectedJobPostingId: board.selectedJobPostingId,
    }).mount(el);
}
