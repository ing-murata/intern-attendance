<?php

namespace App\Console\Commands;

use App\Models\Calendar;
use App\Services\GoogleApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Throwable;

class NotifyAttendance extends Command
{
    protected $signature = 'app:notify-attendance';

    public function handle(GoogleApiService $service): int
    {
        $calendars = Calendar::where('is_active', true)
            ->orderBy('role')
            ->orderBy('user_name')
            ->get();

        $webhookUrl = config('services.slack.webhook_url');
        if ($calendars->isEmpty() || empty($webhookUrl)) {
            if ($calendars->isEmpty()) {
                $this->warn('有効な通知対象がありません。');
            } else {
                $this->error('SLACK_WEBHOOK_URL が設定されていません。');
            }

            return self::SUCCESS;
        }

        $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
        $today = now()->timezone('Asia/Tokyo');
        $dateLine = $today->format('Y年n月j日').'('.$weekdays[$today->dayOfWeek].')';

        $this->line('');
        $this->line("  <fg=cyan;options=bold>稼働状況チェック</> <fg=gray>({$dateLine})</>");
        $this->line('  <fg=gray>────────────────────────────────────</>');

        $groups = [
            '社員' => [],
            'インターン' => [],
        ];

        foreach ($calendars as $calendar) {
            try {
                $attendance = $service->getAttendance($calendar->calendar_id, $calendar->role);
                if ($attendance['status'] === null) {
                    $this->line("  <fg=gray>- {$calendar->user_name}（{$calendar->role}）: 予定なし</>");

                    continue;
                }

                $statusText = $this->formatStatus($attendance);
                $groups[$calendar->role][] = [
                    'name' => $calendar->user_name,
                    'status_text' => $statusText,
                ];

                $this->line("  <fg=green>[OK]</> {$calendar->user_name}（{$calendar->role}）: {$statusText}");
            } catch (Throwable $e) {
                $this->line("  <fg=red>[NG]</> {$calendar->user_name}（{$calendar->role}）: {$e->getMessage()}");
            }
        }

        $totalCount = array_sum(array_map('count', $groups));
        if ($totalCount === 0) {
            $this->line('');
            $this->info('  送信対象の稼働状況はありませんでした。');
            $this->line('');

            return self::SUCCESS;
        }

        $body = $this->buildSlackMessage($dateLine, $groups);

        $response = Http::post($webhookUrl, [
            'text' => $body,
        ]);

        $this->line('  <fg=gray>────────────────────────────────────</>');
        if ($response->successful()) {
            $this->line("  <fg=green;options=bold>Slackへ通知しました</> <fg=gray>(対象: {$totalCount}件)</>");
        } else {
            $this->line("  <fg=red;options=bold>Slack通知に失敗しました</> <fg=gray>(HTTP {$response->status()})</>");
        }
        $this->line('');

        return self::SUCCESS;
    }

    /**
     * 1人分のステータス表記を整形する。
     *
     * @param  array{status: ?string, work_time: ?string}  $attendance
     */
    private function formatStatus(array $attendance): string
    {
        if ($attendance['status'] === '出社' && ! empty($attendance['work_time'])) {
            return "出社（{$attendance['work_time']}）";
        }

        return $attendance['status'];
    }

    /**
     * Slack通知用の本文を組み立てる。
     *
     * @param  array<string, array<int, array{name: string, status_text: string}>>  $groups
     */
    private function buildSlackMessage(string $dateLine, array $groups): string
    {
        $lines = [];
        $lines[] = "*稼働状況一覧*　_{$dateLine}_";

        $sections = [
            '社員' => '*社員*',
            'インターン' => '*インターン*',
        ];

        foreach ($sections as $role => $heading) {
            $members = $groups[$role];
            if ($members === []) {
                continue;
            }

            $lines[] = '';
            $lines[] = $heading;
            foreach ($members as $member) {
                $lines[] = "・{$member['name']}：{$member['status_text']}";
            }
        }

        return implode("\n", $lines);
    }
}
