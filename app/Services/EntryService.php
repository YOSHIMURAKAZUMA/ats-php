<?php

namespace App\Services;

use App\Models\Candidacy;
use App\Repositories\EntryRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class EntryService
{
    public function __construct(
        private readonly EntryRepository $repository,
    ) {}

    /**
     * 応募エントリーを登録する。
     * 履歴書保存・候補者作成・選考作成を1トランザクションで行い、
     * いずれかが失敗したら全てロールバックし、保存済みファイルも削除する。
     *
     * @param  array{name: string, email: string, phone: ?string}  $input
     */
    public function entry(int $jobPostingId, array $input, UploadedFile $resume): Candidacy
    {
        // 1) 履歴書を非公開ストレージへ保存(DBトランザクションの外で先に保存)
        $resumePath = $resume->store('resumes', 'local');

        try {
            // 2) 候補者作成 + 3) 選考作成をトランザクションで
            return DB::transaction(function () use ($jobPostingId, $input, $resumePath) {
                $candidate = $this->repository->firstOrCreateCandidate($input);

                return $this->repository->createCandidacy(
                    $jobPostingId,
                    $candidate->id,
                    $resumePath,
                );
            });
        } catch (Throwable $e) {
            // DB側が失敗したら、先に保存したファイルを削除して整合性を保つ
            Storage::disk('local')->delete($resumePath);
            throw $e;
        }
    }
}
