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

    protected $description = '登録者全員の稼働状況を一覧にしてSlackへ通知します';

    public function handle(GoogleApiService $service): int
    {
        $calendars = Calendar::where('is_active', true)->orderBy('team_name')->get();
        if ($calendars->isEmpty()) {
            $this->info('有効な通知対象がありません。');

            return self::SUCCESS;
        }

        $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
        $today = now()->timezone('Asia/Tokyo');
        $dateLine = $today->format('Y年n月j日').'('.$weekdays[$today->dayOfWeek].')';

        $linesByWebhook = [];
        foreach ($calendars as $calendar) {
            try {
                $attendance = $service->getAttendance($calendar->calendar_id);
                if ($attendance['status'] === null) {
                    continue;
                }
                if (empty($calendar->slack_webhook_url)) {
                    $this->warn("{$calendar->team_name}: slack_webhook_url が未設定のためスキップしました。");
                    continue;
                }
                $statusText = $attendance['status'];
                if ($attendance['status'] === '出社' && ! empty($attendance['work_time'])) {
                    $statusText = "出社（{$attendance['work_time']}の勤務）";
                }
                $linesByWebhook[$calendar->slack_webhook_url][] = "• {$calendar->team_name}：{$statusText}";
            } catch (Throwable $e) {
                $this->error("{$calendar->team_name}: {$e->getMessage()}");
            }
        }

        if ($linesByWebhook === []) {
            $this->info('送信対象の稼働状況がありませんでした。');

            return self::SUCCESS;
        }

        foreach ($linesByWebhook as $webhookUrl => $lines) {
            $body = "【稼働状況一覧】{$dateLine}\n\n".implode("\n", $lines);
            Http::post($webhookUrl, [
                'text' => $body,
            ]);
        }

        $this->info('一覧を送信しました。');

        return self::SUCCESS;
    }
}
