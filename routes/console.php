<?php

use App\Models\TeamInvitation;
use Illuminate\Support\Facades\Schedule;

Schedule::call(function () {
    TeamInvitation::query()
        ->whereNotNull('expires_at')
        ->where('expires_at', '<', now())
        ->delete();
})->daily()->description('Delete expired team invitations');

Schedule::command('inventory:close-month')->monthlyOn(1, '02:00')->description('Close the inventory of every team for the previous month');

Schedule::command('consignment:send-reminders')->dailyAt('08:00')->description('Mark expired consignment holds overdue and remind the warehouse managers');
