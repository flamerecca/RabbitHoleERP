<?php

namespace App\Http\Controllers;

use App\Actions\Accounting\PostJournalEntry;
use App\Actions\Accounting\UpdateAccountingSettings;
use App\Http\Requests\UpdateAccountingSettingsRequest;
use App\Models\AccountingSetting;
use App\Models\Team;
use Illuminate\Http\JsonResponse;

class AccountingSettingController extends Controller
{
    /**
     * Show the accounts and journals the automatic posting of the team uses.
     */
    public function show(Team $team): JsonResponse
    {
        return $this->response(PostJournalEntry::settings($team));
    }

    /**
     * Replace the accounts and journals the automatic posting of the team uses.
     */
    public function update(UpdateAccountingSettingsRequest $request, Team $team, UpdateAccountingSettings $update): JsonResponse
    {
        /** @var array<string, int> $validated */
        $validated = $request->validated();

        return $this->response($update->handle($team, $validated));
    }

    /**
     * Build the response of the accounting settings.
     */
    protected function response(AccountingSetting $settings): JsonResponse
    {
        return response()->json(['data' => $settings->only([...AccountingSetting::ACCOUNT_COLUMNS, ...AccountingSetting::JOURNAL_COLUMNS])]);
    }
}
